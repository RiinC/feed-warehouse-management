<?php
namespace App\Models;

use Core\Model;

/** Bảng KhuVuc: khu vực thuộc kho (maKhuVuc, maKho, tenKhuVuc, loaiKhuVuc, trangThai). */
class KhuVuc extends Model
{
    private const TYPE_CODES = [
        'Lưu trữ chính' => '01',
        'Tiếp nhận tạm' => '02',
        'Cách ly' => '03',
        'Đóng gói' => '04',
        'Xuất hàng' => '05',
    ];

    public static function find(string $maKhuVuc): ?array
    {
        return static::one("SELECT k.*, kho.tenKho, kho.trangThai AS trangThaiKho
            FROM KhuVuc k JOIN Kho kho ON kho.maKho = k.maKho
            WHERE k.maKhuVuc = ?", [$maKhuVuc]);
    }

    /** @return array<string,string> maKhuVuc => tenKhuVuc (chỉ tên khu vực, không kèm tên kho). */
    public static function options(?string $maKho = null): array
    {
        $sql = "SELECT k.maKhuVuc, k.tenKhuVuc
            FROM KhuVuc k JOIN Kho kho ON kho.maKho = k.maKho
            WHERE k.trangThai IN ('Hoạt động', 'Đang hoạt động') AND kho.trangThai IN ('Hoạt động', 'Đang hoạt động')";
        $params = [];
        if ($maKho !== null && $maKho !== '') {
            $sql .= " AND k.maKho = ?";
            $params[] = $maKho;
        }
        $sql .= " ORDER BY k.maKho, k.maKhuVuc";
        $out = [];
        foreach (static::all($sql, $params) as $r) {
            $out[$r['maKhuVuc']] = $r['tenKhuVuc'];
        }
        return $out;
    }

    /** @return array<string,array{label:string,attrs:array}> Dùng cho select phụ thuộc theo kho. */
    public static function optionsWithKho(?string $maKho = null): array
    {
        $sql = "SELECT k.maKhuVuc, k.tenKhuVuc, k.maKho
            FROM KhuVuc k JOIN Kho kho ON kho.maKho = k.maKho
            WHERE k.trangThai IN ('Hoạt động', 'Đang hoạt động') AND kho.trangThai IN ('Hoạt động', 'Đang hoạt động')";
        $params = [];
        if ($maKho !== null && $maKho !== '') {
            $sql .= " AND k.maKho = ?";
            $params[] = $maKho;
        }
        $sql .= " ORDER BY k.maKho, k.maKhuVuc";
        $out = [];
        foreach (static::all($sql, $params) as $r) {
            $out[$r['maKhuVuc']] = ['label' => $r['tenKhuVuc'], 'attrs' => ['data-kho' => $r['maKho']]];
        }
        return $out;
    }

    public static function belongsToActiveKho(string $maKhuVuc, string $maKho): bool
    {
        return (bool)static::one("SELECT 1 AS ok
            FROM KhuVuc k JOIN Kho kho ON kho.maKho = k.maKho
            WHERE k.maKhuVuc = ? AND k.maKho = ?
              AND k.trangThai IN ('Hoạt động', 'Đang hoạt động') AND kho.trangThai IN ('Hoạt động', 'Đang hoạt động')", [$maKhuVuc, $maKho]);
    }

    /** Admin tạo khu vực mới trong kho; sinh mã theo loại khu vực. */
    public static function createInKho(string $maKho, string $tenKhuVuc, string $loaiKhuVuc = 'Lưu trữ chính'): string
    {
        $tenKhuVuc = trim($tenKhuVuc);
        if ($tenKhuVuc === '') {
            throw new \RuntimeException('Vui lòng nhập tên khu vực mới.');
        }
        if (mb_strlen($tenKhuVuc) > 80) {
            throw new \RuntimeException('Tên khu vực tối đa 80 ký tự.');
        }
        if (!isset(self::TYPE_CODES[$loaiKhuVuc])) {
            throw new \RuntimeException('Loại khu vực không hợp lệ.');
        }
        if (!static::one("SELECT 1 AS ok FROM Kho WHERE maKho = ? AND trangThai = 'Đang hoạt động'", [$maKho])) {
            throw new \RuntimeException('Kho không tồn tại hoặc đã ngừng hoạt động.');
        }
        if (static::one("SELECT 1 AS ok FROM KhuVuc WHERE maKho = ? AND tenKhuVuc = ?", [$maKho, $tenKhuVuc])) {
            throw new \RuntimeException('Khu vực này đã tồn tại trong kho.');
        }
        $prefix = 'KV' . self::TYPE_CODES[$loaiKhuVuc];
        for ($i = 0; $i < 5; $i++) {
            $r = static::one("SELECT maKhuVuc AS m FROM KhuVuc WHERE maKhuVuc LIKE ? ORDER BY maKhuVuc DESC LIMIT 1 FOR UPDATE", [$prefix . '%']);
            $seq = $r ? ((int)substr((string)$r['m'], -4)) : 0;
            if ($seq >= 9999) {
                throw new \RuntimeException('Đã hết dãy mã khu vực cho loại này.');
            }
            $code = $prefix . str_pad((string)($seq + 1), 4, '0', STR_PAD_LEFT);
            try {
                static::exec("INSERT INTO KhuVuc (maKhuVuc, maKho, tenKhuVuc, loaiKhuVuc, trangThai, moTa)
                    VALUES (?, ?, ?, ?, 'Đang hoạt động', NULL)", [$code, $maKho, $tenKhuVuc, $loaiKhuVuc]);
                return $code;
            } catch (\mysqli_sql_exception $ex) {
                if ((int)$ex->getCode() === 1062) { continue; }
                throw $ex;
            }
        }
        throw new \RuntimeException('Không sinh được mã khu vực, vui lòng thử lại.');
    }
}

