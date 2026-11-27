<?php
namespace App\Models;

use Core\Model;

/**
 * Dữ liệu danh mục cho các ô chọn (kho, khu vực, vị trí, hàng hóa, nhân viên) của UC24–UC29.
 * Tách riêng một chỗ để không trùng lặp truy vấn trong từng Controller/View; chỉ đọc.
 * Nguồn dữ liệu: feed_wms.
 */
final class DanhMuc extends Model
{
    /** [maKho => tenKho] các kho đang hoạt động. */
    public static function kho(): array
    {
        $rows = static::all("SELECT maKho, tenKho FROM Kho WHERE trangThai IN ('Hoạt động', 'Đang hoạt động') ORDER BY maKho");
        return array_column($rows, 'tenKho', 'maKho');
    }

    /** [maKho => tenKho] các kho phục vụ kiểm kê (chỉ các kho sản xuất/lưu trữ nội bộ, loại trừ Trung tâm phân phối). */
    public static function khoKiemKe(): array
    {
        $rows = static::all("SELECT maKho, tenKho FROM Kho WHERE trangThai IN ('Hoạt động', 'Đang hoạt động') AND loaiKho != 'TongHop' AND tenKho NOT LIKE '%phân phối%' AND maKho != 'K050001' ORDER BY maKho");
        return array_column($rows, 'tenKho', 'maKho');
    }

    /** Tìm thông tin quản lý kho phụ trách của một kho */
    public static function quanLyKho(string $maKho): ?array
    {
        return static::one(
            "SELECT maNhanVien, hoTen FROM NhanVien WHERE maKho = ? AND vaiTro = 'Quản lý kho' AND trangThai = 'Đang làm việc' LIMIT 1",
            [$maKho]
        );
    }

    /** [maKhuVuc => "Tên khu vực (tên kho)"] — lọc theo kho nếu có. */
    public static function khuVuc(string $maKho = ''): array
    {
        $sql = "SELECT kv.maKhuVuc, kv.tenKhuVuc, k.tenKho
                FROM KhuVuc kv JOIN Kho k ON k.maKho = kv.maKho
                WHERE kv.trangThai IN ('Hoạt động', 'Đang hoạt động')";
        $params = [];
        if ($maKho !== '') {
            $sql .= ' AND kv.maKho = ?';
            $params[] = $maKho;
        }
        $out = [];
        foreach (static::all($sql . ' ORDER BY kv.maKho, kv.maKhuVuc', $params) as $r) {
            $out[$r['maKhuVuc']] = $r['tenKhuVuc'] . ' (' . $r['tenKho'] . ')';
        }
        return $out;
    }

    /** [maKho => [maKhuVuc => tenKhuVuc]] — dùng cho ô chọn khu vực phụ thuộc kho ở form lập biên bản. */
    public static function khuVucTheoKho(): array
    {
        $out = [];
        $rows = static::all("SELECT maKho, maKhuVuc, tenKhuVuc FROM KhuVuc WHERE trangThai IN ('Hoạt động', 'Đang hoạt động') ORDER BY maKho, maKhuVuc");
        foreach ($rows as $r) {
            $out[$r['maKho']][$r['maKhuVuc']] = $r['tenKhuVuc'];
        }
        return $out;
    }

    /**
     * Danh sách nhân viên kho thực tế làm việc tại kho đã chọn để giao việc kiểm kê.
     * Chỉ lấy nhân viên trực tiếp thuộc kho (maKho = ?) và vai trò Nhân viên kho đang làm việc.
     * Chỉ trả về [maNhanVien => hoTen] (chỉ hiển thị tên theo yêu cầu).
     */
    public static function nhanVienKho(string $maKho): array
    {
        if ($maKho === '') {
            return [];
        }
        $rows = static::all(
            "SELECT maNhanVien, hoTen 
             FROM NhanVien 
             WHERE maKho = ? AND trangThai = 'Đang làm việc' AND vaiTro = 'Nhân viên kho'
             ORDER BY hoTen",
            [$maKho]
        );
        return array_column($rows, 'hoTen', 'maNhanVien');
    }

