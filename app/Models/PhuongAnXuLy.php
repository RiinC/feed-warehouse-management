<?php
namespace App\Models;

use Core\Model;

/**
 * UC26/UC27 — Phương án xử lý chênh lệch & hàng bất thường (PhuongAnXuLy).
 *
 * Một phương án gắn với ĐÚNG MỘT đối tượng: hoặc dòng chênh lệch kiểm kê (maChenhLech),
 * hoặc bản ghi hàng bất thường (maHangBatThuong) — ràng buộc ck_PhuongAnXuLy_4 trong schema.
 * Biện pháp phải tương thích chiều chênh lệch, nếu không sẽ sinh phiếu điều chỉnh sai dấu.
 */
class PhuongAnXuLy extends Model
{
    public const BIEN_PHAP = [
        'DieuChinhSoSachTang', 'DieuChinhSoSachGiam', 'GhiNhanHaoHut',
        'XuatTraNhaCungCap', 'TieuHuy', 'HaCapChatLuong',
    ];

    public const TT_CHO      = 'Chờ xử lý';
    public const TT_DUYET    = 'Đang xử lý';
    public const TT_HOAN_TAT = 'Hoàn tất';
    public const TT_THI_HANH = 'Hoàn tất';
    public const TT_TU_CHOI  = 'Từ chối';
    public const TT_HUY      = 'Đã hủy';

    public static function find(string $ma): ?array
    {
        return static::one(
            'SELECT p.*, nvd.hoTen AS tenNguoiDeXuat, nvp.hoTen AS tenNguoiPheDuyet,
                    c.loaiChenhLech, c.soLuongChenhLech, c.nguyenNhan,
                    q.maLoHang, q.maViTri, q.soLuongSoSach, q.soLuongThucTe, q.chenhLech,
                    q.maBienBanKiemKe,
                    hbt.loaiBatThuong, hbt.soLuong AS soLuongBatThuong, hbt.trangThai AS trangThaiBatThuong,
                    COALESCE(q.maLoHang, hbt.maLoHang) AS maLoHangApDung,
                    COALESCE(q.maViTri, hbt.maViTri) AS maViTriApDung
             FROM PhuongAnXuLy p
             LEFT JOIN NhanVien nvd ON nvd.maNhanVien = p.maNhanVienDeXuat
             LEFT JOIN NhanVien nvp ON nvp.maNhanVien = p.maNhanVienPheDuyet
             LEFT JOIN ChenhLechKiemKe c ON c.maChenhLech = p.maChenhLech
             LEFT JOIN KetQuaKiemKe q ON q.maKetQuaKiemKe = c.maKetQuaKiemKe
             LEFT JOIN HangBatThuong hbt ON hbt.maHangBatThuong = p.maHangBatThuong
             WHERE p.maPhuongAn = ?',
            [$ma]
        );
    }

    /** Lịch sử phương án của một dòng chênh lệch. */
    public static function forDiscrepancy(string $maChenhLech): array
    {
        return static::all(
            'SELECT p.*, nvd.hoTen AS tenNguoiDeXuat, nvp.hoTen AS tenNguoiPheDuyet
             FROM PhuongAnXuLy p
             LEFT JOIN NhanVien nvd ON nvd.maNhanVien = p.maNhanVienDeXuat
             LEFT JOIN NhanVien nvp ON nvp.maNhanVien = p.maNhanVienPheDuyet
             WHERE p.maChenhLech = ? ORDER BY p.ngayDeXuat DESC, p.maPhuongAn DESC',
            [$maChenhLech]
        );
    }

    /** Hàng đợi phương án của một biên bản kiểm kê (mọi trạng thái, mới nhất trước). */
    public static function byBienBan(string $maBienBan): array
    {
        return static::all(
            "SELECT p.*, nvd.hoTen AS tenNguoiDeXuat, nvp.hoTen AS tenNguoiPheDuyet,
                    c.loaiChenhLech, c.soLuongChenhLech, c.nguyenNhan,
                    q.maLoHang, q.maViTri, q.chenhLech,
                    h.tenHangHoa, v.tenViTri
             FROM PhuongAnXuLy p
             JOIN ChenhLechKiemKe c ON c.maChenhLech = p.maChenhLech
             JOIN KetQuaKiemKe q ON q.maKetQuaKiemKe = c.maKetQuaKiemKe
             JOIN LoHang l ON l.maLoHang = q.maLoHang
             JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
             JOIN ViTriLuuTru v ON v.maViTri = q.maViTri
             LEFT JOIN NhanVien nvd ON nvd.maNhanVien = p.maNhanVienDeXuat
             LEFT JOIN NhanVien nvp ON nvp.maNhanVien = p.maNhanVienPheDuyet
             WHERE q.maBienBanKiemKe = ?
             ORDER BY p.ngayDeXuat DESC, p.maPhuongAn DESC",
            [$maBienBan]
        );
    }

