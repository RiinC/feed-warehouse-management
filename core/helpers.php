<?php
/**
 * Hàm tiện ích dùng chung cho View và Controller (cấu hình, URL, CSRF, flash, định dạng, icon, quyền).
 * Logic nghiệp vụ và truy vấn CSDL KHÔNG đặt ở đây: truy vấn nằm trong app/Models, nghiệp vụ nằm trong app/Controllers.
 */

/* ------------------------------ Cấu hình ------------------------------ */
function config(string $key, $default = null)
{
    $value = $GLOBALS['APP_CONFIG'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}


/* ------------------------------ Chuỗi & URL ------------------------------ */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Đường dẫn gốc của ứng dụng, tính từ thư mục chứa public/index.php
 * (VD "/feedware/public", rỗng nếu DocumentRoot trỏ thẳng vào public/).
 */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $cfg = (string)config('base_url', '');
    if ($cfg !== '') {
        return $base = rtrim($cfg, '/');
    }
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    return $base = rtrim($dir === '/' || $dir === '.' ? '' : $dir, '/');
}

/**
 * URL của một route. VD route('hang-hoa/create'), route('thong-bao/open', ['id' => 5]), route('hang-hoa?q=abc').
 * Với config('url_rewrite') = false sẽ trả về dạng index.php?r=hang-hoa/create (dùng khi server chưa bật rewrite).
 */
function route(string $path = '', array $query = []): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if (strpos($path, '?') !== false) {
        [$path, $qs] = explode('?', $path, 2);
        parse_str($qs, $parsed);
        $query = $parsed + $query;
    }
    $path = trim($path, '/');
    if (config('url_rewrite', true)) {
        $url = base_url() . '/' . $path;
    } else {
        $url = base_url() . '/index.php';
        if ($path !== '') {
            $query = ['r' => $path] + $query;
        }
    }
    return $url . ($query ? (strpos($url, '?') === false ? '?' : '&') . http_build_query($query) : '');
}

/** URL của tệp tĩnh trong public/assets. */
function asset(string $path): string
{
    return base_url() . '/assets/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . route($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Đường dẫn route của trang đang mở (VD "hang-hoa/create"). */
function current_route(): string
{
    return \Core\Request::path();
}

/* ------------------------------ Người dùng & quyền (dùng trong View) ------------------------------ */
function current_user(): ?array
{
    return \Core\Auth::user();
}

function is_logged_in(): bool
{
    return \Core\Auth::check();
}

/** Khóa vai trò hiện tại (admin | manager | warehouse | inventory | director), rỗng nếu chưa đăng nhập. */
function current_role(): string
{
    return \Core\Auth::role();
}

/** Vai trò hiện tại có quyền $action trên $module không (xem app/config/permissions.php). */
function can(string $module, string $action = 'view'): bool
{
    return \Core\Permission::can($module, $action);
}

/** Vai trò hiện tại có được mở route này không (quyền khai báo ở routes/web.php). */
function can_access_route(string $path): bool
{
    return \Core\Permission::canRoute($path);
}

/**
 * In các nút thao tác mà vai trò hiện tại được phép dùng (nút không có quyền thì không xuất hiện).
 * $defs: [[module, action, nhãn, route, class (mặc định 'btn')], ...]
 */
function action_buttons(array $defs): string
{
    $html = '';
    foreach ($defs as $d) {
        [$module, $action, $label, $path] = $d;
        if (can($module, $action)) {
            $html .= '<a class="' . e($d[4] ?? 'btn') . '" href="' . e(route($path)) . '">' . e($label) . '</a> ';
        }
    }
    return trim($html);
}

/** Nhúng một mẫu giao diện nhỏ: partial('components/placeholder', [...]) -> app/Views/components/placeholder.php */
function partial(string $name, array $data = []): void
{
    \Core\View::partial($name, $data);
}

/** "5 phút trước", "2 giờ trước"... */
function time_ago($datetime): string
{
    $t = $datetime ? strtotime((string)$datetime) : false;
    if (!$t) {
        return '';
    }
    $diff = time() - $t;
    if ($diff < 60) {
        return 'Vừa xong';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' phút trước';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' giờ trước';
    }
    if ($diff < 7 * 86400) {
        return floor($diff / 86400) . ' ngày trước';
    }
    return date('d/m/Y H:i', $t);
}

/* ------------------------------ CSRF ------------------------------ */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $sent = (string)($_POST['csrf_token'] ?? '');
    return $sent !== '' && hash_equals(csrf_token(), $sent);
}


/* ------------------------------ Thông báo (flash) ------------------------------ */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];   // success | error | warning | info
}

function flash_get_all(): array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}


/* ------------------------------ Định dạng ------------------------------ */
function fmt_date($value): string
{
    $t = $value ? strtotime((string)$value) : false;
    return $t ? date('d/m/Y', $t) : '';
}

function fmt_datetime($value): string
{
    $t = $value ? strtotime((string)$value) : false;
    return $t ? date('d/m/Y H:i', $t) : '';
}

function fmt_number($value, int $decimals = 0): string
{
    return number_format((float)$value, $decimals, ',', '.');
}


/* ------------------------------ Icon SVG (nét đơn giản) ------------------------------ */
function icon(string $name, int $size = 18): string
{
    static $paths = [
        'home'      => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'package'   => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'layers'    => '<path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/>',
        'inbox'     => '<path d="M3 13l3-8h12l3 8"/><path d="M3 13v6h18v-6h-5l-1 2h-6l-1-2H3z"/>',
        'clipboard' => '<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4h6v3H9z"/><path d="M9 12h6M9 16h6"/>',
        'download'  => '<path d="M12 3v12"/><path d="M7 11l5 5 5-5"/><path d="M4 20h16"/>',
        'upload'    => '<path d="M12 17V5"/><path d="M7 9l5-5 5 5"/><path d="M4 20h16"/>',
        'pin'       => '<path d="M12 21s7-6 7-12a7 7 0 10-14 0c0 6 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'link'      => '<path d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1-1"/>',
        'repeat'    => '<path d="M17 2l4 4-4 4"/><path d="M3 11V9a3 3 0 013-3h15"/><path d="M7 22l-4-4 4-4"/><path d="M21 13v2a3 3 0 01-3 3H3"/>',
        'list'      => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>',
        'alert'     => '<path d="M12 3l10 18H2L12 3z"/><path d="M12 10v5M12 18h.01"/>',
        'archive'   => '<rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v12h14V8"/><path d="M10 12h4"/>',
        'chart'     => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 16v-5M12 16V8M16 16v-8"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6"/><path d="M16 4.5a3.5 3.5 0 010 7M22 20c0-3-2-5-5-5.7"/>',
        'menu'      => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'bell'      => '<path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/>',
        'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3h0a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5h0a1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8v0a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/>',
        'chevron'   => '<path d="M6 9l6 6 6-6"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'tool'      => '<path d="M14.7 6.3a4 4 0 005 5L21 13l-8 8-4-4 8-8z"/><path d="M3 21l4-4"/>',
    ];
    $inner = $paths[$name] ?? $paths['list'];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}

