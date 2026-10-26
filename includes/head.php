<?php
/** Khai báo CSS, tiêu đề trang. Cần: $page_title (tùy chọn) */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($page_title ?? 'Trang chủ') . ' - ' . APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app">
