<?php
/** UC09 - Xác nhận kết quả kiểm tra chất lượng */
$danhSachLoHang = $danhSachLoHang ?? [];
$maLoHang = $maLoHang ?? '';
$tuNgay = $tuNgay ?? '';
$denNgay = $denNgay ?? '';
$thongBao = $thongBao ?? '';
$trang = $trang ?? 1;
$tongSoTrang = $tongSoTrang ?? 1;
$tongSoLoHang = $tongSoLoHang ?? 0;
$soLuongMoiTrang = $soLuongMoiTrang ?? 10;
$loHang = $loHang ?? null;
$maChiTiet = $maChiTiet ?? '';
$loaiHangHoa = $loaiHangHoa ?? '';
$danhSachBienBan = $danhSachBienBan ?? [];
$bienBan = $bienBan ?? null;
$maBienBan = $maBienBan ?? '';
$canChonBienBan = $canChonBienBan ?? false;
$ketQua = $ketQua ?? [];
$ketLuanQC = $ketLuanQC ?? '';
$phienBan = $phienBan ?? '';
$maNhanVienDuyet = $maNhanVienDuyet ?? '';
$coTheXacNhan = $coTheXacNhan ?? false;
$hienThiFormXacNhan = $hienThiFormXacNhan ?? false;
$ketQuaXacNhan = $ketQuaXacNhan ?? '';
$lyDoKhongDat = $lyDoKhongDat ?? '';
$choXacNhan = $choXacNhan ?? false;
$maXacNhan = $maXacNhan ?? '';
$huongXuLyDuKien = $huongXuLyDuKien ?? '';
$loi = $loi ?? [];

