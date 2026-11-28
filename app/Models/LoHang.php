<?php
namespace App\Models;

use Core\Model;

class LoHang extends Model
{
    // =================================================
    // UC07 - TRA CỨU LÔ HÀNG CẦN KIỂM TRA
    // =================================================

    private static function cauTruyVanUC07(): string
    {
        return "
            SELECT
                lh.maLoHang,
                hh.tenHangHoa AS tenLoHang,
                hh.loaiHangHoa,
                lh.soLuongBanDau AS soLuongTiepNhan,
                hh.donViTinhChuan AS donViTinh,
                COALESCE(ptn.ngayTiepNhan, lh.ngayTao) AS thoiGianTiepNhan,
                lh.trangThaiVongDoi AS trangThaiLo,
                lh.trangThaiQC AS trangThaiQC,
                CASE
                    WHEN COALESCE(bb.soBienBan, 0) > 0
                        THEN 'Đã kiểm tra, chờ xác nhận'
                    ELSE lh.trangThaiQC
                END AS trangThaiChatLuong,
                COALESCE(bb.soBienBan, 0) AS soBienBanChoDuyet,
                CASE WHEN COALESCE(bb.soBienBan, 0) > 0 THEN 1 ELSE 0 END AS coBienBanChoDuyet,
                CASE WHEN COALESCE(bb.soBienBan, 0) = 0 THEN 1 ELSE 0 END AS coTheKiemTraMoi,
                ptn.ghiChu
            FROM LoHang lh
            INNER JOIN HangHoa hh ON lh.maHangHoa = hh.maHangHoa
            LEFT JOIN PhieuTiepNhan ptn ON lh.maPhieuTiepNhan = ptn.maPhieuTiepNhan
            LEFT JOIN (
                SELECT maLoHang, COUNT(*) AS soBienBan
                FROM BienBanKiemDinhChatLuong
                WHERE trangThai = 'Chờ duyệt'
                GROUP BY maLoHang
            ) bb ON bb.maLoHang = lh.maLoHang
            WHERE lh.trangThaiQC = 'Chờ kiểm tra'
                AND hh.loaiHangHoa IN ('Nguyên liệu', 'Thành phẩm')
        ";
    }

    private static function boLocUC07(string $maLoHang, string $tuNgay, string $denNgay): array
    {
        $sql = static::cauTruyVanUC07();
        $params = [];
        if ($maLoHang !== '') {
            $sql .= ' AND lh.maLoHang = ?';
            $params[] = $maLoHang;
        }
        if ($tuNgay !== '') {
            $sql .= ' AND COALESCE(ptn.ngayTiepNhan, lh.ngayTao) >= ?';
            $params[] = $tuNgay . ' 00:00:00';
        }
        if ($denNgay !== '') {
            $sql .= ' AND COALESCE(ptn.ngayTiepNhan, lh.ngayTao) < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $denNgay;
        }
        return [$sql, $params];
    }

    public static function traCuuLoHangCanKiemTra(string $maLoHang = '', string $tuNgay = '',
        string $denNgay = '', int $trang = 1, ?int $soLuongMoiTrang = null): array
    {
        [$sql, $params] = static::boLocUC07($maLoHang, $tuNgay, $denNgay);
        $sql .= ' ORDER BY thoiGianTiepNhan DESC, lh.maLoHang DESC';
        if ($soLuongMoiTrang !== null) {
            $trang = max(1, $trang);
            $soLuongMoiTrang = max(1, min(100, $soLuongMoiTrang));
            $offset = ($trang - 1) * $soLuongMoiTrang;
            $sql .= " LIMIT $soLuongMoiTrang OFFSET $offset";
        }
        return static::all($sql, $params);
    }

    public static function demLoHangCanKiemTra(string $maLoHang = '', string $tuNgay = '', string $denNgay = ''): int
    {
        [$sql, $params] = static::boLocUC07($maLoHang, $tuNgay, $denNgay);
        $rows = static::all("SELECT COUNT(*) AS tongSo FROM ($sql) AS danhSach", $params);
        return (int)($rows[0]['tongSo'] ?? 0);
    }

