<?php
/**
 * Layout chính (có sidebar + thanh trên cùng). Biến: $page, $content (HTML của view),
 * $menuTree, $unread, $recentNotifications (do Controller cấp qua sharedData()).
 */
$user = current_user();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($page['title'] !== '' ? $page['title'] . ' | ' : '') . config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/reset.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/variables.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
    <?php if (!empty($page['css'])): ?>
        <link rel="stylesheet" href="<?= e(asset('css/pages/' . $page['css'] . '.css')) ?>">
    <?php endif; ?>
</head>
<body class="app-body">
<div class="app-shell" id="appShell">
    <?php partial('layouts/sidebar', ['menuTree' => $menuTree, 'activeModule' => $page['module']]); ?>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <div class="main">
        <header class="topbar">
            <button type="button" class="icon-btn" id="sidebarToggle" aria-label="Mở/đóng menu"><?= icon('menu', 20) ?></button>
            <div class="topbar-title"><?= e(config('name')) ?> &middot; <?= e(config('tagline')) ?></div>
            <div class="topbar-user">
                <?php partial('layouts/notification-bell', ['unread' => $unread, 'items' => $recentNotifications]); ?>
                <img class="avatar" src="<?= e(asset('images/avatar-default.png')) ?>" alt="">
                <div class="topbar-user-info">
                    <b><?= e($user['hoTen'] ?? '') ?></b>
                    <small><?= e(ROLE_LABELS[$user['role'] ?? ''] ?? '') ?></small>
                </div>
                <form method="post" action="<?= e(route('logout')) ?>" class="logout-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="icon-btn" title="Đăng xuất" aria-label="Đăng xuất"><?= icon('logout', 20) ?></button>
                </form>
            </div>
        </header>
        <main class="content">
            <?php
            render_breadcrumb($page['breadcrumb']);
            render_page_title($page['title'], $page['subtitle'], $page['actions']);
            render_flash();
            echo $content;
            ?>
        </main>
        <footer class="footer">&copy; <?= date('Y') ?> <?= e(config('name')) ?></footer>
    </div>
</div>
<?php render_confirm_modal(); ?>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<script src="<?= e(asset('js/sidebar.js')) ?>"></script>
<script src="<?= e(asset('js/modal.js')) ?>"></script>
<script src="<?= e(asset('js/notifications.js')) ?>"></script>
<script src="<?= e(asset('js/validation.js')) ?>"></script>
</body>
</html>