$baseUrl = '/feed-warehouse-management/public';
$confirmUrl = $baseUrl . '/chat-luong/confirm-result';
$e = static function ($value): string {
    return htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$dinhDangNgay = static function ($value): string {
    if (empty($value)) return '';
    $timestamp = strtotime((string)$value);
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '';
};
$dinhDangSoLuong = static function ($value): string {
    if ($value === null || $value === '') return '';
    if (!is_numeric($value)) return (string)$value;
    return rtrim(rtrim(number_format((float)$value, 6, ',', '.'), '0'), ',');
};
$layGiaTriTieuChuan = static function (array $dong) use ($dinhDangSoLuong): string {
    $min = $dong['giaTriToiThieu'] ?? null;
    $max = $dong['giaTriToiDa'] ?? null;
    $donVi = $dong['donViDo'] ?? '';
    $giaTri = '';
    if ($min !== null && $max !== null) $giaTri = $dinhDangSoLuong($min) . ' - ' . $dinhDangSoLuong($max);
    elseif ($min !== null) $giaTri = '≥ ' . $dinhDangSoLuong($min);
    elseif ($max !== null) $giaTri = '≤ ' . $dinhDangSoLuong($max);
    return $giaTri === '' ? 'Không quy định ngưỡng' : trim($giaTri . ' ' . $donVi);
};
$mauKetQua = static function (string $ketQua): string {
    if ($ketQua === 'Đạt') return '#15803d';
    if ($ketQua === 'Không đạt') return '#dc2626';
    return '#64748b';
};
$thamSoLoc = ['maLoHang' => $maLoHang, 'tuNgay' => $tuNgay, 'denNgay' => $denNgay, 'page' => $trang];
$taoUrl = static function (array $thamSo = []) use ($confirmUrl): string {
    $thamSo = array_filter($thamSo, static function ($value) { return $value !== '' && $value !== null; });
    return empty($thamSo) ? $confirmUrl : $confirmUrl . '?' . http_build_query($thamSo);
};
$quayLaiDanhSachUrl = $taoUrl($thamSoLoc);
$moFormXacNhanUrl = $taoUrl(array_merge($thamSoLoc, ['maChiTiet' => $loHang['maLoHang'] ?? $maChiTiet, 'maBienBan' => $maBienBan, 'hanhDong' => 'xacNhan']));
$xemChiTietUrl = $taoUrl(array_merge($thamSoLoc, ['maChiTiet' => $loHang['maLoHang'] ?? $maChiTiet, 'maBienBan' => $maBienBan]));
$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
$iconXem = $svg . '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
$iconXacNhan = $svg . '<rect x="8" y="3" width="8" height="4" rx="1"/><path d="M16 5h3a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3"/><path d="m9 14 2 2 4-4"/></svg>';
$iconTrangTruoc = $svg . '<path d="m15 18-6-6 6-6"/></svg>';
$iconTrangSau = $svg . '<path d="m9 18 6-6-6-6"/></svg>';
$styleIcon = 'display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0;';
?>

<style>
    .uc09-fixed-field:disabled {
        background-color: #f5f7fa;
        color: #334155;
        -webkit-text-fill-color: currentColor;
        opacity: 1;
        cursor: default;
        pointer-events: none;
    }
</style>

<!-- Thông báo lỗi theo chiều dọc -->
<?php if (!empty($loi)): ?>
<div class="alert alert-warning" style="display: block;" role="alert">
    <div style="margin-bottom: 10px;"><strong>Vui lòng kiểm tra lại thông tin.</strong></div>
    <?php foreach ($loi as $noiDungLoi): ?>
        <?php foreach (is_array($noiDungLoi) ? $noiDungLoi : [$noiDungLoi] as $dongLoi): ?>
            <div style="margin: 6px 0;"><?= $e($dongLoi) ?></div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($loHang === null): ?>
<!-- Bộ lọc -->
<div class="card filter-card">
    <div class="card-head"><strong>Tra cứu lô hàng chờ xác nhận</strong></div>
    <form method="GET" action="<?= $e($confirmUrl) ?>">
        <div class="filter-grid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)) auto; align-items: end; gap: 16px;">
            <div class="field" style="margin-bottom: 0; min-width: 0;">
                <label for="maLoHang">Mã lô hàng</label>
                <input type="text" id="maLoHang" name="maLoHang" maxlength="12" value="<?= $e($maLoHang) ?>" placeholder="Nhập mã lô hàng" style="height: 40px; min-height: 40px; max-height: 40px; box-sizing: border-box; padding-top: 0; padding-bottom: 0; margin-bottom: 0; width: 100%;">
            </div>
            <div class="field" style="margin-bottom: 0; min-width: 0;">
                <label for="tuNgay">Từ ngày</label>
                <input type="date" id="tuNgay" name="tuNgay" value="<?= $e($tuNgay) ?>" style="height: 40px; min-height: 40px; max-height: 40px; box-sizing: border-box; padding-top: 0; padding-bottom: 0; margin-bottom: 0; width: 100%;">
            </div>
            <div class="field" style="margin-bottom: 0; min-width: 0;">
                <label for="denNgay">Đến ngày</label>
                <input type="date" id="denNgay" name="denNgay" value="<?= $e($denNgay) ?>" style="height: 40px; min-height: 40px; max-height: 40px; box-sizing: border-box; padding-top: 0; padding-bottom: 0; margin-bottom: 0; width: 100%;">
            </div>
            <div class="filter-actions" style="margin: 0; padding: 0; align-self: end; display: flex; align-items: center; gap: 8px; flex-wrap: nowrap;">
                <button type="submit" class="btn btn-primary" style="height: 40px; box-sizing: border-box; padding: 0 16px; margin: 0; display: inline-flex; align-items: center; justify-content: center; white-space: nowrap;">Tìm kiếm</button>
                <a href="<?= $e($confirmUrl) ?>" class="btn" style="height: 40px; box-sizing: border-box; padding: 0 16px; margin: 0; display: inline-flex; align-items: center; justify-content: center; white-space: nowrap;">Làm mới</a>
            </div>
        </div>
    </form>
</div>
<?php if ($thongBao !== ''): ?>
<div class="alert alert-warning" style="display: block;"><?= $e($thongBao) ?></div>
<?php endif; ?>

<!-- Danh sách lô hàng: 6 cột -->
<div class="card">
    <div class="card-head">
        <strong>Danh sách lô hàng chờ xác nhận</strong>
        <span class="muted">Tổng số: <?= (int)$tongSoLoHang ?> lô hàng</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th class="w-stt">STT</th>
                    <th>Mã lô hàng</th>
                    <th>Tên lô hàng</th>
                    <th>Thời gian tiếp nhận</th>
                    <th>Trạng thái chất lượng</th>
                    <th class="w-actions">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachLoHang)): ?>
                    <tr><td colspan="6" class="empty-row">Không có lô hàng chờ xác nhận.</td></tr>
                <?php else: ?>
                    <?php foreach ($danhSachLoHang as $i => $lh): ?>
                        <?php
                        $linkChiTiet = $taoUrl(array_merge($thamSoLoc, ['maChiTiet' => $lh['maLoHang']]));
                        $stt = ($trang - 1) * $soLuongMoiTrang + $i + 1;
                        ?>
                        <tr>
                            <td><?= (int)$stt ?></td>
                            <td><?= $e($lh['maLoHang']) ?></td>
                            <td><?= $e($lh['tenLoHang']) ?></td>
                            <td><?= $e($dinhDangNgay($lh['thoiGianTiepNhan'])) ?></td>
                            <td><span class="badge badge-warn"><?= $e($lh['trangThaiChatLuong']) ?></span></td>
                            <td class="w-actions">
                                <a href="<?= $e($linkChiTiet) ?>" class="btn btn-sm" title="Xem chi tiết lô hàng" aria-label="Xem chi tiết lô hàng" style="<?= $e($styleIcon) ?> color: #2563eb;"><?= $iconXem ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($tongSoTrang > 1): ?>
    <div class="filter-actions" style="display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 6px;">
        <?php if ($trang > 1): ?>
            <a href="<?= $e($taoUrl(array_merge($thamSoLoc, ['page' => $trang - 1]))) ?>" class="btn btn-sm" title="Trang trước" aria-label="Trang trước"><?= $iconTrangTruoc ?></a>
        <?php else: ?>
            <button type="button" class="btn btn-sm" title="Trang trước" disabled><?= $iconTrangTruoc ?></button>
        <?php endif; ?>
        <?php $trangBatDau = max(1, $trang - 2); $trangKetThuc = min($tongSoTrang, $trang + 2); ?>
        <?php if ($trangBatDau > 1): ?>
            <a class="btn btn-sm" href="<?= $e($taoUrl(array_merge($thamSoLoc, ['page' => 1]))) ?>">1</a>
            <?php if ($trangBatDau > 2): ?><span class="muted">...</span><?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $trangBatDau; $p <= $trangKetThuc; $p++): ?>
            <a href="<?= $e($taoUrl(array_merge($thamSoLoc, ['page' => $p]))) ?>" class="btn btn-sm <?= $p === $trang ? 'btn-primary' : '' ?>" <?= $p === $trang ? 'aria-current="page"' : '' ?>><?= (int)$p ?></a>
        <?php endfor; ?>
        <?php if ($trangKetThuc < $tongSoTrang): ?>
            <?php if ($trangKetThuc < $tongSoTrang - 1): ?><span class="muted">...</span><?php endif; ?>
            <a class="btn btn-sm" href="<?= $e($taoUrl(array_merge($thamSoLoc, ['page' => $tongSoTrang]))) ?>"><?= (int)$tongSoTrang ?></a>
        <?php endif; ?>
        <?php if ($trang < $tongSoTrang): ?>
            <a href="<?= $e($taoUrl(array_merge($thamSoLoc, ['page' => $trang + 1]))) ?>" class="btn btn-sm" title="Trang sau" aria-label="Trang sau"><?= $iconTrangSau ?></a>
        <?php else: ?>
            <button type="button" class="btn btn-sm" title="Trang sau" disabled><?= $iconTrangSau ?></button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<!-- Thông tin chi tiết lô hàng -->
