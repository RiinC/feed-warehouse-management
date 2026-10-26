<?php
require_once __DIR__ . '/../../includes/auth.php';
require_module('UC17');

$page_title = 'Điều chuyển nội bộ';
$active_menu = 'UC17';
$breadcrumb = [['Tổng quan', 'index.php'], ['Điều chuyển nội bộ', null]];
$toolbar_actions = '<a class="btn btn-primary" href="' . e(url(uc_url('UC17', 'form.php'))) . '">' . icon('plus', 16) . ' Tạo phiếu</a>';
$db = db(DB_UC17);

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<div class="card empty-state">
    <div class="empty-icon"><?= icon('tool', 40) ?></div>
    <h2>Trang danh sách — Điều chuyển nội bộ</h2>
    <p>Khung giao diện, menu và phân quyền của module <b>UC17</b> đã sẵn sàng. Nội dung danh sách sẽ được bổ sung sau.</p>
</div>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
