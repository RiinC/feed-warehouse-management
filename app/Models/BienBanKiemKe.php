<?php
namespace App\Models;

use Core\Model;

/**
 * UC24 — Biên bản kiểm kê (BienBanKiemKe).
 *
 * Nguồn dữ liệu chuẩn: CSDL gốc feed_wms.
 * Bảng: BienBanKiemKe
 * Khóa chính: maBienBanKiemKe (VARCHAR(14), định dạng ^BBKK[0-9]{10}$)
 * Ràng buộc CHECK:
 *   ck_BienBanKiemKe_2: loaiKiemKe in ('DinhKy','DotXuat','TheoMatHang')
 *   ck_BienBanKiemKe_3: trangThai in ('Chờ xử lý','Đang xử lý','Hoàn tất','Từ chối','Đã hủy')
 *   ck_BienBanKiemKe_4: ngayKetThuc is null or ngayKetThuc >= ngayBatDau
 *   ck_BienBanKiemKe_5: (trangThai = 'Hoàn tất' and ngayKetThuc is not null) or
 *                       (trangThai in ('Chờ xử lý','Đang xử lý','Từ chối','Đã hủy') and ngayKetThuc is null)
 */
class BienBanKiemKe extends Model
{
    public const TT_CHO     = 'Chờ xử lý';
    public const TT_DANG    = 'Đang xử lý';
    public const TT_XONG    = 'Hoàn tất';
    public const TT_HUY     = 'Đã hủy';
    public const TT_TU_CHOI = 'Từ chối';

    /** Lấy mã kho phụ trách của nhân viên (ưu tiên session, fallback tra bảng NhanVien). */
    private static function resolveUserKho(?string $staffId = null): string
    {
        $user = \Core\Auth::user();
        if (!empty($user['maKho'])) {
            return (string)$user['maKho'];
        }
        $sid = ($staffId !== null && $staffId !== '') ? $staffId : \Core\Auth::staffId();
        if ($sid === '') {
            return '';
        }
        $nv = NhanVien::find($sid);
        return (string)($nv['maKho'] ?? '');
    }

