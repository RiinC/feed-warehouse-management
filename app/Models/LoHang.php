<?php
namespace App\Models;

use Core\Model;

/**
 * Bảng LoHang. Phục vụ UC16: xác định lô đã nhập kho và lượng thực tế còn phải phân bổ vị trí.
 * - Đã nhập kho: có dòng ChiTietPhieuNhapKho thuộc phiếu Đang xếp kệ / Đã nhập kho.
 * - Đã phân bổ: dòng chi tiết của phiếu Đã nhập kho + các lần gán trong LuuKho.
 */
class LoHang extends Model
{
    public static function find(string $maLoHang): ?array
    {
        return static::one("SELECT l.*, hh.tenHangHoa, hh.donViTinhChuan, hh.loaiHangHoa, phk.maKho, kh.tenKho
            FROM LoHang l JOIN HangHoa hh ON hh.maHangHoa = l.maHangHoa
            LEFT JOIN (SELECT c.maLoHang, MIN(p.maKho) AS maKho FROM ChiTietPhieuNhapKho c JOIN PhieuNhapKho p ON p.maPhieuNhapKho = c.maPhieuNhapKho WHERE p.trangThai IN ('Đang xếp kệ', 'Đã nhập kho') GROUP BY c.maLoHang) phk ON phk.maLoHang = l.maLoHang
            LEFT JOIN Kho kh ON kh.maKho = phk.maKho
            WHERE l.maLoHang = ?", [$maLoHang]);
    }

    public static function lockForUpdate(string $maLoHang): array
    {
        $r = static::one("SELECT * FROM LoHang WHERE maLoHang = ? FOR UPDATE", [$maLoHang]);
        if (!$r) {
            throw new \RuntimeException('Lô hàng không tồn tại.');
        }
        return $r;
    }

    /** @return array{nhap:float,daCt:float,daPhanBo:float,da:float,conLai:float} */
    public static function allocationState(string $maLoHang): array
    {
        $nhap = (float)(static::one("SELECT IFNULL(SUM(c.soLuongChuanKg), 0) AS s
            FROM ChiTietPhieuNhapKho c JOIN PhieuNhapKho p ON p.maPhieuNhapKho = c.maPhieuNhapKho
            WHERE c.maLoHang = ? AND p.trangThai IN ('Đang xếp kệ', 'Đã nhập kho')", [$maLoHang])['s'] ?? 0);
        $daCt = (float)(static::one("SELECT IFNULL(SUM(c.soLuongChuanKg), 0) AS s
            FROM ChiTietPhieuNhapKho c JOIN PhieuNhapKho p ON p.maPhieuNhapKho = c.maPhieuNhapKho
            WHERE c.maLoHang = ? AND p.trangThai = 'Đã nhập kho'", [$maLoHang])['s'] ?? 0);
        $daPb = (float)(static::one("SELECT IFNULL(SUM(soLuongPhanBo), 0) AS s FROM LuuKho WHERE maLoHang = ?", [$maLoHang])['s'] ?? 0);
        $da = $daCt + $daPb;
        return ['nhap' => $nhap, 'daCt' => $daCt, 'daPhanBo' => $daPb, 'da' => $da, 'conLai' => max(0, $nhap - $da)];
    }

    /** Mã phiếu nhập gắn với lô (ưu tiên phiếu đang xếp kệ). */
    public static function firstPhieuCode(string $maLoHang): ?string
    {
        $r = static::one("SELECT c.maPhieuNhapKho
            FROM ChiTietPhieuNhapKho c JOIN PhieuNhapKho p ON p.maPhieuNhapKho = c.maPhieuNhapKho
            WHERE c.maLoHang = ? AND p.trangThai IN ('Đang xếp kệ', 'Đã nhập kho')
            ORDER BY CASE WHEN p.trangThai = 'Đang xếp kệ' THEN 0 ELSE 1 END, p.maPhieuNhapKho DESC
            LIMIT 1", [$maLoHang]);
        return $r ? (string)$r['maPhieuNhapKho'] : null;
    }

    private static function candidateFrom(): string
    {
        return "FROM LoHang l
            JOIN HangHoa hh ON hh.maHangHoa = l.maHangHoa
            JOIN (SELECT c.maLoHang, SUM(c.soLuongChuanKg) AS nhapKg
                  FROM ChiTietPhieuNhapKho c JOIN PhieuNhapKho p ON p.maPhieuNhapKho = c.maPhieuNhapKho
                  WHERE p.trangThai IN ('Đang xếp kệ', 'Đã nhập kho')
                  GROUP BY c.maLoHang) nhap ON nhap.maLoHang = l.maLoHang
            LEFT JOIN (SELECT c.maLoHang, SUM(c.soLuongChuanKg) AS daCt
                  FROM ChiTietPhieuNhapKho c JOIN PhieuNhapKho p ON p.maPhieuNhapKho = c.maPhieuNhapKho
                  WHERE p.trangThai = 'Đã nhập kho'
                  GROUP BY c.maLoHang) ctdn ON ctdn.maLoHang = l.maLoHang
            LEFT JOIN (SELECT maLoHang, SUM(soLuongPhanBo) AS daPb FROM LuuKho GROUP BY maLoHang) lk
                  ON lk.maLoHang = l.maLoHang
            LEFT JOIN (SELECT c.maLoHang, MIN(p.maKho) AS maKho
                  FROM ChiTietPhieuNhapKho c JOIN PhieuNhapKho p ON p.maPhieuNhapKho = c.maPhieuNhapKho
                  WHERE p.trangThai IN ('Đang xếp kệ', 'Đã nhập kho')
                  GROUP BY c.maLoHang) phk ON phk.maLoHang = l.maLoHang
            LEFT JOIN Kho kh ON kh.maKho = phk.maKho";
    }

    /** @return array{0:string,1:array} */
    private static function candidateWhere(?string $scope, string $q): array
    {
        $where = "l.trangThaiQC = 'Đã đạt chuẩn'
            AND l.ngayHetHan >= CURDATE()
            AND l.trangThaiVongDoi IN ('Đã tạo', 'Đang lưu kho', 'Sắp hết hạn')
            AND (nhap.nhapKg - IFNULL(ctdn.daCt, 0) - IFNULL(lk.daPb, 0)) > 0.000001";
        $params = [];
        if ($scope !== null && $scope !== '') {
            $where .= " AND EXISTS (SELECT 1 FROM ChiTietPhieuNhapKho ck
                JOIN PhieuNhapKho pk ON pk.maPhieuNhapKho = ck.maPhieuNhapKho
                WHERE ck.maLoHang = l.maLoHang AND pk.maKho = ?
                  AND pk.trangThai IN ('Đang xếp kệ', 'Đã nhập kho'))";
            $params[] = $scope;
        }
        if ($q !== '') {
            $where .= " AND (l.maLoHang LIKE ? OR hh.tenHangHoa LIKE ?)";
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }
        return [$where, $params];
    }

    /** @return array<int,array> Danh sách lô đã nhập kho nhưng chưa phân bổ đủ vị trí. */
    public static function candidates(array $f, int $page, int $perPage, ?string $scope): array
    {
        [$where, $params] = self::candidateWhere($scope, (string)($f['q'] ?? ''));
        $total = (int)(static::one("SELECT COUNT(*) AS c " . self::candidateFrom() . " WHERE $where", $params)['c'] ?? 0);
        $sql = "SELECT l.maLoHang, l.maHangHoa, l.ngaySanXuat, l.ngayHetHan, l.soLuongBanDau,
                       l.trangThaiQC, l.trangThaiVongDoi, hh.tenHangHoa, hh.donViTinhChuan, phk.maKho, kh.tenKho,
                       nhap.nhapKg AS daNhap, IFNULL(ctdn.daCt, 0) AS daCt, IFNULL(lk.daPb, 0) AS daPhanBo,
                       (nhap.nhapKg - IFNULL(ctdn.daCt, 0) - IFNULL(lk.daPb, 0)) AS conLai

                   " . self::candidateFrom() . " WHERE $where
            ORDER BY l.ngayHetHan, l.maLoHang
            LIMIT ? OFFSET ?";
        $p2 = $params;
        $p2[] = $perPage;
        $p2[] = max(0, ($page - 1) * $perPage);
$rows = static::all($sql, $p2);
        foreach ($rows as &$r) {
            $r['viTriHienTai'] = self::positionSummary((string)$r['maLoHang']);
        }
        unset($r);
        return [$rows, $total];
    }

    /** Toàn bộ lựa chọn cho form gán lô (tối đa 300). */
    public static function candidateOptions(?string $scope): array
    {
        [$where, $params] = self::candidateWhere($scope, '');
        $sql = "SELECT l.maLoHang, l.maHangHoa, l.ngayHetHan, hh.tenHangHoa, hh.donViTinhChuan, phk.maKho, kh.tenKho,
                       nhap.nhapKg AS daNhap, IFNULL(ctdn.daCt, 0) + IFNULL(lk.daPb, 0) AS daPhanBo,
                       (nhap.nhapKg - IFNULL(ctdn.daCt, 0) - IFNULL(lk.daPb, 0)) AS conLai
                   " . self::candidateFrom() . " WHERE $where
            ORDER BY l.ngayHetHan, l.maLoHang LIMIT 300";
        return static::all($sql, $params);
    }

    private static function positionSummary(string $maLoHang): string
    {
        $r = static::one("SELECT GROUP_CONCAT(CONCAT(x.maViTri, ' (', FORMAT(x.qty, 2), ')') SEPARATOR ', ') AS s
            FROM (
                SELECT maViTri, SUM(soLuongTon) AS qty FROM TonKho WHERE maLoHang = ? AND soLuongTon > 0 GROUP BY maViTri
                UNION ALL
                SELECT maViTri, SUM(soLuongPhanBo) AS qty FROM LuuKho WHERE maLoHang = ? GROUP BY maViTri
            ) x", [$maLoHang, $maLoHang]);
        return (string)($r['s'] ?? '' );
    }

}

