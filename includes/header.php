<?php
/** Thanh trên cùng: tìm kiếm, thông báo, thông tin người dùng */
$u = current_user();
$initial = mb_strtoupper(mb_substr(trim(preg_replace('/^.*\s/u', '', $u['ho_ten'] ?? 'U')), 0, 1));
?>
<div class="main">
    <header class="topbar">
        <button class="icon-btn menu-toggle" id="menuToggle" type="button" aria-label="Mở menu"><?= icon('menu', 22) ?></button>
        <div class="search-box">
            <?= icon('search', 16) ?>
            <input type="search" placeholder="Tìm kiếm phiếu, hàng hóa..." aria-label="Tìm kiếm">
        </div>
        <div class="topbar-right">
            <button class="icon-btn bell" type="button" aria-label="Thông báo"><?= icon('bell', 20) ?><i class="dot"></i></button>
            <div class="user-menu" id="userMenu">
                <button class="user-btn" type="button" id="userBtn">
                    <span class="avatar"><?= e($initial) ?></span>
                    <span class="user-info">
                        <b><?= e($u['ho_ten'] ?? '') ?></b>
                        <small><?= e($u['role_name'] ?? '') ?></small>
                    </span>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <div class="ud-head"><b><?= e($u['ho_ten'] ?? '') ?></b><small>@<?= e($u['username'] ?? '') ?></small></div>
                    <a href="<?= e(url('logout.php')) ?>"><?= icon('logout', 16) ?> Đăng xuất</a>
                </div>
            </div>
        </div>
    </header>
    <div class="content">
