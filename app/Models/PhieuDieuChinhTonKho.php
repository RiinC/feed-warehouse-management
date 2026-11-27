<?php
namespace App\Models;

use Core\Model;

/**
 * UC27 — Thi hành phương án xử lý: ghi phiếu điều chỉnh (PhieuDieuChinhTonKho),
 * cập nhật TonKho và ghi LichSuBienDongTonKho trong CÙNG một giao dịch.
 *
 * Bất biến được giữ ở đây (CSDL cũng có ràng buộc tương ứng):
 *  - Không giảm quá tồn hiện có (TonKho.soLuongTon >= 0).
 *  - Không làm tồn thấp hơn lượng đã giữ cho đơn hàng (soLuongDaGiu <= soLuongTon).
 *  - soLuongTonSau = soLuongTonTruoc + soLuongBienDong (ck_LichSuBienDongTon_8).
 *  - Mỗi phương án chỉ thi hành một lần (CAS trên trạng thái phương án).
 */
class PhieuDieuChinhTonKho extends Model
{
    public static function find(string $ma): ?array
    {
        return static::one(
            'SELECT pd.*, p.bienPhapXuLy, p.maChenhLech, p.maHangBatThuong,
                    nv.hoTen AS tenNhanVienThiHanh, l.maHangHoa, h.tenHangHoa, v.tenViTri
             FROM PhieuDieuChinhTonKho pd
             JOIN PhuongAnXuLy p ON p.maPhuongAn = pd.maPhuongAn
             JOIN NhanVien nv ON nv.maNhanVien = pd.maNhanVienThiHanh
             JOIN LoHang l ON l.maLoHang = pd.maLoHang
             JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
             JOIN ViTriLuuTru v ON v.maViTri = pd.maViTri
             WHERE pd.maPhieuDieuChinh = ?',
            [$ma]
        );
    }

    /** Phiếu điều chỉnh phát sinh từ một biên bản kiểm kê. */
    public static function byBienBan(string $maBienBan): array
    {
        return static::all(
            'SELECT pd.*, p.bienPhapXuLy, nv.hoTen AS tenNhanVienThiHanh,
                    h.tenHangHoa, l.maHangHoa, v.tenViTri
             FROM PhieuDieuChinhTonKho pd
             JOIN PhuongAnXuLy p ON p.maPhuongAn = pd.maPhuongAn
             JOIN ChenhLechKiemKe c ON c.maChenhLech = p.maChenhLech
             JOIN KetQuaKiemKe q ON q.maKetQuaKiemKe = c.maKetQuaKiemKe
             JOIN NhanVien nv ON nv.maNhanVien = pd.maNhanVienThiHanh
             JOIN LoHang l ON l.maLoHang = pd.maLoHang
             JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
             JOIN ViTriLuuTru v ON v.maViTri = pd.maViTri
             WHERE q.maBienBanKiemKe = ?
             ORDER BY pd.ngayThiHanh DESC, pd.maPhieuDieuChinh DESC',
            [$maBienBan]
        );
    }

    /** Phiếu điều chỉnh phát sinh từ một bản ghi hàng bất thường. */
    public static function byHangBatThuong(string $maHangBatThuong): array
    {
        return static::all(
            'SELECT pd.*, p.bienPhapXuLy, nv.hoTen AS tenNhanVienThiHanh,
                    h.tenHangHoa, l.maHangHoa, v.tenViTri
             FROM PhieuDieuChinhTonKho pd
             JOIN PhuongAnXuLy p ON p.maPhuongAn = pd.maPhuongAn
             JOIN NhanVien nv ON nv.maNhanVien = pd.maNhanVienThiHanh
             JOIN LoHang l ON l.maLoHang = pd.maLoHang
             JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
             JOIN ViTriLuuTru v ON v.maViTri = pd.maViTri
             WHERE p.maHangBatThuong = ?
             ORDER BY pd.ngayThiHanh DESC, pd.maPhieuDieuChinh DESC',
            [$maHangBatThuong]
        );
    }

    /** Phiếu điều chỉnh gần đây (khối "biến động do kiểm kê" ở trang tồn kho, lọc theo kho nếu có). */
    public static function recent(int $limit = 10, ?string $maKho = null): array
    {
        $sql = 'SELECT pd.*, p.bienPhapXuLy, nv.hoTen AS tenNhanVienThiHanh, h.tenHangHoa, v.tenViTri, kv.maKho
             FROM PhieuDieuChinhTonKho pd
             JOIN PhuongAnXuLy p ON p.maPhuongAn = pd.maPhuongAn
             JOIN NhanVien nv ON nv.maNhanVien = pd.maNhanVienThiHanh
             JOIN LoHang l ON l.maLoHang = pd.maLoHang
             JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
             JOIN ViTriLuuTru v ON v.maViTri = pd.maViTri
             JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc';
        $params = [];
        if ($maKho !== null && $maKho !== '') {
            $sql .= ' WHERE kv.maKho = ?';
            $params[] = $maKho;
        }
        $sql .= ' ORDER BY pd.ngayThiHanh DESC, pd.maPhieuDieuChinh DESC LIMIT ?';
        $params[] = $limit;
        return static::all($sql, $params);
    }

