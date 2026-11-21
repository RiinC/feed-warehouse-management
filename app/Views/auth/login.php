<?php /** Form đăng nhập. Biến: $error, $username. Gửi POST tới route "login" (CSRF được Router kiểm tra). */ ?>
<p class="muted">Đăng nhập để tiếp tục</p>

<?php if ($error !== ''): ?>
    <?php render_alert('error', $error); ?>
<?php endif; ?>

<form method="post" action="<?= e(route('login')) ?>" data-validate novalidate>
    <?= csrf_field() ?>
    <div class="field">
        <label for="username">Tên đăng nhập</label>
        <input type="text" id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
    </div>
    <div class="field">
        <label for="password">Mật khẩu</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
</form>
