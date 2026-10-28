<?php
/**
 * UC06 - Tiếp nhận hàng vào kho (Actor chính: Nhân viên kho)
 * Bước 4-6: màn hình "Tiếp nhận hàng vào kho" -> chọn vị trí, nhập số lượng thực nhận -> kiểm tra.
 * Alternative 6.1: thiếu trường bắt buộc / số lượng <= 0 -> đánh dấu trường lỗi, quay lại bước 5.
 * Exception 3.1.1: không có chứng từ hợp lệ | Exception 4.1: hàng hóa chưa khai báo.
 */
require_once __DIR__ . '/_uc06.php';
require_module('UC06');

$so = '';
foreach ([$_GET['soChungTu'] ?? null, $_POST['soChungTu'] ?? null] as $cand) {
    if (is_string($cand) && trim($cand) !== '') { $so = trim($cand); break; }
}

$ct = $so !== '' ? uc06_chung_tu($so) : null;
$dong = $ct ? uc06_dong_hang($so) : [];
if (!$ct || !$dong) {                                    // Exception 3.1.1
    flash('error', UC06_MSG_KHONG_CO_CHUNG_TU . '.');
    redirect(uc06_url());
}
$chuaKhaiBao = uc06_hang_chua_khai_bao($dong);
if ($chuaKhaiBao) {                                      // Exception 4.1 -> Use Case kết thúc
    unset($_SESSION['uc06_nhap']);
    flash('error', UC06_MSG_HANG_CHUA_KHAI_BAO . ': ' . implode('; ', $chuaKhaiBao) .
        '. Không thể tiếp nhận chứng từ ' . $so . '.');
    redirect(uc06_url());
}

$loi = [];
$nhap = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$loi, $nhap] = uc06_kiem_tra($so, $dong, $_POST);   // Bước 6
    if (!$loi) {
        $_SESSION['uc06_nhap'] = $nhap;                  // Bước 7: sang màn hình xác nhận
        redirect(uc06_url('xac_nhan.php', ['soChungTu' => $so]));
    }
} elseif (!empty($_SESSION['uc06_nhap']) && ($_SESSION['uc06_nhap']['soChungTu'] ?? '') === $so) {
    $nhap = $_SESSION['uc06_nhap'];                      // quay lại từ màn hình xác nhận
}

$v = [
    'ngayNhan' => $nhap['ngayNhan'] ?? date('Y-m-d'),
    'gioNhan' => $nhap['gioNhan'] ?? date('H:i'),
    'maViTri' => $nhap['maViTri'] ?? '',
    'ghiChu' => $nhap['ghiChu'] ?? '',
];
$viTri = uc06_vi_tri_dang_dung();
$maPhieu = uc06_ma_phieu_du_kien();

$page_title = 'Tiếp nhận hàng vào kho';
$active_menu = 'UC06';
$breadcrumb = [['Tổng quan', 'index.php'], ['Tiếp nhận hàng vào kho', uc06_url()], ['Chứng từ ' . $so, null]];

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<?php if ($loi): ?>
    <div class="alert alert-error" role="alert">
        <b>Dữ liệu không hợp lệ.</b> Vui lòng bổ sung các trường được đánh dấu (có dấu *) và nhập Số lượng thực nhận lớn hơn 0.
    </div>
<?php endif; ?>

