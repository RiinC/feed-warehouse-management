<?php
namespace App\Models;

use Core\Model;

/** Bảng NhanVien (maNhanVien, hoTen, soDienThoai, email, chucVu, vaiTro, trangThai, maKho). */
class NhanVien extends Model
{
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
}
