<?php
/**
 * Điều hướng chính:
 *   index.php                -> Tổng quan (dashboard)
 *   index.php?page=<key>     -> các mục menu chưa phát triển (hiện trang "đang phát triển")
 * Các module UC nằm trong thư mục theo vai trò và được liên kết từ sidebar.
 */
require_once __DIR__ . '/includes/auth.php';
require_login();

$page = $_GET['page'] ?? 'tong-quan';
$menu = [];
foreach (menu_items() as $m) {
    $menu[$m['key']] = $m;
}

// ---------- Trang "đang phát triển" ----------
$placeholderKeys = ['hang-hoa', 'kiem-tra-chat-luong', 'vi-tri-luu-kho', 'hang-ton-kho', 'hang-hu-het-han', 'bao-cao', 'cai-dat'];
if ($page !== 'tong-quan') {
    if (!in_array($page, $placeholderKeys, true)) {
        redirect('index.php');
    }
    if (!empty($menu[$page]['admin']) && !is_admin()) {
        forbidden();
    }
    $page_title = $menu[$page]['label'];
    $active_menu = $page;
    $breadcrumb = [['Tổng quan', 'index.php'], [$page_title, null]];
    include ROOT_PATH . '/includes/head.php';
    include ROOT_PATH . '/includes/sidebar.php';
    include ROOT_PATH . '/includes/header.php';
    include ROOT_PATH . '/includes/toolbar.php';
    ?>
    <div class="card empty-state">
        <div class="empty-icon"><?= icon('tool', 40) ?></div>
        <h2>Chức năng đang phát triển</h2>
        <p>Mục <b><?= e($page_title) ?></b> sẽ được bổ sung trong các phiên bản tiếp theo.</p>
        <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Về trang Tổng quan</a>
    </div>
    <?php
    include ROOT_PATH . '/includes/footer.php';
    include ROOT_PATH . '/includes/scripts.php';
    exit;
}

// ---------- Tổng quan ----------
$stats = [
    ['UC06', 'Tiếp nhận hàng vào kho', 'inbox', uc_url('UC06'), 'blue'],
    ['UC10', 'Nhập kho', 'download', uc_url('UC10'), 'green'],
    ['UC17', 'Điều chuyển nội bộ', 'repeat', uc_url('UC17'), 'purple'],
    ['UC23', 'Xuất kho', 'upload', uc_url('UC23'), 'orange'],
    ['UC24', 'Kiểm kê', 'list', uc_url('UC24'), 'teal'],
];

$page_title = 'Tổng quan';
$active_menu = 'tong-quan';
$breadcrumb = [['Tổng quan', null]];
include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<div class="stat-grid">
    <?php foreach ($stats as [$mod, $label, $ico, $href, $color]):
        if (!has_module($mod)) continue; ?>
        <a class="stat-card" href="<?= e(url($href)) ?>">
            <span class="stat-icon c-<?= e($color) ?>"><?= icon($ico, 22) ?></span>
            <span class="stat-body">
                <small><?= e($label) ?> · <?= e($mod) ?></small>
                <b>Mở chức năng</b>
            </span>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-head">
        <h2>Các chức năng theo vai trò</h2>
        <span class="muted"><?= e(current_user()['role_name'] ?? '') ?></span>
    </div>
    <p>Mỗi chức năng được tách trong thư mục vai trò riêng và sử dụng cơ sở dữ liệu QLK tương ứng.</p>
</div>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
