<?php
$u = current_user();
$isOwner = (string)($u['maNhanVien'] ?? '') === (string)$p['maNhanVienYeuCau'];
$canApprove = can('dieu-chuyen', 'approve') && $p['trangThai'] === 'Chờ duyệt' && !$isOwner;
$canExecute = can('dieu-chuyen', 'execute') && in_array($p['trangThai'], ['Đã duyệt', 'Chờ điều chuyển', 'Đang thực hiện'], true) && (float)$remaining > 0.000001;
$canCancel = can('dieu-chuyen', 'create') && $p['trangThai'] === 'Chờ duyệt' && $isOwner;
$actual = $p['soLuongThucChuyen'] !== null ? (float)$p['soLuongThucChuyen'] : 0;
?>
<?php
$dcAbnormalBtn = '';
if (can('hang-bat-thuong', 'record') && (float)$avail > 0) {
    $dcAbnormalBtn = '<a class="btn btn-sm" href="' . e(route('hang-bat-thuong/create', [
        'maKho'    => $p['maKhoNguon'],
        'maKhuVuc' => $p['maKhuVucNguon'],
        'maViTri'  => $p['maViTriNguon'],
        'maLoHang' => $p['maLoHang'],
    ])) . '" title="Ghi nhận hàng rách vỡ / hư hỏng phát hiện khi điều chuyển">' . icon('alert-triangle', 14) . ' Báo bất thường nguồn</a>';
}

render_kv_pair_cards(
    [
        'title'       => 'Lô hàng & Lộ trình điều chuyển',
        'headerRight' => $dcAbnormalBtn,
        'rows'        => [
            'Mã lô'             => (string)$p['maLoHang'],
            'Hàng hóa'          => (string)$p['tenHangHoa'],
            'Nguồn xuất'        => implode(' · ', array_filter([$p['tenKhoNguon'] ?? '', $p['tenKhuVucNguon'] ?? '', $p['tenViTriNguon'] ?? ''])),
            'Đích nhận'         => implode(' · ', array_filter([$p['tenKhoDich'] ?? '', $p['tenKhuVucDich'] ?? '', $p['tenViTriDich'] ?? ''])),
            'Số lượng dự kiến'  => fmt_number($p['soLuongDuKien'], 2) . ' kg',
            'Đã chuyển / Còn lại' => fmt_number($actual, 2) . ' kg / ' . fmt_number($remaining, 2) . ' kg',
            'Lý do điều chuyển' => (string)($p['lyDoDieuChuyen'] ?? '—'),
        ],
    ],
    [
        'title'       => 'Thông tin phiếu ' . $p['maPhieuDieuChuyen'],
        'headerRight' => dieu_chuyen_badge($p['trangThai']),
        'rows'        => [
            'Mã phiếu'           => (string)$p['maPhieuDieuChuyen'],
            'Người lập'          => (string)($p['nguoiLap'] ?? '—'),
            'Thời gian lập'      => fmt_datetime($p['ngayYeuCau']) ?: '—',
            'Người duyệt'        => (string)($p['nguoiDuyet'] ?? '—'),
            'Thời gian duyệt'    => fmt_datetime($p['ngayDuyet']) ?: '—',
            'Người thực hiện'    => (string)($p['nguoiThucHien'] ?? '—'),
            'Thời gian hoàn tất' => fmt_datetime($p['ngayHoanTat']) ?: '—',
        ],
    ]
);
?>
<?php if (!empty($p['lyDoTuChoi']) || in_array($p['trangThai'], ['Đã duyệt', 'Chờ điều chuyển', 'Đang thực hiện'], true)): ?>
<div class="card" style="padding: 12px 16px; margin-bottom: 14px;">
    <?php if (!empty($p['lyDoTuChoi'])): ?><?php render_alert('error', 'Lý do từ chối: ' . $p['lyDoTuChoi']); ?><?php endif; ?>
    <?php if (in_array($p['trangThai'], ['Đã duyệt', 'Chờ điều chuyển', 'Đang thực hiện'], true)): ?>
        <p class="muted small" style="margin: 0;">Đã chuyển <?= e(fmt_number($actual, 2)) ?> / <?= e(fmt_number($p['soLuongDuKien'], 2)) ?> kg. Tồn khả dụng tại nguồn: <?= e(fmt_number($avail, 2)) ?> kg. Sức chứa còn lại tại đích: <?= e(fmt_number($destCon, 2)) ?> kg.</p>
        <?php if ($avail + 0.000001 < $remaining || $destCon + 0.000001 < $remaining): ?><?php render_alert('warning', 'Tồn nguồn hoặc sức chứa đích hiện không đủ cho phần còn lại. Hệ thống sẽ kiểm tra lại khi thực hiện.'); ?><?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($canApprove): ?>
