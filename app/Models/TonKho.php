<?php
namespace App\Models;

use Core\Model;

/**
 * Tồn kho hiện tại (bảng TonKho) — UC29 tra cứu tồn kho.
 *
 * QUY ƯỚC: Model này CHỈ ĐỌC. Mọi thay đổi số lượng tồn đi qua Model của chứng từ
 * (PhieuDieuChinhTonKho::post) để luôn ghi kèm phiếu điều chỉnh + LichSuBienDongTonKho
 * trong cùng một giao dịch.
 *
 * Alias dùng chung trong truy vấn: t = TonKho, l = LoHang, h = HangHoa,
 * v = ViTriLuuTru, kv = KhuVuc, k = Kho.
 */
class TonKho extends Model
{
    /** Mệnh đề FROM dùng chung cho tra cứu tồn và báo cáo. */
    public const FROM_SQL = ' FROM TonKho t
        JOIN LoHang l ON l.maLoHang = t.maLoHang
        JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
        JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
        JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
        JOIN Kho k ON k.maKho = kv.maKho ';

    /** Mức cảnh báo hạn sử dụng (khóa ô lọc + nhãn). */
    public const HAN_SU_DUNG = [
        'het-han' => 'Đã hết hạn',
        '7'       => 'Còn ≤ 7 ngày',
        '30'      => 'Còn ≤ 30 ngày',
        '90'      => 'Còn ≤ 90 ngày',
        'con-han' => 'Còn hạn sử dụng',
    ];

    /** Mệnh đề SQL cho mức cảnh báo hạn sử dụng. */
    public static function hanSuDungSql(string $bucket): string
    {
        switch ($bucket) {
            case 'het-han':
                return 'l.ngayHetHan < CURDATE()';
            case '7':
                return 'l.ngayHetHan >= CURDATE() AND l.ngayHetHan <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)';
            case '30':
                return 'l.ngayHetHan > DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND l.ngayHetHan <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
            case '90':
                return 'l.ngayHetHan > DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND l.ngayHetHan <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)';
            default:
                return 'l.ngayHetHan > DATE_ADD(CURDATE(), INTERVAL 90 DAY)';
        }
    }

    /** Nhãn mức hạn sử dụng của một lô (tính trong SQL để View không phải xử lý). */
    public static function hanSuDungNhanSql(): string
    {
        return "CASE
            WHEN l.ngayHetHan < CURDATE() THEN 'Đã hết hạn'
            WHEN l.ngayHetHan <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Còn ≤ 7 ngày'
            WHEN l.ngayHetHan <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Còn ≤ 30 ngày'
            WHEN l.ngayHetHan <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 'Còn ≤ 90 ngày'
            ELSE 'Còn hạn sử dụng' END";
    }

