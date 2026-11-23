<?php
/** UC14 - Xác nhận ngừng sử dụng. Biến: $pos,$used,$reason. */
?>
<div class="card">
    <h2>Vị trí <?= e($pos['maViTri']) ?></h2>
    <?php render_detail_table([
        'Mã vị trí' => $pos['maViTri'],
        'Tên vị trí' => $pos['tenViTri'],
        'Kho' => $pos['tenKho'],
        'Khu vực' => $pos['tenKhuVuc'],
        'Đang lưu trữ' => fmt_number($used['daDung'], 2) . ' kg',
        'Tồn thực tế' => fmt_number($used['ton'], 2) . ' kg',
        'Đã phân bổ' => fmt_number($used['phanBo'], 2) . ' kg',
        'Trạng thái' => ['html' => vi_tri_badge($pos['trangThai'])],
    ]); ?>
    <?php if ($reason !== null): ?>
        <?php render_alert('error', $reason); ?>
        <p class="muted">Không thể ngừng sử dụng vị trí này. Vui lòng xử lý hết hàng và các giao dịch liên quan trước.</p>
        <a class="btn" href="<?= e(route('vi-tri-luu-tru')) ?>">Quay lại danh sách</a>
    <?php else: ?>
        <p>Vị trí sẽ được chuyển sang trạng thái <b>Đã khóa</b> (xóa mềm), không xóa khỏi hệ thống.</p>
        <form method="post" action="<?= e(route('vi-tri-luu-tru/stop')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($pos['maViTri']) ?>">
            <button type="submit" class="btn btn-danger" data-confirm="Xác nhận ngừng sử dụng vị trí <?= e($pos['maViTri']) ?>?">Ngừng sử dụng</button>
            <a class="btn" href="<?= e(route('vi-tri-luu-tru')) ?>">Hủy</a>
        </form>
    <?php endif; ?>
</div>

