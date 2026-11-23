<?php
namespace App\Models;

use Core\Model;

/** Bảng NhanVien (maNhanVien, hoTen, soDienThoai, email, chucVu, vaiTro, trangThai, maKho). */
class NhanVien extends Model
{
    public static function find(string $maNhanVien): ?array
    {
        return static::one("SELECT * FROM NhanVien WHERE maNhanVien = ?", [$maNhanVien]);
    }

    /**
     * Mã các nhân viên ĐANG LÀM VIỆC thuộc (các) vai trò. $roles: ROLE_* hoặc mảng ROLE_*.
     * $except: bỏ qua nhân viên này (thường là người vừa thực hiện thao tác).
     */
    public static function activeIdsByRoles($roles, ?string $except = null): array
    {
        $roles = (array)$roles;
        $dbRoles = [];
        foreach (ROLE_FROM_DB as $label => $key) {
            if (in_array($key, $roles, true)) {
                $dbRoles[] = $label;
            }
        }
        if (!$dbRoles) {
            return [];
        }
        $in = implode(',', array_fill(0, count($dbRoles), '?'));
        $rows = static::all("SELECT maNhanVien FROM NhanVien WHERE trangThai = 'Đang làm việc' AND vaiTro IN ($in)", $dbRoles);
        $ids = array_column($rows, 'maNhanVien');
        return $except === null ? $ids : array_values(array_diff($ids, [$except]));
    }

    /**
     * Mã các Quản lý kho đang làm việc được phân công phụ trách đúng kho $maKho.
     * Dùng để gửi thông báo phiếu điều chuyển cho đúng người xử lý, không gửi cho mọi quản lý.
     */
    public static function activeManagerIdsByKho(string $maKho, ?string $except = null): array
    {
        $rows = static::all("SELECT maNhanVien FROM NhanVien
            WHERE trangThai = 'Đang làm việc' AND vaiTro = 'Quản lý kho' AND maKho = ?
            ORDER BY maNhanVien", [$maKho]);
        $ids = array_column($rows, 'maNhanVien');
        return $except === null ? $ids : array_values(array_diff($ids, [$except]));
    }
}

