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

    /**
     * Điều kiện "lô thuộc kho ?" (cần alias l = LoHang, ptn = PhieuTiepNhan): lô có tồn tại một vị trí của kho,
     * hoặc đang ở vị trí tiếp nhận tạm của kho (chưa nhập kho). Cần 2 tham số mã kho.
     */
    private const SCOPE_COND = "(EXISTS (SELECT 1 FROM TonKho t6 JOIN ViTriLuuTru v6 ON v6.maViTri = t6.maViTri
                                  JOIN KhuVuc k6 ON k6.maKhuVuc = v6.maKhuVuc WHERE t6.maLoHang = l.maLoHang AND k6.maKho = ?)
             OR EXISTS (SELECT 1 FROM ViTriLuuTru v7 JOIN KhuVuc k7 ON k7.maKhuVuc = v7.maKhuVuc
                        WHERE v7.maViTri = ptn.maViTriTam AND k7.maKho = ?))";

    /**
     * Câu SELECT trong (mỗi lô một dòng) + tham số theo bộ lọc:
     *  q, locKho / khu / viTri (lô đang có tồn tại kho / khu vực / vị trí đó),
     *  kho (phạm vi dữ liệu, null = mọi kho), nhom (nhóm hàng theo nghiệp vụ kho, null = mọi nhóm).
     */
    private static function inner(array $f): array
    {
        $w = ['1 = 1'];
        $p = [];
        $tonCond = '1 = 1';   // chỉ cộng tồn tại các vị trí thuộc kho (nếu bị giới hạn)
        $pJoin = [];
        if (($f['kho'] ?? null) !== null) {
            $tonCond = 'tk.maViTri IN (SELECT v5.maViTri FROM ViTriLuuTru v5 JOIN KhuVuc k5 ON k5.maKhuVuc = v5.maKhuVuc WHERE k5.maKho = ?)';
            $pJoin[] = $f['kho'];
            $w[] = self::SCOPE_COND;
            array_push($p, $f['kho'], $f['kho']);
        }
        if (($f['q'] ?? '') !== '') {
            $like = '%' . HangHoa::escapeLike($f['q']) . '%';
            $w[] = '(l.maLoHang LIKE ? OR l.maHangHoa LIKE ? OR h.tenHangHoa LIKE ?)';
            array_push($p, $like, $like, $like);
        }
        if (isset($f['nhom']) && is_array($f['nhom'])) {
            if (!$f['nhom']) {
                $w[] = '1 = 0';
            } else {
                $w[] = 'h.loaiHangHoa IN (' . implode(', ', array_fill(0, count($f['nhom']), '?')) . ')';
                array_push($p, ...array_values($f['nhom']));
            }
        }
        $pos = [];
        foreach (['locKho' => 'k2.maKho', 'khu' => 'v2.maKhuVuc', 'viTri' => 't2.maViTri'] as $key => $col) {
            if (($f[$key] ?? '') !== '') {
                $pos[] = "$col = ?";
                $p[] = $f[$key];
            }
        }
        if ($pos) {
            $w[] = 'EXISTS (SELECT 1 FROM TonKho t2 JOIN ViTriLuuTru v2 ON v2.maViTri = t2.maViTri JOIN KhuVuc k2 ON k2.maKhuVuc = v2.maKhuVuc
                            WHERE t2.maLoHang = l.maLoHang AND t2.soLuongTon > 0 AND ' . implode(' AND ', $pos) . ')';
        }
        $sql = "SELECT l.maLoHang, l.maHangHoa, h.tenHangHoa, l.ngayHetHan, l.trangThaiQC, l.trangThaiVongDoi,
                       COALESCE((SELECT MIN(b.thoiGianPhatSinh) FROM LichSuBienDongTonKho b
                                 WHERE b.maLoHang = l.maLoHang AND b.loaiGiaoDich = 'Nhập kho'), ptn.ngayTiepNhan, l.ngayTao) AS ngayNhap,
                       COALESCE(SUM(tk.soLuongTon), 0) AS soLuongTon,
                       GROUP_CONCAT(DISTINCT CASE WHEN tk.soLuongTon > 0 THEN k.tenKho END ORDER BY k.maKho SEPARATOR '|') AS tenKho,
                       GROUP_CONCAT(DISTINCT CASE WHEN tk.soLuongTon > 0 THEN kv.tenKhuVuc END ORDER BY kv.maKhuVuc SEPARATOR '|') AS tenKhuVuc,
                       GROUP_CONCAT(CASE WHEN tk.soLuongTon > 0 THEN CONCAT(vt.tenViTri, '~', tk.soLuongTon) END ORDER BY tk.maViTri SEPARATOR '|') AS tenViTri,
                       " . self::TRANG_THAI_SQL . " AS trangThaiHienThi
                FROM LoHang l
                JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
                LEFT JOIN PhieuTiepNhan ptn ON ptn.maPhieuTiepNhan = l.maPhieuTiepNhan
                LEFT JOIN TonKho tk ON tk.maLoHang = l.maLoHang AND $tonCond
                LEFT JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
                LEFT JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
                LEFT JOIN Kho k ON k.maKho = kv.maKho
                WHERE " . implode(' AND ', $w) . "
                GROUP BY l.maLoHang, l.maHangHoa, h.tenHangHoa, l.ngayHetHan, l.trangThaiQC, l.trangThaiVongDoi, l.ngayTao, ptn.ngayTiepNhan";
        return [$sql, array_merge($pJoin, $p)];
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

    /**
     * Kho / khu vực / vị trí lưu trữ đang/đã có tồn kho, dùng cho 3 ô chọn riêng trong form lọc.
     * $maKho: chỉ kho này; null = mọi kho.
     * @return array ['kho' => [maKho => tên], 'khu' => [maKhuVuc => tên], 'khuGroup' => [maKhuVuc => maKho],
     *                'viTri' => [maViTri => tên], 'viTriGroup' => [maViTri => maKhuVuc]]
     */
    public static function viTriOptions(?string $maKho = null): array
    {
        $scope = $maKho !== null ? ' AND k.maKho = ?' : '';
        $rows = static::all(
            "SELECT k.maKho, k.tenKho, kv.maKhuVuc, kv.tenKhuVuc, vt.maViTri, vt.tenViTri
             FROM ViTriLuuTru vt
             JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
             JOIN Kho k ON k.maKho = kv.maKho
             WHERE EXISTS (SELECT 1 FROM TonKho t WHERE t.maViTri = vt.maViTri)$scope
             ORDER BY k.maKho, kv.maKhuVuc, vt.maViTri",
            $maKho !== null ? [$maKho] : []
        );
        $out = ['kho' => [], 'khu' => [], 'khuGroup' => [], 'viTri' => [], 'viTriGroup' => []];
        foreach ($rows as $r) {
            $out['kho'][$r['maKho']] = $r['tenKho'];
            $out['khu'][$r['maKhuVuc']] = $r['tenKhuVuc'];
            $out['khuGroup'][$r['maKhuVuc']] = $r['maKho'];
            $out['viTri'][$r['maViTri']] = $r['tenViTri'] . ' (' . $r['maViTri'] . ')';
            $out['viTriGroup'][$r['maViTri']] = $r['maKhuVuc'];
        }
        return $out;
    }

    /**
     * Chi tiết một lô hàng; null nếu không có (hoặc không thuộc kho $maKho). Gồm 'viTriChiTiet' = tồn tại từng vị trí
     * (chỉ các vị trí thuộc kho $maKho nếu bị giới hạn). $maKho null = mọi kho.
     */
    public static function detail(string $ma, ?string $maKho = null): ?array
    {
        $scope = $maKho !== null ? ' AND ' . self::SCOPE_COND : '';
        $row = static::one(
            "SELECT l.*, h.tenHangHoa, h.loaiHangHoa, h.donViTinhChuan, ptn.ngayTiepNhan, ptn.maPhieuGiao, ptn.maXuong,
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
             WHERE l.maLoHang = ?$scope",
            $maKho !== null ? [$ma, $maKho, $maKho] : [$ma]
        );
        if (!$row) {
            return null;
        }
        $posScope = $maKho !== null ? ' AND k.maKho = ?' : '';
        $row['viTriChiTiet'] = static::all(
            "SELECT tk.maViTri, vt.tenViTri, kv.tenKhuVuc, k.tenKho, tk.soLuongTon, tk.soLuongDaGiu
             FROM TonKho tk
             JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
             JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
             JOIN Kho k ON k.maKho = kv.maKho
             WHERE tk.maLoHang = ?$posScope
             ORDER BY k.maKho, kv.maKhuVuc, vt.maViTri",
            $maKho !== null ? [$ma, $maKho] : [$ma]
        );
        return $row;
    }
}
