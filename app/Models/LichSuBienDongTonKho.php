<?php
namespace App\Models;

use Core\Model;

/** Bảng LichSuBienDongTonKho: nhật ký biến động tồn kho theo lô — vị trí. */
class LichSuBienDongTonKho extends Model
{
    /** Khóa sinh mã dùng chung cho các chứng từ của Module 3. */
    public static function withCodeLock(callable $fn)
    {
        $r = static::one("SELECT GET_LOCK(?, 5) AS ok", ['fw_m3_code']);
        if ((int)($r['ok'] ?? 0) !== 1) {
            throw new \RuntimeException('Hệ thống đang bận sinh mã chứng từ, vui lòng thử lại.');
        }
        try {
            return $fn();
        } finally {
            try {
                static::one("SELECT RELEASE_LOCK(?) AS r", ['fw_m3_code']);
            } catch (\Throwable $e) {
            }
        }
    }

    /** Sinh $count mã BDTK liên tiếp cho hôm nay. @return string[] */
    public static function nextCodes(int $count): array
    {
        return self::withCodeLock(function () use ($count) {
            $prefix = 'BDTK' . date('ymd');
            $r = static::one("SELECT maBienDong AS m FROM LichSuBienDongTonKho WHERE maBienDong LIKE ? ORDER BY maBienDong DESC LIMIT 1 FOR UPDATE", [$prefix . '%']);
            $last = (string)($r['m'] ?? '' );
            $seq = $last !== '' ? (int)substr($last, -6) : 0;
            $out = [];
            for ($i = 1; $i <= $count; $i++) {
                $out[] = $prefix . str_pad((string)($seq + $i), 6, '0', STR_PAD_LEFT);
            }
            return $out;
        });
    }

    public static function record(array $d): void
    {
        static::exec("INSERT INTO LichSuBienDongTonKho
            (maBienDong, maLoHang, maViTri, loaiGiaoDich, soLuongBienDong, soLuongTonTruoc, soLuongTonSau,
             maChungTuLienQuan, maNhanVienThucHien, ghiChu)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
            $d['maBienDong'], $d['maLoHang'], $d['maViTri'], $d['loaiGiaoDich'], $d['soLuongBienDong'],
            $d['soLuongTonTruoc'], $d['soLuongTonSau'], $d['maChungTuLienQuan'], $d['maNhanVienThucHien'],
            $d['ghiChu'] ?? null
        ]);
    }

    public static function byDocument(string $maChungTu): array
    {
        return static::all("SELECT h.*, nv.hoTen
            FROM LichSuBienDongTonKho h JOIN NhanVien nv ON nv.maNhanVien = h.maNhanVienThucHien
            WHERE h.maChungTuLienQuan = ?
            ORDER BY h.thoiGianPhatSinh, h.maBienDong", [$maChungTu]);
    }

    /** Lịch sử biến động tại một vị trí. */
    public static function historyAtPosition(string $maViTri, int $limit = 20): array
    {
        return static::all("SELECT h.*, nv.hoTen
            FROM LichSuBienDongTonKho h JOIN NhanVien nv ON nv.maNhanVien = h.maNhanVienThucHien
            WHERE h.maViTri = ?
            ORDER BY h.thoiGianPhatSinh DESC, h.maBienDong DESC LIMIT ?", [$maViTri, $limit]);
    }

    /** Lịch sử biến động của một cặp (lô hàng, vị trí lưu trữ). */
    public static function historyForStock(string $maLoHang, string $maViTri, int $limit = 50): array
    {
        return static::all(
            "SELECT h.*, nv.hoTen
             FROM LichSuBienDongTonKho h
             JOIN NhanVien nv ON nv.maNhanVien = h.maNhanVienThucHien
             WHERE h.maLoHang = ? AND h.maViTri = ?
             ORDER BY h.thoiGianPhatSinh DESC, h.maBienDong DESC
             LIMIT ?",
            [$maLoHang, $maViTri, $limit]
        );
    }

    /** Biến động tồn kho gần đây (có thể lọc theo kho/khu vực/vị trí/từ khóa). */
    public static function recentScoped(?string $maKho = null, array $f = [], int $limit = 20): array
    {
        $sql = "SELECT h.*, nv.hoTen,
                       l.maHangHoa, hh.tenHangHoa, hh.donViTinhChuan,
                       v.tenViTri, kv.maKhuVuc, kv.tenKhuVuc, k.maKho, k.tenKho
                FROM LichSuBienDongTonKho h
                JOIN NhanVien nv ON nv.maNhanVien = h.maNhanVienThucHien
                JOIN LoHang l ON l.maLoHang = h.maLoHang
                JOIN HangHoa hh ON hh.maHangHoa = l.maHangHoa
                JOIN ViTriLuuTru v ON v.maViTri = h.maViTri
                JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
                JOIN Kho k ON k.maKho = kv.maKho
                WHERE 1 = 1";
        $params = [];
        if ($maKho !== null && $maKho !== '') {
            $sql .= " AND k.maKho = ?";
            $params[] = $maKho;
        }
        $q = trim((string)($f['q'] ?? ''));
        if ($q !== '') {
            $sql .= " AND (hh.maHangHoa LIKE ? OR hh.tenHangHoa LIKE ? OR l.maLoHang LIKE ? OR h.maChungTuLienQuan LIKE ? OR v.maViTri LIKE ?)";
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if (!empty($f['maKhuVuc'])) {
            $sql .= " AND kv.maKhuVuc = ?";
            $params[] = $f['maKhuVuc'];
        }
        if (!empty($f['maViTri'])) {
            $sql .= " AND h.maViTri = ?";
            $params[] = $f['maViTri'];
        }
        $sql .= " ORDER BY h.thoiGianPhatSinh DESC, h.maBienDong DESC LIMIT ?";
        $params[] = $limit;
        return static::all($sql, $params);
    }
}