    /** Lịch sử phương án của một bản ghi hàng bất thường. */
    public static function byHangBatThuong(string $maHangBatThuong): array
    {
        return static::all(
            'SELECT p.*, nvd.hoTen AS tenNguoiDeXuat, nvp.hoTen AS tenNguoiPheDuyet
             FROM PhuongAnXuLy p
             LEFT JOIN NhanVien nvd ON nvd.maNhanVien = p.maNhanVienDeXuat
             LEFT JOIN NhanVien nvp ON nvp.maNhanVien = p.maNhanVienPheDuyet
             WHERE p.maHangBatThuong = ? ORDER BY p.ngayDeXuat DESC, p.maPhuongAn DESC',
            [$maHangBatThuong]
        );
    }

    /** Biện pháp hợp lệ cho một dòng chênh lệch [mã DB => nhãn hiển thị]. */
    public static function bienPhapChoChenhLech(string $loaiChenhLech): array
    {
        if ($loaiChenhLech === 'Thừa') {
            return [
                'DieuChinhSoSachTang' => 'Điều chỉnh sổ sách tăng',
            ];
        }
        if ($loaiChenhLech === 'Thiếu') {
            return [
                'DieuChinhSoSachGiam' => 'Điều chỉnh sổ sách giảm',
                'GhiNhanHaoHut'       => 'Ghi nhận hao hụt',
            ];
        }
        return [];
    }

    /** Biện pháp hợp lệ cho hàng bất thường [mã DB => nhãn hiển thị]. */
    public static function bienPhapChoHangBatThuong(): array
    {
        return [
            'TieuHuy'           => 'Tiêu hủy',
            'XuatTraNhaCungCap' => 'Xuất trả nhà cung cấp',
            'GhiNhanHaoHut'     => 'Ghi nhận hao hụt',
            'HaCapChatLuong'    => 'Hạ cấp chất lượng',
        ];
    }

