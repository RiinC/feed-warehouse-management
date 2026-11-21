<?php
namespace App\Controllers;

use App\Models\TaiKhoan;
use Core\Auth;
use Core\Request;

/** Đăng nhập / đăng xuất. Tài khoản ở bảng TaiKhoan (gắn với NhanVien); vai trò lấy từ NhanVien.vaiTro. */
class AuthController extends Controller
{
    /** GET login */
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $this->renderLogin('', '');
    }

    /** POST login (CSRF đã được Router kiểm tra) */
    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $username = (string)Request::post('username');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->renderLogin('Vui lòng nhập tên đăng nhập và mật khẩu.', $username);
            return;
        }
        $error = $this->attempt($username, $password);
        if ($error !== null) {
            $this->renderLogin($error, $username);
            return;
        }
        $this->redirect('dashboard');
    }

    /** POST logout */
    public function logout(): void
    {
        Auth::logout();
        $this->flash('success', 'Bạn đã đăng xuất.');
        $this->redirect('login');
    }

    /** Thử đăng nhập. Trả về null nếu thành công, ngược lại trả về thông báo lỗi. */
    private function attempt(string $username, string $password): ?string
    {
        $row = TaiKhoan::findForLogin($username);

        // Luôn chạy password_verify để thời gian phản hồi không lộ việc tài khoản có tồn tại hay không
        $hash = $row['matKhau'] ?? '$2y$10$7QA0U1Wg0tNFkB6vA7ufgOR472ycLSstDPWMVt2asBldLrqPmzb/G';
        $ok = password_verify($password, $hash);

        if (!$row || !$ok) {
            return 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
        if ($row['trangThaiTaiKhoan'] !== 'Đang hoạt động' || $row['trangThaiNhanVien'] !== 'Đang làm việc') {
            return 'Tài khoản đang bị khóa hoặc ngừng hoạt động.';
        }
        $role = ROLE_FROM_DB[$row['vaiTro']] ?? null;
        if ($role === null) {
            return 'Vai trò "' . $row['vaiTro'] . '" chưa được hỗ trợ trong hệ thống.';
        }

        TaiKhoan::touchLastLogin($row['maTaiKhoan']);
        Auth::login([
            'maTaiKhoan' => $row['maTaiKhoan'],
            'maNhanVien' => $row['maNhanVien'],
            'username'   => $row['tenDangNhap'],
            'hoTen'      => $row['hoTen'],
            'vaiTro'     => $row['vaiTro'],
            'role'       => $role,
        ]);
        return null;
    }

    private function renderLogin(string $error, string $username): void
    {
        $this->render('auth/login', ['error' => $error, 'username' => $username], ['title' => 'Đăng nhập'], 'auth');
    }
}
