<?php
namespace App\Models;

use Core\Model;

/**
 * UC28 — Hàng bất thường (HangBatThuong): rách vỡ, hư hỏng, hết hạn.
 *
 * Nguồn phát sinh theo quy trình: UC09 (hàng không đạt khi nhập), UC27 (chênh lệch kiểm kê)
 * và các lô sắp/đã hết hạn. Trong hệ thống hiện tại phần "lô hết hạn" được quét theo yêu cầu
 * (nút "Quét lô hết hạn") thay vì tiến trình nền, và chỉ tạo bản ghi cho lô CHƯA có bản ghi
 * đang mở để tránh nhân đôi số lượng.
 */
class HangBatThuong extends Model
{
    public const LOAI = ['Rách vỡ', 'Hư hỏng', 'Hết hạn'];

    public const TT_CACH_LY    = 'Đang cách ly';
    public const TT_DANG_XU_LY = 'Đang xử lý';
    public const TT_DA_XU_LY   = 'Đã xử lý';

    private static function filterSql(array $f, array &$params, ?string $scope = null, ?string $staffOnly = null): string
    {
        $where = ' WHERE 1 = 1';
        if ($scope !== null) {
            if ($scope === '') {
                return ' WHERE 1 = 0';
            }
            $where .= ' AND k.maKho = ?';
            $params[] = $scope;
        }
        if ($staffOnly !== null) {
            if ($staffOnly === '') {
                return ' WHERE 1 = 0';
            }
            $where .= ' AND (hb.maNhanVienPhatHien = ? OR EXISTS (SELECT 1 FROM PhuongAnXuLy p WHERE p.maHangBatThuong = hb.maHangBatThuong AND p.maNhanVienDeXuat = ?))';
            array_push($params, $staffOnly, $staffOnly);
        }
        $q = trim((string)($f['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (hb.maHangBatThuong LIKE ? OR hb.maLoHang LIKE ? OR h.tenHangHoa LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        if (!empty($f['loaiBatThuong'])) {
            $where .= ' AND hb.loaiBatThuong = ?';
            $params[] = $f['loaiBatThuong'];
        }
        if (!empty($f['trangThai'])) {
            $where .= ' AND hb.trangThai = ?';
            $params[] = $f['trangThai'];
        }
        if (!empty($f['maKho'])) {
            $where .= ' AND k.maKho = ?';
            $params[] = $f['maKho'];
        }
        if (!empty($f['tuNgay'])) {
            $where .= ' AND hb.ngayPhatHien >= ?';
            $params[] = $f['tuNgay'] . ' 00:00:00';
        }
        if (!empty($f['denNgay'])) {
            $where .= ' AND hb.ngayPhatHien <= ?';
            $params[] = $f['denNgay'] . ' 23:59:59';
        }
        return $where;
    }

    private const FROM_SQL = ' FROM HangBatThuong hb
        JOIN LoHang l ON l.maLoHang = hb.maLoHang
        JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
        JOIN ViTriLuuTru v ON v.maViTri = hb.maViTri
        JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
        JOIN Kho k ON k.maKho = kv.maKho
        JOIN NhanVien nv ON nv.maNhanVien = hb.maNhanVienPhatHien ';

    /** Danh sách hàng bất thường kèm phương án mới nhất. Trả về [các dòng, tổng số]. */
    public static function search(array $f, int $page, int $perPage, ?string $scope = null, ?string $staffOnly = null): array
    {
        $params = [];
        $where = self::filterSql($f, $params, $scope, $staffOnly);
        $total = (int)(static::one('SELECT COUNT(*) AS c' . self::FROM_SQL . $where, $params)['c'] ?? 0);
        $rows = static::all(
            "SELECT hb.*, l.maHangHoa, l.ngayHetHan, h.tenHangHoa, h.donViTinhChuan,
                    v.tenViTri, kv.tenKhuVuc, k.maKho, k.tenKho, nv.hoTen AS tenNhanVienPhatHien,
                    (SELECT COUNT(*) FROM PhuongAnXuLy p WHERE p.maHangBatThuong = hb.maHangBatThuong) AS soPhuongAn,
                    (SELECT p.bienPhapXuLy FROM PhuongAnXuLy p WHERE p.maHangBatThuong = hb.maHangBatThuong
                     ORDER BY p.ngayDeXuat DESC, p.maPhuongAn DESC LIMIT 1) AS bienPhapGanNhat,
                    (SELECT p.trangThai FROM PhuongAnXuLy p WHERE p.maHangBatThuong = hb.maHangBatThuong
                     ORDER BY p.ngayDeXuat DESC, p.maPhuongAn DESC LIMIT 1) AS trangThaiPhuongAn"
            . self::FROM_SQL . $where . '
             ORDER BY hb.ngayPhatHien DESC, hb.maHangBatThuong DESC
             LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, max(0, ($page - 1) * $perPage)])
        );
        return [$rows, $total];
    }