    /**
     * Đề xuất phương án xử lý. Chỉ nhận đúng một đối tượng (chênh lệch HOẶC hàng bất thường)
     * và chặn đề xuất trùng khi đã có phương án còn hiệu lực. Trả về mã phương án.
     */
    public static function propose(array $d, string $maNhanVien, ?string $scope = null): string
    {
        $maCL = trim((string)($d['maChenhLech'] ?? ''));
        $maHBT = trim((string)($d['maHangBatThuong'] ?? ''));
        $bienPhap = resolution_method_code((string)($d['bienPhapXuLy'] ?? ''));
        $moTa = trim((string)($d['moTa'] ?? ''));
        if (mb_strlen($moTa) > 255) {
            throw new \RuntimeException('Mô tả đề xuất tối đa 255 ký tự.');
        }
        $moTaDb = $moTa !== '' ? $moTa : null;
        if (($maCL === '') === ($maHBT === '')) {
            throw new \RuntimeException('Mỗi phương án chỉ gắn với một dòng chênh lệch hoặc một bản ghi hàng bất thường.');
        }
        if (!in_array($bienPhap, self::BIEN_PHAP, true)) {
            throw new \RuntimeException('Biện pháp xử lý không hợp lệ.');
        }
        return static::transaction(function () use ($maCL, $maHBT, $bienPhap, $moTaDb, $maNhanVien, $scope) {
            if ($maCL !== '') {
                $cl = static::one(
                    'SELECT c.*, q.maBienBanKiemKe, b.trangThai AS trangThaiBienBan
                     FROM ChenhLechKiemKe c
                     JOIN KetQuaKiemKe q ON q.maKetQuaKiemKe = c.maKetQuaKiemKe
                     JOIN BienBanKiemKe b ON b.maBienBanKiemKe = q.maBienBanKiemKe
                     WHERE c.maChenhLech = ? FOR UPDATE',
                    [$maCL]
                );
                if (!$cl) {
                    throw new \RuntimeException('Không tìm thấy dòng chênh lệch.');
                }
                if ($cl['trangThaiBienBan'] !== BienBanKiemKe::TT_DANG) {
                    throw new \RuntimeException('Biên bản đã chốt nên không đề xuất thêm phương án.');
                }
                $hopLe = self::bienPhapChoChenhLech((string)$cl['loaiChenhLech']);
                if (!$hopLe) {
                    throw new \RuntimeException('Dòng này không có chênh lệch nên không cần phương án xử lý.');
                }
                if (!array_key_exists($bienPhap, $hopLe)) {
                    throw new \RuntimeException('Chênh lệch "' . $cl['loaiChenhLech'] . '" chỉ xử lý bằng: ' . implode(', ', $hopLe) . '.');
                }
                self::assertNoOpenProposal('maChenhLech', $maCL);
                $ma = MaChungTu::next('PhuongAnXuLy');
                static::exec(
                    'INSERT INTO PhuongAnXuLy (maPhuongAn, maChenhLech, bienPhapXuLy, moTa, maNhanVienDeXuat, trangThai) VALUES (?, ?, ?, ?, ?, ?)',
                    [$ma, $maCL, $bienPhap, $moTaDb, $maNhanVien, self::TT_CHO]
                );
                return $ma;
            }

            $hbt = static::one(
                'SELECT hb.*, kv.maKho
                 FROM HangBatThuong hb
                 JOIN ViTriLuuTru v ON v.maViTri = hb.maViTri
                 JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
                 WHERE hb.maHangBatThuong = ? FOR UPDATE',
                [$maHBT]
            );
            if (!$hbt) {
                throw new \RuntimeException('Không tìm thấy bản ghi hàng bất thường.');
            }
            if ($scope !== null && ($scope === '' || $hbt['maKho'] !== $scope)) {
                throw new \RuntimeException('Bạn chỉ được đề xuất phương án cho hàng bất thường thuộc kho mình phụ trách.');
            }
            if ($hbt['trangThai'] === HangBatThuong::TT_DA_XU_LY) {
                throw new \RuntimeException('Hàng bất thường đã được xử lý xong nên không đề xuất thêm phương án.');
            }
            $hopLeHbt = self::bienPhapChoHangBatThuong();
            if (!array_key_exists($bienPhap, $hopLeHbt)) {
                throw new \RuntimeException('Hàng bất thường chỉ xử lý bằng: ' . implode(', ', $hopLeHbt) . '.');
            }
            self::assertNoOpenProposal('maHangBatThuong', $maHBT);
            $ma = MaChungTu::next('PhuongAnXuLy');
            static::exec(
                'INSERT INTO PhuongAnXuLy (maPhuongAn, maHangBatThuong, bienPhapXuLy, moTa, maNhanVienDeXuat, trangThai) VALUES (?, ?, ?, ?, ?, ?)',
                [$ma, $maHBT, $bienPhap, $moTaDb, $maNhanVien, self::TT_CHO]
            );
            if ($hbt['trangThai'] === HangBatThuong::TT_CACH_LY) {
                static::exec(
                    'UPDATE HangBatThuong SET trangThai = ? WHERE maHangBatThuong = ?',
                    [HangBatThuong::TT_DANG_XU_LY, $maHBT]
                );
            }
            return $ma;
        });
    }

