<?php /** Trang lỗi. Biến: $heading, $hint, $backRoute, $backLabel. */ ?>
<div class="card empty-state">
    <h2><?= e($heading) ?></h2>
    <p class="muted"><?= e($hint) ?></p>
    <a class="btn btn-primary" href="<?= e(route($backRoute)) ?>"><?= e($backLabel) ?></a>
</div>