    /**
     * Điều kiện lọc dùng chung. $params được nối thêm tham số theo đúng thứ tự.
     * $f: q, maKho, maKhuVuc, maViTri, maHangHoa, loaiHangHoa, hanSuDung, conHang
     */
    public static function filterSql(array $f, array &$params): string
    {
        $where = ' WHERE 1 = 1';
        $q = trim((string)($f['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (h.maHangHoa LIKE ? OR h.tenHangHoa LIKE ? OR l.maLoHang LIKE ? OR v.maViTri LIKE ? OR v.tenViTri LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if (!empty($f['maKho'])) {
            $where .= ' AND k.maKho = ?';
            $params[] = $f['maKho'];
        }
        if (!empty($f['maKhuVuc'])) {
            $where .= ' AND kv.maKhuVuc = ?';
            $params[] = $f['maKhuVuc'];
        }
        if (!empty($f['maViTri'])) {
            $where .= ' AND t.maViTri = ?';
            $params[] = $f['maViTri'];
        }
        if (!empty($f['maHangHoa'])) {
            $where .= ' AND h.maHangHoa = ?';
            $params[] = $f['maHangHoa'];
        }
        if (!empty($f['loaiHangHoa'])) {
            $where .= ' AND h.loaiHangHoa = ?';
            $params[] = $f['loaiHangHoa'];
        }
        if (!empty($f['hanSuDung']) && isset(self::HAN_SU_DUNG[$f['hanSuDung']])) {
            $where .= ' AND ' . self::hanSuDungSql((string)$f['hanSuDung']);
        }
        $conHang = (string)($f['conHang'] ?? '');
        if ($conHang === '1') {
            $where .= ' AND t.soLuongTon > 0';
        } elseif ($conHang === 'kha-dung') {
            $where .= ' AND (t.soLuongTon - t.soLuongDaGiu) > 0';
        } elseif ($conHang === 'da-giu') {
            $where .= ' AND t.soLuongDaGiu > 0';
        } elseif ($conHang === 'het-ton') {
            $where .= ' AND t.soLuongTon <= 0';
        }
        return $where;
    }

    /** Danh sách tồn theo (lô, vị trí) có phân trang. Trả về [các dòng, tổng số dòng]. */
    public static function search(array $f, int $page, int $perPage): array
    {
        $params = [];
        $where = self::filterSql($f, $params);
        $total = (int)(static::one('SELECT COUNT(*) AS c' . self::FROM_SQL . $where, $params)['c'] ?? 0);

        $sortMap = [
            'hang' => 'h.tenHangHoa',
            'hsd'  => 'l.ngayHetHan',
            'ton'  => 't.soLuongTon',
            'kd'   => 'soLuongKhaDung',
            'capnhat' => 't.ngayCapNhatCuoi',
        ];
        $sortCol = $sortMap[(string)($f['sort'] ?? '')] ?? null;
        $sortDir = strtolower((string)($f['dir'] ?? '')) === 'desc' ? 'DESC' : 'ASC';
        $orderBy = $sortCol ? ($sortCol . ' ' . $sortDir . ', l.ngayHetHan ASC, v.maViTri ASC') : 'k.maKho, h.tenHangHoa, l.ngayHetHan, v.maViTri';

        $rows = static::all(
            'SELECT t.maTonKho, t.maLoHang, t.maViTri, t.soLuongTon, t.soLuongDaGiu,
                    (t.soLuongTon - t.soLuongDaGiu) AS soLuongKhaDung, t.ngayCapNhatCuoi,
                    l.ngayHetHan, l.trangThaiQC, l.trangThaiVongDoi, l.maLoHang AS lo,
                    DATEDIFF(l.ngayHetHan, CURDATE()) AS soNgayConLai,
                    h.maHangHoa, h.tenHangHoa, h.loaiHangHoa, h.donViTinhChuan,
                    v.tenViTri, v.trangThai AS trangThaiViTri, kv.maKhuVuc, kv.tenKhuVuc, k.maKho, k.tenKho,
                    ' . self::hanSuDungNhanSql() . ' AS mucHanSuDung'
            . self::FROM_SQL . $where . '
             ORDER BY ' . $orderBy . '
             LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, max(0, ($page - 1) * $perPage)])
        );
        return [$rows, $total];
    }