    /** Kiểm tra một bản ghi hàng bất thường có thuộc nhân viên (người phát hiện hoặc người đề xuất PA) hay không. */
    public static function isOwnedByStaff(string $ma, string $staffId): bool
    {
        if ($staffId === '') {
            return false;
        }
        $r = static::one(
            'SELECT 1 FROM HangBatThuong hb
             WHERE hb.maHangBatThuong = ?
               AND (hb.maNhanVienPhatHien = ?
                    OR EXISTS (SELECT 1 FROM PhuongAnXuLy p WHERE p.maHangBatThuong = hb.maHangBatThuong AND p.maNhanVienDeXuat = ?))
             LIMIT 1',
            [$ma, $staffId, $staffId]
        );
        return (bool)$r;
    }

    /** Số bản ghi theo trạng thái + tổng số lượng đang chờ xử lý. */
    public static function totals(array $f = [], ?string $scope = null, ?string $staffOnly = null): array
    {
        $params = [];
        $where = self::filterSql($f, $params, $scope, $staffOnly);
        $r = static::one(
            'SELECT COUNT(*) AS soBanGhi,
                    SUM(CASE WHEN hb.trangThai IN (\'Đang cách ly\', \'Đang xử lý\') THEN 1 ELSE 0 END) AS choXuLy,
                    SUM(CASE WHEN hb.trangThai = \'Đang cách ly\' THEN 1 ELSE 0 END) AS dangCachLy,
                    SUM(CASE WHEN hb.trangThai = \'Đang xử lý\' THEN 1 ELSE 0 END) AS dangXuLy,
                    SUM(CASE WHEN hb.trangThai = \'Đã xử lý\' THEN 1 ELSE 0 END) AS daXuLy,
                    COALESCE(SUM(CASE WHEN hb.trangThai IN (\'Đang cách ly\', \'Đang xử lý\') THEN hb.soLuong ELSE 0 END), 0) AS soLuongChoXuLy'
            . self::FROM_SQL . $where,
            $params
        );
        return $r ?: [];
    }

    public static function find(string $ma): ?array
    {
        return static::one(
            'SELECT hb.*, l.maHangHoa, l.ngayHetHan, l.trangThaiQC, l.trangThaiVongDoi,
                    h.tenHangHoa, h.donViTinhChuan, v.tenViTri, kv.tenKhuVuc, k.maKho, k.tenKho,
                    nv.hoTen AS tenNhanVienPhatHien,
                    (SELECT COUNT(*) FROM TonKho t WHERE t.maLoHang = hb.maLoHang AND t.maViTri = hb.maViTri AND t.soLuongTon > 0) AS coDongTon
             FROM HangBatThuong hb
             JOIN LoHang l ON l.maLoHang = hb.maLoHang
             JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
             JOIN ViTriLuuTru v ON v.maViTri = hb.maViTri
             JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
             JOIN Kho k ON k.maKho = kv.maKho
             JOIN NhanVien nv ON nv.maNhanVien = hb.maNhanVienPhatHien
             WHERE hb.maHangBatThuong = ?',
            [$ma]
        );
    }

    /**
     * Lô còn tồn, đã đạt kiểm tra chất lượng, đang lưu kho nhưng sắp/đã hết hạn và
     * CHƯA có bản ghi hàng bất thường đang mở. Dùng để xem trước trước khi quét.
     */
    public static function expiredCandidates(int $soNgay = 30, ?string $scope = null): array
    {
        if ($scope === '') {
            return [];
        }
        $soNgay = max(0, min(365, $soNgay));
        $sql = "SELECT t.maLoHang, t.maViTri, t.soLuongTon, l.ngayHetHan,
                       DATEDIFF(l.ngayHetHan, CURDATE()) AS soNgayConLai,
                       h.maHangHoa, h.tenHangHoa, h.donViTinhChuan, v.tenViTri, kv.tenKhuVuc, k.maKho, k.tenKho
                FROM TonKho t
                JOIN LoHang l ON l.maLoHang = t.maLoHang
                JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
                JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
                JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
                JOIN Kho k ON k.maKho = kv.maKho
                WHERE t.soLuongTon > 0
                  AND l.trangThaiQC IN ('Đạt', 'Đã đạt chuẩn')
                  AND l.trangThaiVongDoi IN ('Đang lưu kho', 'Sắp hết hạn', 'Hết hạn')
                  AND l.ngayHetHan <= DATE_ADD(CURDATE(), INTERVAL " . $soNgay . " DAY)
                  AND NOT EXISTS (SELECT 1 FROM HangBatThuong hb
                                  WHERE hb.maLoHang = t.maLoHang AND hb.maViTri = t.maViTri
                                    AND hb.trangThai IN ('Đang cách ly', 'Đang xử lý'))";
        $params = [];
        if ($scope !== null) {
            $sql .= ' AND k.maKho = ?';
            $params[] = $scope;
        }
        $sql .= ' ORDER BY l.ngayHetHan, h.tenHangHoa, t.maLoHang';
        return static::all($sql, $params);
    }

    /**
     * Quét và tạo bản ghi 'Hết hạn' cho các lô đến hạn trong $soNgay ngày tới.
     * Số lượng lấy theo tồn thực tế tại (lô, vị trí) ở thời điểm quét.
     * Trả về số bản ghi đã tạo.
     */
    public static function detectExpired(string $maNhanVien, int $soNgay = 30, ?string $scope = null): int
    {
        return static::transaction(function () use ($maNhanVien, $soNgay, $scope) {
            $rows = self::expiredCandidates($soNgay, $scope);
            $created = 0;
            foreach ($rows as $r) {
                $soLuong = (float)$r['soLuongTon'];
                if ($soLuong <= 0) {
                    continue;
                }
                static::exec(
                    'INSERT INTO HangBatThuong (maHangBatThuong, maLoHang, maViTri, loaiBatThuong, soLuong, maNhanVienPhatHien, trangThai, moTa)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [MaChungTu::next('HangBatThuong'), $r['maLoHang'], $r['maViTri'], 'Hết hạn', $soLuong, $maNhanVien, self::TT_CACH_LY,
                     mb_substr('Quét tự động: lô hết hạn ' . date('d/m/Y', strtotime((string)$r['ngayHetHan'])) . ', còn tồn ' . $r['soLuongTon'], 0, 255)]
                );
                $created++;
            }
            return $created;
        });
    }

    /** Danh sách dòng tồn kho đang có hàng để chọn khi ghi nhận hàng bất thường thủ công (ưu tiên hạn sử dụng thấp nhất). */
    public static function stockCandidates(?string $scope = null): array
    {
        if ($scope === '') {
            return [];
        }
        $sql = "SELECT t.maTonKho, t.maLoHang, t.maViTri, t.soLuongTon, t.soLuongDaGiu,
                       (t.soLuongTon - t.soLuongDaGiu) AS khaDung, t.ngayCapNhatCuoi,
                       l.maHangHoa, l.soLoNhaCungCap, l.ngaySanXuat, l.ngayHetHan, l.trangThaiQC,
                       DATEDIFF(l.ngayHetHan, CURDATE()) AS soNgayConLai,
                       h.tenHangHoa, h.loaiHangHoa, h.donViTinhChuan,
                       v.tenViTri, kv.maKhuVuc, kv.tenKhuVuc, k.maKho, k.tenKho,
                       (SELECT GROUP_CONCAT(DISTINCT hb.loaiBatThuong SEPARATOR ', ')
                        FROM HangBatThuong hb
                        WHERE hb.maLoHang = t.maLoHang AND hb.maViTri = t.maViTri
                          AND hb.trangThai IN ('Đang cách ly', 'Đang xử lý')) AS hbtDangMo,
                       (SELECT hb2.trangThai
                        FROM HangBatThuong hb2
                        WHERE hb2.maLoHang = t.maLoHang AND hb2.maViTri = t.maViTri
                          AND hb2.trangThai IN ('Đang cách ly', 'Đang xử lý')
                        ORDER BY hb2.ngayPhatHien DESC, hb2.maHangBatThuong DESC
                        LIMIT 1) AS hbtTrangThai
                FROM TonKho t
                JOIN LoHang l ON l.maLoHang = t.maLoHang
                JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
                JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
                JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
                JOIN Kho k ON k.maKho = kv.maKho
                WHERE t.soLuongTon > 0";
        $params = [];
        if ($scope !== null) {
            $sql .= ' AND k.maKho = ?';
            $params[] = $scope;
        }
        $sql .= ' ORDER BY soNgayConLai ASC, l.ngayHetHan ASC, h.tenHangHoa ASC, t.maLoHang ASC';
        return static::all($sql, $params);
    }

    /** Kiểm tra hợp lệ và ghi nhận một dòng hàng bất thường trong transaction hiện tại. */
    private static function insertManualItem(string $maLo, string $maViTri, string $loai, int $soLuong, ?string $moTaDb, string $maNhanVien, ?string $scope): string
    {
        $ton = static::one(
            'SELECT t.*, kv.maKho
             FROM TonKho t
             JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
             JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
             WHERE t.maLoHang = ? AND t.maViTri = ? FOR UPDATE',
            [$maLo, $maViTri]
        );
        if (!$ton || (float)$ton['soLuongTon'] <= 0) {
            throw new \RuntimeException('Không tìm thấy tồn kho hợp lệ tại lô ' . $maLo . ' và vị trí đã chọn.');
        }
        if ($scope !== null && ($scope === '' || $ton['maKho'] !== $scope)) {
            throw new \RuntimeException('Bạn chỉ được ghi nhận hàng bất thường thuộc kho mình phụ trách.');
        }
        if ($soLuong > (float)$ton['soLuongTon'] + 0.000001) {
            throw new \RuntimeException('Số lượng bất thường của lô ' . $maLo . ' (' . $soLuong . ') không được vượt quá tồn kho hiện có (' . (float)$ton['soLuongTon'] . ').');
        }
        $dup = static::one(
            "SELECT maHangBatThuong FROM HangBatThuong
             WHERE maLoHang = ? AND maViTri = ? AND loaiBatThuong = ? AND trangThai IN ('Đang cách ly', 'Đang xử lý')
             LIMIT 1",
            [$maLo, $maViTri, $loai]
        );
        if ($dup) {
            throw new \RuntimeException('Lô ' . $maLo . ' tại vị trí này đã có bản ghi "' . $loai . '" đang mở (' . $dup['maHangBatThuong'] . ').');
        }
        $ma = MaChungTu::next('HangBatThuong');
        static::exec(
            'INSERT INTO HangBatThuong (maHangBatThuong, maLoHang, maViTri, loaiBatThuong, soLuong, maNhanVienPhatHien, trangThai, moTa)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$ma, $maLo, $maViTri, $loai, $soLuong, $maNhanVien, self::TT_CACH_LY, $moTaDb]
        );
        return $ma;
    }

    /** Ghi nhận hàng loạt (1 hoặc nhiều lô hàng bất thường cùng lúc) trong 1 giao dịch. Trả về mảng mã HBT. */
    public static function createManualBatch(array $d, string $maNhanVien, ?string $scope = null): array
    {
        $moTa = trim((string)($d['moTa'] ?? ''));
        if (mb_strlen($moTa) > 255) {
            throw new \RuntimeException('Mô tả chi tiết tối đa 255 ký tự.');
        }
        $moTaDb = $moTa !== '' ? $moTa : null;

        $rawItems = [];
        if (!empty($d['itemsJson'])) {
            $decoded = json_decode((string)$d['itemsJson'], true);
            if (is_array($decoded)) {
                $rawItems = $decoded;
            }
        }
        if (!$rawItems) {
            $pair = trim((string)($d['tonKhoKey'] ?? ''));
            if ($pair !== '' && strpos($pair, '|') !== false) {
                [$maLo, $maViTri] = explode('|', $pair, 2);
            } else {
                $maLo = trim((string)($d['maLoHang'] ?? ''));
                $maViTri = trim((string)($d['maViTri'] ?? ''));
            }
            $rawItems[] = [
                'maLoHang'      => $maLo,
                'maViTri'       => $maViTri,
                'loaiBatThuong' => $d['loaiBatThuong'] ?? '',
                'soLuong'       => $d['soLuong'] ?? '',
            ];
        }

        $parsed = [];
        $seenKeys = [];
        foreach ($rawItems as $row) {
            if (!is_array($row)) {
                continue;
            }
            $maLo = trim((string)($row['maLoHang'] ?? ''));
            $maViTri = trim((string)($row['maViTri'] ?? ''));
            $loai = trim((string)($row['loaiBatThuong'] ?? ''));
            $soLuong = parse_qty($row['soLuong'] ?? '', false);

            if ($maLo === '' || $maViTri === '') {
                throw new \RuntimeException('Vui lòng chọn lô hàng và vị trí lưu trữ.');
            }
            if (!in_array($loai, self::LOAI, true)) {
                throw new \RuntimeException('Loại bất thường không hợp lệ cho lô ' . $maLo . '.');
            }
            if ($soLuong === null) {
                throw new \RuntimeException('Số lượng hàng bất thường chỉ được nhập số nguyên lớn hơn 0.');
            }
            $dupKey = $maLo . '|' . $maViTri . '|' . $loai;
            if (isset($seenKeys[$dupKey])) {
                throw new \RuntimeException('Không thể chọn trùng cùng loại bất thường "' . $loai . '" trên lô ' . $maLo . '.');
            }
            $seenKeys[$dupKey] = true;
            $parsed[] = [
                'maLoHang'      => $maLo,
                'maViTri'       => $maViTri,
                'loaiBatThuong' => $loai,
                'soLuong'       => $soLuong,
            ];
        }
        if (!$parsed) {
            throw new \RuntimeException('Vui lòng chọn ít nhất một lô hàng để ghi nhận.');
        }

        return static::transaction(function () use ($parsed, $moTaDb, $maNhanVien, $scope) {
            $created = [];
            foreach ($parsed as $item) {
                $created[] = self::insertManualItem(
                    $item['maLoHang'],
                    $item['maViTri'],
                    $item['loaiBatThuong'],
                    $item['soLuong'],
                    $moTaDb,
                    $maNhanVien,
                    $scope
                );
            }
            return $created;
        });
    }

    /** Ghi nhận thủ công một bản ghi hàng bất thường (Rách vỡ / Hư hỏng / Hết hạn). Trả về mã HBT. */
    public static function createManual(array $d, string $maNhanVien, ?string $scope = null): string
    {
        $ids = self::createManualBatch($d, $maNhanVien, $scope);
        return $ids[0];
    }

    /** Bản ghi hàng bất thường gắn với một cặp (lô hàng, vị trí) để tra cứu từ màn hình tồn kho. */
    public static function byStockSlot(string $maLoHang, string $maViTri): array
    {
        return static::all(
            "SELECT maHangBatThuong, loaiBatThuong, soLuong, trangThai, ngayPhatHien
             FROM HangBatThuong
             WHERE maLoHang = ? AND maViTri = ?
             ORDER BY ngayPhatHien DESC",
            [$maLoHang, $maViTri]
        );
    }
}