    public static function chiTietLoHangCanKiemTra(string $maLoHang): ?array
    {
        return static::one(static::cauTruyVanUC07() . ' AND lh.maLoHang = ? LIMIT 1', [$maLoHang]);
    }

    // =================================================
    // UC08 - GHI NHẬN KẾT QUẢ KIỂM TRA CHẤT LƯỢNG
    // =================================================

    private static function cauTruyVanUC08(): string
    {
        // Giữ cả lô có biên bản chờ duyệt để xem/sửa/xóa theo quyền.
        return static::cauTruyVanUC07();
    }

    public static function layLoHangUC08(string $maLoHang): ?array
    {
        return static::one(static::cauTruyVanUC08() . ' AND lh.maLoHang = ? LIMIT 1', [$maLoHang]);
    }

    public static function conChoKiemTraUC08(string $maLoHang): bool
    {
        // Giữ ngữ nghĩa cũ: lô còn thuộc phạm vi xử lý UC08.
        return static::layLoHangUC08($maLoHang) !== null;
    }

    // Chỉ dùng khi cần kiểm tra khả năng tạo biên bản mới.
    public static function coTheKiemTraMoiUC08(string $maLoHang): bool
    {
        $loHang = static::layLoHangUC08($maLoHang);
        return $loHang !== null && (int)$loHang['coTheKiemTraMoi'] === 1;
    }

