<?php
/** UC10 - Danh sách lô hàng chờ nhập kho. */
$danhSachLoHang = $danhSachLoHang ?? [];
$khoPhuTrach = $khoPhuTrach ?? null;
$maLoHang = $maLoHang ?? '';
$tuNgay = $tuNgay ?? '';
$denNgay = $denNgay ?? '';
$thongBao = $thongBao ?? '';
$tongSoLoHang = max(0, (int)($tongSoLoHang ?? 0));
$tongSoTrang = max(1, (int)($tongSoTrang ?? 1));
$trang = max(1, min($tongSoTrang, (int)($trang ?? 1)));
$soLuongMoiTrang = max(1, (int)($soLuongMoiTrang ?? 10));
$coQuyenLapPhieu = ($coQuyenLapPhieu ?? false) && can('nhap-kho', 'create') && $khoPhuTrach !== null;

$baseUrl = '/feed-warehouse-management/public';
$indexUrl = $baseUrl . '/nhap-kho';
$createUrl = $indexUrl . '/create';
$e = static function ($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
};
$boLoc = ['maLoHang' => $maLoHang, 'tuNgay' => $tuNgay, 'denNgay' => $denNgay];
$linkTrang = static function (int $page) use ($indexUrl, $boLoc): string {
    return $indexUrl . '?' . http_build_query(array_merge($boLoc, ['page' => $page]));
};
$offset = ($trang - 1) * $soLuongMoiTrang;
$batDau = $danhSachLoHang ? $offset + 1 : 0;
$ketThuc = $danhSachLoHang ? min($offset + count($danhSachLoHang), $tongSoLoHang) : 0;
?>

<!-- Bộ lọc đồng bộ UC07/UC09. -->
<?php if ($coQuyenLapPhieu): ?>
<div class="card filter-card">
    <div class="card-head"><strong>Thông tin tra cứu</strong></div>
    <form method="GET" action="<?= $e($indexUrl) ?>">
        <div class="filter-grid" style="align-items:end;">
            <div class="field" style="margin-bottom:0;">
                <label for="maLoHang">Mã lô hàng</label>
                <input type="text" id="maLoHang" name="maLoHang"
                    value="<?= $e($maLoHang) ?>" placeholder="Nhập mã lô hàng" maxlength="12"
                    style="height:40px; min-height:40px; box-sizing:border-box; margin-bottom:0; padding-top:0; padding-bottom:0;">
            </div>
            <div class="field" style="margin-bottom:0;">
                <label for="tuNgay">Thời gian bắt đầu</label>
                <input type="date" id="tuNgay" name="tuNgay" value="<?= $e($tuNgay) ?>"
                    style="height:40px; min-height:40px; box-sizing:border-box; margin-bottom:0; padding-top:0; padding-bottom:0;">
            </div>
            <div class="field" style="margin-bottom:0;">
                <label for="denNgay">Thời gian kết thúc</label>
                <input type="date" id="denNgay" name="denNgay" value="<?= $e($denNgay) ?>"
                    style="height:40px; min-height:40px; box-sizing:border-box; margin-bottom:0; padding-top:0; padding-bottom:0;">
            </div>
            <div class="filter-actions" style="display:flex; gap:8px; align-items:center; align-self:end; margin:0; padding:0; flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary" style="height:40px; box-sizing:border-box; padding:0 16px; margin:0; white-space:nowrap;">Áp dụng bộ lọc</button>
                <a href="<?= $e($indexUrl) ?>" class="btn" style="height:40px; box-sizing:border-box; padding:0 16px; margin:0; display:inline-flex; align-items:center; white-space:nowrap;">Đặt lại</a>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if ($thongBao !== ''): ?>
    <div class="alert alert-warning"><?= $e($thongBao) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <strong>Danh sách lô hàng chờ nhập kho</strong>
        <span class="muted">Tổng số: <?= $tongSoLoHang ?> lô hàng</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th class="w-stt">STT</th>
                    <th>Mã lô hàng</th>
                    <th>Tên lô hàng</th>
                    <th>Thời gian tiếp nhận</th>
                    <th>Trạng thái lô</th>
                    <th>Trạng thái chất lượng</th>
                    <th class="w-actions">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachLoHang)): ?>
                    <tr><td colspan="7" class="empty-row">Không có lô hàng phù hợp.</td></tr>
                <?php else: ?>
                    <?php foreach ($danhSachLoHang as $i => $lo): ?>
                        <?php
                        $timestamp = strtotime((string)($lo['thoiGianTiepNhan'] ?? ''));
                        $thoiGian = $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '—';
                        $lapPhieuUrl = $createUrl . '?' . http_build_query(['maLoHang' => $lo['maLoHang']]);
                        $duocNhap = $coQuyenLapPhieu
                            && ($lo['maKho'] ?? '') === ($khoPhuTrach['maKho'] ?? null)
                            && ($lo['trangThaiQC'] ?? '') === 'Đã đạt chuẩn'
                            && ($lo['trangThaiVongDoi'] ?? '') === 'Đã tạo';
                        ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td><?= $e($lo['maLoHang']) ?></td>
                            <td><?= $e($lo['tenLoHang']) ?></td>
                            <td><?= $e($thoiGian) ?></td>
                            <td><span class="badge badge-warn"><?= $e($lo['trangThaiLo']) ?></span></td>
                            <td><span class="badge" style="background:#dcfce7; color:#166534;"><?= $e($lo['trangThaiChatLuong']) ?></span></td>
                            <td class="w-actions">
                                <div class="cell-actions">
                                    <?php if ($duocNhap): ?>
                                        <a href="<?= $e($lapPhieuUrl) ?>" class="btn btn-primary btn-sm">Lập phiếu</a>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-head">
        <span class="muted">Hiển thị <?= $batDau ?> - <?= $ketThuc ?> / <?= $tongSoLoHang ?> lô hàng</span>
        <?php if ($tongSoTrang > 1): ?>
            <nav class="pagination" aria-label="Phân trang">
                <?php if ($trang > 1): ?>
                    <a href="<?= $e($linkTrang($trang - 1)) ?>" aria-label="Trang trước">&laquo;</a>
                <?php else: ?>
                    <button type="button" class="btn btn-sm" disabled aria-label="Trang trước">&laquo;</button>
                <?php endif; ?>
                <?php for ($p = 1; $p <= $tongSoTrang; $p++): ?>
                    <?php if ($p === $trang): ?>
                        <span class="current" aria-current="page"><?= $p ?></span>
                    <?php else: ?>
                        <a href="<?= $e($linkTrang($p)) ?>"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($trang < $tongSoTrang): ?>
                    <a href="<?= $e($linkTrang($trang + 1)) ?>" aria-label="Trang sau">&raquo;</a>
                <?php else: ?>
                    <button type="button" class="btn btn-sm" disabled aria-label="Trang sau">&raquo;</button>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</div>
