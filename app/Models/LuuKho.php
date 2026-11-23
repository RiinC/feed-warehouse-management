<?php
namespace App\Models;

use Core\Model;

/**
 * Bảng LuuKho: sổ phân bổ lô hàng vào vị trí lưu trữ (UC16).
 * Mỗi dòng là một lần gán; KHÔNG thay đổi TonKho (tổng tồn kho của lô giữ nguyên).
 */
class LuuKho extends Model
{
    private const EPS = 0.000001;

    public static function record(string $maLoHang, string $maViTri, ?string $maPhieuNhapKho, float $soLuong, string $maNhanVien, string $ghiChu = ''): void
    {
        static::exec("INSERT INTO LuuKho
            (maLoHang, maViTri, maPhieuNhapKho, soLuongPhanBo, maNhanVienThucHien, ghiChu)
            VALUES (?, ?, ?, ?, ?, ?)", [
            $maLoHang, $maViTri, $maPhieuNhapKho, $soLuong, $maNhanVien, $ghiChu !== '' ? $ghiChu : null
        ]);
    }

    /**
     * Gán lô vào vị trí trong một transaction.
     * $d: maLoHang, maViTri, soLuong, ghiChu, maKho (phạm vi kho của người dùng; null = không giới hạn).
     * @return array<string,mixed>
     */
    public static function assign(array $d, string $staffId): array
    {
        return static::transaction(function () use ($d, $staffId) {
            $lot = LoHang::lockForUpdate((string)$d['maLoHang']);
            if ($lot['trangThaiQC'] !== 'Đã đạt chuẩn') {
                throw new \RuntimeException('Chỉ gán lô đã đạt chuẩn kiểm tra chất lượng.');
            }
            if ((string)$lot['ngayHetHan'] < date('Y-m-d')) {
                throw new \RuntimeException('Lô hàng đã hết hạn, không thể lưu trữ.');
            }
            if (!in_array($lot['trangThaiVongDoi'], ['Đã tạo', 'Đang lưu kho', 'Sắp hết hạn'], true)) {
                throw new \RuntimeException('Lô hàng không ở trạng thái có thể lưu trữ.');
            }
            $scope = isset($d['maKho']) && $d['maKho'] !== '' ? (string)$d['maKho'] : null;
            if ($scope !== null) {
                $ok = static::one("SELECT 1 AS ok FROM ChiTietPhieuNhapKho c
                    JOIN PhieuNhapKho p ON p.maPhieuNhapKho = c.maPhieuNhapKho
                    WHERE c.maLoHang = ? AND p.maKho = ? AND p.trangThai IN ('Đang xếp kệ', 'Đã nhập kho') LIMIT 1", [$d['maLoHang'], $scope]);
                if (!$ok) {
                    throw new \RuntimeException('Lô hàng không thuộc kho bạn phụ trách.');
                }
            }
            $state = LoHang::allocationState((string)$d['maLoHang']);
            if ($state['nhap'] <= self::EPS) {
                throw new \RuntimeException('Lô hàng chưa được nhập kho.');
            }
            $qty = (float)$d['soLuong'];
            if ($qty <= 0) {
                throw new \RuntimeException('Số lượng cần gán phải lớn hơn 0.');
            }
            if ($qty > $state['conLai'] + self::EPS) {
                throw new \RuntimeException('Số lượng vượt quá phần còn lại của lô (còn ' . round($state['conLai'], 2) . ' kg).');
            }
            $vt = ViTriLuuTru::lockForUpdate((string)$d['maViTri']);
            if ($scope !== null && $vt['maKho'] !== $scope) {
                throw new \RuntimeException('Vị trí lưu trữ không thuộc kho bạn phụ trách.');
            }
            if (!in_array($vt['trangThai'], ['Đang để trống', 'Đang chứa hàng'], true)) {
                throw new \RuntimeException('Vị trí không ở trạng thái sử dụng.');
            }
            if ($vt['khuTrangThai'] !== 'Đang hoạt động' || $vt['khoTrangThai'] !== 'Đang hoạt động') {
                throw new \RuntimeException('Kho hoặc khu vực của vị trí đã ngừng hoạt động.');
            }
            $q = ViTriLuuTru::quantities((string)$d['maViTri']);
            if ($q['daDung'] + $qty > (float)$vt['sucChuaToiDa'] + self::EPS) {
                $con = max(0, (float)$vt['sucChuaToiDa'] - $q['daDung']);
                throw new \RuntimeException('Vị trí chỉ còn ' . round($con, 2) . ' kg, không đủ để gán ' . round($qty, 2) . ' kg.');
            }
            $pnk = LoHang::firstPhieuCode((string)$d['maLoHang']);
            $note = trim((string)($d['ghiChu'] ?? ''));
            if (mb_strlen($note) > 255) { $note = mb_substr($note, 0, 255); }
            self::record((string)$d['maLoHang'], (string)$d['maViTri'], $pnk, $qty, $staffId, $note !== '' ? $note : 'Gán lô vào vị trí lưu trữ');
            ViTriLuuTru::refreshStatus((string)$d['maViTri']);
            return ['maLoHang' => $d['maLoHang'], 'maViTri' => $d['maViTri'], 'soLuong' => $qty, 'conLaiSau' => max(0, $state['conLai'] - $qty)];
        });
    }


    public static function allocatedForLot(string $maLoHang): float
    {
        $r = static::one("SELECT IFNULL(SUM(soLuongPhanBo), 0) AS s FROM LuuKho WHERE maLoHang = ?", [$maLoHang]);
        return (float)($r['s'] ?? 0);
    }

    /** @return array{0:array,1:int} Lịch sử phân bổ (có lọc). */
    public static function history(array $f, int $page, int $perPage, ?string $scope): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($scope !== null && $scope !== '') {
            $where[] = 'k.maKho = ?';
            $params[] = $scope;
        }
        if (!empty($f['maLoHang'])) {
            $where[] = 'lk.maLoHang = ?';
            $params[] = $f['maLoHang'];
        }
        if (!empty($f['maViTri'])) {
            $where[] = 'lk.maViTri = ?';
            $params[] = $f['maViTri'];
        }
        if (!empty($f['q'])) {
            $where[] = '(lk.maLoHang LIKE ? OR lk.maViTri LIKE ? OR hh.tenHangHoa LIKE ?)';
            $like = '%' . $f['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $w = implode(' AND ', $where);
        $from = "FROM LuuKho lk
            JOIN LoHang l ON l.maLoHang = lk.maLoHang
            JOIN HangHoa hh ON hh.maHangHoa = l.maHangHoa
            JOIN ViTriLuuTru v ON v.maViTri = lk.maViTri
            JOIN KhuVuc k ON k.maKhuVuc = v.maKhuVuc
            JOIN Kho kho ON kho.maKho = k.maKho
            JOIN NhanVien nv ON nv.maNhanVien = lk.maNhanVienThucHien";
        $total = (int)(static::one("SELECT COUNT(*) AS c " . $from . " WHERE $w", $params)['c'] ?? 0);
        $sql = "SELECT lk.maLuuKho, lk.maLoHang, lk.maViTri, lk.maPhieuNhapKho, lk.soLuongPhanBo,
                       lk.thoiDiemPhanBo, lk.ghiChu, l.maHangHoa, hh.tenHangHoa, hh.donViTinhChuan,
                       v.tenViTri, k.tenKhuVuc, k.maKho, kho.tenKho, nv.hoTen, nv.maNhanVien
                   " . $from . " WHERE $w
            ORDER BY lk.thoiDiemPhanBo DESC, lk.maLuuKho DESC
            LIMIT ? OFFSET ?";
        $p2 = $params;
        $p2[] = $perPage;
        $p2[] = max(0, ($page - 1) * $perPage);
        return [static::all($sql, $p2), $total];
    }

    /** Các lần phân bổ tại một vị trí (cho trang xem vị trí). */
    public static function atPosition(string $maViTri): array
    {
        return static::all("SELECT lk.maLuuKho, lk.maLoHang, lk.maPhieuNhapKho, lk.soLuongPhanBo,
                lk.thoiDiemPhanBo, lk.ghiChu, l.maHangHoa, hh.tenHangHoa, nv.hoTen
            FROM LuuKho lk
            JOIN LoHang l ON l.maLoHang = lk.maLoHang
            JOIN HangHoa hh ON hh.maHangHoa = l.maHangHoa
            JOIN NhanVien nv ON nv.maNhanVien = lk.maNhanVienThucHien
            WHERE lk.maViTri = ?
            ORDER BY lk.thoiDiemPhanBo DESC, lk.maLuuKho DESC", [$maViTri]);
    }
}