    /** Danh sách nhân viên đang làm việc phục vụ phân công trưởng đoàn / nhân viên kiểm kê */
    public static function nhanVien(?string $maKho = null): array
    {
        $sql = "SELECT maNhanVien, hoTen, chucVu, vaiTro, maKho FROM NhanVien WHERE trangThai = 'Đang làm việc'";
        $params = [];
        if (!empty($maKho)) {
            $sql .= ' AND (maKho = ? OR maKho IS NULL OR maKho = "")';
            $params[] = $maKho;
        }
        $sql .= ' ORDER BY maKho DESC, vaiTro, hoTen';
        $rows = static::all($sql, $params);
        $out = [];
        foreach ($rows as $r) {
            $extra = $r['chucVu'] ?: $r['vaiTro'];
            $out[$r['maNhanVien']] = $r['hoTen'] . ' (' . $extra . ' - ' . $r['maNhanVien'] . ')';
        }
        return $out;
    }

    /** [maHangHoa => "mã — tên"] hàng hóa đang hoạt động. */
    public static function hangHoa(): array
    {
        $rows = static::all("SELECT maHangHoa, tenHangHoa FROM HangHoa WHERE trangThai IN ('Hoạt động', 'Đang kinh doanh') ORDER BY maHangHoa");
        $out = [];
        foreach ($rows as $r) {
            $out[$r['maHangHoa']] = $r['maHangHoa'] . ' — ' . $r['tenHangHoa'];
        }
        return $out;
    }

    /** [maLoai => nhãn tiếng Việt] danh sách loại hàng hóa phục vụ lọc tra cứu tồn / báo cáo. */
    public static function loaiHangHoa(): array
    {
        $rows = static::all("SELECT DISTINCT loaiHangHoa FROM HangHoa WHERE loaiHangHoa IS NOT NULL AND loaiHangHoa != '' ORDER BY loaiHangHoa");
        $out = [];
        foreach ($rows as $r) {
            $code = (string)$r['loaiHangHoa'];
            $out[$code] = function_exists('product_type_label') ? product_type_label($code) : $code;
        }
        return $out;
    }

    /** Danh sách vị trí đang có cấu trúc kho/khu vực phục vụ bộ lọc phụ thuộc (Kho → Khu vực → Vị trí). */
    public static function viTriChiTiet(?string $maKho = null): array
    {
        $sql = "SELECT v.maViTri, v.tenViTri, v.maKhuVuc, kv.tenKhuVuc, kv.maKho
                FROM ViTriLuuTru v
                JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
                JOIN Kho k ON k.maKho = kv.maKho
                WHERE 1 = 1";
        $params = [];
        if ($maKho !== null && $maKho !== '') {
            $sql .= ' AND kv.maKho = ?';
            $params[] = $maKho;
        }
        return static::all($sql . ' ORDER BY kv.maKho, kv.maKhuVuc, v.maViTri', $params);
    }

    /** Danh sách loại kiểm kê: sử dụng mapping tập trung */
    public static function loaiKiemKe(): array
    {
        return inventory_type_options();
    }

    /** Lô hàng đang còn tồn */
    public static function loHangConTon(string $maKho = ''): array
    {
        $sql = 'SELECT l.maLoHang, l.maLoHang AS ma, h.tenHangHoa, l.ngayHetHan
                FROM LoHang l
                JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
                WHERE EXISTS (SELECT 1 FROM TonKho t JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
                              JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
                              WHERE t.maLoHang = l.maLoHang AND t.soLuongTon > 0' . ($maKho !== '' ? ' AND kv.maKho = ?' : '') . ')';
        $out = [];
        foreach (static::all($sql . ' ORDER BY l.ngayHetHan, l.maLoHang LIMIT 500', $maKho !== '' ? [$maKho] : []) as $r) {
            $out[$r['maLoHang']] = $r['maLoHang'] . ' — ' . $r['tenHangHoa'] . ' — HSD ' . date('d/m/Y', strtotime((string)$r['ngayHetHan']));
        }
        return $out;
    }
}