    /** Tổng hợp tồn kho theo từng mặt hàng (và kho nếu không gộp). */
    public static function searchSummaryByProduct(array $f, int $page, int $perPage): array
    {
        $params = [];
        $where = self::filterSql($f, $params);
        $total = (int)(static::one(
            'SELECT COUNT(*) AS c FROM (SELECT 1' . self::FROM_SQL . $where . ' GROUP BY k.maKho, h.maHangHoa) AS x',
            $params
        )['c'] ?? 0);
        $rows = static::all(
            'SELECT k.maKho, k.tenKho, h.maHangHoa, h.tenHangHoa, h.loaiHangHoa, h.donViTinhChuan,
                    COUNT(DISTINCT t.maLoHang) AS soLo,
                    COUNT(DISTINCT t.maViTri) AS soViTri,
                    COALESCE(SUM(t.soLuongTon), 0) AS soLuongTon,
                    COALESCE(SUM(t.soLuongDaGiu), 0) AS soLuongDaGiu,
                    COALESCE(SUM(t.soLuongTon - t.soLuongDaGiu), 0) AS soLuongKhaDung,
                    MIN(CASE WHEN t.soLuongTon > 0 THEN l.ngayHetHan END) AS ngayHetHanGanNhat,
                    MAX(t.ngayCapNhatCuoi) AS ngayCapNhatCuoi'
            . self::FROM_SQL . $where . '
             GROUP BY k.maKho, k.tenKho, h.maHangHoa, h.tenHangHoa, h.loaiHangHoa, h.donViTinhChuan
             ORDER BY k.maKho, h.tenHangHoa
             LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, max(0, ($page - 1) * $perPage)])
        );
        return [$rows, $total];
    }

    /** Chi tiết một dòng tồn kho theo maTonKho. */
    public static function findDetail(int $maTonKho): ?array
    {
        return static::one(
            'SELECT t.maTonKho, t.maLoHang, t.maViTri, t.soLuongTon, t.soLuongDaGiu,
                    (t.soLuongTon - t.soLuongDaGiu) AS soLuongKhaDung, t.ngayCapNhatCuoi,
                    l.soLoNhaCungCap, l.ngaySanXuat, l.ngayHetHan, l.soLuongBanDau,
                    l.trangThaiQC, l.trangThaiVongDoi, DATEDIFF(l.ngayHetHan, CURDATE()) AS soNgayConLai,
                    h.maHangHoa, h.tenHangHoa, h.loaiHangHoa, h.donViTinhChuan, h.dieuKienBaoQuan, h.moTa AS moTaHangHoa,
                    v.tenViTri, v.day, v.ke, v.tang, v.o, v.sucChuaToiDa, v.trangThai AS trangThaiViTri,
                    kv.maKhuVuc, kv.tenKhuVuc, kv.loaiKhuVuc, k.maKho, k.tenKho,
                    ' . self::hanSuDungNhanSql() . ' AS mucHanSuDung'
            . self::FROM_SQL . ' WHERE t.maTonKho = ?',
            [$maTonKho]
        );
    }

    /** Tổng hợp cho dải thẻ đầu trang tra cứu tồn. */
    public static function totals(array $f): array
    {
        $params = [];
        $where = self::filterSql($f, $params);
        $r = static::one(
            'SELECT COUNT(*) AS soDong, COALESCE(SUM(t.soLuongTon), 0) AS tongTon,
                    COALESCE(SUM(t.soLuongDaGiu), 0) AS tongDaGiu,
                    COUNT(DISTINCT h.maHangHoa) AS soMatHang,
                    COUNT(DISTINCT t.maLoHang) AS soLo,
                    COALESCE(SUM(CASE WHEN l.ngayHetHan < CURDATE() THEN t.soLuongTon ELSE 0 END), 0) AS tonHetHan,
                    COALESCE(SUM(CASE WHEN l.ngayHetHan >= CURDATE() AND l.ngayHetHan <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN t.soLuongTon ELSE 0 END), 0) AS tonSapHetHan'
            . self::FROM_SQL . $where,
            $params
        );
        return $r ?: [];
    }

    /* -------------------- Các phương thức phục vụ Module 3 (Lưu kho & Điều chuyển) -------------------- */

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

    public static function increment(string $maLoHang, string $maViTri, float $soLuong): void
    {
        $row = self::lockRow($maLoHang, $maViTri);
        if ($row) {
            static::exec("UPDATE TonKho SET soLuongTon = soLuongTon + ? WHERE maTonKho = ?", [$soLuong, $row['maTonKho']]);
            return;
        }
        static::exec("INSERT INTO TonKho (maLoHang, maViTri, soLuongTon, soLuongDaGiu) VALUES (?, ?, ?, 0)", [$maLoHang, $maViTri, $soLuong]);
    }

    public static function decrement(string $maLoHang, string $maViTri, float $soLuong): void
    {
        $row = self::lockRow($maLoHang, $maViTri);
        if (!$row) {
            throw new \RuntimeException('Không tìm thấy tồn kho của lô tại vị trí nguồn.');
        }
        static::exec("UPDATE TonKho SET soLuongTon = soLuongTon - ? WHERE maTonKho = ?", [$soLuong, $row['maTonKho']]);
    }

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