<div class="card">
    <h2>Xử lý phiếu</h2>
    <div class="page-actions">
        <form method="post" action="<?= e(route('dieu-chuyen/approve/save')) ?>" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($p['maPhieuDieuChuyen']) ?>"><input type="hidden" name="action" value="approve">
            <button type="submit" class="btn btn-primary" data-confirm="Phê duyệt phiếu <?= e($p['maPhieuDieuChuyen']) ?>?">Phê duyệt</button>
        </form>
        <button type="button" class="btn btn-danger" data-open-reject>Từ chối</button>
    </div>
</div>
<div class="modal" id="rejectModal" hidden>
    <div class="modal-backdrop" data-close-reject></div>
    <div class="modal-dialog" role="dialog" aria-modal="true">
        <h3>Từ chối phiếu</h3>
        <form method="post" action="<?= e(route('dieu-chuyen/approve/save')) ?>">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($p['maPhieuDieuChuyen']) ?>"><input type="hidden" name="action" value="reject">
            <div class="field"><label for="lyDoTuChoi">Lý do từ chối <span class="req">*</span></label><textarea id="lyDoTuChoi" name="lyDoTuChoi" maxlength="255" required></textarea></div>
            <div class="modal-actions"><button type="button" class="btn btn-ghost" data-close-reject>Hủy</button><button type="submit" class="btn btn-danger">Xác nhận từ chối</button></div>
        </form>
    </div>
</div>
<?php endif; ?>


<?php if ($canExecute): ?>
<div class="card">
    <h2>Thực hiện điều chuyển</h2>
    <p class="muted small">Còn lại <?= e(fmt_number($remaining, 2)) ?> kg. Có thể chuyển một phần; phiếu chỉ chuyển sang Đã hoàn tất khi đã chuyển đủ số lượng dự kiến.</p>
    <form method="post" action="<?= e(route('dieu-chuyen/execute')) ?>">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($p['maPhieuDieuChuyen']) ?>">
        <div class="field"><label for="soLuongThucChuyen">Số lượng thực chuyển (kg) <span class="req">*</span></label><input id="soLuongThucChuyen" name="soLuongThucChuyen" type="text" inputmode="numeric" data-qty="positive" data-min="1" data-max="<?= e((int)floor($remaining)) ?>" value="<?= e((int)floor($remaining)) ?>" required></div>
        <button type="submit" class="btn btn-primary" data-confirm="Xác nhận thực hiện điều chuyển?">Thực hiện</button>
    </form>
</div>
<?php endif; ?>

<?php if ($canCancel): ?>
<div class="card"><form method="post" action="<?= e(route('dieu-chuyen/cancel')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($p['maPhieuDieuChuyen']) ?>"><button type="submit" class="btn btn-danger" data-confirm="Hủy phiếu <?= e($p['maPhieuDieuChuyen']) ?>?">Hủy phiếu</button></form></div>
<?php endif; ?>

<div class="card"><h2>Lịch sử di chuyển</h2><?php render_data_table(['maBienDong' => ['label' => 'Mã bút toán', 'class' => 'nowrap'], 'loaiGiaoDich' => ['label' => 'Loại', 'class' => 'nowrap'], 'maViTri' => ['label' => 'Vị trí', 'class' => 'nowrap'], 'soLuongBienDong' => ['label' => 'Biến động (kg)', 'class' => 'num nowrap'], 'soLuongTonTruoc' => ['label' => 'Tồn trước', 'class' => 'num nowrap'], 'soLuongTonSau' => ['label' => 'Tồn sau', 'class' => 'num nowrap'], 'thoiGianPhatSinh' => ['label' => 'Thời điểm', 'class' => 'nowrap'], 'hoTen' => ['label' => 'Người thực hiện', 'class' => 'nowrap']], $history, ['stt' => true, 'empty' => 'Chưa có bút toán di chuyển.']); ?></div>