    /** Phê duyệt phương án đang chờ (CAS để hai người bấm cùng lúc không ghi đè nhau). Trả về thông tin PA. */
    public static function approve(string $ma, string $maNhanVien, string $yKien = '', ?string $scope = null): array
    {
        $yKien = trim($yKien);
        if (mb_strlen($yKien) > 255) {
            throw new \RuntimeException('Ý kiến phê duyệt tối đa 255 ký tự.');
        }
        return static::transaction(function () use ($ma, $maNhanVien, $yKien, $scope) {
            $pa = static::one(
                'SELECT p.*, kv.maKho
                 FROM PhuongAnXuLy p
                 LEFT JOIN HangBatThuong hb ON hb.maHangBatThuong = p.maHangBatThuong
                 LEFT JOIN ViTriLuuTru v ON v.maViTri = hb.maViTri
                 LEFT JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
                 WHERE p.maPhuongAn = ? FOR UPDATE',
                [$ma]
            );
            if (!$pa || $pa['trangThai'] !== self::TT_CHO) {
                throw new \RuntimeException('Chỉ phê duyệt được phương án đang chờ phê duyệt (có thể người khác vừa xử lý).');
            }
            if (!empty($pa['maHangBatThuong']) && $scope !== null && ($scope === '' || $pa['maKho'] !== $scope)) {
                throw new \RuntimeException('Bạn không có quyền phê duyệt phương án của kho khác.');
            }
            $n = static::exec(
                'UPDATE PhuongAnXuLy
                 SET trangThai = ?, maNhanVienPheDuyet = ?, ngayPheDuyet = GREATEST(NOW(), ngayDeXuat), yKienPheDuyet = ?
                 WHERE maPhuongAn = ? AND trangThai = ?',
                [self::TT_DUYET, $maNhanVien, $yKien !== '' ? $yKien : null, $ma, self::TT_CHO]
            );
            if ($n !== 1) {
                throw new \RuntimeException('Chỉ phê duyệt được phương án đang chờ phê duyệt (có thể người khác vừa xử lý).');
            }
            if (!empty($pa['maHangBatThuong'])) {
                static::exec(
                    'UPDATE HangBatThuong SET trangThai = ? WHERE maHangBatThuong = ? AND trangThai = ?',
                    [HangBatThuong::TT_DANG_XU_LY, $pa['maHangBatThuong'], HangBatThuong::TT_CACH_LY]
                );
            }
            return $pa;
        });
    }

    /** Từ chối phương án đang chờ — bắt buộc nêu lý do (ràng buộc ck_PhuongAnXuLy_7). Trả về thông tin PA. */
    public static function reject(string $ma, string $maNhanVien, string $lyDo, ?string $scope = null): array
    {
        $lyDo = trim($lyDo);
        if ($lyDo === '') {
            throw new \RuntimeException('Vui lòng nhập lý do từ chối.');
        }
        if (mb_strlen($lyDo) > 255) {
            throw new \RuntimeException('Lý do từ chối tối đa 255 ký tự.');
        }
        return static::transaction(function () use ($ma, $maNhanVien, $lyDo, $scope) {
            $pa = static::one(
                'SELECT p.*, kv.maKho
                 FROM PhuongAnXuLy p
                 LEFT JOIN HangBatThuong hb ON hb.maHangBatThuong = p.maHangBatThuong
                 LEFT JOIN ViTriLuuTru v ON v.maViTri = hb.maViTri
                 LEFT JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
                 WHERE p.maPhuongAn = ? FOR UPDATE',
                [$ma]
            );
            if (!$pa || $pa['trangThai'] !== self::TT_CHO) {
                throw new \RuntimeException('Chỉ từ chối được phương án đang chờ phê duyệt (có thể người khác vừa xử lý).');
            }
            if (!empty($pa['maHangBatThuong']) && $scope !== null && ($scope === '' || $pa['maKho'] !== $scope)) {
                throw new \RuntimeException('Bạn không có quyền từ chối phương án của kho khác.');
            }
            $n = static::exec(
                'UPDATE PhuongAnXuLy
                 SET trangThai = ?, maNhanVienPheDuyet = ?, ngayPheDuyet = GREATEST(NOW(), ngayDeXuat), yKienPheDuyet = ?
                 WHERE maPhuongAn = ? AND trangThai = ?',
                [self::TT_TU_CHOI, $maNhanVien, $lyDo, $ma, self::TT_CHO]
            );
            if ($n !== 1) {
                throw new \RuntimeException('Chỉ từ chối được phương án đang chờ phê duyệt (có thể người khác vừa xử lý).');
            }
            if (!empty($pa['maHangBatThuong'])) {
                self::syncHangBatThuongStatus((string)$pa['maHangBatThuong']);
            }
            return $pa;
        });
    }

