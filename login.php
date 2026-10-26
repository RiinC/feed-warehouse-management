<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect('index.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
    } else {
        $user = db_one(
            "SELECT t.maTaiKhoan, t.tenDangNhap, t.matKhau, n.maNV, n.hoTen, n.vaiTro, n.quyenHan
             FROM TaiKhoan t JOIN NhanVien n ON n.maNV = t.maNhanVien
             WHERE t.tenDangNhap = ? AND t.trangThai = 'Đang hoạt động'
               AND n.trangThai = 'Đang hoạt động'",
            's',
            [$username],
            DB_DUNG_CHUNG
        );
        $storedPassword = (string)($user['matKhau'] ?? '');
        $isLegacyMd5 = preg_match('/^[a-f0-9]{32}$/i', $storedPassword) === 1;
        $passwordMatches = $user && (
            ($isLegacyMd5 && hash_equals(strtolower($storedPassword), md5($password))) ||
            (!$isLegacyMd5 && password_verify($password, $storedPassword))
        );

        if ($passwordMatches) {
            $roleMap = [
                'Quản trị viên' => 'admin',
                'Quản lý kho' => 'quan_ly_kho',
                'Nhân viên kho' => 'nhan_vien_kho',
            ];
            $role = $roleMap[$user['vaiTro']] ?? '';
            $mods = array_filter(array_map('trim', explode(',', (string)$user['quyenHan'])));
            if ($role === 'admin') {
                $mods = ['UC06', 'UC10', 'UC17', 'UC23', 'UC24'];
            }
            if ($role !== '') {
                if ($isLegacyMd5) {
                    db_exec(
                        'UPDATE TaiKhoan SET matKhau = ? WHERE maTaiKhoan = ?',
                        'ss',
                        [password_hash($password, PASSWORD_DEFAULT), $user['maTaiKhoan']],
                        DB_DUNG_CHUNG
                    );
                }
                db_exec(
                    'UPDATE TaiKhoan SET lanDangNhapCuoi = NOW() WHERE maTaiKhoan = ?',
                    's',
                    [$user['maTaiKhoan']],
                    DB_DUNG_CHUNG
                );
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => $user['maNV'],
                    'username' => $user['tenDangNhap'],
                    'ho_ten' => $user['hoTen'],
                    'role' => $role,
                    'role_name' => $user['vaiTro'],
                    'modules' => $mods,
                ];
                redirect('index.php');
            }
        }
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/login.css')) ?>">
</head>

<body>
    <main class="login-wrap">
        <section class="login-hero">
            <div class="hero-logo"><?= icon('warehouse', 34) ?></div>
            <h1><?= e(APP_NAME) ?></h1>
            <p>Quản lý tiếp nhận, nhập kho, điều chuyển, xuất kho và kiểm kê thức ăn chăn nuôi trên một nền tảng thống nhất.</p>
            <ul>
                <li>Theo dõi phiếu tiếp nhận theo thời gian thực</li>
                <li>Phân quyền theo vai trò: Quản trị viên, Quản lý kho, Nhân viên kho</li>
                <li>Số liệu hàng hóa minh bạch, dễ đối chiếu</li>
            </ul>
        </section>
        <section class="login-panel">
            <form class="login-card" method="post" autocomplete="on">
                <h2>Đăng nhập</h2>
                <p class="muted">Vui lòng đăng nhập để tiếp tục</p>
                <?php if ($error): ?><div class="login-error" role="alert"><?= e($error) ?></div><?php endif; ?>
                <?= csrf_field() ?>
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" value="<?= e($username) ?>" placeholder="Nhập tên đăng nhập" autofocus required>
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                <button type="submit">Đăng nhập</button>
                <div class="demo-accounts">
                    <b>Tài khoản mẫu</b>
                    <span>admin / admin123</span>
                    <span>quanlykho / quanlykho123</span>
                    <span>nhanvienkho / nhanvienkho123</span>
                </div>
            </form>
        </section>
    </main>
</body>

</html>