    public static function layTieuChiKiemTraUC08(string $maLoHang): array
    {
        return static::all("
            SELECT tc.maTieuChi, tc.tenTieuChi, tc.loaiHangHoa, tc.donViDo,
                tc.giaTriToiThieu, tc.giaTriToiDa, tc.phuongPhapKiemTra
            FROM TieuChiKiemTra tc
            INNER JOIN HangHoa hh ON (tc.loaiHangHoa = hh.loaiHangHoa OR tc.loaiHangHoa = 'Chung')
            INNER JOIN LoHang lh ON lh.maHangHoa = hh.maHangHoa
            WHERE lh.maLoHang = ? AND lh.trangThaiQC = 'Chờ kiểm tra'
                AND hh.loaiHangHoa IN ('Nguyên liệu', 'Thành phẩm')
            ORDER BY tc.maTieuChi ASC
        ", [$maLoHang]);
    }

    public static function coBienBanChoDuyetUC08(string $maLoHang): bool
    {
        $bienBan = static::one("SELECT maBienBan FROM BienBanKiemDinhChatLuong
            WHERE maLoHang = ? AND trangThai = 'Chờ duyệt' LIMIT 1", [$maLoHang]);
        return $bienBan !== null;
    }

    // =================================================
    // UC09 - XÁC NHẬN KẾT QUẢ KIỂM TRA CHẤT LƯỢNG
    // =================================================

    private static function dieuKienPhanQuyenUC09(): string
    {
        return "EXISTS (
            SELECT 1 FROM NhanVien nvql
            LEFT JOIN Kho k ON nvql.maKho = k.maKho
            WHERE nvql.maNhanVien = ? AND nvql.trangThai = 'Đang làm việc'
                AND (nvql.vaiTro = 'Quản trị viên' OR (
                    nvql.vaiTro = 'Quản lý kho'
                    AND (k.loaiKho = hh.loaiHangHoa OR k.loaiKho = 'Tổng hợp')
                ))
        )";
    }

    private static function cauTruyVanUC09(): string
    {
        $dieuKienPhanQuyen = static::dieuKienPhanQuyenUC09();
        return "
            SELECT lh.maLoHang, lh.maHangHoa, lh.maPhieuTiepNhan,
                hh.tenHangHoa AS tenLoHang, hh.loaiHangHoa,
                lh.soLuongBanDau AS soLuongTiepNhan, hh.donViTinhChuan AS donViTinh,
                COALESCE(ptn.ngayTiepNhan, lh.ngayTao) AS thoiGianTiepNhan,
                lh.trangThaiVongDoi AS trangThaiLo, lh.trangThaiQC AS trangThaiQC,
                'Đã kiểm tra, chờ xác nhận' AS trangThaiChatLuong,
                ptn.ghiChu,
                (SELECT COUNT(*) FROM BienBanKiemDinhChatLuong bb
                    WHERE bb.maLoHang = lh.maLoHang AND bb.trangThai = 'Chờ duyệt') AS soBienBanChoDuyet
            FROM LoHang lh
            INNER JOIN HangHoa hh ON lh.maHangHoa = hh.maHangHoa
            LEFT JOIN PhieuTiepNhan ptn ON lh.maPhieuTiepNhan = ptn.maPhieuTiepNhan
            WHERE lh.trangThaiQC = 'Chờ kiểm tra'
                AND hh.loaiHangHoa IN ('Nguyên liệu', 'Thành phẩm')
                AND EXISTS (SELECT 1 FROM BienBanKiemDinhChatLuong bb
                    WHERE bb.maLoHang = lh.maLoHang AND bb.trangThai = 'Chờ duyệt')
                AND $dieuKienPhanQuyen
        ";
    }

    private static function boLocUC09(string $maNhanVienDuyet, string $maLoHang, string $tuNgay, string $denNgay): array
    {
        $sql = static::cauTruyVanUC09();
        $params = [$maNhanVienDuyet];
        if ($maLoHang !== '') {
            $sql .= ' AND lh.maLoHang = ?';
            $params[] = $maLoHang;
        }
        if ($tuNgay !== '') {
            $sql .= ' AND COALESCE(ptn.ngayTiepNhan, lh.ngayTao) >= ?';
            $params[] = $tuNgay . ' 00:00:00';
        }
        if ($denNgay !== '') {
            $sql .= ' AND COALESCE(ptn.ngayTiepNhan, lh.ngayTao) < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $denNgay;
        }
        return [$sql, $params];
    }

    public static function traCuuLoHangChoXacNhanUC09(string $maNhanVienDuyet, string $maLoHang = '',
        string $tuNgay = '', string $denNgay = '', int $trang = 1, ?int $soLuongMoiTrang = null): array
    {
        if ($maNhanVienDuyet === '') return [];
        [$sql, $params] = static::boLocUC09($maNhanVienDuyet, $maLoHang, $tuNgay, $denNgay);
        $sql .= ' ORDER BY thoiGianTiepNhan DESC, lh.maLoHang DESC';
        if ($soLuongMoiTrang !== null) {
            $trang = max(1, $trang);
            $soLuongMoiTrang = max(1, min(100, $soLuongMoiTrang));
            $offset = ($trang - 1) * $soLuongMoiTrang;
            $sql .= " LIMIT $soLuongMoiTrang OFFSET $offset";
        }
        return static::all($sql, $params);
    }

    public static function demLoHangChoXacNhanUC09(string $maNhanVienDuyet, string $maLoHang = '',
        string $tuNgay = '', string $denNgay = ''): int
    {
        if ($maNhanVienDuyet === '') return 0;
        [$sql, $params] = static::boLocUC09($maNhanVienDuyet, $maLoHang, $tuNgay, $denNgay);
        $rows = static::all("SELECT COUNT(*) AS tongSo FROM ($sql) AS danhSach", $params);
        return (int)($rows[0]['tongSo'] ?? 0);
    }

    public static function chiTietLoHangUC09(string $maLoHang, string $maNhanVienDuyet): ?array
    {
        if ($maLoHang === '' || $maNhanVienDuyet === '') return null;
        return static::one(static::cauTruyVanUC09() . ' AND lh.maLoHang = ? LIMIT 1', [$maNhanVienDuyet, $maLoHang]);
    }

    public static function conChoXacNhanUC09(string $maLoHang, string $maNhanVienDuyet): bool
    {
        return static::chiTietLoHangUC09($maLoHang, $maNhanVienDuyet) !== null;
    }

    public static function layDanhSachBienBanUC09(string $maLoHang, string $maNhanVienDuyet): array
    {
        if ($maLoHang === '' || $maNhanVienDuyet === '') return [];
        $dieuKienPhanQuyen = static::dieuKienPhanQuyenUC09();
        return static::all("
            SELECT bb.maBienBan, bb.maLoHang, bb.maNhanVienQC, nv.hoTen AS tenNhanVienQC,
                bb.ngayKiemDinh, bb.ketLuan, bb.lyDoKhongDat, bb.maNhanVienDuyet,
                bb.ngayXacNhan, bb.trangThai, bb.ghiChu,
                (SELECT COUNT(*) FROM KetQuaKiemTra kq WHERE kq.maBienBan = bb.maBienBan) AS soTieuChiDaKiemTra
            FROM BienBanKiemDinhChatLuong bb
            INNER JOIN NhanVien nv ON bb.maNhanVienQC = nv.maNhanVien
            INNER JOIN LoHang lh ON bb.maLoHang = lh.maLoHang
            INNER JOIN HangHoa hh ON lh.maHangHoa = hh.maHangHoa
            WHERE bb.maLoHang = ? AND bb.trangThai = 'Chờ duyệt'
                AND lh.trangThaiQC = 'Chờ kiểm tra'
                AND hh.loaiHangHoa IN ('Nguyên liệu', 'Thành phẩm')
                AND $dieuKienPhanQuyen
            ORDER BY bb.ngayKiemDinh DESC, bb.maBienBan DESC
        ", [$maLoHang, $maNhanVienDuyet]);
    }

    public static function layBienBanUC09(string $maLoHang, string $maBienBan, string $maNhanVienDuyet): ?array
    {
        if ($maLoHang === '' || $maBienBan === '' || $maNhanVienDuyet === '') return null;
        $dieuKienPhanQuyen = static::dieuKienPhanQuyenUC09();
        return static::one("
            SELECT bb.maBienBan, bb.maLoHang, bb.maNhanVienQC, nv.hoTen AS tenNhanVienQC,
                bb.ngayKiemDinh, bb.ketLuan, bb.lyDoKhongDat, bb.maNhanVienDuyet,
                bb.ngayXacNhan, bb.trangThai, bb.ghiChu
            FROM BienBanKiemDinhChatLuong bb
            INNER JOIN NhanVien nv ON bb.maNhanVienQC = nv.maNhanVien
            INNER JOIN LoHang lh ON bb.maLoHang = lh.maLoHang
            INNER JOIN HangHoa hh ON lh.maHangHoa = hh.maHangHoa
            WHERE bb.maLoHang = ? AND bb.maBienBan = ? AND bb.trangThai = 'Chờ duyệt'
                AND lh.trangThaiQC = 'Chờ kiểm tra'
                AND hh.loaiHangHoa IN ('Nguyên liệu', 'Thành phẩm')
                AND $dieuKienPhanQuyen
            LIMIT 1
        ", [$maLoHang, $maBienBan, $maNhanVienDuyet]);
    }

    public static function layKetQuaKiemTraUC09(string $maLoHang, string $maBienBan, string $maNhanVienDuyet): array
    {
        if ($maLoHang === '' || $maBienBan === '' || $maNhanVienDuyet === '') return [];
        $dieuKienPhanQuyen = static::dieuKienPhanQuyenUC09();
        return static::all("
            SELECT kq.maKetQuaKiemTra, kq.maBienBan, kq.maTieuChi,
                tc.tenTieuChi, tc.loaiHangHoa, tc.donViDo, tc.giaTriToiThieu,
                tc.giaTriToiDa, tc.phuongPhapKiemTra,
                kq.giaTriDoDuoc, kq.giaTriDinhTinh, kq.danhGia, kq.ghiChu
            FROM KetQuaKiemTra kq
            INNER JOIN TieuChiKiemTra tc ON kq.maTieuChi = tc.maTieuChi
            INNER JOIN BienBanKiemDinhChatLuong bb ON kq.maBienBan = bb.maBienBan
            INNER JOIN LoHang lh ON bb.maLoHang = lh.maLoHang
            INNER JOIN HangHoa hh ON lh.maHangHoa = hh.maHangHoa
            WHERE bb.maLoHang = ? AND bb.maBienBan = ? AND bb.trangThai = 'Chờ duyệt'
                AND lh.trangThaiQC = 'Chờ kiểm tra'
                AND hh.loaiHangHoa IN ('Nguyên liệu', 'Thành phẩm')
                AND $dieuKienPhanQuyen
            ORDER BY tc.maTieuChi ASC, kq.maKetQuaKiemTra ASC
        ", [$maLoHang, $maBienBan, $maNhanVienDuyet]);
    }

    // =================================================
    // UC10 - LAP PHIEU NHAP KHO
    // =================================================

    // Kho dich lay tu phan cong nhan vien, khong lay tu du lieu POST.
    public static function layKhoPhuTrachUC10(string $maNhanVien): ?array
    {
        if ($maNhanVien === '') return null;
        return static::one("SELECT k.maKho, k.tenKho, k.loaiKho, k.trangThai
            FROM NhanVien nv INNER JOIN Kho k ON k.maKho = nv.maKho
            WHERE nv.maNhanVien = ? AND nv.vaiTro = 'Nhân viên kho'
                AND nv.trangThai = 'Đang làm việc'
                AND k.trangThai = 'Đang hoạt động'", [$maNhanVien]);
    }

    private static function cauTruyVanUC10(): string
    {
        return "SELECT lh.maLoHang, lh.maHangHoa, lh.maPhieuTiepNhan,
                hh.tenHangHoa AS tenLoHang, hh.loaiHangHoa,
                lh.soLuongBanDau AS soLuongTiepNhan,
                hh.donViTinhChuan AS donViTinh, hh.heSoQuyDoi,
                ptn.ngayTiepNhan AS thoiGianTiepNhan,
                lh.trangThaiVongDoi, lh.trangThaiQC,
                'Chờ nhập kho' AS trangThaiLo,
                lh.trangThaiQC AS trangThaiChatLuong,
                ptn.maPhieuGiao, ptn.maXuong, ptn.ghiChu,
                k.maKho, k.tenKho, k.loaiKho
            FROM LoHang lh
            INNER JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa
            INNER JOIN PhieuTiepNhan ptn ON ptn.maPhieuTiepNhan = lh.maPhieuTiepNhan
            INNER JOIN NhanVien nv ON nv.maNhanVien = ?
            INNER JOIN Kho k ON k.maKho = nv.maKho
            WHERE nv.vaiTro = 'Nhân viên kho' AND nv.trangThai = 'Đang làm việc'
                AND k.trangThai = 'Đang hoạt động'
                AND (k.loaiKho = hh.loaiHangHoa OR k.loaiKho = 'Tổng hợp')
                AND hh.loaiHangHoa IN ('Nguyên liệu', 'Thành phẩm')
                AND lh.trangThaiQC = 'Đã đạt chuẩn'
                AND lh.trangThaiVongDoi = 'Đã tạo'
                AND ptn.trangThai NOT IN ('Đã hủy', 'Đã từ chối tiếp nhận')
                AND EXISTS (SELECT 1 FROM BienBanKiemDinhChatLuong bb
                    WHERE bb.maLoHang = lh.maLoHang AND bb.trangThai = 'Đã duyệt'
                        AND bb.ketLuan = 'Đạt' AND bb.maNhanVienDuyet IS NOT NULL
                        AND bb.ngayXacNhan IS NOT NULL)
                AND NOT EXISTS (SELECT 1 FROM ChiTietPhieuNhapKho ct
                    INNER JOIN PhieuNhapKho pn ON pn.maPhieuNhapKho = ct.maPhieuNhapKho
                    WHERE ct.maLoHang = lh.maLoHang AND pn.trangThai <> 'Đã hủy')";
    }

    private static function boLocUC10(string $maNhanVien, string $maLoHang,
        string $tuNgay, string $denNgay): array
    {
        $sql = static::cauTruyVanUC10();
        $params = [$maNhanVien];
        if ($maLoHang !== '') {
            $sql .= ' AND lh.maLoHang = ?';
            $params[] = $maLoHang;
        }
        if ($tuNgay !== '') {
            $sql .= ' AND ptn.ngayTiepNhan >= ?';
            $params[] = $tuNgay . ' 00:00:00';
        }
        if ($denNgay !== '') {
            $sql .= ' AND ptn.ngayTiepNhan < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $denNgay;
        }
        return [$sql, $params];
    }

    public static function traCuuLoHangChoNhapKhoUC10(string $maNhanVien,
        string $maLoHang = '', string $tuNgay = '', string $denNgay = '',
        int $trang = 1, ?int $soLuongMoiTrang = null): array
    {
        if ($maNhanVien === '') return [];
        [$sql, $params] = static::boLocUC10($maNhanVien, $maLoHang, $tuNgay, $denNgay);
        $sql .= ' ORDER BY thoiGianTiepNhan DESC, lh.maLoHang DESC';
        if ($soLuongMoiTrang !== null) {
            $trang = max(1, $trang);
            $soLuongMoiTrang = max(1, min(100, $soLuongMoiTrang));
            $offset = ($trang - 1) * $soLuongMoiTrang;
            $sql .= " LIMIT $soLuongMoiTrang OFFSET $offset";
        }
        return static::all($sql, $params);
    }

    public static function demLoHangChoNhapKhoUC10(string $maNhanVien,
        string $maLoHang = '', string $tuNgay = '', string $denNgay = ''): int
    {
        if ($maNhanVien === '') return 0;
        [$sql, $params] = static::boLocUC10($maNhanVien, $maLoHang, $tuNgay, $denNgay);
        $row = static::one("SELECT COUNT(*) AS tongSo FROM ($sql) AS danhSach", $params);
        return (int)($row['tongSo'] ?? 0);
    }

    public static function chiTietLoHangUC10(string $maLoHang, string $maNhanVien): ?array
    {
        if ($maLoHang === '' || $maNhanVien === '') return null;
        return static::one(static::cauTruyVanUC10() . ' AND lh.maLoHang = ? LIMIT 1',
            [$maNhanVien, $maLoHang]);
    }

    public static function conChoNhapKhoUC10(string $maLoHang, string $maNhanVien): bool
    {
        return static::chiTietLoHangUC10($maLoHang, $maNhanVien) !== null;
    }

    // Tra cuu chung tu goc va cac ung vien yeu cau nhap; khong tu chon neu trung.
    public static function layChungTuLienQuanUC10(string $maLoHang, string $maNhanVien): ?array
    {
        $loHang = static::chiTietLoHangUC10($maLoHang, $maNhanVien);
        if ($loHang === null) return null;
        $bienBan = static::all("SELECT maBienBan, maNhanVienDuyet, ngayXacNhan, ketLuan
            FROM BienBanKiemDinhChatLuong
            WHERE maLoHang = ? AND trangThai = 'Đã duyệt' AND ketLuan = 'Đạt'
                AND maNhanVienDuyet IS NOT NULL AND ngayXacNhan IS NOT NULL
            ORDER BY ngayXacNhan DESC, maBienBan DESC", [$maLoHang]);
        $yeuCau = static::all("SELECT yc.maYeuCauNhap, yc.nguonNhap, yc.ngayYeuCau,
                yc.trangThai, ct.soLuongYeuCau, ct.donViTinh
            FROM PhieuYeuCauNhapKho yc
            INNER JOIN ChiTietYeuCauNhapKho ct ON ct.maYeuCauNhap = yc.maYeuCauNhap
            INNER JOIN PhieuTiepNhan ptn ON ptn.maPhieuTiepNhan = ?
            WHERE ct.maHangHoa = ? AND yc.trangThai IN ('Chờ xử lý', 'Đang nhập kho')
                AND ((yc.nguonNhap = 'Nhà cung cấp' AND ptn.maPhieuGiao IS NOT NULL
                    AND yc.maPhieuGiao = ptn.maPhieuGiao)
                OR (yc.nguonNhap = 'Xưởng sản xuất' AND ptn.maXuong IS NOT NULL
                    AND yc.maXuong = ptn.maXuong))
            ORDER BY yc.ngayYeuCau DESC, yc.maYeuCauNhap DESC",
            [$loHang['maPhieuTiepNhan'], $loHang['maHangHoa']]);
        return [
            'maPhieuTiepNhan' => $loHang['maPhieuTiepNhan'],
            'maPhieuGiao' => $loHang['maPhieuGiao'],
            'maXuong' => $loHang['maXuong'],
            'danhSachBienBanDat' => $bienBan,
            'danhSachYeuCauNhap' => $yeuCau
        ];
    }
}
