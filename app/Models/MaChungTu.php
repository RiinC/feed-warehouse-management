<?php
namespace App\Models;

use Core\Model;

/**
 * Sinh mã chứng tự theo quy ước của schema: <tiền tố><YYMMDD><số thứ tự>.
 * Ví dụ: BBKK2611240001 (Biên bản kiểm kê), KQKK261124000001 (Kết quả kiểm kê).
 *
 * CHỈ gọi BÊN TRONG một giao dịch (Database::transaction): hàm dùng SELECT ... FOR UPDATE
 * trên dòng mã lớn nhất của ngày để hai người lập cùng lúc không sinh trùng mã.
 */
final class MaChungTu extends Model
{
    /** bảng => [cột khóa chính, tiền tố, số chữ số của phần thứ tự] */
    private const MAP = [
        'BienBanKiemKe'        => ['maBienBanKiemKe', 'BBKK', 4],
        'KetQuaKiemKe'         => ['maKetQuaKiemKe', 'KQKK', 6],
        'ChenhLechKiemKe'      => ['maChenhLech', 'CLKK', 6],
        'PhuongAnXuLy'         => ['maPhuongAn', 'PAXL', 4],
        'PhieuDieuChinhTonKho' => ['maPhieuDieuChinh', 'PDCTK', 4],
        'LichSuBienDongTonKho' => ['maBienDong', 'BDTK', 6],
        'HangBatThuong'        => ['maHangBatThuong', 'HBT', 4],
    ];

    public static function next(string $table): string
    {
        if (!isset(self::MAP[$table])) {
            throw new \InvalidArgumentException('Không hỗ trợ sinh mã cho bảng: ' . $table);
        }
        [$column, $prefix, $digits] = self::MAP[$table];
        $head = $prefix . date('ymd');
        $row = static::one(
            "SELECT `$column` AS ma FROM `$table` WHERE `$column` LIKE ? ORDER BY `$column` DESC LIMIT 1 FOR UPDATE",
            [$head . '%']
        );
        $next = $row ? ((int)substr((string)$row['ma'], strlen($head)) + 1) : 1;
        if ($next >= 10 ** $digits) {
            throw new \RuntimeException('Đã hết số thứ tự chứng tự trong ngày cho ' . $table . '.');
        }
        return $head . str_pad((string)$next, $digits, '0', STR_PAD_LEFT);
    }

    /** Xem trước mã chứng từ tiếp theo mà không khóa bảng (cho preview form) */
    public static function preview(string $table): string
    {
        if (!isset(self::MAP[$table])) {
            throw new \InvalidArgumentException('Không hỗ trợ sinh mã cho bảng: ' . $table);
        }
        [$column, $prefix, $digits] = self::MAP[$table];
        $head = $prefix . date('ymd');
        $row = static::one(
            "SELECT `$column` AS ma FROM `$table` WHERE `$column` LIKE ? ORDER BY `$column` DESC LIMIT 1",
            [$head . '%']
        );
        $next = $row ? ((int)substr((string)$row['ma'], strlen($head)) + 1) : 1;
        return $head . str_pad((string)$next, $digits, '0', STR_PAD_LEFT);
    }
}
