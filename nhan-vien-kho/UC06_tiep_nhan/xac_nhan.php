<?php
/**
 * UC06 - Tiếp nhận hàng vào kho (Actor chính: Nhân viên kho)
 * Bước 7-11: màn hình xác nhận -> "Xác nhận" -> lưu phiếu + chi tiết -> tạo lô hàng -> thông báo thành công.
 * Exception 10.1: lỗi lưu dữ liệu -> "Tiếp nhận hàng thất bại" + "Thử lại" / "Hủy".
 */
require_once __DIR__ . '/_uc06.php';
require_module('UC06');

$so = '';
foreach ([$_GET['soChungTu'] ?? null, $_POST['soChungTu'] ?? null] as $cand) {
    if (is_string($cand) && trim($cand) !== '') { $so = trim($cand); break; }
}

$nhap = $_SESSION['uc06_nhap'] ?? null;
if (!$nhap || ($nhap['soChungTu'] ?? '') !== $so) {
    redirect($so !== '' ? uc06_url('form.php', ['soChungTu' => $so]) : uc06_url());
}

$ct = uc06_chung_tu($so);
$dong = $ct ? uc06_dong_hang($so) : [];
if (!$ct || !$dong) {
    unset($_SESSION['uc06_nhap']);
    flash('error', UC06_MSG_KHONG_CO_CHUNG_TU . '.');
    redirect(uc06_url());
}
if (uc06_hang_chua_khai_bao($dong)) {
    unset($_SESSION['uc06_nhap']);
    flash('error', UC06_MSG_HANG_CHUA_KHAI_BAO . '. Không thể tiếp nhận chứng từ ' . $so . '.');
    redirect(uc06_url());
}

$thatBai = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'huy') {                              // "Hủy" -> kết thúc Use Case
        unset($_SESSION['uc06_nhap']);
        flash('info', 'Đã hủy tiếp nhận chứng từ ' . $so . '.');
        redirect(uc06_url());
    }
    if ($action === 'xac_nhan') {                         // Bước 8-11 (cũng dùng cho "Thử lại")
        try {
            $kq = uc06_luu(current_user(), $nhap);
            unset($_SESSION['uc06_nhap']);                // làm sạch dữ liệu cho lần tiếp nhận sau
            flash('success', UC06_MSG_THANH_CONG . '. Phiếu ' . $kq['maTiepNhan'] . ' · lô hàng ' . implode(', ', $kq['dsLo']) .
                ' (Chờ kiểm tra chất lượng) · chứng từ ' . $so . ': ' . $kq['tinhTrangChungTu'] . '.');
            redirect(uc06_url());
        } catch (DomainException $ex) {                   // lỗi nghiệp vụ
            unset($_SESSION['uc06_nhap']);
            flash('error', $ex->getMessage() . '.');
            redirect(uc06_url());
        } catch (Throwable $ex) {                         // Exception 10.1: lỗi lưu dữ liệu
            $thatBai = true;
        }
    }
}

$tenViTri = uc06_ten_vi_tri($nhap['maViTri']);
$tongThucNhan = 0.0;

$page_title = 'Xác nhận tiếp nhận hàng';
$active_menu = 'UC06';
$breadcrumb = [['Tổng quan', 'index.php'], ['Tiếp nhận hàng vào kho', uc06_url()], ['Xác nhận', null]];

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<?php if ($thatBai): ?>
    <div class="alert alert-error" role="alert">
        <b><?= e(UC06_MSG_THAT_BAI) ?>.</b> Hệ thống không thể lưu thông tin tiếp nhận (chưa có dữ liệu nào được ghi nhận).
        Bạn muốn <b>Thử lại</b> hay <b>Hủy</b>?
    </div>
<?php else: ?>
    <div class="alert alert-info">Vui lòng kiểm tra lại thông tin tổng hợp bên dưới, sau đó bấm <b>Xác nhận</b> để lưu phiếu tiếp nhận.</div>
