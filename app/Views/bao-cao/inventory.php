<?php
/** UC30 - Xem báo cáo tồn kho. View chỉ hiển thị dữ liệu từ Controller. */
$e = static fn($v): string => htmlspecialchars(is_scalar($v) ? (string)$v : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$num = static fn($v): string => is_numeric($v) ? rtrim(rtrim(number_format((float)$v, 6, ',', '.'), '0'), ',') : '—';
$date = static function ($v): string {
    $t = !empty($v) ? strtotime((string)$v) : false;
    return $t === false ? '—' : date('d/m/Y H:i:s', $t);
};
$url = '/feed-warehouse-management/public/bao-cao/inventory';
$danhSachKho = $danhSachKho ?? [];
$laQuanLyKho = (bool)($laQuanLyKho ?? false);
$khoPhuTrach = $khoPhuTrach ?? null;
$maKho = $maKho ?? '';
$tuNgay = $tuNgay ?? '';
$denNgay = $denNgay ?? '';
$daYeuCau = (bool)($daYeuCau ?? false);
$baoCao = $baoCao ?? null;
$danhSach = $danhSach ?? [];
$tongCong = $tongCong ?? null;
$canhBao = $canhBao ?? [];
$thongBao = $thongBao ?? '';
$loi = (bool)($loi ?? false);
$tongSoHangHoa = max(0, (int)($tongSoHangHoa ?? 0));
$tongSoTrang = max(1, (int)($tongSoTrang ?? 1));
$trang = max(1, min($tongSoTrang, (int)($trang ?? 1)));
$soLuongMoiTrang = max(1, (int)($soLuongMoiTrang ?? 10));
$offset = ($trang - 1) * $soLuongMoiTrang;
$loc = ['xemBaoCao'=>'1', 'maKho'=>$maKho, 'tuNgay'=>$tuNgay, 'denNgay'=>$denNgay];
$taoUrl = static fn(int $p): string => $url . '?' . http_build_query(array_merge($loc, ['page'=>$p]));
$cotSo = ['tonDauKy', 'nhapTrongKy', 'xuatTrongKy', 'tonCuoiKy'];
?>
<style>
.uc30-page .uc30-filter, .uc30-page .uc30-info {
    display:grid; gap:16px; align-items:start;
    grid-template-columns:repeat(4,minmax(0,1fr));
}
.uc30-page .uc30-filter { grid-template-columns:minmax(0,1.4fr) repeat(2,minmax(0,1fr)) auto; align-items:end; }
.uc30-page .field { min-width:0; margin-bottom:0; }
.uc30-page .uc30-filter input, .uc30-page .uc30-filter select, .uc30-page .uc30-filter .btn,
.uc30-page .uc30-info input { height:40px; min-height:40px; box-sizing:border-box; margin:0; }
.uc30-page .uc30-filter input, .uc30-page .uc30-filter select, .uc30-page .uc30-info input { width:100%; }
.uc30-page .uc30-filter .btn { display:inline-flex; align-items:center; padding:0 16px; white-space:nowrap; }
.uc30-page .uc30-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.uc30-page .uc30-fixed:disabled { background:#f5f7fa; color:#334155; -webkit-text-fill-color:#334155; opacity:1; pointer-events:none; cursor:default; }
.uc30-page .uc30-number { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
.uc30-page .uc30-totals td { font-weight:600; border-top:2px solid #dbe2ea; }
.uc30-page .uc30-warning { margin-bottom:16px; }
.uc30-page .uc30-warning ul { margin:8px 0 0; padding-left:22px; }
.uc30-page .uc30-info { margin-bottom:16px; }
.uc30-page .uc30-footer { margin-top:16px; flex-wrap:wrap; gap:8px; }
@media(max-width:1050px) { .uc30-page .uc30-filter,.uc30-page .uc30-info { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:640px) { .uc30-page .uc30-filter,.uc30-page .uc30-info { grid-template-columns:1fr; } }
</style>
<div class="uc30-page">
<div class="card filter-card">
    <div class="card-head"><strong>Thông tin tra cứu</strong></div>
    <form method="GET" action="<?= $e($url) ?>">
        <input type="hidden" name="xemBaoCao" value="1">
        <div class="filter-grid uc30-filter">
            <div class="field">
                <label for="uc30-maKho">Mã kho *</label>
                <?php if ($laQuanLyKho): ?>
                    <?php $maCoDinh = (string)($khoPhuTrach['maKho'] ?? ''); ?>
                    <input id="uc30-maKho" class="uc30-fixed" type="text" disabled value="<?= $e($maCoDinh !== '' ? $maCoDinh . ' - ' . ($khoPhuTrach['tenKho'] ?? '') : 'Không xác định được kho phụ trách') ?>">
                    <input type="hidden" name="maKho" value="<?= $e($maCoDinh) ?>">
                <?php else: ?>
                    <select id="uc30-maKho" name="maKho" required>
                        <option value="">Chọn kho</option>
                        <?php foreach ($danhSachKho as $k): ?>
                            <option value="<?= $e($k['maKho']) ?>" <?= $maKho === $k['maKho'] ? 'selected' : '' ?>><?= $e($k['maKho'] . ' - ' . $k['tenKho']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="field">
                <label for="uc30-tuNgay">Thời gian bắt đầu</label>
                <input type="date" id="uc30-tuNgay" name="tuNgay" value="<?= $e($tuNgay) ?>">
            </div>
            <div class="field">
                <label for="uc30-denNgay">Thời gian kết thúc</label>
                <input type="date" id="uc30-denNgay" name="denNgay" value="<?= $e($denNgay) ?>">
            </div>
            <div class="filter-actions uc30-actions">
                <button type="submit" class="btn btn-primary" <?= !$danhSachKho ? 'disabled' : '' ?>>Xem báo cáo</button>
                <a class="btn" href="<?= $e($url) ?>">Đặt lại</a>
            </div>
        </div>
    </form>
</div>
<?php if ($thongBao !== ''): ?>
    <div class="alert <?= $loi ? 'alert-danger' : 'alert-warning' ?>" role="alert"><?= $e($thongBao) ?></div>
<?php endif; ?>
<?php if ($daYeuCau && !$loi && is_array($baoCao)): ?>
    <?php if ($canhBao): ?>
        <div class="alert alert-warning uc30-warning" role="alert">
            <strong>Dữ liệu tồn kho cần đối chiếu.</strong>
            <div>Dấu — biểu thị số liệu chưa xác định được, không phải 0 kg.</div>
            <details>
                <summary>Xem chi tiết <?= count($canhBao) ?> cảnh báo</summary>
                <ul>
                    <?php foreach ($canhBao as $cb): ?>
                        <li><?= $e(($cb['maLoHang'] ?? '') . ' / ' . ($cb['maViTri'] ?? '') . ': ' . ($cb['noiDung'] ?? '')) ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        </div>
    <?php endif; ?>
    <div class="card">
        <div class="card-head"><strong>Báo cáo tồn kho</strong><span class="muted">Tổng số: <?= $tongSoHangHoa ?> hàng hóa</span></div>
        <?php $fields = [
            'Mã kho' => $baoCao['kho']['maKho'] ?? $maKho,
            'Tên kho' => $baoCao['kho']['tenKho'] ?? '—',
            'Bắt đầu kỳ báo cáo' => !empty($baoCao['thoiGianBatDau']) ? $date($baoCao['thoiGianBatDau']) : 'Toàn bộ lịch sử',
            'Kết thúc kỳ báo cáo' => $date($baoCao['thoiGianKetThuc'] ?? null),
        ]; ?>
        <div class="filter-grid uc30-info">
            <?php foreach ($fields as $label => $value): ?>
                <div class="field">
                    <label><?= $e($label) ?></label>
                    <input type="text" class="uc30-fixed" disabled aria-label="<?= $e($label) ?>" value="<?= $e($value) ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th class="w-stt">STT</th><th>Tên hàng</th><th>Đơn vị tính</th><th class="uc30-number">Tồn đầu kỳ</th><th class="uc30-number">Nhập trong kỳ</th><th class="uc30-number">Xuất trong kỳ</th><th class="uc30-number">Tồn cuối kỳ</th></tr></thead>
                <tbody>
                <?php if (!$danhSach): ?>
                    <tr><td colspan="7" class="empty-row">Không tìm thấy dữ liệu tồn kho phù hợp.</td></tr>
                <?php else: foreach ($danhSach as $i => $dong): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><?= $e($dong['tenHangHoa'] ?? '—') ?><?php if (!empty($dong['coCanhBao'])): ?> <span title="Số liệu cần đối chiếu" aria-label="Số liệu cần đối chiếu">⚠</span><?php endif; ?></td>
                        <td>kg</td>
                        <?php foreach ($cotSo as $cot): ?><td class="uc30-number"><?= $e($num($dong[$cot] ?? null)) ?></td><?php endforeach; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
                <?php if ($danhSach && is_array($tongCong)): ?>
                    <tfoot><tr class="uc30-totals"><td colspan="2">Tổng cộng (toàn bộ báo cáo)</td><td>kg</td>
                        <?php foreach ($cotSo as $cot): ?><td class="uc30-number"><?= $e($num($tongCong[$cot] ?? null)) ?></td><?php endforeach; ?>
                    </tr></tfoot>
                <?php endif; ?>
            </table>
        </div>
        <div class="card-head uc30-footer">
            <span class="muted">Hiển thị <?= $danhSach ? $offset + 1 : 0 ?> - <?= $danhSach ? $offset + count($danhSach) : 0 ?> / <?= $tongSoHangHoa ?> hàng hóa</span>
            <?php if ($tongSoTrang > 1): ?>
                <nav class="pagination" aria-label="Phân trang">
                    <?php if ($trang > 1): ?><a href="<?= $e($taoUrl($trang - 1)) ?>" aria-label="Trang trước">&laquo;</a>
                    <?php else: ?><button class="btn btn-sm" type="button" disabled aria-label="Trang trước">&laquo;</button><?php endif; ?>
                    <?php $pages = array_unique(array_merge([1], range(max(1,$trang-2), min($tongSoTrang,$trang+2)), [$tongSoTrang])); $last = 0; ?>
                    <?php foreach ($pages as $p): ?>
                        <?php if ($p > $last + 1): ?><span class="muted">…</span><?php endif; ?>
                        <?php if ($p === $trang): ?><span class="current" aria-current="page"><?= $p ?></span>
                        <?php else: ?><a href="<?= $e($taoUrl($p)) ?>"><?= $p ?></a><?php endif; $last = $p; ?>
                    <?php endforeach; ?>
                    <?php if ($trang < $tongSoTrang): ?><a href="<?= $e($taoUrl($trang + 1)) ?>" aria-label="Trang sau">&raquo;</a>
                    <?php else: ?><button class="btn btn-sm" type="button" disabled aria-label="Trang sau">&raquo;</button><?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
</div>
