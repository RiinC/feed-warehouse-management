<?php
/**
 * Tiêu đề trang + breadcrumb + nút thao tác.
 * Cần: $page_title, $breadcrumb (mảng [nhãn, link|null]), $toolbar_actions (HTML, tùy chọn)
 */
$flash = pull_flash();
?>
<div class="toolbar">
    <div class="toolbar-left">
        <h1><?= e($page_title ?? '') ?></h1>
        <?php if (!empty($breadcrumb)): ?>
            <nav class="breadcrumb" aria-label="breadcrumb">
                <?php foreach ($breadcrumb as $i => $b): ?>
                    <?php if ($i > 0): ?><span class="sep">›</span><?php endif; ?>
                    <?php if (!empty($b[1])): ?><a href="<?= e(url($b[1])) ?>"><?= e($b[0]) ?></a>
                    <?php else: ?><span class="current"><?= e($b[0]) ?></span><?php endif; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
    <div class="toolbar-actions"><?= $toolbar_actions ?? '' ?></div>
</div>
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['msg']) ?></div>
<?php endif; ?>