<?php
$thongTinLoHang = [
    'Mã lô hàng' => $loHang['maLoHang'] ?? '',
    'Tên lô hàng' => $loHang['tenLoHang'] ?? '',
    'Số lượng tiếp nhận' => $dinhDangSoLuong($loHang['soLuongTiepNhan'] ?? ''),
    'Đơn vị tính' => $loHang['donViTinh'] ?? '',
    'Thời gian tiếp nhận' => $dinhDangNgay($loHang['thoiGianTiepNhan'] ?? ''),
    'Trạng thái lô' => $loHang['trangThaiLo'] ?? '',
    'Trạng thái chất lượng' => $loHang['trangThaiChatLuong'] ?? ''
];
?>
<div class="card">
    <div class="card-head">
        <strong>Thông tin lô hàng</strong>
        <?php if (!$choXacNhan): ?><a href="<?= $e($quayLaiDanhSachUrl) ?>" class="btn btn-sm">Quay lại danh sách</a><?php endif; ?>
    </div>
    <div class="table-wrap">
        <table class="table" style="width:100%; table-layout:fixed;">
            <colgroup><col style="width:35%;"><col style="width:65%;"></colgroup>
            <thead>
                <tr><th scope="col">Thông tin</th><th scope="col">Giá trị</th></tr>
            </thead>
            <tbody>
                <?php foreach ($thongTinLoHang as $nhan => $giaTri): ?>
                    <tr>
                        <td><strong><?= $e($nhan) ?></strong></td>
                        <td style="overflow-wrap:anywhere;"><?= $e($giaTri) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if ($thongBao !== ''): ?><div class="alert alert-warning" style="display: block;"><?= $e($thongBao) ?></div><?php endif; ?>

