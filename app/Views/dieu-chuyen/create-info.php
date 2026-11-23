<?php
partial('components/transfer-steps', ['current' => 4]);
?>
<div class="card">
    <h2>Thông tin lô hàng</h2>
    <?php render_detail_table([
        'Mã lô' => $draft['maLoHang'],
        'Hàng hóa' => $lot['tenHangHoa'] ?? '—',
        'Kho' => $lot['tenKho'] ?? $draft['maKho'],
        'Hạn sử dụng' => $lot['ngayHetHan'] ?? '—',
        'Khu vực nguồn' => $src['tenKhuVuc'] ?? '—',
        'Vị trí nguồn' => $src['tenViTri'] ?? $draft['maViTriNguon'],
        'Khu vực đích' => $dst['tenKhuVuc'] ?? '—',
        'Vị trí đích' => $dst['tenViTri'] ?? $draft['maViTriDich'],
        'Khả dụng tại nguồn' => fmt_number($avail, 2) . ' kg',
        'Sức chứa còn lại tại đích' => fmt_number($destCon, 2) . ' kg',
    ]); ?>
</div>
<form method="post" action="<?= e(route('dieu-chuyen/create/info')) ?>" class="card" data-validate novalidate>
    <?= csrf_field() ?>
    <h2>Nhập thông tin điều chuyển</h2>
    <div class="filter-grid">
        <div class="field"><label for="soLuong">Số lượng cần điều chuyển (kg) <span class="req">*</span></label><input id="soLuong" name="soLuong" type="number" min="0.01" step="0.01" max="<?= e(min($avail, $destCon)) ?>" value="<?= e($defaultQty) ?>" required></div>
        <div class="field"><label for="lyDoDieuChuyen">Lý do điều chuyển <span class="req">*</span></label><textarea id="lyDoDieuChuyen" name="lyDoDieuChuyen" maxlength="200" placeholder="VD: Chuyển sang khu vực gần cửa xuất" required><?= e($draft['lyDoDieuChuyen'] ?? '') ?></textarea></div>
    </div>
    <p class="muted small">Số lượng mặc định là lượng hàng đang có tại vị trí nguồn; có thể giảm nếu chỉ điều chuyển một phần.</p>
    <div class="page-actions"><button type="submit" class="btn btn-primary">Tiếp tục</button><a class="btn" href="<?= e(route('dieu-chuyen/create/destination')) ?>">Quay lại</a></div>
</form>

