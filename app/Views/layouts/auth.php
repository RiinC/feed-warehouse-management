<?php
/** Layout không có sidebar (đăng nhập, lỗi khi chưa đăng nhập). Biến: $page, $content. */
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
</head>
<body class="login-body">
<main class="login-card">
    <img class="login-logo" src="<?= e(asset('images/logo.png')) ?>" alt="Logo">
    <h1><?= e(config('name')) ?></h1>
    <p class="login-tagline"><?= e(config('tagline')) ?></p>
    <?php render_flash(); ?>
    <?= $content ?>
</main>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<script src="<?= e(asset('js/validation.js')) ?>"></script>
</body>
</html>
