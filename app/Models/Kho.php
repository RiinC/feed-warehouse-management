<?php
namespace App\Models;

use Core\Model;

/** Bảng Kho: danh mục kho vật lý (maKho, tenKho, loaiKho, trangThai). */
class Kho extends Model
{
    public static function find(string $maKho): ?array
    {
        return static::one("SELECT * FROM Kho WHERE maKho = ?", [$maKho]);
    }

    /** @return array<string,string> maKho => tenKho (chỉ kho đang hoạt động) */
    public static function activeOptions(?string $maKho = null): array
    {
        $sql = "SELECT maKho, tenKho FROM Kho WHERE trangThai = 'Đang hoạt động'";
        $params = [];
        if ($maKho !== null && $maKho !== '') {
            $sql .= " AND maKho = ?";
            $params[] = $maKho;
        }
        $sql .= " ORDER BY maKho";
        $out = [];
        foreach (static::all($sql, $params) as $r) {
            $out[$r['maKho']] = $r['tenKho'];
        }
        return $out;
    }
}

