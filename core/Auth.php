<?php
namespace Core;

/** Phiên đăng nhập hiện tại (lưu trong $_SESSION['user']). Việc kiểm tra mật khẩu nằm ở AuthController + Model TaiKhoan. */
final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** Khóa vai trò (admin | manager | warehouse | inventory | director), rỗng nếu chưa đăng nhập. */
    public static function role(): string
    {
        return self::user()['role'] ?? '';
    }

    /** Mã nhân viên của người đang đăng nhập (khóa ngoại dùng trong các bảng nghiệp vụ). */
    public static function staffId(): string
    {
        return (string)(self::user()['maNhanVien'] ?? '');
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }
}