    /** Điều kiện lọc danh sách */
    private static function filterSql(array $f, array &$params, ?string $userStaffId = null, ?string $userRole = null): string
    {
        $where = ' WHERE 1 = 1';

        // Phân quyền theo vai trò
        if ($userRole === ROLE_MANAGER && !empty($userStaffId)) {
            $userKho = self::resolveUserKho($userStaffId);
            if ($userKho !== '') {
                $where .= ' AND b.maKho = ?';
                $params[] = $userKho;
            }
        } elseif (($userRole === ROLE_INVENTORY || $userRole === ROLE_WAREHOUSE) && !empty($userStaffId)) {
            $where .= ' AND b.maNhanVienTruongDoan = ?';
            $params[] = $userStaffId;
            if ($userRole === ROLE_WAREHOUSE) {
                $userKho = self::resolveUserKho($userStaffId);
                if ($userKho !== '') {
                    $where .= ' AND b.maKho = ?';
                    $params[] = $userKho;
                }
            }
        }

        $q = trim((string)($f['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (b.maBienBanKiemKe LIKE ? OR k.tenKho LIKE ? OR nv.hoTen LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }

        if (!empty($f['trangThai'])) {
            $stCode = inventory_status_code($f['trangThai']);
            $where .= ' AND b.trangThai = ?';
            $params[] = $stCode;
        }

        if (!empty($f['loaiKiemKe'])) {
            $typeCode = inventory_type_code($f['loaiKiemKe']);
            $where .= ' AND b.loaiKiemKe = ?';
            $params[] = $typeCode;
        }

        if (!empty($f['maKho'])) {
            $where .= ' AND b.maKho = ?';
            $params[] = $f['maKho'];
        }

        if (!empty($f['maKhuVuc'])) {
            $where .= ' AND b.maKhuVuc = ?';
            $params[] = $f['maKhuVuc'];
        }

        if (!empty($f['tuNgay'])) {
            $where .= ' AND b.ngayBatDau >= ?';
            $params[] = $f['tuNgay'] . ' 00:00:00';
        }

        if (!empty($f['denNgay'])) {
            $where .= ' AND b.ngayBatDau <= ?';
            $params[] = $f['denNgay'] . ' 23:59:59';
        }

        return $where;
    }

    /** Danh sách biên bản kèm tiến độ thực tế từ CSDL gốc */
    public static function search(array $f, int $page, int $perPage, ?string $userStaffId = null, ?string $userRole = null): array
    {
        $paramsCount = [];
        $whereCount = self::filterSql($f, $paramsCount, $userStaffId, $userRole);

        $totalSql = "SELECT COUNT(*) AS c
                     FROM BienBanKiemKe b
                     JOIN Kho k ON k.maKho = b.maKho
                     LEFT JOIN KhuVuc kv ON kv.maKhuVuc = b.maKhuVuc
                     JOIN NhanVien nv ON nv.maNhanVien = b.maNhanVienTruongDoan"
                     . $whereCount;

        $total = (int)(static::one($totalSql, $paramsCount)['c'] ?? 0);

        $paramsList = [];
        $whereList = self::filterSql($f, $paramsList, $userStaffId, $userRole);

        $rows = static::all(
            "SELECT b.*, k.tenKho, kv.tenKhuVuc, vt_b.tenViTri, nv.hoTen AS tenTruongDoan,
                    (SELECT COUNT(DISTINCT tk.maTonKho)
                     FROM TonKho tk
                     JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
                     JOIN KhuVuc kv_sub ON kv_sub.maKhuVuc = vt.maKhuVuc
                     WHERE kv_sub.maKho = b.maKho
                       AND (b.maKhuVuc IS NULL OR kv_sub.maKhuVuc = b.maKhuVuc)
                       AND (b.maViTri IS NULL OR tk.maViTri = b.maViTri)
                       AND tk.soLuongTon > 0) AS soDong,
                    (SELECT COUNT(DISTINCT vt.maViTri)
                     FROM ViTriLuuTru vt
                     JOIN KhuVuc kv_sub ON kv_sub.maKhuVuc = vt.maKhuVuc
                     WHERE kv_sub.maKho = b.maKho
                       AND (b.maKhuVuc IS NULL OR kv_sub.maKhuVuc = b.maKhuVuc)
                       AND (b.maViTri IS NULL OR vt.maViTri = b.maViTri)) AS soViTri,
                    (SELECT COUNT(*) FROM KetQuaKiemKe q WHERE q.maBienBanKiemKe = b.maBienBanKiemKe) AS soDaGhi,
                    (SELECT COUNT(DISTINCT q.maViTri) FROM KetQuaKiemKe q WHERE q.maBienBanKiemKe = b.maBienBanKiemKe) AS viTriDaKiem,
                    (SELECT COUNT(*) FROM KetQuaKiemKe q WHERE q.maBienBanKiemKe = b.maBienBanKiemKe AND ABS(q.chenhLech) > 0.000001) AS soChenhLech
             FROM BienBanKiemKe b
             JOIN Kho k ON k.maKho = b.maKho
             LEFT JOIN KhuVuc kv ON kv.maKhuVuc = b.maKhuVuc
             LEFT JOIN ViTriLuuTru vt_b ON vt_b.maViTri = b.maViTri
             JOIN NhanVien nv ON nv.maNhanVien = b.maNhanVienTruongDoan"
            . $whereList . '
             ORDER BY (CASE WHEN b.trangThai = "Đang xử lý" THEN 0 WHEN b.trangThai = "Chờ xử lý" THEN 1 ELSE 2 END),
                      b.ngayBatDau DESC, b.maBienBanKiemKe DESC
             LIMIT ? OFFSET ?',
            array_merge($paramsList, [$perPage, max(0, ($page - 1) * $perPage)])
        );

        return [$rows, $total];
    }

    /** Đếm số lượng biên bản theo trạng thái */
    public static function statusCounts(array $f = [], ?string $userStaffId = null, ?string $userRole = null): array
    {
        unset($f['trangThai']);
        $params = [];
        $where = self::filterSql($f, $params, $userStaffId, $userRole);

        $r = static::one(
            "SELECT COUNT(*) AS tong,
                    SUM(CASE WHEN b.trangThai = 'Đang xử lý' THEN 1 ELSE 0 END) AS dangKiem,
                    SUM(CASE WHEN b.trangThai = 'Chờ xử lý' THEN 1 ELSE 0 END) AS choKiem,
                    SUM(CASE WHEN b.trangThai = 'Từ chối' THEN 1 ELSE 0 END) AS biTraLai,
                    SUM(CASE WHEN b.trangThai = 'Hoàn tất' THEN 1 ELSE 0 END) AS hoanThanh,
                    SUM(CASE WHEN b.trangThai = 'Đã hủy' THEN 1 ELSE 0 END) AS daHuy
             FROM BienBanKiemKe b
             JOIN Kho k ON k.maKho = b.maKho
             LEFT JOIN KhuVuc kv ON kv.maKhuVuc = b.maKhuVuc
             JOIN NhanVien nv ON nv.maNhanVien = b.maNhanVienTruongDoan" . $where,
            $params
        );

        return $r ?: ['tong' => 0, 'dangKiem' => 0, 'choKiem' => 0, 'biTraLai' => 0, 'hoanThanh' => 0, 'daHuy' => 0];
    }

    /** Chi tiết một biên bản kiểm kê */
    public static function find(string $ma): ?array
    {
        return static::one(
            "SELECT b.*, k.tenKho, kv.tenKhuVuc, vt_b.tenViTri, nv.hoTen AS tenTruongDoan, nv.chucVu AS chucVuTruongDoan,
                    (SELECT COUNT(DISTINCT tk.maTonKho)
                     FROM TonKho tk
                     JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
                     JOIN KhuVuc kv_sub ON kv_sub.maKhuVuc = vt.maKhuVuc
                     WHERE kv_sub.maKho = b.maKho
                       AND (b.maKhuVuc IS NULL OR kv_sub.maKhuVuc = b.maKhuVuc)
                       AND (b.maViTri IS NULL OR tk.maViTri = b.maViTri)
                       AND tk.soLuongTon > 0) AS soDong,
                    (SELECT COUNT(DISTINCT vt.maViTri)
                     FROM ViTriLuuTru vt
                     JOIN KhuVuc kv_sub ON kv_sub.maKhuVuc = vt.maKhuVuc
                     WHERE kv_sub.maKho = b.maKho
                       AND (b.maKhuVuc IS NULL OR kv_sub.maKhuVuc = b.maKhuVuc)
                       AND (b.maViTri IS NULL OR vt.maViTri = b.maViTri)) AS soViTri,
                    (SELECT COUNT(*) FROM KetQuaKiemKe q WHERE q.maBienBanKiemKe = b.maBienBanKiemKe) AS soDaGhi,
                    (SELECT COUNT(DISTINCT q.maViTri) FROM KetQuaKiemKe q WHERE q.maBienBanKiemKe = b.maBienBanKiemKe) AS viTriDaKiem,
                    (SELECT COUNT(*) FROM KetQuaKiemKe q WHERE q.maBienBanKiemKe = b.maBienBanKiemKe AND ABS(q.chenhLech) > 0.000001) AS soChenhLech
             FROM BienBanKiemKe b
             JOIN Kho k ON k.maKho = b.maKho
             LEFT JOIN KhuVuc kv ON kv.maKhuVuc = b.maKhuVuc
             LEFT JOIN ViTriLuuTru vt_b ON vt_b.maViTri = b.maViTri
             JOIN NhanVien nv ON nv.maNhanVien = b.maNhanVienTruongDoan
             WHERE b.maBienBanKiemKe = ?",
            [$ma]
        );
    }

    /** Kiểm tra quyền truy cập của một nhân viên đối với biên bản */
    public static function canAccess(string $ma, string $staffId, string $role, ?string $staffKho = null): bool
    {
        if (in_array($role, [ROLE_ADMIN, ROLE_DIRECTOR], true)) {
            return true;
        }

        $bb = static::one('SELECT maKho, maNhanVienTruongDoan FROM BienBanKiemKe WHERE maBienBanKiemKe = ?', [$ma]);
        if (!$bb) {
            return false;
        }

        if ($role === ROLE_MANAGER) {
            return empty($staffKho) || $bb['maKho'] === $staffKho;
        }

        if ($role === ROLE_INVENTORY || $role === ROLE_WAREHOUSE) {
            return $bb['maNhanVienTruongDoan'] === $staffId;
        }

        return false;
    }

    /** Tiến độ kiểm kê chi tiết: số dòng, số vị trí, chênh lệch, đối chiếu */
    public static function progress(string $ma): array
    {
        $bb = static::one('SELECT maKho, maKhuVuc, maViTri FROM BienBanKiemKe WHERE maBienBanKiemKe = ?', [$ma]);
        if (!$bb) {
            return [
                'soViTri' => 0, 'viTriDaKiem' => 0, 'soDong' => 0, 'soDaGhi' => 0, 'soChenhLech' => 0,
                'soChuaDoiChieu' => 0, 'soChuaDeXuat' => 0, 'soTuChoi' => 0, 'soDaXuLy' => 0,
            ];
        }

        $soViTri = (int)(static::one(
            "SELECT COUNT(DISTINCT vt.maViTri) AS c
             FROM ViTriLuuTru vt
             JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
             WHERE kv.maKho = ? AND (? IS NULL OR kv.maKhuVuc = ?) AND (? IS NULL OR vt.maViTri = ?)",
            [$bb['maKho'], $bb['maKhuVuc'], $bb['maKhuVuc'], $bb['maViTri'], $bb['maViTri']]
        )['c'] ?? 0);

        $soDong = (int)(static::one(
            "SELECT COUNT(DISTINCT tk.maTonKho) AS c
             FROM TonKho tk
             JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
             JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
             WHERE kv.maKho = ? AND (? IS NULL OR kv.maKhuVuc = ?) AND (? IS NULL OR tk.maViTri = ?) AND tk.soLuongTon > 0",
            [$bb['maKho'], $bb['maKhuVuc'], $bb['maKhuVuc'], $bb['maViTri'], $bb['maViTri']]
        )['c'] ?? 0);

        $sql = "SELECT
            (SELECT COUNT(*) FROM KetQuaKiemKe WHERE maBienBanKiemKe = ?) AS soDaGhi,
            (SELECT COUNT(DISTINCT maViTri) FROM KetQuaKiemKe WHERE maBienBanKiemKe = ?) AS viTriDaKiem,
            (SELECT COUNT(*) FROM KetQuaKiemKe WHERE maBienBanKiemKe = ? AND ABS(chenhLech) > 0.000001) AS soChenhLech,
            (SELECT COUNT(*) FROM KetQuaKiemKe q WHERE q.maBienBanKiemKe = ? AND ABS(q.chenhLech) > 0.000001
                AND NOT EXISTS (SELECT 1 FROM ChenhLechKiemKe c WHERE c.maKetQuaKiemKe = q.maKetQuaKiemKe)) AS soChuaDoiChieu,
            (SELECT COUNT(*) FROM KetQuaKiemKe q JOIN ChenhLechKiemKe c ON c.maKetQuaKiemKe = q.maKetQuaKiemKe
                WHERE q.maBienBanKiemKe = ? AND NOT EXISTS (SELECT 1 FROM PhuongAnXuLy p WHERE p.maChenhLech = c.maChenhLech)) AS soChuaDeXuat,
            (SELECT COUNT(*) FROM KetQuaKiemKe q JOIN ChenhLechKiemKe c ON c.maKetQuaKiemKe = q.maKetQuaKiemKe
                JOIN PhuongAnXuLy p ON p.maChenhLech = c.maChenhLech
                WHERE q.maBienBanKiemKe = ? AND p.trangThai = 'Từ chối') AS soTuChoi,
            (SELECT COUNT(*) FROM KetQuaKiemKe q JOIN ChenhLechKiemKe c ON c.maKetQuaKiemKe = q.maKetQuaKiemKe
                JOIN PhuongAnXuLy p ON p.maChenhLech = c.maChenhLech
                WHERE q.maBienBanKiemKe = ? AND p.trangThai = 'Hoàn tất') AS soDaXuLy";

        $r = static::one($sql, array_fill(0, 7, $ma));
        return array_merge([
            'soViTri' => $soViTri,
            'soDong'  => $soDong,
        ], $r ?: [
            'soDaGhi' => 0, 'viTriDaKiem' => 0, 'soChenhLech' => 0,
            'soChuaDoiChieu' => 0, 'soChuaDeXuat' => 0, 'soTuChoi' => 0, 'soDaXuLy' => 0,
        ]);
    }

    /**
     * Danh sách toàn bộ các lô hàng trong phạm vi kiểm kê của biên bản.
     * Bao gồm cả các dòng đã kiểm kê và các dòng chưa kiểm kê (kèm chênh lệch).
     */
    public static function scopeItems(string $ma): array
    {
        $bb = self::find($ma);
        if (!$bb) {
            return [];
        }

        // 1. Các lô hàng đang tồn trong phạm vi kho / khu vực (từ TonKho)
        $sql = "SELECT tk.maTonKho, tk.maLoHang, tk.maViTri, tk.soLuongTon AS soLuongSoSachHienTai,
                       lh.ngaySanXuat, lh.ngayHetHan, lh.trangThaiQC,
                       hh.maHangHoa, hh.tenHangHoa, hh.donViTinhChuan, hh.loaiHangHoa,
                       vt.tenViTri, kv.maKhuVuc, kv.tenKhuVuc,
                       kq.maKetQuaKiemKe,
                       IFNULL(kq.soLuongSoSach, tk.soLuongTon) AS soLuongSoSach,
                       kq.soLuongThucTe, kq.chenhLech,
                       kq.maNhanVienKiemKe, kq.ghiChu AS ghiChuKQ,
                       nv.hoTen AS tenNhanVienKiemKe
                FROM BienBanKiemKe b
                JOIN Kho k ON k.maKho = b.maKho
                JOIN KhuVuc kv ON kv.maKho = k.maKho AND (b.maKhuVuc IS NULL OR kv.maKhuVuc = b.maKhuVuc)
                JOIN ViTriLuuTru vt ON vt.maKhuVuc = kv.maKhuVuc AND (b.maViTri IS NULL OR vt.maViTri = b.maViTri)
                JOIN TonKho tk ON tk.maViTri = vt.maViTri
                JOIN LoHang lh ON lh.maLoHang = tk.maLoHang
                JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa
                LEFT JOIN KetQuaKiemKe kq ON kq.maBienBanKiemKe = b.maBienBanKiemKe
                                         AND kq.maLoHang = tk.maLoHang
                                         AND kq.maViTri = tk.maViTri
                LEFT JOIN NhanVien nv ON nv.maNhanVien = kq.maNhanVienKiemKe
                WHERE b.maBienBanKiemKe = ?
                  AND (tk.soLuongTon > 0 OR kq.maKetQuaKiemKe IS NOT NULL)
                ORDER BY kv.tenKhuVuc, vt.tenViTri, hh.tenHangHoa, lh.maLoHang";

        $rows = static::all($sql, [$ma]);

        // 2. Các lô hàng phát hiện trong kết quả kiểm kê nhưng không có trong TonKho (hàng ngoài sổ / thừa vị trí)
        $sqlExtra = "SELECT NULL AS maTonKho, kq.maLoHang, kq.maViTri, 0 AS soLuongSoSachHienTai,
                            lh.ngaySanXuat, lh.ngayHetHan, lh.trangThaiQC,
                            hh.maHangHoa, hh.tenHangHoa, hh.donViTinhChuan, hh.loaiHangHoa,
                            vt.tenViTri, kv.maKhuVuc, kv.tenKhuVuc,
                            kq.maKetQuaKiemKe, kq.soLuongSoSach, kq.soLuongThucTe, kq.chenhLech,
                            kq.maNhanVienKiemKe, kq.ghiChu AS ghiChuKQ,
                            nv.hoTen AS tenNhanVienKiemKe
                     FROM KetQuaKiemKe kq
                     JOIN BienBanKiemKe b ON b.maBienBanKiemKe = kq.maBienBanKiemKe
                     JOIN LoHang lh ON lh.maLoHang = kq.maLoHang
                     JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa
                     JOIN ViTriLuuTru vt ON vt.maViTri = kq.maViTri
                     JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
                     LEFT JOIN NhanVien nv ON nv.maNhanVien = kq.maNhanVienKiemKe
                     WHERE kq.maBienBanKiemKe = ?
                       AND NOT EXISTS (
                           SELECT 1 FROM TonKho tk WHERE tk.maLoHang = kq.maLoHang AND tk.maViTri = kq.maViTri
                       )";

        $extras = static::all($sqlExtra, [$ma]);
        return array_merge($rows, $extras);
    }

    /**
     * UC24 — Lập biên bản kiểm kê mới.
     * Tuân thủ triệt để CSDL gốc feed_wms:
     * - Mã sinh: BBKK + yymmdd + 4 số thứ tự (14 ký tự, regex ^BBKK[0-9]{10}$)
     * - loaiKiemKe: 'DinhKy', 'DotXuat', 'TheoMatHang'
     * - trangThai: 'Đang xử lý'
     * - ngayBatDau: NOW()
     * - ngayKetThuc: NULL
     * Không thêm bảng hoặc cột ngoài feed_wms.
     */
    public static function create(array $d, string $maNhanVienLap): string
    {
        $maKho = trim((string)($d['maKho'] ?? ''));
        $maKhuVuc = trim((string)($d['maKhuVuc'] ?? ''));
        $maViTri = trim((string)($d['maViTri'] ?? ''));
        $rawLoai = trim((string)($d['loaiKiemKe'] ?? ''));
        $maTruongDoan = trim((string)($d['maNhanVienTruongDoan'] ?? ''));
        $rawGhiChu = trim((string)($d['ghiChu'] ?? ''));

        if ($maKho === '') {
            throw new \RuntimeException('Vui lòng chọn kho cần kiểm kê.');
        }

        // Quản lý kho chỉ được lập biên bản cho kho mình phụ trách
        $userKhoScoped = self::resolveUserKho($maNhanVienLap);
        if (\Core\Auth::role() === ROLE_MANAGER && $userKhoScoped !== '' && $userKhoScoped !== $maKho) {
            throw new \RuntimeException('Bạn chỉ có quyền lập biên bản kiểm kê cho kho mình phụ trách (' . $userKhoScoped . ').');
        }

        // Chuyển loại kiểm kê về đúng mã DB
        $loai = inventory_type_code($rawLoai);

        // Mặc định trưởng đoàn là người lập nếu không chọn
        if ($maTruongDoan === '') {
            $maTruongDoan = $maNhanVienLap;
        }

        // Trường ghi chú: chuỗi tự do tối đa 255 ký tự
        $ghiChu = ($rawGhiChu !== '') ? mb_substr($rawGhiChu, 0, 255) : null;

        return static::transaction(function () use ($maKho, $maKhuVuc, $maViTri, $loai, $maTruongDoan, $ghiChu) {
            // 1. Khóa dòng kho tương ứng bằng SELECT ... FOR UPDATE để chống race condition khi nhiều quản lý lập biên bản
            $kho = static::one('SELECT maKho, tenKho FROM Kho WHERE maKho = ? FOR UPDATE', [$maKho]);
            if (!$kho) {
                throw new \RuntimeException('Kho không tồn tại trong hệ thống.');
            }
            if ($maKho === 'K050001' || !isset(DanhMuc::khoKiemKe()[$maKho])) {
                throw new \RuntimeException('Không thể lập biên bản kiểm kê cho Trung tâm phân phối.');
            }

            // 2. Kiểm tra khu vực nếu có chọn
            $tenKhuVuc = null;
            if ($maKhuVuc !== '') {
                $kv = static::one('SELECT maKho, tenKhuVuc FROM KhuVuc WHERE maKhuVuc = ?', [$maKhuVuc]);
                if (!$kv || $kv['maKho'] !== $maKho) {
                    throw new \RuntimeException('Khu vực không thuộc kho đã chọn.');
                }
                $tenKhuVuc = $kv['tenKhuVuc'];
            }

            // 3. Kiểm tra vị trí (nếu có chọn) & đảm bảo phạm vi đã chọn có lô hàng tồn kho hợp lệ
            $scopeSql = "SELECT COUNT(*) AS c
                         FROM TonKho tk
                         JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
                         JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
                         WHERE kv.maKho = ? AND tk.soLuongTon > 0";
            $scopeParams = [$maKho];
            if ($maKhuVuc !== '') {
                $scopeSql .= ' AND kv.maKhuVuc = ?';
                $scopeParams[] = $maKhuVuc;
            }

            if ($maViTri !== '') {
                $vtCheck = static::one(
                    'SELECT vt.maViTri, kv.maKho, kv.maKhuVuc
                     FROM ViTriLuuTru vt
                     JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
                     WHERE vt.maViTri = ?',
                    [$maViTri]
                );
                if (!$vtCheck || $vtCheck['maKho'] !== $maKho) {
                    throw new \RuntimeException('Vị trí lưu trữ không thuộc kho đã chọn.');
                }
                if ($maKhuVuc !== '' && $vtCheck['maKhuVuc'] !== $maKhuVuc) {
                    throw new \RuntimeException('Vị trí lưu trữ không thuộc khu vực đã chọn.');
                }
                if ($maKhuVuc === '') {
                    $maKhuVuc = (string)$vtCheck['maKhuVuc'];
                }

                $bbVtConflict = static::one(
                    "SELECT b.maBienBanKiemKe, vt.tenViTri
                     FROM BienBanKiemKe b
                     JOIN ViTriLuuTru vt ON vt.maViTri = b.maViTri
                     WHERE b.maViTri = ? AND b.trangThai IN ('Chờ xử lý', 'Đang xử lý', 'Từ chối')
                     LIMIT 1",
                    [$maViTri]
                );
                if ($bbVtConflict) {
                    throw new \RuntimeException("Vị trí {$bbVtConflict['tenViTri']} đang có biên bản {$bbVtConflict['maBienBanKiemKe']} chưa hoàn tất.");
                }

                $vtConflict = static::one(
                    "SELECT b.maBienBanKiemKe, vt.tenViTri
                     FROM KetQuaKiemKe kq
                     JOIN BienBanKiemKe b ON b.maBienBanKiemKe = kq.maBienBanKiemKe
                     JOIN ViTriLuuTru vt ON vt.maViTri = kq.maViTri
                     WHERE kq.maViTri = ? AND b.trangThai IN ('Chờ xử lý', 'Đang xử lý', 'Từ chối')
                     LIMIT 1",
                    [$maViTri]
                );
                if ($vtConflict) {
                    throw new \RuntimeException("Vị trí {$vtConflict['tenViTri']} đang có biên bản {$vtConflict['maBienBanKiemKe']} chưa hoàn tất.");
                }

                $scopeSql .= ' AND vt.maViTri = ?';
                $scopeParams[] = $maViTri;
            }

            $scopeLotCount = (int)(static::one($scopeSql, $scopeParams)['c'] ?? 0);
            if ($scopeLotCount <= 0) {
                throw new \RuntimeException('Phạm vi đã chọn hiện không có lô hàng tồn kho nào để kiểm kê.');
            }

            // 4. Kiểm tra trưởng đoàn
            if (!static::one('SELECT maNhanVien FROM NhanVien WHERE maNhanVien = ?', [$maTruongDoan])) {
                throw new \RuntimeException('Nhân viên trưởng đoàn không hợp lệ.');
            }

            // 5. Sinh mã chứng từ chuẩn feed_wms
            $ma = MaChungTu::next('BienBanKiemKe');

            // 6. Lưu biên bản kiểm kê kèm phạm vi vị trí (nếu chọn cụ thể)
            static::exec(
                'INSERT INTO BienBanKiemKe (maBienBanKiemKe, maKho, maKhuVuc, maViTri, maNhanVienTruongDoan, ngayBatDau, ngayKetThuc, loaiKiemKe, trangThai, ghiChu)
                 VALUES (?, ?, ?, ?, ?, NOW(), NULL, ?, "Chờ xử lý", ?)',
                [
                    $ma,
                    $maKho,
                    $maKhuVuc !== '' ? $maKhuVuc : null,
                    $maViTri !== '' ? $maViTri : null,
                    $maTruongDoan,
                    $loai,
                    $ghiChu,
                ]
            );

            return $ma;
        });
    }

    /**
     * Cập nhật thông tin biên bản kiểm kê (khu vực, trưởng đoàn).
     * Chỉ áp dụng khi biên bản còn ở trạng thái 'Chờ xử lý' và CHƯA có kết quả kiểm đếm.
     */
    public static function update(string $ma, array $d): void
    {
        $maKhuVuc = trim((string)($d['maKhuVuc'] ?? ''));
        $maViTri = trim((string)($d['maViTri'] ?? ''));
        $maTruongDoan = trim((string)($d['maNhanVienTruongDoan'] ?? ''));

        static::transaction(function () use ($ma, $maKhuVuc, $maViTri, $maTruongDoan, $d) {
            $bb = static::one('SELECT * FROM BienBanKiemKe WHERE maBienBanKiemKe = ? FOR UPDATE', [$ma]);
            if (!$bb) {
                throw new \RuntimeException('Không tìm thấy biên bản kiểm kê cần sửa.');
            }
            if ($bb['trangThai'] !== 'Chờ xử lý') {
                throw new \RuntimeException('Chỉ có thể chỉnh sửa thông tin khi biên bản ở trạng thái "Chờ kiểm kê".');
            }

            // Kiểm tra nếu đã phát sinh kết quả kiểm đếm thì khóa cứng không cho đổi
            $soKQ = (int)(static::one('SELECT COUNT(*) AS c FROM KetQuaKiemKe WHERE maBienBanKiemKe = ?', [$ma])['c'] ?? 0);
            if ($soKQ > 0) {
                throw new \RuntimeException('Biên bản đã phát sinh kết quả kiểm đếm, không thể chỉnh sửa phạm vi hoặc người phụ trách.');
            }

            // Kiểm tra khu vực & vị trí nếu thay đổi
            $newKhuVuc = ($maKhuVuc !== '') ? $maKhuVuc : null;
            $newViTri  = ($maViTri !== '') ? $maViTri : null;
            if ($newKhuVuc !== null) {
                $kv = static::one('SELECT maKho FROM KhuVuc WHERE maKhuVuc = ?', [$newKhuVuc]);
                if (!$kv || $kv['maKho'] !== $bb['maKho']) {
                    throw new \RuntimeException('Khu vực không thuộc kho của biên bản.');
                }
            }
            if ($newViTri !== null) {
                $vtCheck = static::one(
                    'SELECT vt.maViTri, kv.maKho, kv.maKhuVuc
                     FROM ViTriLuuTru vt
                     JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
                     WHERE vt.maViTri = ?',
                    [$newViTri]
                );
                if (!$vtCheck || $vtCheck['maKho'] !== $bb['maKho']) {
                    throw new \RuntimeException('Vị trí lưu trữ không thuộc kho của biên bản.');
                }
                if ($newKhuVuc !== null && $vtCheck['maKhuVuc'] !== $newKhuVuc) {
                    throw new \RuntimeException('Vị trí lưu trữ không thuộc khu vực đã chọn.');
                }
                if ($newKhuVuc === null) {
                    $newKhuVuc = (string)$vtCheck['maKhuVuc'];
                }
            }

            $scopeLotsCount = (int)(static::one(
                "SELECT COUNT(*) AS c
                 FROM TonKho tk
                 JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
                 JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
                 WHERE kv.maKho = ?
                   AND (? IS NULL OR kv.maKhuVuc = ?)
                   AND (? IS NULL OR vt.maViTri = ?)
                   AND tk.soLuongTon > 0",
                [$bb['maKho'], $newKhuVuc, $newKhuVuc, $newViTri, $newViTri]
            )['c'] ?? 0);
            if ($scopeLotsCount <= 0) {
                throw new \RuntimeException('Phạm vi đã chọn hiện không có lô hàng tồn kho nào để kiểm kê.');
            }

            // Kiểm tra trùng phạm vi nếu thay đổi khu vực hoặc vị trí
            if ($newKhuVuc !== $bb['maKhuVuc'] || $newViTri !== ($bb['maViTri'] ?? null)) {
                $conflictSql = "SELECT b.maBienBanKiemKe
                                FROM BienBanKiemKe b
                                WHERE b.maKho = ?
                                  AND b.maBienBanKiemKe != ?
                                  AND b.trangThai IN ('Chờ xử lý', 'Đang xử lý', 'Từ chối')
                                  AND (? IS NULL OR b.maKhuVuc IS NULL OR b.maKhuVuc = ?)
                                  AND (? IS NULL OR b.maViTri IS NULL OR b.maViTri = ?)
                                LIMIT 1";
                $conflict = static::one($conflictSql, [$bb['maKho'], $ma, $newKhuVuc, $newKhuVuc, $newViTri, $newViTri]);
                if ($conflict) {
                    throw new \RuntimeException("Phạm vi mới trùng với biên bản {$conflict['maBienBanKiemKe']} đang hoạt động.");
                }
            }

            if ($maTruongDoan !== '') {
                if (!static::one('SELECT maNhanVien FROM NhanVien WHERE maNhanVien = ?', [$maTruongDoan])) {
                    throw new \RuntimeException('Nhân viên trưởng đoàn không hợp lệ.');
                }
            } else {
                $maTruongDoan = $bb['maNhanVienTruongDoan'];
            }

            $rawGhiChu = trim((string)($d['ghiChu'] ?? ''));
            $ghiChu = ($rawGhiChu !== '') ? mb_substr($rawGhiChu, 0, 255) : null;

            static::exec(
                'UPDATE BienBanKiemKe SET maKhuVuc = ?, maViTri = ?, maNhanVienTruongDoan = ?, ghiChu = ? WHERE maBienBanKiemKe = ?',
                [
                    $newKhuVuc,
                    $newViTri,
                    $maTruongDoan,
                    $ghiChu,
                    $ma
                ]
            );
        });
    }

    /**
     * Nhân viên gửi biên bản cho Quản lý kho duyệt sau khi đã đếm đủ 100%.
     * Chuyển trạng thái từ 'Chờ xử lý' hoặc 'Từ chối' sang 'Đang xử lý'.
     */
    public static function submitForApproval(string $ma, string $staffId): void
    {
        static::transaction(function () use ($ma, $staffId) {
            $bb = static::one('SELECT * FROM BienBanKiemKe WHERE maBienBanKiemKe = ? FOR UPDATE', [$ma]);
            if (!$bb) {
                throw new \RuntimeException('Không tìm thấy biên bản kiểm kê.');
            }
            if (\Core\Auth::role() !== ROLE_ADMIN && $bb['maNhanVienTruongDoan'] !== $staffId) {
                throw new \RuntimeException('Chỉ nhân viên được phân công phụ trách mới có quyền gửi quản lý duyệt.');
            }
            if (!in_array($bb['trangThai'], ['Chờ xử lý', 'Từ chối'], true)) {
                throw new \RuntimeException('Biên bản đã ở trạng thái "' . inventory_status_label($bb['trangThai']) . '", không thể gửi duyệt lại.');
            }

            $items = self::scopeItems($ma);
            if (empty($items)) {
                throw new \RuntimeException('Phạm vi kiểm kê không có lô hàng nào. Không thể gửi duyệt.');
            }

            $uncounted = [];
            foreach ($items as $it) {
                if ($it['maKetQuaKiemKe'] === null || $it['soLuongThucTe'] === null) {
                    $uncounted[] = 'Lô ' . $it['maLoHang'] . ' (' . $it['tenHangHoa'] . ') tại ' . $it['tenViTri'];
                }
            }

            if (!empty($uncounted)) {
                $totalUncounted = count($uncounted);
                $sample = implode('; ', array_slice($uncounted, 0, 3));
                throw new \RuntimeException("Còn {$totalUncounted} dòng chưa được kiểm đếm ({$sample}" . ($totalUncounted > 3 ? '...' : '') . "). Vui lòng nhập đủ 100% trước khi gửi quản lý duyệt.");
            }

            // Chuyển sang 'Đang xử lý', giữ ngayKetThuc = NULL
            static::exec(
                'UPDATE BienBanKiemKe SET trangThai = "Đang xử lý", ngayKetThuc = NULL WHERE maBienBanKiemKe = ? AND trangThai IN ("Chờ xử lý", "Từ chối")',
                [$ma]
            );

            // Gửi thông báo cho Quản lý kho
            $ql = DanhMuc::quanLyKho($bb['maKho']);
            if ($ql && !empty($ql['maNhanVien'])) {
                $staffName = \Core\Auth::user()['hoTen'] ?? 'Nhân viên';
                ThongBao::notifyUser(
                    $ql['maNhanVien'],
                    'Yêu cầu duyệt biên bản kiểm kê ' . $ma,
                    "Nhân viên {$staffName} đã hoàn tất kiểm đếm và gửi biên bản {$ma} (" . count($items) . " lô). Vui lòng kiểm tra và xác nhận.",
                    'kiem-ke/detail?ma=' . rawurlencode($ma),
                    'info'
                );
            }
        });
    }

    /**
     * Quản lý kho trả lại kết quả kiểm đếm cho nhân viên kiểm tra lại.
     * Chuyển trạng thái từ 'Đang xử lý' sang 'Từ chối', giữ nguyên ngayKetThuc = NULL.
     */
    public static function returnResult(string $ma, string $managerId, string $lyDo = ''): void
    {
        static::transaction(function () use ($ma, $lyDo) {
            $bb = static::one('SELECT * FROM BienBanKiemKe WHERE maBienBanKiemKe = ? FOR UPDATE', [$ma]);
            if (!$bb) {
                throw new \RuntimeException('Không tìm thấy biên bản kiểm kê.');
            }
            $userKho = self::resolveUserKho();
            if (\Core\Auth::role() !== ROLE_ADMIN && $userKho !== '' && $bb['maKho'] !== $userKho) {
                throw new \RuntimeException('Bạn chỉ được thao tác trên biên bản thuộc kho mình phụ trách.');
            }
            if ($bb['trangThai'] !== 'Đang xử lý') {
                throw new \RuntimeException('Chỉ có thể từ chối biên bản khi đang ở trạng thái "Chờ xác nhận".');
            }

            static::exec(
                'UPDATE BienBanKiemKe SET trangThai = "Từ chối", ngayKetThuc = NULL WHERE maBienBanKiemKe = ? AND trangThai = "Đang xử lý"',
                [$ma]
            );

            if (!empty($bb['maNhanVienTruongDoan'])) {
                $reasonMsg = $lyDo !== '' ? " Lý do: {$lyDo}." : '';
                ThongBao::notifyUser(
                    $bb['maNhanVienTruongDoan'],
                    'Biên bản kiểm kê ' . $ma . ' bị trả lại',
                    "Quản lý kho đã trả lại biên bản {$ma}.{$reasonMsg} Vui lòng kiểm tra lại số liệu kiểm đếm và gửi lại duyệt.",
                    'kiem-ke/record-result?ma=' . rawurlencode($ma),
                    'warning'
                );
            }
        });
    }

    /**
     * Hủy biên bản kiểm kê (chỉ khi đang ở 'Chờ xử lý' và chưa có kết quả kiểm kê).
     */
    public static function cancel(string $ma, string $lyDo = '', ?string $staffId = null): void
    {
        static::transaction(function () use ($ma) {
            $bb = static::one('SELECT * FROM BienBanKiemKe WHERE maBienBanKiemKe = ? FOR UPDATE', [$ma]);
            if (!$bb) {
                throw new \RuntimeException('Không tìm thấy biên bản kiểm kê cần hủy.');
            }
            $userKho = self::resolveUserKho();
            if (\Core\Auth::role() !== ROLE_ADMIN && $userKho !== '' && $bb['maKho'] !== $userKho) {
                throw new \RuntimeException('Bạn chỉ được thao tác trên biên bản thuộc kho mình phụ trách.');
            }
            if ($bb['trangThai'] !== 'Chờ xử lý') {
                throw new \RuntimeException('Chỉ có thể hủy biên bản khi đang ở trạng thái "Chờ kiểm kê".');
            }

            $soKQ = (int)(static::one('SELECT COUNT(*) AS c FROM KetQuaKiemKe WHERE maBienBanKiemKe = ?', [$ma])['c'] ?? 0);
            if ($soKQ > 0) {
                throw new \RuntimeException('Biên bản đã ghi nhận ' . $soKQ . ' kết quả kiểm đếm, không thể hủy.');
            }

            // Theo ràng buộc ck_BienBanKiemKe_5: khi 'Đã hủy', ngayKetThuc bắt buộc phải là NULL!
            static::exec(
                'UPDATE BienBanKiemKe SET trangThai = "Đã hủy", ngayKetThuc = NULL WHERE maBienBanKiemKe = ? AND trangThai = "Chờ xử lý"',
                [$ma]
            );
        });
    }

    /**
     * Chốt hoàn tất biên bản kiểm kê khi quản lý chấp nhận kết quả.
     * Chuyển trạng thái từ 'Đang xử lý' sang 'Hoàn tất', cập nhật ngayKetThuc = NOW().
     */
    public static function close(string $ma): void
    {
        static::transaction(function () use ($ma) {
            $bb = static::one('SELECT * FROM BienBanKiemKe WHERE maBienBanKiemKe = ? FOR UPDATE', [$ma]);
            if (!$bb) {
                throw new \RuntimeException('Không tìm thấy biên bản kiểm kê.');
            }
            $userKho = self::resolveUserKho();
            if (\Core\Auth::role() !== ROLE_ADMIN && $userKho !== '' && $bb['maKho'] !== $userKho) {
                throw new \RuntimeException('Bạn chỉ được thao tác trên biên bản thuộc kho mình phụ trách.');
            }
            if ($bb['trangThai'] !== 'Đang xử lý') {
                throw new \RuntimeException('Chỉ có thể xác nhận hoàn thành khi biên bản đang ở trạng thái "Chờ xác nhận".');
            }

            // Theo ck_BienBanKiemKe_5: khi 'Hoàn tất', ngayKetThuc bắt buộc phải NOT NULL!
            static::exec(
                'UPDATE BienBanKiemKe SET trangThai = "Hoàn tất", ngayKetThuc = NOW() WHERE maBienBanKiemKe = ? AND trangThai = "Đang xử lý"',
                [$ma]
            );

            // Gửi thông báo cho nhân viên kiểm kê được phân công
            if (!empty($bb['maNhanVienTruongDoan'])) {
                ThongBao::notifyUser(
                    $bb['maNhanVienTruongDoan'],
                    'Biên bản kiểm kê ' . $ma . ' đã được hoàn thành',
                    'Quản lý kho đã xác nhận hoàn tất kết quả kiểm đếm của biên bản ' . $ma . '.',
                    'kiem-ke/detail?ma=' . rawurlencode($ma),
                    'success'
                );
            }
        });
    }

    /** Lấy danh sách các biên bản đang kiểm kê để chọn nhanh */
    public static function openOptions(): array
    {
        $rows = static::all(
            "SELECT b.maBienBanKiemKe, b.maKho, k.tenKho, b.ngayBatDau, b.loaiKiemKe
             FROM BienBanKiemKe b JOIN Kho k ON k.maKho = b.maKho
             WHERE b.trangThai IN ('Đang xử lý', 'Chờ xử lý')
             ORDER BY b.ngayBatDau DESC"
        );
        $out = [];
        foreach ($rows as $r) {
            $loai = inventory_type_label($r['loaiKiemKe']);
            $out[$r['maBienBanKiemKe']] = $r['maBienBanKiemKe'] . ' — ' . $r['tenKho'] . ' (' . $loai . ') — ' . date('d/m/Y H:i', strtotime((string)$r['ngayBatDau']));
        }
        return $out;
    }
}
