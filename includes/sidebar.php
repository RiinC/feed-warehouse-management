<?php
/** Menu trái. Cần: $active_menu (key của mục đang chọn) */
$active_menu = $active_menu ?? '';
?>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(url('index.php')) ?>">
        <span class="brand-logo"><?= icon('warehouse', 22) ?></span>
        <span class="brand-text"><?= e(APP_NAME) ?></span>
    </a>
    <nav class="nav">
        <?php foreach (menu_items() as $m):
            if (!empty($m['admin']) && !is_admin()) continue;
            if (!empty($m['module']) && !has_module($m['module'])) continue;
            $isActive = ($active_menu === $m['key']);
            ?>
            <a class="nav-item<?= $isActive ? ' active' : '' ?>" href="<?= e(url($m['href'])) ?>">
                <?= icon($m['icon']) ?>
                <span><?= e($m['label']) ?></span>
                <?php if (!empty($m['module'])): ?><em class="nav-tag"><?= e($m['module']) ?></em><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
