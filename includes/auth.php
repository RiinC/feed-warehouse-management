<?php
/**
 * Kiểm tra đăng nhập, phân quyền theo module và các hàm tiện ích dùng chung.
 */
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- Tiện ích chung ---------- */
function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function fmt_money($n): string
{
    return number_format((float)$n, 0, ',', '.');
}

function fmt_qty($n): string
{
    $s = number_format((float)$n, 2, ',', '.');
    return rtrim(rtrim($s, '0'), ',');
}

function fmt_date(?string $d): string
{
    if (!$d) return '';
    $t = strtotime($d);
    return $t ? date('d/m/Y', $t) : '';
}

/* ---------- Flash message ---------- */
function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function pull_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    if (!$ok) {
        http_response_code(400);
        die('Phiên làm việc không hợp lệ (CSRF). Vui lòng tải lại trang và thử lại.');
    }
}

/* ---------- Đăng nhập & phân quyền ---------- */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('login.php');
    }
}

/** Admin vào được mọi module; các vai trò khác dùng danh sách quyenHan của NhanVien. */
function has_module(string $module): bool
{
    $u = current_user();
    if (!$u) return false;
    return $u['role'] === 'admin' || in_array($module, $u['modules'], true);
}

/** Admin và Quản lý kho có quyền quản lý phiếu. */
function is_manager(): bool
{
    $u = current_user();
    return $u && in_array($u['role'], ['admin', 'quan_ly_kho'], true);
}

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function require_module(string $module): void
{
    require_login();
    if (!has_module($module)) {
        forbidden();
    }
}

function forbidden(): void
{
    http_response_code(403);
    $page_title = 'Không có quyền truy cập';
    $active_menu = '';
    $breadcrumb = [['Tổng quan', 'index.php'], ['Không có quyền', null]];
    include ROOT_PATH . '/includes/head.php';
    include ROOT_PATH . '/includes/sidebar.php';
    include ROOT_PATH . '/includes/header.php';
    include ROOT_PATH . '/includes/toolbar.php';
    ?>
    <div class="card empty-state">
        <div class="empty-icon"><?= icon('lock', 40) ?></div>
        <h2>Bạn không có quyền truy cập chức năng này</h2>
        <p>Vai trò hiện tại của bạn (<b><?= e(current_user()['role_name'] ?? '') ?></b>) chưa được cấp quyền vào module này.
            Vui lòng liên hệ quản trị viên nếu cần hỗ trợ.</p>
        <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Về trang Tổng quan</a>
    </div>
    <?php
    include ROOT_PATH . '/includes/footer.php';
    include ROOT_PATH . '/includes/scripts.php';
    exit;
}

/* ---------- Trạng thái phiếu ---------- */
function status_list(): array
{
    return ['Chờ xử lý' => 'warn', 'Đang kiểm tra' => 'info', 'Đã nhập kho' => 'ok'];
}

function status_badge(string $st): string
{
    $cls = status_list()[$st] ?? 'muted';
    return '<span class="badge badge-' . $cls . '">' . e($st) . '</span>';
}

/* ---------- Menu sidebar (thứ tự hiển thị) ---------- */
function menu_items(): array
{
    return [
        ['key' => 'tong-quan', 'label' => 'Tổng quan', 'icon' => 'home', 'href' => 'index.php'],
        ['key' => 'hang-hoa', 'label' => 'Hàng hóa', 'icon' => 'package', 'href' => 'index.php?page=hang-hoa'],
        ['key' => 'UC06', 'label' => 'Tiếp nhận hàng', 'icon' => 'inbox', 'href' => uc_url('UC06'), 'module' => 'UC06'],
        ['key' => 'kiem-tra-chat-luong', 'label' => 'Kiểm tra chất lượng', 'icon' => 'clipboard', 'href' => 'index.php?page=kiem-tra-chat-luong'],
        ['key' => 'UC10', 'label' => 'Nhập kho', 'icon' => 'download', 'href' => uc_url('UC10'), 'module' => 'UC10'],
        ['key' => 'vi-tri-luu-kho', 'label' => 'Vị trí lưu kho', 'icon' => 'pin', 'href' => 'index.php?page=vi-tri-luu-kho'],
        ['key' => 'UC17', 'label' => 'Điều chuyển nội bộ', 'icon' => 'repeat', 'href' => uc_url('UC17'), 'module' => 'UC17'],
        ['key' => 'UC23', 'label' => 'Xuất kho', 'icon' => 'upload', 'href' => uc_url('UC23'), 'module' => 'UC23'],
        ['key' => 'UC24', 'label' => 'Kiểm kê', 'icon' => 'list', 'href' => uc_url('UC24'), 'module' => 'UC24'],
        ['key' => 'hang-ton-kho', 'label' => 'Hàng tồn kho', 'icon' => 'archive', 'href' => 'index.php?page=hang-ton-kho'],
        ['key' => 'hang-hu-het-han', 'label' => 'Hàng hỏng / hết hạn', 'icon' => 'alert', 'href' => 'index.php?page=hang-hu-het-han'],
        ['key' => 'bao-cao', 'label' => 'Báo cáo thống kê', 'icon' => 'chart', 'href' => 'index.php?page=bao-cao'],
        ['key' => 'cai-dat', 'label' => 'Cài đặt hệ thống', 'icon' => 'settings', 'href' => 'index.php?page=cai-dat', 'admin' => true],
    ];
}

/** Tạo đường dẫn module trong nhánh thư mục đúng với vai trò đã đăng nhập. */
function uc_url(string $module, string $file = 'index.php'): string
{
    $folders = [
        'UC06' => 'UC06_tiep_nhan',
        'UC10' => 'UC10_nhap_kho',
        'UC17' => 'UC17_dieu_chuyen',
        'UC23' => 'UC23_xuat_kho',
        'UC24' => 'UC24_kiem_ke',
    ];
    if (!isset($folders[$module]) || !in_array($file, ['index.php', 'form.php'], true)) {
        throw new InvalidArgumentException('Đường dẫn UC không hợp lệ.');
    }

    $user = current_user();
    $roleFolder = $user && $user['role'] === 'nhan_vien_kho' ? 'nhan-vien-kho' : 'quan-ly-kho';
    return $roleFolder . '/' . $folders[$module] . '/' . $file;
}

/* ---------- Icon SVG (nét mảnh, không cần thư viện ngoài) ---------- */
function icon(string $name, int $size = 18): string
{
    static $paths = [
        'home' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'package' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
        'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'clipboard' => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 14l2 2 4-4"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
        'pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'repeat' => '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
        'list' => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
        'archive' => '<polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/>',
        'alert' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'chart' => '<line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'plus' => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'edit' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'trash' => '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'menu' => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'tool' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'x' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'warehouse' => '<path d="M3 21V9l9-6 9 6v12"/><path d="M7 21v-8h10v8"/><line x1="7" y1="17" x2="17" y2="17"/>',
    ];
    $p = $paths[$name] ?? '';
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}
