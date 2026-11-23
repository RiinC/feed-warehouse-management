<?php
namespace App\Models;

use Core\Model;

/** Bảng TonKho: tồn thực tế theo lô — vị trí. Chỉ cập nhật trong transaction của nghiệp vụ. */
class TonKho extends Model
{
    public static function findRow(string $maLoHang, string $maViTri): ?array
    {
        return static::one("SELECT * FROM TonKho WHERE maLoHang = ? AND maViTri = ?", [$maLoHang, $maViTri]);
    }

    public static function lockRow(string $maLoHang, string $maViTri): ?array
    {
        return static::one("SELECT * FROM TonKho WHERE maLoHang = ? AND maViTri = ? FOR UPDATE", [$maLoHang, $maViTri]);
    }

    public static function totalAtPosition(string $maViTri): float
    {
        $r = static::one("SELECT IFNULL(SUM(soLuongTon), 0) AS s FROM TonKho WHERE maViTri = ?", [$maViTri]);
        return (float)($r['s'] ?? 0);
    }

    public static function totalForLot(string $maLoHang): float
    {
        $r = static::one("SELECT IFNULL(SUM(soLuongTon), 0) AS s FROM TonKho WHERE maLoHang = ?", [$maLoHang]);
        return (float)($r['s'] ?? 0);
    }

    /** Tăng tồn tại cặp lô — vị trí; tạo dòng mới nếu chưa có. */
    public static function increment(string $maLoHang, string $maViTri, float $soLuong): void
    {
        $row = self::lockRow($maLoHang, $maViTri);
        if ($row) {
            static::exec("UPDATE TonKho SET soLuongTon = soLuongTon + ? WHERE maTonKho = ?", [$soLuong, $row['maTonKho']]);
            return;
        }
        static::exec("INSERT INTO TonKho (maLoHang, maViTri, soLuongTon, soLuongDaGiu) VALUES (?, ?, ?, 0)", [$maLoHang, $maViTri, $soLuong]);
    }

    /** Giảm tồn tại cặp lô — vị trí. */
    public static function decrement(string $maLoHang, string $maViTri, float $soLuong): void
    {
        $row = self::lockRow($maLoHang, $maViTri);
        if (!$row) {
            throw new \RuntimeException('Không tìm thấy tồn kho của lô tại vị trí nguồn.');
        }
        static::exec("UPDATE TonKho SET soLuongTon = soLuongTon - ? WHERE maTonKho = ?", [$soLuong, $row['maTonKho']]);
    }

    /** Danh sách lô — vị trí còn khả dụng để lập phiếu điều chuyển. */
    public static function sourceOptions(?string $scope): array
    {
        $sql = "SELECT t.maLoHang, t.maViTri, t.soLuongTon, t.soLuongDaGiu,
                       (t.soLuongTon - t.soLuongDaGiu) AS khaDung,
                       l.maHangHoa, hh.tenHangHoa, hh.donViTinhChuan, l.ngayHetHan, l.trangThaiQC, l.trangThaiVongDoi,
                       v.tenViTri, k.tenKhuVuc, k.maKho, kho.tenKho
            FROM TonKho t
            JOIN LoHang l ON l.maLoHang = t.maLoHang
            JOIN HangHoa hh ON hh.maHangHoa = l.maHangHoa
            JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
            JOIN KhuVuc k ON k.maKhuVuc = v.maKhuVuc
            JOIN Kho kho ON kho.maKho = k.maKho
            WHERE (t.soLuongTon - t.soLuongDaGiu) > 0
              AND v.trangThai NOT IN ('Đã khóa', 'Đang bảo trì')
              AND k.trangThai = 'Đang hoạt động' AND kho.trangThai = 'Đang hoạt động'";
        $params = [];
        if ($scope !== null && $scope !== '') {
            $sql .= " AND k.maKho = ?";
            $params[] = $scope;
        }
        $sql .= " ORDER BY l.ngayHetHan, t.maLoHang, t.maViTri";
        return static::all($sql, $params);
    }

    /** Danh sách lô còn tồn khả dụng, nhóm theo lô + kho (cho bước 1 điều chuyển). */
    public static function sourceLots(?string $scope, string $q = ''): array
    {
        $sql = "SELECT t.maLoHang, k.maKho, kho.tenKho, l.maHangHoa, hh.tenHangHoa, hh.donViTinhChuan,
                       l.ngayHetHan, l.trangThaiQC, l.trangThaiVongDoi,
                       SUM(t.soLuongTon - t.soLuongDaGiu) AS khaDung, COUNT(*) AS soViTri
            FROM TonKho t
            JOIN LoHang l ON l.maLoHang = t.maLoHang
            JOIN HangHoa hh ON hh.maHangHoa = l.maHangHoa
            JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
            JOIN KhuVuc k ON k.maKhuVuc = v.maKhuVuc
            JOIN Kho kho ON kho.maKho = k.maKho
            WHERE (t.soLuongTon - t.soLuongDaGiu) > 0
              AND v.trangThai NOT IN ('Đã khóa', 'Đang bảo trì')
              AND k.trangThai = 'Đang hoạt động' AND kho.trangThai = 'Đang hoạt động'";
        $params = [];
        if ($scope !== null && $scope !== '') {
            $sql .= " AND k.maKho = ?";
            $params[] = $scope;
        }
        if ($q !== '') {
            $sql .= " AND (t.maLoHang LIKE ? OR hh.tenHangHoa LIKE ?)";
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= " GROUP BY t.maLoHang, k.maKho, kho.tenKho, l.maHangHoa, hh.tenHangHoa, hh.donViTinhChuan,
                        l.ngayHetHan, l.trangThaiQC, l.trangThaiVongDoi
            ORDER BY l.ngayHetHan, t.maLoHang";
        return static::all($sql, $params);
    }

    /** Các vị trí đang chứa lô trong kho (cho bước 2 điều chuyển). */
    public static function positionsForLot(string $maLoHang, string $maKho, ?string $scope): array
    {
        $sql = "SELECT t.maLoHang, t.maViTri, t.soLuongTon, t.soLuongDaGiu,
                       (t.soLuongTon - t.soLuongDaGiu) AS khaDung,
                       v.tenViTri, v.sucChuaToiDa, v.maKhuVuc, k.tenKhuVuc, k.maKho, kho.tenKho
            FROM TonKho t
            JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
            JOIN KhuVuc k ON k.maKhuVuc = v.maKhuVuc
            JOIN Kho kho ON kho.maKho = k.maKho
            WHERE t.maLoHang = ? AND k.maKho = ?
              AND (t.soLuongTon - t.soLuongDaGiu) > 0
              AND v.trangThai NOT IN ('Đã khóa', 'Đang bảo trì')";
        $params = [$maLoHang, $maKho];
        if ($scope !== null && $scope !== '') {
            $sql .= " AND k.maKho = ?";
            $params[] = $scope;
        }
        $sql .= " ORDER BY k.maKhuVuc, t.maViTri";
        return static::all($sql, $params);
    }
}

