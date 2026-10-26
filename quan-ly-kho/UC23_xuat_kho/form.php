<?php
require_once __DIR__ . '/../../includes/auth.php';
require_module('UC23');

$page_title = 'Tạo phiếu xuất kho';
$active_menu = 'UC23';
$breadcrumb = [['Tổng quan', 'index.php'], ['Xuất kho', uc_url('UC23')], [$page_title, null]];
$db = db(DB_UC23);

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<div class="card">
    <h2><?= e($page_title) ?></h2>
    <form method="post">
        <?= csrf_field() ?>
        <!-- TODO (UC23): thêm trường biểu mẫu và xử lý lưu phiếu tại đây. -->
        <div class="toolbar-actions">
            <a class="btn" href="<?= e(url(uc_url('UC23'))) ?>">Quay lại danh sách</a>
            <button class="btn btn-primary" type="submit">Lưu phiếu</button>
        </div>
    </form>
</div>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
