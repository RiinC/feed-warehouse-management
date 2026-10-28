<?php
/**
 * UC06 - Chi tiết phiếu tiếp nhận.
 *  - ?modal=1 : trả về đoạn HTML nội dung chi tiết cho popup ở trang danh sách (không có khung trang).
 *  - không có modal : hiển thị thành trang đầy đủ (dự phòng khi trình duyệt tắt JavaScript).
 */
require_once __DIR__ . '/_uc06.php';

$ma = is_string($_GET['ma'] ?? null) ? trim($_GET['ma']) : '';

if (($_GET['modal'] ?? '') === '1') {
    if (!current_user()) {
        http_response_code(401);
        exit('Phiên đăng nhập đã hết hạn.');
    }
    if (!has_module('UC06')) {
        http_response_code(403);
        exit('Bạn không có quyền xem phiếu tiếp nhận.');
    }
    $p = $ma !== '' ? uc06_chi_tiet_phieu($ma) : null;
    if (!$p) {
        http_response_code(404);
        exit('Không tìm thấy phiếu tiếp nhận.');
    }
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    uc06_in_chi_tiet_phieu($p);
    exit;
}

require_module('UC06');

$p = $ma !== '' ? uc06_chi_tiet_phieu($ma) : null;
if (!$p) {
    flash('error', 'Không tìm thấy phiếu tiếp nhận.');
    redirect(uc06_url());
}

$page_title = 'Chi tiết phiếu tiếp nhận';
$active_menu = 'UC06';
$breadcrumb = [['Tổng quan', 'index.php'], ['Tiếp nhận hàng vào kho', uc06_url()], ['Phiếu ' . $p['maTiepNhan'], null]];

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<div class="card" style="max-width:640px">
    <?php uc06_in_chi_tiet_phieu($p); ?>
    <div class="form-actions">
        <a class="btn btn-ghost" href="<?= e(url(uc06_url())) ?>">Quay lại</a>
    </div>
</div>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
