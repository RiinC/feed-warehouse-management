<?php
/** Thanh tiến trình 5 bước. Biến: $current (1..5). */
$steps = ['Chọn lô hàng', 'Chọn vị trí nguồn', 'Chọn vị trí đích', 'Nhập thông tin', 'Xác nhận'];
$current = (int)($current ?? 1);
?>
<div class="card">
    <div class="step-head">
        <h2>Quy trình điều chuyển</h2>
        <form method="post" action="<?= e(route('dieu-chuyen/create/reset')) ?>" class="inline-form">
            <?= csrf_field() ?>
            <button type="submit" class="btn-link-danger" data-confirm="Hủy quá trình lập phiếu và quay lại danh sách?">Hủy và quay lại</button>
        </form>
    </div>
    <div class="steps">
        <?php foreach ($steps as $i => $label): $n = $i + 1; ?>
            <?php if ($i > 0): ?><span class="step-line"></span><?php endif; ?>
            <div class="step <?= $n === $current ? 'active' : ($n < $current ? 'done' : '') ?>">
                <span class="num"><?= $n ?></span><span><?= e($label) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

