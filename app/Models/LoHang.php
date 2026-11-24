<?php
namespace App\Models;

use Core\Model;

/**
 * Bảng LoHang (+ TonKho, ViTriLuuTru, PhieuTiepNhan...) cho UC05 — Tra cứu thông tin lô hàng.
 *
 * Trạng thái hiển thị theo tài liệu UC: Còn hạn | Hết hạn | Đã xuất hết | Ngừng sử dụng, suy ra từ
 * trangThaiVongDoi và ngayHetHan (xem TRANG_THAI_SQL).
 */
class LoHang extends Model
{
    public const TRANG_THAI = ['Còn hạn', 'Hết hạn', 'Đã xuất hết', 'Ngừng sử dụng'];

    private const TRANG_THAI_SQL = "CASE
        WHEN l.trangThaiVongDoi IN ('Đã thanh lý', 'Đã tiêu hủy') THEN 'Ngừng sử dụng'
        WHEN l.trangThaiVongDoi = 'Đã xuất hết' THEN 'Đã xuất hết'
        WHEN l.ngayHetHan < CURDATE() THEN 'Hết hạn'
        ELSE 'Còn hạn' END";

    /** Câu SELECT trong (mỗi lô một dòng) + tham số theo bộ lọc q, viTri. */
    private static function inner(array $f): array
    {
        $w = ['1 = 1'];
        $p = [];
        if (($f['q'] ?? '') !== '') {
            $like = '%' . HangHoa::escapeLike($f['q']) . '%';
            $w[] = '(l.maLoHang LIKE ? OR l.maHangHoa LIKE ? OR h.tenHangHoa LIKE ?)';
            array_push($p, $like, $like, $like);
        }
        if (($f['viTri'] ?? '') !== '') {
            $w[] = 'EXISTS (SELECT 1 FROM TonKho t2 WHERE t2.maLoHang = l.maLoHang AND t2.maViTri = ? AND t2.soLuongTon > 0)';
            $p[] = $f['viTri'];
        }
        $sql = "SELECT l.maLoHang, l.maHangHoa, h.tenHangHoa, l.ngayHetHan, l.trangThaiQC, l.trangThaiVongDoi,
                       COALESCE((SELECT MIN(b.thoiGianPhatSinh) FROM LichSuBienDongTonKho b
                                 WHERE b.maLoHang = l.maLoHang AND b.loaiGiaoDich = 'Nhập kho'), ptn.ngayTiepNhan, l.ngayTao) AS ngayNhap,
                       COALESCE(SUM(tk.soLuongTon), 0) AS soLuongTon,
                       GROUP_CONCAT(DISTINCT CASE WHEN tk.soLuongTon > 0 THEN tk.maViTri END ORDER BY tk.maViTri SEPARATOR ', ') AS viTri,
                       " . self::TRANG_THAI_SQL . " AS trangThaiHienThi
                FROM LoHang l
                JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
                LEFT JOIN PhieuTiepNhan ptn ON ptn.maPhieuTiepNhan = l.maPhieuTiepNhan
                LEFT JOIN TonKho tk ON tk.maLoHang = l.maLoHang
                WHERE " . implode(' AND ', $w) . "
                GROUP BY l.maLoHang, l.maHangHoa, h.tenHangHoa, l.ngayHetHan, l.trangThaiQC, l.trangThaiVongDoi, l.ngayTao, ptn.ngayTiepNhan";
        return [$sql, $p];
    }

    private static function outerWhere(array $f, array $p): array
    {
        $w = '1 = 1';
        if (($f['trangThai'] ?? '') !== '') {
            $w .= ' AND x.trangThaiHienThi = ?';
            $p[] = $f['trangThai'];
        }
        return [$w, $p];
    }

    public static function count(array $f): int
    {
        [$inner, $p] = self::inner($f);
        [$w, $p] = self::outerWhere($f, $p);
        $r = static::one("SELECT COUNT(*) AS c FROM ($inner) x WHERE $w", $p);
        return (int)($r['c'] ?? 0);
    }

    public static function search(array $f, int $limit, int $offset): array
    {
        [$inner, $p] = self::inner($f);
        [$w, $p] = self::outerWhere($f, $p);
        $p[] = $limit;
        $p[] = $offset;
        return static::all("SELECT x.* FROM ($inner) x WHERE $w ORDER BY x.ngayNhap DESC, x.maLoHang DESC LIMIT ? OFFSET ?", $p);
    }

    /** Các vị trí lưu trữ đang/đã có tồn kho, dùng cho ô chọn kho/kệ/vị trí: [maViTri => nhãn]. */
    public static function viTriOptions(): array
    {
        $rows = static::all(
            "SELECT vt.maViTri, CONCAT(k.tenKho, ' › ', kv.tenKhuVuc, ' › ', vt.tenViTri) AS nhan
             FROM ViTriLuuTru vt
             JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
             JOIN Kho k ON k.maKho = kv.maKho
             WHERE EXISTS (SELECT 1 FROM TonKho t WHERE t.maViTri = vt.maViTri)
             ORDER BY k.maKho, kv.maKhuVuc, vt.maViTri"
        );
        return array_column($rows, 'nhan', 'maViTri');
    }

    /** Chi tiết một lô hàng; null nếu không có. Gồm 'viTriChiTiet' = số lượng tồn tại từng vị trí. */
    public static function detail(string $ma): ?array
    {
        $row = static::one(
            "SELECT l.*, h.tenHangHoa, h.donViTinhChuan, ptn.ngayTiepNhan, ptn.maPhieuGiao, ptn.maXuong,
                    ncc.tenNCC, xs.tenXuong,
                    COALESCE((SELECT MIN(b.thoiGianPhatSinh) FROM LichSuBienDongTonKho b
                              WHERE b.maLoHang = l.maLoHang AND b.loaiGiaoDich = 'Nhập kho'), ptn.ngayTiepNhan, l.ngayTao) AS ngayNhap,
                    " . self::TRANG_THAI_SQL . " AS trangThaiHienThi
             FROM LoHang l
             JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
             LEFT JOIN PhieuTiepNhan ptn ON ptn.maPhieuTiepNhan = l.maPhieuTiepNhan
             LEFT JOIN PhieuGiaoNCC pg ON pg.maPhieuGiao = ptn.maPhieuGiao
             LEFT JOIN NhaCungCap ncc ON ncc.maNCC = pg.maNCC
             LEFT JOIN XuongSanXuat xs ON xs.maXuong = ptn.maXuong
             WHERE l.maLoHang = ?",
            [$ma]
        );
        if (!$row) {
            return null;
        }
        $row['viTriChiTiet'] = static::all(
            "SELECT tk.maViTri, vt.tenViTri, kv.tenKhuVuc, k.tenKho, tk.soLuongTon, tk.soLuongDaGiu
             FROM TonKho tk
             JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
             JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
             JOIN Kho k ON k.maKho = kv.maKho
             WHERE tk.maLoHang = ?
             ORDER BY k.maKho, kv.maKhuVuc, vt.maViTri",
            [$ma]
        );
        return $row;
    }
}