<?php if ($canChonBienBan || $bienBan === null || count($danhSachBienBan) !== 1): ?>
<!-- Dữ liệu không đáp ứng quy tắc một biên bản chờ duyệt trên mỗi lô -->
<div class="card">
    <div class="alert alert-warning" style="display: block;" role="alert">
        <?= $e(count($danhSachBienBan) > 1
            ? 'Lô hàng có nhiều biên bản chờ duyệt, không phù hợp quy tắc nghiệp vụ. Cần xử lý dữ liệu trước khi xác nhận.'
            : 'Không thể tải biên bản kiểm định hợp lệ. Vui lòng quay lại danh sách và kiểm tra lại.') ?>
    </div>
    <div class="filter-actions"><a href="<?= $e($quayLaiDanhSachUrl) ?>" class="btn">Quay lại danh sách</a></div>
</div>
<?php else: ?>
<!-- Kết quả QC -->
<div class="card">
    <div class="card-head"><strong>Kết quả kiểm tra chất lượng</strong><span class="muted">Tổng số: <?= count($ketQua) ?> tiêu chí</span></div>
    <div class="filter-grid">
        <div class="field"><label>Mã biên bản kiểm định</label><input type="text" value="<?= $e($bienBan['maBienBan'] ?? '') ?>" class="uc09-fixed-field" disabled></div>
        <div class="field"><label>Nhân viên kiểm tra</label><input type="text" value="<?= $e($bienBan['tenNhanVienQC'] ?? '') ?>" class="uc09-fixed-field" disabled></div>
        <div class="field"><label>Thời gian kiểm định</label><input type="text" value="<?= $e($dinhDangNgay($bienBan['ngayKiemDinh'] ?? '')) ?>" class="uc09-fixed-field" disabled></div>
    </div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th class="w-stt">STT</th><th>Mã kết quả kiểm tra</th><th>Tiêu chí</th><th>Giá trị tiêu chuẩn</th><th>Giá trị đo được</th><th>Giá trị định tính</th><th>Kết quả</th><th>Ghi chú</th></tr></thead>
        <tbody>
            <?php if (empty($ketQua)): ?>
                <tr><td colspan="8" class="empty-row">Chưa có kết quả kiểm tra.</td></tr>
            <?php else: ?>
                <?php foreach ($ketQua as $i => $kq): ?>
                    <?php
                    $giaTriDoDuoc = $kq['giaTriDoDuoc'] ?? null;
                    $giaTriDinhTinh = $kq['giaTriDinhTinh'] ?? '';
                    $danhGia = $kq['danhGia'] ?? '';
                    $ghiChu = $kq['ghiChu'] ?? '';
                    $donViDo = $kq['donViDo'] ?? '';
                    $giaTriDoHienThi = '—';
                    if ($giaTriDoDuoc !== null && $giaTriDoDuoc !== '') $giaTriDoHienThi = trim($dinhDangSoLuong($giaTriDoDuoc) . ' ' . $donViDo);
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td><td><?= $e($kq['maKetQuaKiemTra'] ?? '') ?></td>
                        <td><strong><?= $e($kq['tenTieuChi'] ?? '') ?></strong><?php if (!empty($kq['phuongPhapKiemTra'])): ?><div class="muted"><?= $e($kq['phuongPhapKiemTra']) ?></div><?php endif; ?></td>
                        <td><?= $e($layGiaTriTieuChuan($kq)) ?></td><td><?= $e($giaTriDoHienThi) ?></td><td><?= $e($giaTriDinhTinh !== '' ? $giaTriDinhTinh : '—') ?></td>
                        <td><span class="badge" style="color: <?= $e($mauKetQua($danhGia)) ?>;"><?= $e($danhGia) ?></span></td>
                        <td style="white-space: pre-wrap; overflow-wrap: anywhere;"><?= $e($ghiChu !== '' ? $ghiChu : '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table></div>
    <div class="card-head"><strong>Kết luận kiểm tra của QC</strong><span class="badge" style="color: <?= $e($mauKetQua($ketLuanQC)) ?>;"><?= $e($ketLuanQC) ?></span></div>
    <?php if ($ketLuanQC === 'Không đạt' && !empty($bienBan['lyDoKhongDat'])): ?>
        <div class="field"><label>Lý do không đạt của QC</label><textarea rows="2" class="uc09-fixed-field" disabled><?= $e($bienBan['lyDoKhongDat']) ?></textarea></div>
    <?php endif; ?>
    <?php if (!empty($bienBan['ghiChu'])): ?>
        <div class="field"><label>Ghi chú biên bản</label><textarea rows="2" class="uc09-fixed-field" disabled><?= $e($bienBan['ghiChu']) ?></textarea></div>
    <?php endif; ?>
    <?php if (!$hienThiFormXacNhan && !$choXacNhan): ?>
        <div class="filter-actions">
            <a href="<?= $e($quayLaiDanhSachUrl) ?>" class="btn">Quay lại</a>
            <?php if ($coTheXacNhan): ?><a href="<?= $e($moFormXacNhanUrl) ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;"><?= $iconXacNhan ?> Xác nhận kết quả</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Biểu mẫu xác nhận -->
<?php if ($hienThiFormXacNhan && !$choXacNhan && $coTheXacNhan): ?>
<div class="card" id="uc09-confirm-panel" style="padding: 24px; scroll-margin-top: 90px;">
    <div class="card-head"><strong style="font-size: 22px;">Xác nhận kết quả kiểm tra chất lượng</strong></div>
    <form method="POST" action="<?= $e($confirmUrl) ?>" id="form-xac-nhan-uc09">
        <?= csrf_field() ?>
        <input type="hidden" name="maLoHang" value="<?= $e($loHang['maLoHang']) ?>">
        <input type="hidden" name="maBienBan" value="<?= $e($maBienBan) ?>">
        <input type="hidden" name="phienBan" value="<?= $e($phienBan) ?>">
        <input type="hidden" id="loaiHangHoaUC09" value="<?= $e($loaiHangHoa) ?>">
        <div class="field">
            <label id="nhanKetQuaXacNhan">Kết quả xác nhận <span style="color: #dc2626;">*</span></label>
            <div role="radiogroup" aria-labelledby="nhanKetQuaXacNhan" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin: 12px 0 20px;">
                <?php foreach (['Đạt' => '#15803d', 'Không đạt' => '#dc2626'] as $luaChon => $mau): ?>
                    <label class="uc09-choice" style="display: flex; align-items: center; gap: 14px; padding: 22px; min-height: 84px; box-sizing: border-box; border: 2px solid #dbe2ea; border-radius: 12px; cursor: pointer; color: <?= $e($mau) ?>;" data-color="<?= $e($mau) ?>">
                        <input type="radio" name="ketQuaXacNhan" value="<?= $e($luaChon) ?>" class="js-ket-qua-xac-nhan" <?= $ketQuaXacNhan === $luaChon ? 'checked' : '' ?> style="width: 22px; height: 22px; flex: 0 0 22px; accent-color: <?= $e($mau) ?>; margin: 0;">
                        <span style="font-size: 20px; font-weight: 700;"><?= $e($luaChon) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="field" id="khungLyDoKhongDat" <?= $ketQuaXacNhan !== 'Không đạt' ? 'hidden' : '' ?>>
            <label for="lyDoKhongDat">Lý do không đạt <span style="color: #dc2626;">*</span></label>
            <textarea name="lyDoKhongDat" id="lyDoKhongDat" rows="4" maxlength="200" placeholder="Nhập lý do không đạt..." aria-describedby="demKyTuLyDo"><?= $e($lyDoKhongDat) ?></textarea>
            <div class="muted" id="demKyTuLyDo" style="text-align: right;">0/200 ký tự</div>
        </div>
        <div id="canhBaoKhacQC" class="alert alert-warning" style="display: none;" data-ket-luan-qc="<?= $e($ketLuanQC) ?>">Kết quả xác nhận đang khác với kết luận kiểm tra của QC. Vui lòng kiểm tra kỹ trước khi thực hiện xác nhận.</div>
        <div id="huongXuLyUC09" class="alert alert-warning" style="display: none;"></div>
        <div class="filter-actions"><a href="<?= $e($quayLaiDanhSachUrl) ?>" class="btn" style="min-height: 46px; padding: 12px 24px;">Quay lại</a><button type="submit" name="hanhDong" value="kiemTra" class="btn btn-primary" style="min-height: 46px; padding: 12px 24px;">Xác nhận kết quả</button></div>
    </form>
</div>
<?php endif; ?>

<!-- Xác nhận cuối cùng -->
<?php if ($choXacNhan): ?>
<div class="card" id="uc09-confirm-panel" style="padding: 24px; scroll-margin-top: 90px;">
    <div class="card-head"><strong style="font-size: 22px;">Xác nhận kết quả kiểm tra</strong></div>
    <div class="filter-grid">
        <div class="field"><label>Mã lô hàng</label><input type="text" value="<?= $e($loHang['maLoHang']) ?>" class="uc09-fixed-field" disabled></div>
        <div class="field"><label>Mã biên bản kiểm định</label><input type="text" value="<?= $e($maBienBan) ?>" class="uc09-fixed-field" disabled></div>
        <div class="field"><label>Kết luận của QC</label><input type="text" value="<?= $e($ketLuanQC) ?>" style="color: <?= $e($mauKetQua($ketLuanQC)) ?>; font-weight: 600;" class="uc09-fixed-field" disabled></div>
        <div class="field"><label>Kết quả xác nhận</label><input type="text" value="<?= $e($ketQuaXacNhan) ?>" style="color: <?= $e($mauKetQua($ketQuaXacNhan)) ?>; font-weight: 600;" class="uc09-fixed-field" disabled></div>
    </div>
    <?php if ($ketQuaXacNhan === 'Không đạt'): ?><div class="field"><label>Lý do không đạt</label><textarea rows="3" class="uc09-fixed-field" disabled><?= $e($lyDoKhongDat) ?></textarea></div><?php endif; ?>
    <?php if ($ketLuanQC !== '' && $ketLuanQC !== $ketQuaXacNhan): ?>
        <div class="alert alert-warning" style="display: block;">Kết quả xác nhận khác với kết luận của QC. Kết luận tổng hợp của biên bản sẽ được cập nhật theo kết quả xác nhận của quản lý kho. Kết quả từng tiêu chí do QC ghi nhận vẫn được giữ nguyên.</div>
    <?php endif; ?>
    <div class="alert alert-warning" style="display: block;">
        <div style="margin-bottom: 10px;">Bạn có chắc chắn muốn xác nhận kết quả kiểm tra chất lượng của lô hàng <strong><?= $e($loHang['maLoHang']) ?></strong> là <strong style="color: <?= $e($mauKetQua($ketQuaXacNhan)) ?>;"><?= $e($ketQuaXacNhan) ?></strong> không?</div>
        <?php if ($huongXuLyDuKien !== ''): ?><div style="margin: 8px 0;"><?= $e($huongXuLyDuKien) ?></div><?php endif; ?>
        <div style="margin-top: 8px;">Sau khi xác nhận thành công, lô hàng sẽ không còn xuất hiện trong danh sách chờ xác nhận.</div>
    </div>
    <form method="POST" action="<?= $e($confirmUrl) ?>" id="form-xac-nhan-cuoi-uc09">
        <?= csrf_field() ?>
        <input type="hidden" name="maXacNhan" value="<?= $e($maXacNhan) ?>">
        <div class="filter-actions"><button type="submit" name="hanhDong" value="huy" class="btn">Hủy</button><button type="submit" name="hanhDong" value="xacNhan" class="btn btn-primary" <?= ($maXacNhan === '' || !$coTheXacNhan) ? 'disabled' : '' ?>>Xác nhận</button></div>
    </form>
</div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<script>
(function () {
    const form = document.getElementById('form-xac-nhan-uc09');
    if (form) {
        const danhSachKetQua = form.querySelectorAll('.js-ket-qua-xac-nhan');
        const khungLyDo = document.getElementById('khungLyDoKhongDat');
        const oLyDo = document.getElementById('lyDoKhongDat');
        const demKyTu = document.getElementById('demKyTuLyDo');
        const huongXuLy = document.getElementById('huongXuLyUC09');
        const canhBaoKhacQC = document.getElementById('canhBaoKhacQC');
        const oLoaiHangHoa = document.getElementById('loaiHangHoaUC09');
        function capNhatSoKyTu() {
            if (!oLyDo || !demKyTu) return;
            const soKyTu = Array.from(oLyDo.value).length;
            demKyTu.textContent = soKyTu + '/200 ký tự';
            demKyTu.style.color = soKyTu >= 200 ? '#dc2626' : '';
        }
        function capNhatHuongXuLy(ketQua, loaiHangHoa) {
            if (!huongXuLy) return;
            let noiDung = '';
            if (ketQua === 'Đạt') {
                noiDung = 'Lô hàng đủ điều kiện về chất lượng để thực hiện lập phiếu nhập kho.';
            } else if (ketQua === 'Không đạt') {
                if (loaiHangHoa === 'Nguyên liệu') noiDung = 'Hệ thống sẽ lập biên bản trả hàng và chuyển sang chờ xuất trả.';
                else if (loaiHangHoa === 'Thành phẩm') noiDung = 'Lô thành phẩm sẽ được ghi nhận cách ly và xác định kho hàng lỗi làm kho đích dự kiến.';
            }
            huongXuLy.textContent = noiDung;
            huongXuLy.style.display = noiDung !== '' ? 'block' : 'none';
        }
        function capNhatFormXacNhan() {
            const ketQuaDaChon = form.querySelector('input[name="ketQuaXacNhan"]:checked');
            const ketQua = ketQuaDaChon ? ketQuaDaChon.value : '';
            const loaiHangHoa = oLoaiHangHoa ? oLoaiHangHoa.value : '';
            const laKhongDat = ketQua === 'Không đạt';
            if (khungLyDo) {
                khungLyDo.hidden = !laKhongDat;
                khungLyDo.style.display = laKhongDat ? '' : 'none';
            }
            if (oLyDo) oLyDo.disabled = !laKhongDat;
            if (canhBaoKhacQC) {
                const ketLuanQC = canhBaoKhacQC.dataset.ketLuanQc || '';
                const khacQC = ketQua !== '' && ketLuanQC !== '' && ketQua !== ketLuanQC;
                canhBaoKhacQC.style.display = khacQC ? 'block' : 'none';
            }
            danhSachKetQua.forEach(function (radio) {
                const label = radio.closest('.uc09-choice');
                if (!label) return;
                label.style.borderColor = radio.checked ? label.dataset.color : '#dbe2ea';
                label.style.backgroundColor = radio.checked ? (radio.value === 'Đạt' ? '#f0fdf4' : '#fef2f2') : '';
            });
            capNhatHuongXuLy(ketQua, loaiHangHoa);
            capNhatSoKyTu();
        }
        danhSachKetQua.forEach(function (radio) { radio.addEventListener('change', capNhatFormXacNhan); });
        if (oLyDo) oLyDo.addEventListener('input', capNhatSoKyTu);
        capNhatFormXacNhan();
    }
    const panel = document.getElementById('uc09-confirm-panel');
    if (panel && <?= empty($loi) ? 'true' : 'false' ?>) {
        requestAnimationFrame(function () { panel.scrollIntoView({behavior: 'auto', block: 'start'}); });
    }
    const formXacNhanCuoi = document.getElementById('form-xac-nhan-cuoi-uc09');
    if (formXacNhanCuoi) {
        formXacNhanCuoi.addEventListener('submit', function (event) {
            if (formXacNhanCuoi.dataset.dangXuLy === '1') { event.preventDefault(); return; }
            formXacNhanCuoi.dataset.dangXuLy = '1';
        });
    }
    window.addEventListener('pageshow', function (event) {
        if (event.persisted && formXacNhanCuoi) delete formXacNhanCuoi.dataset.dangXuLy;
    });
})();
</script>
