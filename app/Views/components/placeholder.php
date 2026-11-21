<?php
/**
 * Khung tạm cho màn hình chưa code. Biến: $uc (mã Use Case), $title, $files (các file cần viết).
 * Khi viết giao diện thật, xóa dòng gọi partial('components/placeholder', ...) trong view tương ứng.
 */
?>
<div class="card empty-state">
    <div class="empty-icon"><?= icon('tool', 40) ?></div>
    <h2><?= e($title) ?></h2>
    <p class="muted"><?= e($uc) ?> &middot; chức năng sẽ được bổ sung.</p>
    <?php foreach ($files as $label => $file): ?>
        <p class="muted small"><?= e($label) ?>: <code><?= e($file) ?></code></p>
    <?php endforeach; ?>
</div>
