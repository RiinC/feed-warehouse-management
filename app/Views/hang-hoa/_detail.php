<?php
/** Cửa sổ chi tiết hàng hóa (UC04). Biến: $modal [item], $closeUrl, $listQuery. */
use App\Models\HangHoa;

$item = $modal['item'];
$note = HangHoa::stopNote($item['moTa'] ?? '');
$stopped = $item['trangThai'] === HangHoa::TT_NGUNG;
?>
<div class="modal" id="hangHoaModal">
    <a class="modal-backdrop" href="<?= e($closeUrl) ?>" aria-label="Đóng"></a>
    <div class="modal-dialog modal-lg" role="dialog" aria-modal="true" aria-labelledby="hhTitle">
        <h3 id="hhTitle">Chi tiết hàng hóa</h3>
        <dl class="detail-grid">
            <dt>Mã hàng</dt><dd><?= e($item['maHangHoa']) ?></dd>
            <dt>Tên hàng</dt><dd><?= e($item['tenHangHoa']) ?></dd>
            <dt>Nhóm hàng</dt><dd><?= e($item['loaiHangHoa']) ?></dd>
            <dt>Đơn vị tính</dt><dd><?= e($item['donViTinhChuan']) ?></dd>
            <dt>Quy cách đóng gói</dt><dd><?= e(fmt_number($item['heSoQuyDoi'], 3)) ?> kg / <?= e($item['donViTinhChuan']) ?></dd>
            <dt>Hạn sử dụng tiêu chuẩn</dt><dd><?= $item['hanSuDungTieuChuanNgay'] === null ? '—' : e(fmt_number($item['hanSuDungTieuChuanNgay'])) . ' ngày' ?></dd>
            <dt>Điều kiện bảo quản</dt><dd><?= e($item['dieuKienBaoQuan'] ?: '—') ?></dd>
            <dt>Mô tả</dt><dd><?= e(HangHoa::stripStopNote($item['moTa'] ?? '') ?: '—') ?></dd>
            <dt>Thời gian nhập (gần nhất)</dt><dd><?= $item['lanNhapCuoi'] ? e(fmt_datetime($item['lanNhapCuoi'])) : 'Chưa phát sinh' ?></dd>
            <dt>Thời gian xuất (gần nhất)</dt><dd><?= $item['lanXuatCuoi'] ? e(fmt_datetime($item['lanXuatCuoi'])) : 'Chưa phát sinh' ?></dd>
            <dt>Số lượng tồn</dt><dd><?= e(fmt_number($item['soLuongTon'], 2)) ?> kg</dd>
            <dt>Trạng thái sử dụng</dt><dd><span class="badge <?= $stopped ? 'badge-danger' : 'badge-ok' ?>"><?= e(HangHoa::statusLabel($item['trangThai'])) ?></span></dd>
            <?php if ($stopped && $note): ?>
                <dt>Ngừng sử dụng lúc</dt><dd><?= e($note[0]) ?></dd>
                <dt>Lý do ngừng sử dụng</dt><dd><?= e($note[1]) ?></dd>
            <?php endif; ?>
        </dl>
        <div class="modal-actions">
            <?php if (can('hang-hoa', 'edit')): ?>
                <a class="btn" href="<?= e(route('hang-hoa', ['mode' => 'edit', 'id' => $item['maHangHoa']] + $listQuery)) ?>">Chỉnh sửa</a>
            <?php endif; ?>
            <?php if (can('hang-hoa', 'stop') && !$stopped): ?>
                <a class="btn btn-danger" href="<?= e(route('hang-hoa', ['mode' => 'stop', 'id' => $item['maHangHoa']] + $listQuery)) ?>">Ngừng sử dụng</a>
            <?php endif; ?>
            <a class="btn btn-primary" href="<?= e($closeUrl) ?>">Đóng</a>
        </div>
    </div>
</div>
