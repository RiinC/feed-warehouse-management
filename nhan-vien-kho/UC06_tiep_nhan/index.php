<?php
require_once __DIR__ . '/../../includes/auth.php';
require_module('UC06');

$page_title = 'Tiếp nhận hàng vào kho';
$active_menu = 'UC06';
$breadcrumb = [['Tổng quan', 'index.php'], ['Tiếp nhận hàng', null]];
$toolbar_actions = '<a class="btn btn-primary" href="' . e(url(uc_url('UC06', 'form.php'))) . '">' . icon('plus', 16) . ' Tạo phiếu</a>';
$db = db(DB_UC06);

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<div class="card empty-state">
    <div class="empty-icon"><?= icon('tool', 40) ?></div>
    <h2>Trang danh sách — Tiếp nhận hàng vào kho</h2>
    <p>Khung giao diện, menu và phân quyền của module <b>UC06</b> đã sẵn sàng. Nội dung danh sách sẽ được bổ sung sau.</p>
</div>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
