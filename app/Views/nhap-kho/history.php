<?php
/** UC11 - Tra cứu lịch sử nhập kho. Chỉ đọc dữ liệu từ Controller. */
$e = static fn($v): string => htmlspecialchars(is_scalar($v) ? (string)$v : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$num = static fn($v): string => is_numeric($v) ? rtrim(rtrim(number_format((float)$v, 6, ',', '.'), '0'), ',') : '—';
$date = static function ($v, string $format = 'd/m/Y'): string {
    $t = !empty($v) ? strtotime((string)$v) : false;
    return $t !== false ? date($format, $t) : '—';
};
$danhSachPhieuNhapKho = $danhSachPhieuNhapKho ?? [];
$chiTiet = $chiTiet ?? null;
$maPhieuNhapKho = $maPhieuNhapKho ?? '';
$tuNgay = $tuNgay ?? '';
$denNgay = $denNgay ?? '';
$thongBao = $thongBao ?? '';
$tongSoPhieu = max(0, (int)($tongSoPhieu ?? 0));
$tongSoTrang = max(1, (int)($tongSoTrang ?? 1));
$trang = max(1, min($tongSoTrang, (int)($trang ?? 1)));
$soLuongMoiTrang = max(1, (int)($soLuongMoiTrang ?? 10));
$url = '/feed-warehouse-management/public/nhap-kho/history';
$loc = ['maPhieuNhapKho'=>$maPhieuNhapKho, 'tuNgay'=>$tuNgay, 'denNgay'=>$denNgay, 'page'=>$trang];
$taoUrl = static function (array $them = []) use ($url, $loc): string {
    $params = array_filter(array_merge($loc, $them), static fn($v) => $v !== '' && $v !== null);
    return $params ? $url . '?' . http_build_query($params) : $url;
};
$offset = ($trang - 1) * $soLuongMoiTrang;
$batDau = $danhSachPhieuNhapKho ? $offset + 1 : 0;
$ketThuc = $danhSachPhieuNhapKho ? $offset + count($danhSachPhieuNhapKho) : 0;
?>
<style>
.uc11-page .uc11-filter, .uc11-page .uc11-detail {
    display:grid; gap:16px; align-items:start;
    grid-template-columns:repeat(4,minmax(0,1fr));
}
.uc11-page .uc11-filter { grid-template-columns:repeat(3,minmax(0,1fr)) auto; align-items:end; }
.uc11-page .uc11-filter .field, .uc11-page .uc11-detail .field { margin-bottom:0; min-width:0; }
.uc11-page .uc11-filter input, .uc11-page .uc11-filter .btn { height:40px; min-height:40px; box-sizing:border-box; margin:0; }
.uc11-page .uc11-filter input { width:100%; padding-top:0; padding-bottom:0; }
.uc11-page .uc11-filter .btn { display:inline-flex; align-items:center; padding:0 16px; white-space:nowrap; }
.uc11-page .uc11-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.uc11-page .uc11-fixed:disabled { background:#f5f7fa; color:#334155; -webkit-text-fill-color:#334155; opacity:1; pointer-events:none; cursor:default; width:100%; box-sizing:border-box; }
.uc11-page input.uc11-fixed { height:42px; min-height:42px; }
.uc11-page textarea.uc11-fixed { resize:none; }
.uc11-page .uc11-eye { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; padding:0; }
.uc11-page .uc11-total { display:flex; justify-content:flex-end; gap:16px; margin-top:16px; flex-wrap:wrap; }
@media(max-width:1000px) { .uc11-page .uc11-filter,.uc11-page .uc11-detail { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:640px) { .uc11-page .uc11-filter,.uc11-page .uc11-detail { grid-template-columns:1fr; } }
</style>
<div class="uc11-page">
<?php if ($thongBao !== ''): ?>
    <div class="alert alert-warning" role="alert"><?= $e($thongBao) ?></div>
<?php endif; ?>
<?php if ($chiTiet === null): ?>
<div class="card filter-card">
    <div class="card-head"><strong>Thông tin tra cứu</strong></div>
    <form method="GET" action="<?= $e($url) ?>">
        <div class="filter-grid uc11-filter">
            <div class="field">
                <label for="uc11-maPhieu">Mã phiếu nhập kho</label>
                <input type="text" id="uc11-maPhieu" name="maPhieuNhapKho" maxlength="13" value="<?= $e($maPhieuNhapKho) ?>" placeholder="Nhập mã phiếu nhập kho">
            </div>
            <div class="field">
                <label for="uc11-tuNgay">Thời gian bắt đầu</label>
                <input type="date" id="uc11-tuNgay" name="tuNgay" value="<?= $e($tuNgay) ?>">
            </div>
            <div class="field">
                <label for="uc11-denNgay">Thời gian kết thúc</label>
                <input type="date" id="uc11-denNgay" name="denNgay" value="<?= $e($denNgay) ?>">
            </div>
            <div class="filter-actions uc11-actions">
                <button type="submit" class="btn btn-primary">Áp dụng bộ lọc</button>
                <a class="btn" href="<?= $e($url) ?>">Đặt lại</a>
            </div>
        </div>
    </form>
</div>
<div class="card">
    <div class="card-head">
        <strong>Danh sách phiếu nhập kho</strong>
        <span class="muted">Tổng số: <?= $tongSoPhieu ?> phiếu</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th class="w-stt">STT</th><th>Mã phiếu nhập kho</th><th>Nhân viên nhập</th><th>Ngày lập phiếu</th><th class="w-actions">Thao tác</th></tr></thead>
            <tbody>
            <?php if (!$danhSachPhieuNhapKho): ?>
                <tr><td colspan="5" class="empty-row">Không có phiếu nhập kho phù hợp.</td></tr>
            <?php else: foreach ($danhSachPhieuNhapKho as $i => $phieu): ?>
                <tr>
                    <td><?= $offset + $i + 1 ?></td>
                    <td><?= $e($phieu['maPhieuNhapKho'] ?? '') ?></td>
                    <td><?= $e(trim((string)($phieu['tenNhanVienLap'] ?? '')) ?: '—') ?></td>
                    <td><?= $e($date($phieu['ngayLapPhieu'] ?? $phieu['ngayNhapKho'] ?? null)) ?></td>
                    <td class="w-actions"><div class="cell-actions">
                        <a class="btn btn-sm uc11-eye" href="<?= $e($taoUrl(['maChiTiet'=>$phieu['maPhieuNhapKho']])) ?>" title="Xem chi tiết phiếu" aria-label="<?= $e('Xem chi tiết phiếu ' . $phieu['maPhieuNhapKho']) ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                    </div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-head">
        <span class="muted">Hiển thị <?= $batDau ?> - <?= $ketThuc ?> / <?= $tongSoPhieu ?> phiếu nhập kho</span>
        <?php if ($tongSoTrang > 1): ?>
        <nav class="pagination" aria-label="Phân trang">
            <?php if ($trang > 1): ?><a href="<?= $e($taoUrl(['page'=>$trang-1])) ?>" aria-label="Trang trước">&laquo;</a>
            <?php else: ?><button class="btn btn-sm" type="button" disabled aria-label="Trang trước">&laquo;</button><?php endif; ?>
            <?php $pages = array_unique(array_merge([1], range(max(1,$trang-2), min($tongSoTrang,$trang+2)), [$tongSoTrang])); $last = 0; ?>
            <?php foreach ($pages as $p): ?>
                <?php if ($p > $last + 1): ?><span class="muted">…</span><?php endif; ?>
                <?php if ($p === $trang): ?><span class="current" aria-current="page"><?= $p ?></span>
                <?php else: ?><a href="<?= $e($taoUrl(['page'=>$p])) ?>"><?= $p ?></a><?php endif; $last = $p; ?>
            <?php endforeach; ?>
            <?php if ($trang < $tongSoTrang): ?><a href="<?= $e($taoUrl(['page'=>$trang+1])) ?>" aria-label="Trang sau">&raquo;</a>
            <?php else: ?><button class="btn btn-sm" type="button" disabled aria-label="Trang sau">&raquo;</button><?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</div>
<?php else:
    $dongHang = $chiTiet['chiTiet'] ?? [];
    $fields = [
        'Mã phiếu nhập kho'=>$chiTiet['maPhieuNhapKho'] ?? '—',
        'Kho nhập'=>trim((string)($chiTiet['tenKho'] ?? '')) ?: '—',
        'Nhân viên lập phiếu'=>trim((string)($chiTiet['tenNhanVienLap'] ?? '')) ?: '—',
        'Số lượng thực nhập'=>$num($chiTiet['soLuongThucNhap'] ?? null),
        'Đơn vị tính'=>$chiTiet['donViTinh'] ?? '—',
        'Ngày lập phiếu'=>$date($chiTiet['ngayLapPhieu'] ?? $chiTiet['ngayNhapKho'] ?? null),
        'Thời gian lập'=>$chiTiet['thoiGianLap'] ?? $date($chiTiet['ngayNhapKho'] ?? null, 'H:i:s'),
        'Trạng thái'=>$chiTiet['trangThai'] ?? '—',
    ];
?>
<div class="card">
    <div class="card-head">
        <strong>Thông tin phiếu nhập kho</strong>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th style="width:35%;">Thông tin</th><th>Giá trị</th></tr></thead>
            <tbody>
            <?php foreach ($fields as $label => $value): ?>
                <tr><td><strong><?= $e($label) ?></strong></td><td><?= $e($value) ?></td></tr>
            <?php endforeach; ?>
                <tr><td><strong>Ghi chú</strong></td><td style="white-space:pre-wrap; overflow-wrap:anywhere;"><?= $e(trim((string)($chiTiet['ghiChu'] ?? '')) !== '' ? $chiTiet['ghiChu'] : '—') ?></td></tr>
            </tbody>
        </table>
    </div>
</div>
<div class="card">
    <div class="card-head"><strong>Chi tiết hàng hóa nhập kho</strong><span class="muted">Tổng số: <?= count($dongHang) ?> dòng</span></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th class="w-stt">STT</th><th>Mã lô hàng</th><th>Tên hàng hóa</th><th>Vị trí lưu trữ</th><th>Số lượng thực nhập</th><th>Đơn vị tính</th><th>Khối lượng chuẩn (kg)</th></tr></thead>
            <tbody>
            <?php if (!$dongHang): ?><tr><td colspan="7" class="empty-row">Phiếu không có dữ liệu chi tiết.</td></tr>
            <?php else: foreach ($dongHang as $i => $dong): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= $e($dong['maLoHang'] ?? '—') ?></td>
                    <td><?= $e($dong['tenHangHoa'] ?? '—') ?></td>
                    <td><?= $e(trim((string)($dong['tenViTri'] ?? '')) ?: '—') ?></td>
                    <td><?= $e($num($dong['soLuongNhap'] ?? null)) ?></td>
                    <td><?= $e($dong['donViTinh'] ?? '—') ?></td>
                    <td><?= $e($num($dong['soLuongChuanKg'] ?? null)) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="uc11-total"><strong>Tổng khối lượng nhập:</strong><strong><?= $e($num($chiTiet['tongSoLuongChuanKg'] ?? null)) ?><?= isset($chiTiet['tongSoLuongChuanKg']) ? ' kg' : '' ?></strong></div>
    <div style="display:flex; justify-content:flex-end; margin-top:20px;">
        <a class="btn btn-sm" href="<?= $e($taoUrl()) ?>">Quay lại danh sách</a>
    </div>
</div>
<?php endif; ?>
</div>