    /**
     * Thi hành phương án đã phê duyệt: khoá dòng tồn, kiểm tra bất biến, cập nhật TonKho,
     * ghi phiếu điều chỉnh + lịch sử biến động, đổi trạng thái phương án/hàng bất thường.
     * Trả về mã phiếu điều chỉnh, hoặc null nếu biện pháp không đổi số lượng tồn
     * ('Hạ cấp chất lượng' chỉ đổi trạng thái xử lý).
     */
    public static function post(string $maPhuongAn, string $maNhanVien, ?string $scope = null): ?string
    {
        return static::transaction(function () use ($maPhuongAn, $maNhanVien, $scope) {
            $pa = static::one(
                "SELECT p.*, c.loaiChenhLech, c.soLuongChenhLech,
                        q.maLoHang AS loChenhLech, q.maViTri AS viTriChenhLech, q.soLuongSoSach,
                        b.trangThai AS trangThaiBienBan,
                        hb.maLoHang AS loBatThuong, hb.maViTri AS viTriBatThuong,
                        hb.soLuong AS soLuongBatThuong, hb.trangThai AS trangThaiBatThuong,
                        kv.maKho AS maKhoBatThuong
                 FROM PhuongAnXuLy p
                 LEFT JOIN ChenhLechKiemKe c ON c.maChenhLech = p.maChenhLech
                 LEFT JOIN KetQuaKiemKe q ON q.maKetQuaKiemKe = c.maKetQuaKiemKe
                 LEFT JOIN BienBanKiemKe b ON b.maBienBanKiemKe = q.maBienBanKiemKe
                 LEFT JOIN HangBatThuong hb ON hb.maHangBatThuong = p.maHangBatThuong
                 LEFT JOIN ViTriLuuTru v ON v.maViTri = hb.maViTri
                 LEFT JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
                 WHERE p.maPhuongAn = ? FOR UPDATE",
                [$maPhuongAn]
            );
            if (!$pa) {
                throw new \RuntimeException('Không tìm thấy phương án xử lý.');
            }
            if ($pa['maHangBatThuong'] !== null && $scope !== null && ($scope === '' || $pa['maKhoBatThuong'] !== $scope)) {
                throw new \RuntimeException('Bạn không có quyền thi hành phương án của kho khác.');
            }
            if ($pa['trangThai'] !== PhuongAnXuLy::TT_DUYET) {
                throw new \RuntimeException('Chỉ thi hành được phương án đã phê duyệt (trạng thái hiện tại: ' . proposal_status_label((string)$pa['trangThai']) . ').');
            }
            $bienPhap = resolution_method_code((string)$pa['bienPhapXuLy']);
            $soSach = null;

            if ($pa['maChenhLech'] !== null) {
                if ($pa['trangThaiBienBan'] !== BienBanKiemKe::TT_DANG) {
                    throw new \RuntimeException('Biên bản kiểm kê đã chốt nên không thi hành điều chỉnh được.');
                }
                $maLo = (string)$pa['loChenhLech'];
                $maViTri = (string)$pa['viTriChenhLech'];
                $soLech = abs((float)$pa['soLuongChenhLech']);
                if ($soLech <= 0.000001) {
                    throw new \RuntimeException('Dòng chênh lệch có số lượng 0, không cần điều chỉnh.');
                }
                $delta = $pa['loaiChenhLech'] === 'Thừa' ? $soLech : -$soLech;
                if ($bienPhap === 'GhiNhanHaoHut') {
                    $delta = -$soLech;
                }
                $loaiGiaoDich = $bienPhap === 'GhiNhanHaoHut'
                    ? 'HaoHut'
                    : ($delta > 0 ? 'DieuChinhTang' : 'DieuChinhGiam');
                $soSach = (float)$pa['soLuongSoSach'];
            } else {
                if ($pa['trangThaiBatThuong'] === HangBatThuong::TT_DA_XU_LY) {
                    throw new \RuntimeException('Hàng bất thường đã xử lý xong nên không thi hành thêm được.');
                }
                $maLo = (string)$pa['loBatThuong'];
                $maViTri = (string)$pa['viTriBatThuong'];
                $soLuong = abs((float)$pa['soLuongBatThuong']);
                if ($soLuong <= 0.000001) {
                    throw new \RuntimeException('Số lượng hàng bất thường phải lớn hơn 0.');
                }
                if ($bienPhap === 'HaCapChatLuong') {
                    self::markExecuted($maPhuongAn);
                    static::exec(
                        'UPDATE HangBatThuong SET trangThai = ? WHERE maHangBatThuong = ? AND trangThai IN (?, ?)',
                        [HangBatThuong::TT_DA_XU_LY, $pa['maHangBatThuong'], HangBatThuong::TT_CACH_LY, HangBatThuong::TT_DANG_XU_LY]
                    );
                    return null;
                }
                $delta = -$soLuong;
                $loaiGiaoDich = $bienPhap === 'GhiNhanHaoHut' ? 'HaoHut' : 'DieuChinhGiam';
            }

            // Khoá dòng tồn của (lô, vị trí) để kiểm tra và cập nhật nguyên tử.
            $ton = static::one('SELECT * FROM TonKho WHERE maLoHang = ? AND maViTri = ? FOR UPDATE', [$maLo, $maViTri]);
            $truoc = $ton ? (float)$ton['soLuongTon'] : 0.0;
            $daGiu = $ton ? (float)$ton['soLuongDaGiu'] : 0.0;
            if ($soSach !== null && abs($truoc - $soSach) > 0.000001) {
                throw new \RuntimeException(
                    'Tồn sổ sách hiện tại (' . self::qty($truoc) . ') đã khác số liệu lúc kiểm kê (' . self::qty($soSach)
                    . '). Cần kiểm kê lại dòng ' . $maLo . ' tại ' . $maViTri . ' trước khi thi hành.'
                );
            }
            $sau = $truoc + $delta;
            if ($sau < 0) {
                throw new \RuntimeException('Không đủ tồn để giảm ' . self::qty(abs($delta)) . ' (hiện còn ' . self::qty($truoc) . ').');
            }
            if ($sau < $daGiu) {
                throw new \RuntimeException('Không thể giảm: ' . self::qty($daGiu) . ' đang được giữ cho đơn hàng chưa xuất.');
            }

            self::markExecuted($maPhuongAn);

            if ($ton) {
                static::exec('UPDATE TonKho SET soLuongTon = ? WHERE maTonKho = ?', [$sau, $ton['maTonKho']]);
            } else {
                static::exec('INSERT INTO TonKho (maLoHang, maViTri, soLuongTon) VALUES (?, ?, ?)', [$maLo, $maViTri, $sau]);
            }

            $maPhieu = MaChungTu::next('PhieuDieuChinhTonKho');
            static::exec(
                'INSERT INTO PhieuDieuChinhTonKho (maPhieuDieuChinh, maPhuongAn, maLoHang, maViTri, loaiDieuChinh, soLuongDieuChinh, maNhanVienThiHanh, trangThai, ghiChu)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$maPhieu, $maPhuongAn, $maLo, $maViTri, $delta > 0 ? 'Tang' : 'Giam', abs($delta), $maNhanVien, 'Hoàn tất',
                 mb_substr('Theo phương án ' . $maPhuongAn . ' — ' . resolution_method_label($bienPhap), 0, 255)]
            );

            static::exec(
                'INSERT INTO LichSuBienDongTonKho (maBienDong, maLoHang, maViTri, loaiGiaoDich, soLuongBienDong,
                    soLuongTonTruoc, soLuongTonSau, maChungTuLienQuan, maNhanVienThucHien, ghiChu)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [MaChungTu::next('LichSuBienDongTonKho'), $maLo, $maViTri, $loaiGiaoDich, $delta, $truoc, $sau,
                 $maPhieu, $maNhanVien, mb_substr('Điều chỉnh theo phương án ' . $maPhuongAn, 0, 255)]
            );

            if ($pa['maHangBatThuong'] !== null) {
                static::exec(
                    'UPDATE HangBatThuong SET trangThai = ? WHERE maHangBatThuong = ? AND trangThai IN (?, ?)',
                    [HangBatThuong::TT_DA_XU_LY, $pa['maHangBatThuong'], HangBatThuong::TT_CACH_LY, HangBatThuong::TT_DANG_XU_LY]
                );
            }
            return $maPhieu;
        });
    }

    /** CAS: chuyển phương án 'Đang xử lý' (đã phê duyệt) → 'Hoàn tất' (đã thi hành) đúng một lần. */
    private static function markExecuted(string $maPhuongAn): void
    {
        $n = static::exec(
            'UPDATE PhuongAnXuLy SET trangThai = ? WHERE maPhuongAn = ? AND trangThai = ?',
            [PhuongAnXuLy::TT_HOAN_TAT, $maPhuongAn, PhuongAnXuLy::TT_DUYET]
        );
        if ($n !== 1) {
            throw new \RuntimeException('Phương án không còn ở trạng thái đã phê duyệt, vui lòng tải lại trang.');
        }
    }

    /** Định dạng số lượng trong thông báo lỗi (bỏ số 0 thừa). */
    private static function qty(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }
}


