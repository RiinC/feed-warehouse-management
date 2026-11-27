<?php
namespace App\Models;

use Core\Model;

/**
 * Bảng TaiKhoan (maTaiKhoan, tenDangNhap, matKhau, trangThai, ngayTao, lanDangNhapCuoi, maNhanVien)
 * kết hợp NhanVien (maNhanVien, hoTen, vaiTro, trangThai, maKho).
 */
class TaiKhoan extends Model
{
    /** Tài khoản + thông tin nhân viên theo tên đăng nhập; null nếu không có. */
    public static function findForLogin(string $username): ?array
    {
        return static::one(
            'SELECT t.maTaiKhoan, t.tenDangNhap, t.matKhau, t.trangThai AS trangThaiTaiKhoan,
                    n.maNhanVien, n.hoTen, n.vaiTro, n.trangThai AS trangThaiNhanVien, n.maKho
             FROM TaiKhoan t
             JOIN NhanVien n ON n.maNhanVien = t.maNhanVien
             WHERE t.tenDangNhap = ?',
            [$username]
        );
    }

    public static function touchLastLogin(string $maTaiKhoan): void
    {
        static::exec('UPDATE TaiKhoan SET lanDangNhapCuoi = NOW() WHERE maTaiKhoan = ?', [$maTaiKhoan]);
    }
}
