<?php
/** Chi tiết vị trí. Biến: $pos,$used,$lots,$allocations,$history. */
$can = $pos['sucChuaToiDa'] - $used['daDung']; if ($can < 0) { $can = 0; }
?>
<div class="card">
    <div class="card-head">
        <h2>Vị trí <?= e($pos['maViTri']) ?> <?= vi_tri_badge($pos['trangThai']) ?></h2>
        <div class="page-actions">
            <?php if (can('vi-tri-luu-tru', 'edit')): ?><a class="btn" href="<?= e(route('vi-tri-luu-tru/edit', ['id' => $pos['maViTri']])) ?>">Sửa</a><?php endif; ?>
            <?php if (can('vi-tri-luu-tru', 'stop') && $pos['trangThai'] !== 'Đã khóa'): ?><a class="btn btn-danger" href="<?= e(route('vi-tri-luu-tru/stop-using', ['id' => $pos['maViTri']])) ?>">Ngừng sử dụng</a><?php endif; ?>
        </div>
    </div>
    <?php render_detail_table([
        'Mã vị trí' => $pos['maViTri'],
        'Tên vị trí' => $pos['tenViTri'],
        'Kho' => $pos['tenKho'],
        'Khu vực' => $pos['tenKhuVuc'],
        'Tọa độ (dãy/kệ/tầng/ô)' => $pos['day'] . '/' . $pos['ke'] . '/' . $pos['tang'] . '/' . $pos['o'],
        'Sức chứa tối đa' => fmt_number($pos['sucChuaToiDa'], 2) . ' kg',
        'Đang lưu trữ' => fmt_number($used['daDung'], 2) . ' kg',
        'Còn lại' => fmt_number($can, 2) . ' kg',
        'Trạng thái' => ['html' => vi_tri_badge($pos['trangThai'])],
    ]); ?>
    <?php if ((float)$used['daDung'] > (float)$pos['sucChuaToiDa'] + 0.000001): ?>
        <?php render_alert('warning', 'Cảnh báo dữ liệu: đang lưu trữ ' . fmt_number($used['daDung'], 2) . ' kg, vượt sức chứa tối đa ' . fmt_number($pos['sucChuaToiDa'], 2) . ' kg. Vui lòng kiểm tra lại phân bổ tồn hoặc tăng sức chứa.'); ?>
    <?php endif; ?>
</div>
<div class="card">
    <h2>Tồn thực tế tại vị trí</h2>
    <?php render_data_table(['maLoHang' => ['label' => 'Mã lô', 'class' => 'nowrap'], 'tenHangHoa' => ['label' => 'Hàng hóa', 'class' => 'wrap'], 'ngayHetHan' => ['label' => 'Hạn sử dụng', 'class' => 'nowrap'], 'soLuongTon' => ['label' => 'Tồn (kg)', 'class' => 'num nowrap'], 'soLuongDaGiu' => ['label' => 'Đang giữ (kg)', 'class' => 'num nowrap']], $lots, ['stt' => true, 'empty' => 'Vị trí chưa có tồn thực tế.']); ?>
</div>
<div class="card">
    <h2>Đã phân bổ tại vị trí</h2>
    <?php render_data_table(['maLoHang' => ['label' => 'Mã lô', 'class' => 'nowrap'], 'tenHangHoa' => ['label' => 'Hàng hóa', 'class' => 'wrap'], 'soLuongPhanBo' => ['label' => 'Số lượng (kg)', 'class' => 'num nowrap'], 'thoiDiemPhanBo' => ['label' => 'Thời điểm', 'class' => 'nowrap'], 'hoTen' => ['label' => 'Người thực hiện', 'class' => 'nowrap'], 'ghiChu' => ['label' => 'Ghi chú', 'class' => 'wrap']], $allocations, ['stt' => true, 'empty' => 'Chưa có phân bổ tại vị trí.']); ?>
</div>
<div class="card">
    <h2>Lịch sử biến động gần đây</h2>
    <?php render_data_table(['maBienDong' => ['label' => 'Mã bút toán', 'class' => 'nowrap'], 'loaiGiaoDich' => ['label' => 'Loại', 'class' => 'nowrap'], 'soLuongBienDong' => ['label' => 'Biến động (kg)', 'class' => 'num nowrap'], 'soLuongTonTruoc' => ['label' => 'Tồn trước', 'class' => 'num nowrap'], 'soLuongTonSau' => ['label' => 'Tồn sau', 'class' => 'num nowrap'], 'thoiGianPhatSinh' => ['label' => 'Thời điểm', 'class' => 'nowrap'], 'hoTen' => ['label' => 'Người thực hiện', 'class' => 'nowrap']], $history, ['stt' => true, 'empty' => 'Chưa có biến động.']); ?>
</div>