<form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="soChungTu" value="<?= e($so) ?>">
    <div class="doc-layout">
        <section class="card info-panel">
            <h2 class="card-title">Thông tin phiếu tiếp nhận</h2>
            <div class="field">
                <label>Mã phiếu tiếp nhận</label>
                <input type="text" value="<?= e($maPhieu) ?>" disabled>
                <small class="hint">Tự sinh khi lưu phiếu.</small>
            </div>
            <div class="field<?= isset($loi['ngayNhan']) ? ' invalid' : '' ?>">
                <label for="ngayNhan">Ngày nhận <i class="req">*</i></label>
                <input type="date" id="ngayNhan" name="ngayNhan" value="<?= e($v['ngayNhan']) ?>">
                <?php if (isset($loi['ngayNhan'])): ?><span class="err-msg"><?= e($loi['ngayNhan']) ?></span><?php endif; ?>
            </div>
            <div class="field<?= isset($loi['gioNhan']) ? ' invalid' : '' ?>">
                <label for="gioNhan">Giờ nhận <i class="req">*</i></label>
                <input type="time" id="gioNhan" name="gioNhan" value="<?= e($v['gioNhan']) ?>">
                <?php if (isset($loi['gioNhan'])): ?><span class="err-msg"><?= e($loi['gioNhan']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label>Người nhận</label>
                <input type="text" value="<?= e(current_user()['ho_ten']) ?>" disabled>
            </div>
            <div class="field">
                <label>Nhà cung cấp / Nguồn giao</label>
                <input type="text" value="<?= e($ct['nguonGiao']) ?>" disabled>
            </div>
            <div class="field">
                <label>Số chứng từ</label>
                <input type="text" value="<?= e($ct['soChungTu']) ?> (<?= e($ct['loaiChungTu']) ?>)" disabled>
            </div>
            <div class="field<?= isset($loi['maViTri']) ? ' invalid' : '' ?>">
                <label for="maViTri">Vị trí lưu trữ <i class="req">*</i></label>
                <select id="maViTri" name="maViTri">
                    <option value="">— Chọn vị trí lưu trữ —</option>
                    <?php foreach ($viTri as $vt): ?>
                        <option value="<?= e($vt['maViTri']) ?>" <?= $v['maViTri'] === $vt['maViTri'] ? 'selected' : '' ?>>
                            <?= e($vt['maViTri'] . ' - ' . $vt['tenViTri']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($loi['maViTri'])): ?><span class="err-msg"><?= e($loi['maViTri']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label for="ghiChu">Ghi chú</label>
                <textarea id="ghiChu" name="ghiChu" rows="3" maxlength="500" placeholder="Ghi chú thêm (nếu có)"><?= e($v['ghiChu']) ?></textarea>
            </div>
        </section>

        <section class="card detail-panel">
            <h2 class="card-title">Danh sách hàng hóa tiếp nhận</h2>
            <div class="table-wrap">
                <table class="table lines">
                    <thead>
                    <tr>
                        <th class="w-stt">STT</th>
                        <th>Mã hàng</th>
                        <th>Tên hàng</th>
                        <th>ĐVT</th>
                        <th class="num">SL theo chứng từ</th>
                        <th class="num">Đã nhận</th>
                        <th class="num">Còn lại</th>
                        <th class="col-num">Số lượng thực nhận <i class="req">*</i></th>
                        <th>Ghi chú</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dong as $i => $r):
                        $ma = $r['maHangHoa'];
                        $sai = isset($loi['dong'][$ma]);
                        $gtSl = $nhap['dong'][$ma]['raw'] ?? (isset($nhap['dong'][$ma]) ? (string)$nhap['dong'][$ma]['soLuong'] : '');
                        $gtGc = $nhap['dong'][$ma]['ghiChu'] ?? '';
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><b><?= e($ma) ?></b></td>
                            <td><?= e($r['tenHangHoa']) ?></td>
                            <td><?= e($r['donViTinh']) ?></td>
                            <td class="num"><?= fmt_qty($r['soLuongChungTu']) ?></td>
                            <td class="num"><?= fmt_qty($r['soLuongDaNhan']) ?></td>
                            <td class="num"><b><?= fmt_qty($r['conLai']) ?></b></td>
                            <td>
                                <input type="number" name="soLuongThucNhan[<?= e($ma) ?>]" value="<?= e($gtSl) ?>"
                                       min="0" step="any" placeholder="<?= e(fmt_qty($r['conLai'])) ?>"
                                       class="<?= $sai ? 'is-invalid' : '' ?>" aria-label="Số lượng thực nhận của <?= e($ma) ?>">
                                <?php if ($sai): ?><span class="err-msg"><?= e($loi['dong'][$ma]) ?></span><?php endif; ?>
                            </td>
                            <td><input type="text" name="ghiChuDong[<?= e($ma) ?>]" value="<?= e($gtGc) ?>" maxlength="255" placeholder="Ghi chú"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="hint">Chỉ hiển thị các dòng hàng còn phải tiếp nhận của chứng từ. Số lượng thực nhận phải lớn hơn 0.</p>
        </section>
    </div>

    <div class="form-actions">
        <a class="btn btn-ghost" href="<?= e(url(uc06_url())) ?>">Hủy</a>
        <button class="btn btn-primary" type="submit">Tiếp tục</button>
    </div>
</form>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
