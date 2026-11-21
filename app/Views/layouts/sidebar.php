<?php
/**
 * Sidebar theo bố cục từng vai trò (config/menu-layout.php), chỉ hiện chức năng vai trò đó có quyền dùng.
 * Nhóm có mũi tên bấm để mở/đóng; nhóm chứa trang đang xem tự mở. Biến: $menuTree, $activeModule.
 */
$current = current_route();

// Mục được tô sáng: khớp đúng trang; nếu không có (trang con) thì mục đầu tiên cùng module
$leaves = [];
foreach ($menuTree as $node) {
    foreach (isset($node['items']) ? $node['items'] : [$node] as $leaf) {
        $leaves[] = $leaf;
    }
}
$activeRoute = '';
foreach ($leaves as $leaf) {
    if ($leaf['route'] === $current) {
        $activeRoute = $leaf['route'];
        break;
    }
}
if ($activeRoute === '' && $activeModule !== '') {
    foreach ($leaves as $leaf) {
        if ($leaf['module'] === $activeModule) {
            $activeRoute = $leaf['route'];
            break;
        }
    }
}
?>
<aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="<?= e(route('dashboard')) ?>">
        <img src="<?= e(asset('images/logo.png')) ?>" alt="" width="34" height="34">
        <span><?= e(config('name')) ?></span>
    </a>
    <nav class="nav">
        <?php foreach ($menuTree as $node): ?>
            <?php if (!isset($node['items'])): ?>
                <a class="nav-item<?= $node['route'] === $activeRoute ? ' active' : '' ?>" href="<?= e(route($node['route'])) ?>">
                    <?= icon($node['icon'], 18) ?>
                    <span><?= e($node['label']) ?></span>
                </a>
            <?php else:
                $open = in_array($activeRoute, array_column($node['items'], 'route'), true); ?>
                <div class="nav-group<?= $open ? ' open' : '' ?>">
                    <button type="button" class="nav-item nav-toggle<?= $open ? ' has-active' : '' ?>" aria-expanded="<?= $open ? 'true' : 'false' ?>">
                        <?= icon($node['icon'], 18) ?>
                        <span><?= e($node['label']) ?></span>
                        <span class="nav-chevron"><?= icon('chevron', 16) ?></span>
                    </button>
                    <div class="nav-sub">
                        <?php foreach ($node['items'] as $it): ?>
                            <a class="nav-subitem<?= $it['route'] === $activeRoute ? ' active' : '' ?>" href="<?= e(route($it['route'])) ?>">
                                <?= icon('chevron-right', 12) ?>
                                <span><?= e($it['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>
