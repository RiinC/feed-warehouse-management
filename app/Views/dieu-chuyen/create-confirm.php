<?php
partial('components/transfer-steps', ['current' => 5]);
$u = current_user();
?>
<div class="card">
    <h2>Xem lại phiếu điều chuyển</h2>
    <?php render_detail_table([
        'Người lập' => $u['hoTen'] ?? '',
        'Thời gian lập' => date('d/m/Y H:i'),
        'Kho' => $lot['tenKho'] ?? $draft['maKho'],
        'Lô hàng' => $draft['maLoHang'],
        'Hàng hóa' => $lot['tenHangHoa'] ?? '—',
        'Khu vực nguồn' => $src['tenKhuVuc'] ?? '—',
        'Vị trí nguồn' => $src['tenViTri'] ?? $draft['maViTriNguon'],
        'Khu vực đích' => $dst['tenKhuVuc'] ?? '—',
        'Vị trí đích' => $dst['tenViTri'] ?? $draft['maViTriDich'],
        'Số lượng' => fmt_number($draft['soLuong'], 2) . ' kg',
        'Lý do điều chuyển' => $draft['lyDoDieuChuyen'],
        'Trạng thái sau khi lập' => ['html' => dieu_chuyen_badge('Chờ duyệt')],
    ]); ?>
</div>
<form method="post" action="<?= e(route('dieu-chuyen/store')) ?>" class="card">
    <?= csrf_field() ?>
    <p class="muted small">Sau khi xác nhận, phiếu sẽ ở trạng thái Chờ duyệt và được gửi tới quản lý kho phụ trách.</p>
    <div class="page-actions"><button type="submit" class="btn btn-primary" data-confirm="Xác nhận lập phiếu điều chuyển?">Xác nhận lập phiếu</button><a class="btn" href="<?= e(route('dieu-chuyen/create/info')) ?>">Quay lại</a></div>
</form>