<?php endif; ?>

<div class="doc-layout">
    <section class="card info-panel">
        <h2 class="card-title">Thông tin phiếu tiếp nhận</h2>
        <dl class="kv">
            <dt>Mã phiếu tiếp nhận</dt><dd><?= e(uc06_ma_phieu_du_kien()) ?> <small class="muted">(cấp khi lưu)</small></dd>
            <dt>Ngày nhận</dt><dd><?= e(fmt_date($nhap['ngayNhan'])) ?></dd>
            <dt>Giờ nhận</dt><dd><?= e($nhap['gioNhan']) ?></dd>
            <dt>Người nhận</dt><dd><?= e(current_user()['ho_ten']) ?></dd>
            <dt>Nhà cung cấp / Nguồn giao</dt><dd><?= e($ct['nguonGiao']) ?></dd>
            <dt>Số chứng từ</dt><dd><?= e($ct['soChungTu']) ?> <small class="muted">(<?= e($ct['loaiChungTu']) ?>)</small></dd>
            <dt>Vị trí lưu trữ</dt><dd><?= e($nhap['maViTri'] . ' - ' . $tenViTri) ?></dd>
            <dt>Ghi chú</dt><dd><?= $nhap['ghiChu'] !== '' ? e($nhap['ghiChu']) : '<span class="muted">—</span>' ?></dd>
            <dt>Tình trạng sau khi lưu</dt><dd><?= status_badge('Chờ kiểm định') ?></dd>
        </dl>
    </section>

    <section class="card detail-panel">
        <h2 class="card-title">Danh sách hàng hóa tiếp nhận</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th class="w-stt">STT</th><th>Mã hàng</th><th>Tên hàng</th><th>ĐVT</th>
                    <th class="num">SL theo chứng từ</th><th class="num">Số lượng thực nhận</th><th>Ghi chú</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($dong as $i => $r):
                    $ma = $r['maHangHoa'];
                    $sl = (float)($nhap['dong'][$ma]['soLuong'] ?? 0);
                    $tongThucNhan += $sl; ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><b><?= e($ma) ?></b></td>
                        <td><?= e($r['tenHangHoa']) ?></td>
                        <td><?= e($r['donViTinh']) ?></td>
                        <td class="num"><?= fmt_qty($r['soLuongChungTu']) ?></td>
                        <td class="num"><b><?= fmt_qty($sl) ?></b></td>
                        <td><?= ($nhap['dong'][$ma]['ghiChu'] ?? '') !== '' ? e($nhap['dong'][$ma]['ghiChu']) : '<span class="muted">—</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr><td colspan="5" class="num"><b>Tổng số lượng thực nhận (<?= count($dong) ?> dòng)</b></td>
                    <td class="num total"><?= fmt_qty($tongThucNhan) ?></td><td></td></tr>
                </tfoot>
            </table>
        </div>
        <p class="hint">Sau khi xác nhận, hệ thống tự tạo lô hàng cho từng dòng với trạng thái "Chờ kiểm tra chất lượng".</p>
    </section>
</div>

<div class="form-actions">
    <?php if ($thatBai): ?>
        <form method="post" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="soChungTu" value="<?= e($so) ?>"><input type="hidden" name="action" value="huy">
            <button class="btn btn-ghost" type="submit">Hủy</button>
        </form>
        <form method="post" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="soChungTu" value="<?= e($so) ?>"><input type="hidden" name="action" value="xac_nhan">
            <button class="btn btn-primary" type="submit">Thử lại</button>
        </form>
    <?php else: ?>
        <a class="btn btn-ghost" href="<?= e(url(uc06_url('form.php', ['soChungTu' => $so]))) ?>">Quay lại chỉnh sửa</a>
        <form method="post" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="soChungTu" value="<?= e($so) ?>"><input type="hidden" name="action" value="xac_nhan">
            <button class="btn btn-primary" type="submit">Xác nhận</button>
        </form>
    <?php endif; ?>
</div>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
