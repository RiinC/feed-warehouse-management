<?php
/** Tổng quan: lối tắt tới chức năng được phép. Biến: $sections (đã lọc theo quyền ở Core\Menu). CSS riêng: public/assets/css/pages/dashboard.css */
foreach ($sections as $section):
    $items = array_filter($section['items'], fn($it) => $it['route'] !== 'dashboard');
    if (!$items) {
        continue;
    }
    ?>
    <h2 class="group-title"><?= e($section['title']) ?></h2>
    <div class="shortcut-grid">
        <?php foreach ($items as $it): ?>
            <a class="shortcut-card" href="<?= e(route($it['route'])) ?>"><?= icon($it['icon'], 22) ?><span><?= e($it['label']) ?></span></a>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