    /** Hủy phương án chưa thi hành ('Chờ xử lý' hoặc 'Đang xử lý' chưa thi hành). */
    public static function cancel(string $ma, string $maNhanVien, string $lyDo = '', bool $canHandle = false, ?string $scope = null): array
    {
        $lyDo = trim($lyDo);
        if (mb_strlen($lyDo) > 255) {
            throw new \RuntimeException('Lý do hủy tối đa 255 ký tự.');
        }
        return static::transaction(function () use ($ma, $maNhanVien, $lyDo, $canHandle, $scope) {
            $pa = static::one(
                'SELECT p.*, kv.maKho
                 FROM PhuongAnXuLy p
                 LEFT JOIN HangBatThuong hb ON hb.maHangBatThuong = p.maHangBatThuong
                 LEFT JOIN ViTriLuuTru v ON v.maViTri = hb.maViTri
                 LEFT JOIN KhuVuc kv ON kv.maKhuVuc = v.maKhuVuc
                 WHERE p.maPhuongAn = ? FOR UPDATE',
                [$ma]
            );
            if (!$pa || !in_array($pa['trangThai'], [self::TT_CHO, self::TT_DUYET], true)) {
                throw new \RuntimeException('Chỉ hủy được phương án đang chờ duyệt hoặc chưa thi hành.');
            }
            if (!empty($pa['maHangBatThuong']) && $scope !== null && ($scope === '' || $pa['maKho'] !== $scope)) {
                throw new \RuntimeException('Bạn không có quyền hủy phương án của kho khác.');
            }
            if ($pa['trangThai'] === self::TT_DUYET && !$canHandle) {
                throw new \RuntimeException('Chỉ Quản lý kho hoặc Quản trị viên mới được hủy phương án đã phê duyệt.');
            }
            if ($pa['trangThai'] === self::TT_CHO && !$canHandle && $pa['maNhanVienDeXuat'] !== $maNhanVien) {
                throw new \RuntimeException('Bạn chỉ được hủy phương án do chính mình đề xuất.');
            }
            $note = $lyDo !== '' ? $lyDo : 'Hủy phương án đề xuất';
            $n = static::exec(
                'UPDATE PhuongAnXuLy
                 SET trangThai = ?, maNhanVienPheDuyet = ?, ngayPheDuyet = GREATEST(NOW(), ngayDeXuat), yKienPheDuyet = ?
                 WHERE maPhuongAn = ? AND trangThai IN (?, ?)',
                [self::TT_HUY, $maNhanVien, $note, $ma, self::TT_CHO, self::TT_DUYET]
            );
            if ($n !== 1) {
                throw new \RuntimeException('Không thể hủy phương án (có thể người khác vừa xử lý).');
            }
            if (!empty($pa['maHangBatThuong'])) {
                self::syncHangBatThuongStatus((string)$pa['maHangBatThuong']);
            }
            return $pa;
        });
    }

    /** Đồng bộ lại trạng thái HangBatThuong khi phương án bị từ chối hoặc hủy. */
    private static function syncHangBatThuongStatus(string $maHbt): void
    {
        $done = static::one(
            'SELECT 1 AS x FROM PhuongAnXuLy WHERE maHangBatThuong = ? AND trangThai = ? LIMIT 1',
            [$maHbt, self::TT_HOAN_TAT]
        );
        if ($done) {
            return;
        }
        $open = static::one(
            'SELECT 1 AS x FROM PhuongAnXuLy WHERE maHangBatThuong = ? AND trangThai IN (?, ?) LIMIT 1',
            [$maHbt, self::TT_CHO, self::TT_DUYET]
        );
        if (!$open) {
            static::exec(
                'UPDATE HangBatThuong SET trangThai = ? WHERE maHangBatThuong = ? AND trangThai = ?',
                [HangBatThuong::TT_CACH_LY, $maHbt, HangBatThuong::TT_DANG_XU_LY]
            );
        }
    }

    /** Chặn đề xuất trùng khi đã có phương án chờ duyệt/đã duyệt/đã thi hành. */
    private static function assertNoOpenProposal(string $cot, string $ma): void
    {
        $row = static::one(
            "SELECT maPhuongAn, trangThai FROM PhuongAnXuLy
             WHERE `$cot` = ? AND trangThai IN (?, ?, ?)
             ORDER BY ngayDeXuat DESC LIMIT 1",
            [$ma, self::TT_CHO, self::TT_DUYET, self::TT_HOAN_TAT]
        );
        if ($row) {
            throw new \RuntimeException('Đã có phương án ' . $row['maPhuongAn'] . ' ở trạng thái "' . proposal_status_label((string)$row['trangThai']) . '".');
        }
    }
}

