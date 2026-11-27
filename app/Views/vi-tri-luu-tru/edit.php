<?php
if ($error !== '') { render_alert('error', $error); }
$minCap = (float)$used['daDung'] > 0 ? (float)$used['daDung'] : 0.01;
?>
<form method="post" action="<?= e(route('vi-tri-luu-tru/update')) ?>" class="card" data-validate novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($pos['maViTri']) ?>">
    <?php render_detail_table([
        'Mã vị trí' => $pos['maViTri'],
        'Kho' => $pos['tenKho'],
        'Khu vực' => $pos['tenKhuVuc'],
        'Tọa độ (dãy/kệ/tầng/ô)' => $pos['day'] . '/' . $pos['ke'] . '/' . $pos['tang'] . '/' . $pos['o'],
    ]); ?>
    <?php if ((float)$used['daDung'] > (float)$pos['sucChuaToiDa'] + 0.000001): ?>
        <?php render_alert('warning', 'Dữ liệu hiện tại đang vượt sức chứa: ' . fmt_number($used['daDung'], 2) . ' kg / ' . fmt_number($pos['sucChuaToiDa'], 2) . ' kg. Cần tăng sức chứa hoặc điều chuyển bớt hàng.'); ?>
    <?php endif; ?>
    <div class="form-stack">
        <div class="field">
            <label for="tenViTri">Tên vị trí <span class="req">*</span></label>
            <div class="input-with-action">
                <input id="tenViTri" name="tenViTri" value="<?= e($old['tenViTri'] ?? $pos['tenViTri']) ?>" maxlength="60" placeholder="VD: Kệ 01 - Tầng 01 - Ô 01" required>
                <button type="button" class="btn btn-sm" id="restoreName" data-default="<?= e($defaultName) ?>">Khôi phục tên mặc định</button>
            </div>
        </div>
        <div class="field">
            <label for="sucChuaToiDa">Sức chứa tối đa (kg) <span class="req">*</span></label>
            <input id="sucChuaToiDa" name="sucChuaToiDa" type="text" inputmode="numeric" data-qty="positive" data-min="<?= e((int)ceil($minCap)) ?>" value="<?= e((int)round((float)($old['sucChuaToiDa'] ?? $pos['sucChuaToiDa']))) ?>" required>
        </div>
    </div>
    <p class="muted small">Đang lưu trữ: <?= e(fmt_number($used['daDung'], 2)) ?> kg (tồn thực tế <?= e(fmt_number($used['ton'], 2)) ?> kg, đã phân bổ <?= e(fmt_number($used['phanBo'], 2)) ?> kg). Không được giảm sức chứa thấp hơn mức này. Mã, kho, khu vực và tọa độ không thay đổi.</p>
    <div class="page-actions">
        <button type="submit" class="btn btn-primary" data-confirm="Lưu thay đổi vị trí?">Lưu thay đổi</button>
        <a class="btn" href="<?= e(route('vi-tri-luu-tru')) ?>">Hủy</a>
    </div>
</form>
<script>
    (function () { var b = document.getElementById('restoreName'), i = document.getElementById('tenViTri'); if (b && i) { b.addEventListener('click', function () { i.value = b.getAttribute('data-default') || ''; i.focus(); }); } })();
</script>

