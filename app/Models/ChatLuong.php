<?php
namespace App\Models;

use Core\Model;
use DomainException;

class ChatLuong extends Model
{
    // UC08 - Ghi nhận kết quả kiểm tra chất lượng
    private static function taoMaBienBan(): string
    {
        return 'BBKDCL' . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
    }

    public static function layDanhSachBienBanUC08(string $maLoHang): array
    {
        return static::all("SELECT bb.maBienBan, bb.maLoHang, bb.maNhanVienQC,
            nv.hoTen AS tenNhanVienQC, bb.ngayKiemDinh, bb.ketLuan,
            bb.lyDoKhongDat, bb.trangThai
            FROM BienBanKiemDinhChatLuong bb
            INNER JOIN NhanVien nv ON bb.maNhanVienQC = nv.maNhanVien
            WHERE bb.maLoHang = ? AND bb.trangThai = 'Chờ duyệt'
            ORDER BY bb.ngayKiemDinh DESC, bb.maBienBan DESC", [$maLoHang]);
    }

    public static function layKetQuaBienBanUC08(string $maBienBan): array
    {
        $rows = static::all("SELECT maTieuChi, giaTriDoDuoc, giaTriDinhTinh, danhGia, ghiChu
            FROM KetQuaKiemTra WHERE maBienBan = ? ORDER BY maTieuChi ASC", [$maBienBan]);
        return static::chuanHoaKetQua($rows);
    }

    private static function chuanHoaKetQua(array $rows): array
    {
        $ketQua = [];
        foreach ($rows as $row) {
            $ketQua[$row['maTieuChi']] = [
                'giaTriDoDuoc' => (string)($row['giaTriDoDuoc'] ?? ''),
                'giaTriDinhTinh' => (string)($row['giaTriDinhTinh'] ?? ''),
                'danhGia' => $row['danhGia'],
                'ghiChu' => (string)($row['ghiChu'] ?? '')
            ];
        }
        return $ketQua;
    }

    private static function taoPhienBanUC08(array $bienBan, array $chiTiet): string
    {
        return hash('sha256', json_encode([$bienBan, $chiTiet],
            JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR));
    }

    public static function layBienBanUC08(string $maLoHang, string $maBienBan): ?array
    {
        $bienBan = static::one("SELECT * FROM BienBanKiemDinhChatLuong
            WHERE maBienBan = ? AND maLoHang = ? AND trangThai = 'Chờ duyệt'", [$maBienBan, $maLoHang]);
        if ($bienBan === null) return null;
        $chiTiet = static::all("SELECT * FROM KetQuaKiemTra WHERE maBienBan = ? ORDER BY maTieuChi ASC", [$maBienBan]);
        return ['bienBan' => $bienBan, 'ketQua' => static::chuanHoaKetQua($chiTiet),
            'phienBan' => static::taoPhienBanUC08($bienBan, $chiTiet)];
    }

    public static function kiemTraDuLieuUC08(array $danhSachTieuChi, array $ketQua): array
    {
        $loi = [];
        if (empty($danhSachTieuChi)) return ['_chung' => 'Lô hàng chưa có tiêu chí kiểm tra phù hợp.'];
        foreach ($danhSachTieuChi as $tc) {
            $ma = $tc['maTieuChi'];
            $dong = $ketQua[$ma] ?? [];
            if (!is_array($dong)) {
                $loi[$ma][] = 'Thông tin kiểm tra không hợp lệ.';
                continue;
            }
            $danhGia = $dong['danhGia'] ?? '';
            $giaTriDoDuoc = $dong['giaTriDoDuoc'] ?? '';
            $giaTriDinhTinh = $dong['giaTriDinhTinh'] ?? '';
            $ghiChu = $dong['ghiChu'] ?? '';
            if (!is_string($danhGia) || !is_string($giaTriDoDuoc) || !is_string($giaTriDinhTinh) || !is_string($ghiChu)) {
                $loi[$ma][] = 'Thông tin kiểm tra không hợp lệ.';
                continue;
            }
            if (!mb_check_encoding($ghiChu, 'UTF-8') || mb_strlen($ghiChu, 'UTF-8') > 255 || strpos($ghiChu, "\0") !== false)
                $loi[$ma][] = 'Ghi chú không hợp lệ hoặc vượt quá 255 ký tự.';
            if (!mb_check_encoding($giaTriDinhTinh, 'UTF-8') || mb_strlen($giaTriDinhTinh, 'UTF-8') > 150 || strpos($giaTriDinhTinh, "\0") !== false)
                $loi[$ma][] = 'Giá trị định tính không hợp lệ hoặc vượt quá 150 ký tự.';
            $giaTriDoDuoc = trim($giaTriDoDuoc);
            $giaTriDinhTinh = trim($giaTriDinhTinh);
            $ghiChu = trim($ghiChu);
            if (!in_array($danhGia, ['Đạt', 'Không đạt'], true))
                $loi[$ma][] = 'Chưa chọn kết quả cho tiêu chí ' . $tc['tenTieuChi'] . '.';
            if ($danhGia === 'Không đạt' && $ghiChu === '')
                $loi[$ma][] = 'Bạn phải nhập ghi chú vì sao chọn không đạt.';
            if ($giaTriDoDuoc === '' && $giaTriDinhTinh === '')
                $loi[$ma][] = 'Phải nhập giá trị đo được hoặc giá trị định tính.';
            if ($giaTriDoDuoc !== '' && (!is_numeric($giaTriDoDuoc) || !is_finite((float)$giaTriDoDuoc)))
                $loi[$ma][] = 'Giá trị đo được phải là số hợp lệ.';
        }
        $maHopLe = array_column($danhSachTieuChi, 'maTieuChi');
        foreach (array_keys($ketQua) as $ma) {
            if (!in_array($ma, $maHopLe, true)) $loi['_chung'] = 'Danh sách tiêu chí kiểm tra không hợp lệ.';
        }
        return $loi;
    }

    public static function xacDinhKetLuanUC08(array $ketQua): string
    {
        if (empty($ketQua)) throw new DomainException('Chưa có kết quả kiểm tra.');
        foreach ($ketQua as $dong) if ($dong['danhGia'] === 'Không đạt') return 'Không đạt';
        return 'Đạt';
    }

    private static function layLyDoKhongDatUC08(array $ketQua): ?string
    {
        $so = 0;
        foreach ($ketQua as $dong) if ($dong['danhGia'] === 'Không đạt') $so++;
        return $so === 0 ? null : 'Có ' . $so . ' tiêu chí không đạt. Xem ghi chú chi tiết.';
    }

    // BỔ SUNG: Chỉ chủ sở hữu biên bản chờ duyệt mới được sửa.
    public static function coQuyenSuaBienBanUC08(string $maLoHang, string $maBienBan, string $maNhanVienQC): bool
    {
        if ($maLoHang === '' || $maBienBan === '' || $maNhanVienQC === '') return false;
        return static::one("SELECT bb.maBienBan FROM BienBanKiemDinhChatLuong bb
            INNER JOIN LoHang lh ON bb.maLoHang = lh.maLoHang
            WHERE bb.maBienBan = ? AND bb.maLoHang = ? AND bb.maNhanVienQC = ?
              AND bb.trangThai = 'Chờ duyệt' AND lh.trangThaiQC = 'Chờ kiểm tra' LIMIT 1",
            [$maBienBan, $maLoHang, $maNhanVienQC]) !== null;
    }

    public static function luuKetQuaUC08(string $maLoHang, string $maNhanVienQC, array $ketQua,
        string $maBienBan = '', string $phienBan = ''): string
    {
        if ($maNhanVienQC === '') throw new DomainException('Không xác định được nhân viên kiểm tra.');
        return static::transaction(function () use ($maLoHang, $maNhanVienQC, $ketQua, $maBienBan, $phienBan) {
            $loHang = static::one("SELECT lh.maLoHang, lh.trangThaiQC, hh.loaiHangHoa
                FROM LoHang lh INNER JOIN HangHoa hh ON lh.maHangHoa = hh.maHangHoa
                WHERE lh.maLoHang = ? FOR UPDATE", [$maLoHang]);
            if (!$loHang || $loHang['trangThaiQC'] !== 'Chờ kiểm tra' || !in_array($loHang['loaiHangHoa'], ['Nguyên liệu', 'Thành phẩm'], true))
                throw new DomainException('Lô hàng không còn ở trạng thái chờ kiểm tra chất lượng.');
            $danhSachBienBan = static::all("SELECT * FROM BienBanKiemDinhChatLuong
                WHERE maLoHang = ? AND trangThai = 'Chờ duyệt' ORDER BY maBienBan ASC FOR UPDATE", [$maLoHang]);
            $bienBanCapNhat = null;
            $chiTietCu = [];
            if ($maBienBan !== '') {
                foreach ($danhSachBienBan as $bb) {
                    if ($bb['maBienBan'] === $maBienBan) { $bienBanCapNhat = $bb; break; }
                }
                if ($bienBanCapNhat === null) throw new DomainException('Biên bản không tồn tại hoặc không còn ở trạng thái chờ duyệt.');
                // BỔ SUNG: kiểm tra quyền trên bản ghi đã khóa, không dựa vào giao diện.
                if ($bienBanCapNhat['maNhanVienQC'] !== $maNhanVienQC)
                    throw new DomainException('Bạn chỉ được sửa biên bản kiểm định do mình thực hiện.');
                $chiTietCu = static::all("SELECT * FROM KetQuaKiemTra WHERE maBienBan = ? ORDER BY maTieuChi ASC FOR UPDATE", [$maBienBan]);
                if ($phienBan === '' || !hash_equals(static::taoPhienBanUC08($bienBanCapNhat, $chiTietCu), $phienBan))
                    throw new DomainException('Biên bản đã thay đổi. Vui lòng tải lại thông tin trước khi cập nhật.');
            } elseif (!empty($danhSachBienBan)) {
                throw new DomainException('Lô hàng đã có biên bản chờ duyệt. Vui lòng chọn biên bản cần cập nhật.');
            }
            $danhSachTieuChi = static::all("SELECT maTieuChi, tenTieuChi, loaiHangHoa, donViDo,
                giaTriToiThieu, giaTriToiDa, phuongPhapKiemTra FROM TieuChiKiemTra
                WHERE loaiHangHoa = ? OR loaiHangHoa = 'Chung' ORDER BY maTieuChi ASC FOR UPDATE", [$loHang['loaiHangHoa']]);
            if (!empty(static::kiemTraDuLieuUC08($danhSachTieuChi, $ketQua)))
                throw new DomainException('Kết quả kiểm tra chưa đầy đủ hoặc không hợp lệ.');
            $maHopLe = array_column($danhSachTieuChi, 'maTieuChi');
            foreach ($chiTietCu as $dongCu) if (!in_array($dongCu['maTieuChi'], $maHopLe, true))
                throw new DomainException('Biên bản có tiêu chí cũ không còn áp dụng. Cần kiểm tra lại trước khi cập nhật.');
            $ketLuan = static::xacDinhKetLuanUC08($ketQua);
            $lyDoKhongDat = static::layLyDoKhongDatUC08($ketQua);
            if ($bienBanCapNhat === null) {
                $maBienBan = static::taoMaBienBan();
                static::exec("INSERT INTO BienBanKiemDinhChatLuong (maBienBan, maLoHang, maNhanVienQC,
                    ngayKiemDinh, ketLuan, lyDoKhongDat, maNhanVienDuyet, ngayXacNhan, trangThai, lyDoTuChoiDuyet, ghiChu)
                    VALUES (?, ?, ?, NOW(), ?, ?, NULL, NULL, 'Chờ duyệt', NULL, NULL)",
                    [$maBienBan, $maLoHang, $maNhanVienQC, $ketLuan, $lyDoKhongDat]);
            } else {
                // BỔ SUNG: không thay đổi maNhanVienQC khi sửa; thêm điều kiện chủ sở hữu.
                static::exec("UPDATE BienBanKiemDinhChatLuong SET ngayKiemDinh = NOW(), ketLuan = ?, lyDoKhongDat = ?
                    WHERE maBienBan = ? AND maLoHang = ? AND maNhanVienQC = ? AND trangThai = 'Chờ duyệt'",
                    [$ketLuan, $lyDoKhongDat, $maBienBan, $maLoHang, $maNhanVienQC]);
            }
            foreach ($danhSachTieuChi as $tc) {
                $ma = $tc['maTieuChi'];
                $dong = $ketQua[$ma];
                $so = trim($dong['giaTriDoDuoc'] ?? '');
                $dinhTinh = trim($dong['giaTriDinhTinh'] ?? '');
                $ghiChu = trim($dong['ghiChu'] ?? '');
                static::exec("INSERT INTO KetQuaKiemTra (maBienBan, maTieuChi, giaTriDoDuoc, giaTriDinhTinh, danhGia, ghiChu)
                    VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE giaTriDoDuoc = VALUES(giaTriDoDuoc),
                    giaTriDinhTinh = VALUES(giaTriDinhTinh), danhGia = VALUES(danhGia), ghiChu = VALUES(ghiChu)",
                    [$maBienBan, $ma, $so === '' ? null : (float)$so, $dinhTinh === '' ? null : $dinhTinh,
                    $dong['danhGia'], $ghiChu === '' ? null : $ghiChu]);
            }
            return $maBienBan;
        });
    }

    public static function coQuyenXoaBienBanUC08(string $maLoHang, string $maBienBan, string $maNhanVienQC): bool
    {
        return static::coQuyenSuaBienBanUC08($maLoHang, $maBienBan, $maNhanVienQC);
    }

    public static function xoaBienBanUC08(string $maLoHang, string $maBienBan, string $maNhanVienQC, string $phienBan): bool
    {
        if (preg_match('/^LO[0-9]{10}$/D', $maLoHang) !== 1 || preg_match('/^BBKDCL[0-9]{10}$/D', $maBienBan) !== 1 ||
            $maNhanVienQC === '' || preg_match('/^[a-f0-9]{64}$/D', $phienBan) !== 1)
            throw new DomainException('Thông tin xóa biên bản không hợp lệ.');
        return static::transaction(function () use ($maLoHang, $maBienBan, $maNhanVienQC, $phienBan) {
            $loHang = static::one('SELECT maLoHang, trangThaiQC FROM LoHang WHERE maLoHang = ? FOR UPDATE', [$maLoHang]);
            if ($loHang === null) throw new DomainException('Lô hàng không tồn tại.');
            if ($loHang['trangThaiQC'] !== 'Chờ kiểm tra') throw new DomainException('Lô hàng không còn ở trạng thái cho phép xóa kết quả kiểm định.');
            $bienBan = static::one('SELECT * FROM BienBanKiemDinhChatLuong WHERE maBienBan = ? AND maLoHang = ? FOR UPDATE', [$maBienBan, $maLoHang]);
            if ($bienBan === null) throw new DomainException('Biên bản kiểm định không tồn tại hoặc đã được xóa.');
            if ($bienBan['trangThai'] !== 'Chờ duyệt') throw new DomainException('Chỉ được xóa biên bản đang ở trạng thái chờ duyệt.');
            if ($bienBan['maNhanVienQC'] !== $maNhanVienQC) throw new DomainException('Bạn không có quyền xóa biên bản kiểm định của nhân viên khác.');
            $chiTiet = static::all('SELECT * FROM KetQuaKiemTra WHERE maBienBan = ? ORDER BY maTieuChi ASC FOR UPDATE', [$maBienBan]);
            if (!hash_equals(static::taoPhienBanUC08($bienBan, $chiTiet), $phienBan))
                throw new DomainException('Biên bản đã thay đổi. Vui lòng tải lại thông tin trước khi xóa.');
            static::exec('DELETE FROM KetQuaKiemTra WHERE maBienBan = ?', [$maBienBan]);
            static::exec("DELETE FROM BienBanKiemDinhChatLuong WHERE maBienBan = ? AND maLoHang = ? AND maNhanVienQC = ? AND trangThai = 'Chờ duyệt'", [$maBienBan, $maLoHang, $maNhanVienQC]);
            if (static::one('SELECT maBienBan FROM BienBanKiemDinhChatLuong WHERE maBienBan = ? LIMIT 1', [$maBienBan]) !== null)
                throw new DomainException('Không thể xóa biên bản kiểm định.');
            return true;
        });
    }

    // UC09 - Xác nhận kết quả kiểm tra chất lượng
    public static function kiemTraDuLieuUC09(string $ketQuaXacNhan, string $lyDoKhongDat = ''): array
    {
        if (!in_array($ketQuaXacNhan, ['Đạt', 'Không đạt'], true))
            return ['ketQuaXacNhan' => 'Vui lòng chọn kết quả xác nhận.'];
        if ($ketQuaXacNhan === 'Không đạt') {
            $lyDoKhongDat = trim($lyDoKhongDat);
            if ($lyDoKhongDat === '' || preg_match('/^[\s\p{Z}]*$/u', $lyDoKhongDat) === 1)
                return ['lyDoKhongDat' => 'Vui lòng nhập lý do khi chọn không đạt.'];
            if (!mb_check_encoding($lyDoKhongDat, 'UTF-8') || mb_strlen($lyDoKhongDat, 'UTF-8') > 200 ||
                preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $lyDoKhongDat) === 1 ||
                preg_match('/[\p{L}\p{N}]/u', $lyDoKhongDat) !== 1)
                return ['lyDoKhongDat' => 'Lý do bạn nhập không hợp lệ.'];
        }
        return [];
    }

    public static function coQuyenXacNhanUC09(string $maLoHang, string $maNhanVienDuyet): bool
    {
        if ($maLoHang === '' || $maNhanVienDuyet === '') return false;
        return LoHang::chiTietLoHangUC09($maLoHang, $maNhanVienDuyet) !== null;
    }

    public static function layDuLieuXacNhanUC09(string $maLoHang, string $maBienBan, string $maNhanVienDuyet): ?array
    {
        $loHang = LoHang::chiTietLoHangUC09($maLoHang, $maNhanVienDuyet);
        if ($loHang === null) return null;
        $bienBan = static::one("SELECT * FROM BienBanKiemDinhChatLuong WHERE maBienBan = ? AND maLoHang = ? AND trangThai = 'Chờ duyệt'", [$maBienBan, $maLoHang]);
        if ($bienBan === null) return null;
        $chiTiet = static::all('SELECT * FROM KetQuaKiemTra WHERE maBienBan = ? ORDER BY maTieuChi ASC', [$maBienBan]);
        if (empty($chiTiet)) return null;
        $ketQua = LoHang::layKetQuaKiemTraUC09($maLoHang, $maBienBan, $maNhanVienDuyet);
        if (empty($ketQua)) return null;
        $thongTinBienBan = LoHang::layBienBanUC09($maLoHang, $maBienBan, $maNhanVienDuyet);
        if ($thongTinBienBan === null) return null;
        $ketQuaGoc = [];
        foreach ($chiTiet as $dong) {
            $ketQuaGoc[$dong['maKetQuaKiemTra']] = [(string)($dong['giaTriDoDuoc'] ?? ''),
                (string)($dong['giaTriDinhTinh'] ?? ''), (string)$dong['danhGia'], (string)($dong['ghiChu'] ?? '')];
        }
        $ketQuaHienThi = [];
        foreach ($ketQua as $dong) {
            $ketQuaHienThi[$dong['maKetQuaKiemTra']] = [(string)($dong['giaTriDoDuoc'] ?? ''),
                (string)($dong['giaTriDinhTinh'] ?? ''), (string)$dong['danhGia'], (string)($dong['ghiChu'] ?? '')];
        }
        ksort($ketQuaGoc);
        ksort($ketQuaHienThi);
        if ($ketQuaGoc !== $ketQuaHienThi) return null;
        foreach (['maNhanVienQC', 'ngayKiemDinh', 'ketLuan', 'lyDoKhongDat', 'trangThai'] as $cot) {
            if (($bienBan[$cot] ?? null) !== ($thongTinBienBan[$cot] ?? null)) return null;
        }
        return ['loHang' => $loHang, 'bienBan' => $thongTinBienBan, 'ketQua' => $ketQua,
            'phienBan' => static::taoPhienBanUC08($bienBan, $chiTiet)];
    }

    private static function taoMaBienBanTraHangUC09(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $ma = 'BBTH' . str_pad((string)random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
            if (static::one('SELECT maBienBanTraHang FROM BienBanTraHang WHERE maBienBanTraHang = ? LIMIT 1', [$ma]) === null) return $ma;
        }
        throw new DomainException('Không thể tạo mã biên bản trả hàng.');
    }

    private static function lapBienBanTraHangUC09(array $loHang, string $maNhanVienDuyet, string $lyDoKhongDat): string
    {
        $maLoHang = $loHang['maLoHang'];
        if ($loHang['loaiHangHoa'] !== 'Nguyên liệu') throw new DomainException('Lô hàng không thuộc loại nguyên liệu.');
        $maPhieuTiepNhan = $loHang['maPhieuTiepNhan'] ?? '';
        if ($maPhieuTiepNhan === '') throw new DomainException('Không tìm thấy thông tin tiếp nhận nguyên liệu.');
        $phieuTiepNhan = static::one('SELECT maPhieuTiepNhan, maPhieuGiao, maXuong FROM PhieuTiepNhan WHERE maPhieuTiepNhan = ? FOR UPDATE', [$maPhieuTiepNhan]);
        if ($phieuTiepNhan === null || empty($phieuTiepNhan['maPhieuGiao']))
            throw new DomainException('Không xác định được chứng từ giao hàng của nhà cung cấp.');
        $maPhieuGiao = $phieuTiepNhan['maPhieuGiao'];
        if (static::one('SELECT maPhieuGiao FROM PhieuGiaoNCC WHERE maPhieuGiao = ? FOR UPDATE', [$maPhieuGiao]) === null)
            throw new DomainException('Phiếu giao hàng của nhà cung cấp không tồn tại.');
        $soLuongTra = (float)$loHang['soLuongBanDau'];
        if (!is_finite($soLuongTra) || $soLuongTra <= 0) throw new DomainException('Số lượng hàng cần trả không hợp lệ.');
        if (static::one("SELECT maBienBanTraHang FROM BienBanTraHang WHERE maLoHang = ? AND trangThai <> 'Đã hủy' LIMIT 1 FOR UPDATE", [$maLoHang]) !== null)
            throw new DomainException('Lô hàng đã có biên bản trả hàng chưa bị hủy.');
        $ma = static::taoMaBienBanTraHangUC09();
        static::exec("INSERT INTO BienBanTraHang (maBienBanTraHang, maLoHang, maPhieuGiao, soLuongTra, maNhanVienLap,
            ngayTraHang, lyDoTraHang, daiDienNhaCungCap, trangThai) VALUES (?, ?, ?, ?, ?, NULL, ?, NULL, 'Chờ xuất trả')",
            [$ma, $maLoHang, $maPhieuGiao, $soLuongTra, $maNhanVienDuyet, $lyDoKhongDat]);
        return $ma;
    }

    private static function layKhoHangLoiUC09(): ?array
    {
        return static::one("SELECT maKho, tenKho, loaiKho, trangThai FROM Kho WHERE loaiKho = 'Hàng lỗi'
            AND trangThai = 'Đang hoạt động' ORDER BY CASE WHEN maKho = 'K060001' THEN 0 ELSE 1 END, maKho ASC LIMIT 1 FOR UPDATE");
    }

    private static function cachLyThanhPhamUC09(array $loHang): array
    {
        if ($loHang['loaiHangHoa'] !== 'Thành phẩm') throw new DomainException('Lô hàng không thuộc loại thành phẩm.');
        $kho = static::layKhoHangLoiUC09();
        if ($kho === null) throw new DomainException('Không tìm thấy kho hàng lỗi đang hoạt động.');
        static::exec("UPDATE LoHang SET trangThaiVongDoi = 'Đang cách ly' WHERE maLoHang = ? AND trangThaiQC = 'Đã kiểm tra không đạt'", [$loHang['maLoHang']]);
        $sau = static::one('SELECT maLoHang, trangThaiQC, trangThaiVongDoi FROM LoHang WHERE maLoHang = ?', [$loHang['maLoHang']]);
        if ($sau === null || $sau['trangThaiVongDoi'] !== 'Đang cách ly')
            throw new DomainException('Không thể cập nhật trạng thái cách ly của lô thành phẩm.');
        return ['maKho' => $kho['maKho'], 'tenKho' => $kho['tenKho'], 'trangThaiVongDoi' => 'Đang cách ly'];
    }

    public static function xacNhanKetQuaUC09(string $maLoHang, string $maBienBan, string $maNhanVienDuyet,
        string $ketQuaXacNhan, string $lyDoKhongDat, string $phienBan): array
    {
        if (preg_match('/^LO[0-9]{10}$/D', $maLoHang) !== 1 || preg_match('/^BBKDCL[0-9]{10}$/D', $maBienBan) !== 1 ||
            preg_match('/^NV[0-9]{6}$/D', $maNhanVienDuyet) !== 1 || preg_match('/^[a-f0-9]{64}$/D', $phienBan) !== 1)
            throw new DomainException('Thông tin xác nhận kết quả không hợp lệ.');
        $lyDoKhongDat = trim($lyDoKhongDat);
        $loi = static::kiemTraDuLieuUC09($ketQuaXacNhan, $lyDoKhongDat);
        if (!empty($loi)) throw new DomainException(implode(' ', array_values($loi)));
        if ($ketQuaXacNhan === 'Đạt') $lyDoKhongDat = '';
        return static::transaction(function () use ($maLoHang, $maBienBan, $maNhanVienDuyet, $ketQuaXacNhan, $lyDoKhongDat, $phienBan) {
            $loHang = static::one("SELECT lh.maLoHang, lh.maHangHoa, lh.maPhieuTiepNhan, lh.soLuongBanDau,
                lh.trangThaiQC, lh.trangThaiVongDoi, hh.tenHangHoa, hh.loaiHangHoa, hh.donViTinhChuan
                FROM LoHang lh INNER JOIN HangHoa hh ON lh.maHangHoa = hh.maHangHoa WHERE lh.maLoHang = ? FOR UPDATE", [$maLoHang]);
            if ($loHang === null) throw new DomainException('Lô hàng không còn tồn tại trong hệ thống.');
            if ($loHang['trangThaiQC'] !== 'Chờ kiểm tra') throw new DomainException('Lô hàng không còn ở trạng thái chờ xác nhận.');
            if (!in_array($loHang['loaiHangHoa'], ['Nguyên liệu', 'Thành phẩm'], true))
                throw new DomainException('Loại hàng hóa không thuộc phạm vi xác nhận chất lượng.');
            $nhanVien = static::one('SELECT maNhanVien, hoTen, vaiTro, trangThai, maKho FROM NhanVien WHERE maNhanVien = ? FOR UPDATE', [$maNhanVienDuyet]);
            if ($nhanVien === null || $nhanVien['trangThai'] !== 'Đang làm việc')
                throw new DomainException('Không xác định được nhân viên có quyền xác nhận.');
            if (!in_array($nhanVien['vaiTro'], ['Quản lý kho', 'Quản trị viên'], true))
                throw new DomainException('Bạn không có quyền xác nhận kết quả kiểm tra chất lượng.');
            if ($nhanVien['vaiTro'] === 'Quản lý kho') {
                $kho = static::one('SELECT maKho, tenKho, loaiKho FROM Kho WHERE maKho = ? FOR UPDATE', [$nhanVien['maKho']]);
                if ($kho === null) throw new DomainException('Không xác định được kho bạn đang phụ trách.');
                if ($kho['loaiKho'] !== $loHang['loaiHangHoa'] && $kho['loaiKho'] !== 'Tổng hợp')
                    throw new DomainException('Lô hàng không thuộc phạm vi kho bạn quản lý.');
            }
            $bienBan = static::one('SELECT * FROM BienBanKiemDinhChatLuong WHERE maBienBan = ? AND maLoHang = ? FOR UPDATE', [$maBienBan, $maLoHang]);
            if ($bienBan === null) throw new DomainException('Biên bản kiểm định không còn tồn tại.');
            if ($bienBan['trangThai'] !== 'Chờ duyệt') throw new DomainException('Biên bản không còn ở trạng thái chờ duyệt.');
            $chiTiet = static::all('SELECT * FROM KetQuaKiemTra WHERE maBienBan = ? ORDER BY maTieuChi ASC FOR UPDATE', [$maBienBan]);
            if (empty($chiTiet)) throw new DomainException('Biên bản chưa có kết quả kiểm tra chất lượng.');
            if (!hash_equals(static::taoPhienBanUC08($bienBan, $chiTiet), $phienBan))
                throw new DomainException('Kết quả kiểm định đã thay đổi. Vui lòng tải lại thông tin trước khi xác nhận.');
            $danhSachTieuChi = static::all("SELECT maTieuChi, tenTieuChi, loaiHangHoa, donViDo, giaTriToiThieu, giaTriToiDa, phuongPhapKiemTra
                FROM TieuChiKiemTra WHERE loaiHangHoa = ? OR loaiHangHoa = 'Chung' ORDER BY maTieuChi ASC FOR UPDATE", [$loHang['loaiHangHoa']]);
            $ketQuaQC = static::chuanHoaKetQua($chiTiet);
            if (!empty(static::kiemTraDuLieuUC08($danhSachTieuChi, $ketQuaQC)))
                throw new DomainException('Kết quả kiểm định chưa đầy đủ hoặc không hợp lệ. Cần yêu cầu QC kiểm tra lại.');
            $ketLuanQC = $bienBan['ketLuan'];
            $lyDoKhongDatQC = $bienBan['lyDoKhongDat'] ?? null;
            $trangThaiQC = $ketQuaXacNhan === 'Đạt' ? 'Đã đạt chuẩn' : 'Đã kiểm tra không đạt';
            $lyDoLuu = $ketQuaXacNhan === 'Không đạt' ? $lyDoKhongDat : null;
            static::exec("UPDATE BienBanKiemDinhChatLuong SET ketLuan = ?, lyDoKhongDat = ?, maNhanVienDuyet = ?,
                ngayXacNhan = NOW(), trangThai = 'Đã duyệt', lyDoTuChoiDuyet = NULL
                WHERE maBienBan = ? AND maLoHang = ? AND trangThai = 'Chờ duyệt'",
                [$ketQuaXacNhan, $lyDoLuu, $maNhanVienDuyet, $maBienBan, $maLoHang]);
            static::exec("UPDATE LoHang SET trangThaiQC = ? WHERE maLoHang = ? AND trangThaiQC = 'Chờ kiểm tra'", [$trangThaiQC, $maLoHang]);
            $maBienBanTraHang = null;
            $maKhoHangLoi = null;
            $tenKhoHangLoi = null;
            $trangThaiVongDoi = $loHang['trangThaiVongDoi'];
            $huongXuLy = '';
            if ($ketQuaXacNhan === 'Đạt') {
                $huongXuLy = 'Lô hàng đã đạt yêu cầu chất lượng để thực hiện UC10.';
            } elseif ($loHang['loaiHangHoa'] === 'Nguyên liệu') {
                $maBienBanTraHang = static::lapBienBanTraHangUC09($loHang, $maNhanVienDuyet, $lyDoKhongDat);
                $huongXuLy = 'Đã lập biên bản trả hàng ' . $maBienBanTraHang . '. Lô hàng đang chờ xuất trả.';
            } elseif ($loHang['loaiHangHoa'] === 'Thành phẩm') {
                $kho = static::cachLyThanhPhamUC09($loHang);
                $maKhoHangLoi = $kho['maKho'];
                $tenKhoHangLoi = $kho['tenKho'];
                $trangThaiVongDoi = $kho['trangThaiVongDoi'];
                $huongXuLy = 'Lô thành phẩm đã được ghi nhận cách ly. Kho đích dự kiến: ' . $tenKhoHangLoi . ' (' . $maKhoHangLoi . '). Cần thực hiện nghiệp vụ chuyển hàng thực tế.';
            }
            $bienBanSauDuyet = static::one('SELECT maBienBan, ketLuan, lyDoKhongDat, maNhanVienDuyet, ngayXacNhan, trangThai FROM BienBanKiemDinhChatLuong WHERE maBienBan = ? AND maLoHang = ?', [$maBienBan, $maLoHang]);
            if ($bienBanSauDuyet === null || $bienBanSauDuyet['trangThai'] !== 'Đã duyệt' || $bienBanSauDuyet['ketLuan'] !== $ketQuaXacNhan ||
                $bienBanSauDuyet['maNhanVienDuyet'] !== $maNhanVienDuyet || empty($bienBanSauDuyet['ngayXacNhan']) || $bienBanSauDuyet['lyDoKhongDat'] !== $lyDoLuu)
                throw new DomainException('Không thể lưu kết quả xác nhận kiểm định.');
            $loHangSauDuyet = static::one('SELECT maLoHang, trangThaiQC, trangThaiVongDoi FROM LoHang WHERE maLoHang = ?', [$maLoHang]);
            if ($loHangSauDuyet === null || $loHangSauDuyet['trangThaiQC'] !== $trangThaiQC || $loHangSauDuyet['trangThaiVongDoi'] !== $trangThaiVongDoi)
                throw new DomainException('Không thể cập nhật trạng thái của lô hàng.');
            return ['thanhCong' => true, 'maLoHang' => $maLoHang, 'maBienBan' => $maBienBan,
                'tenLoHang' => $loHang['tenHangHoa'], 'loaiHangHoa' => $loHang['loaiHangHoa'], 'ketLuanQC' => $ketLuanQC,
                'lyDoKhongDatQC' => $lyDoKhongDatQC, 'ketQuaXacNhan' => $ketQuaXacNhan, 'lyDoKhongDat' => $lyDoLuu,
                'maNhanVienDuyet' => $maNhanVienDuyet, 'ngayXacNhan' => $bienBanSauDuyet['ngayXacNhan'],
                'trangThaiBienBan' => 'Đã duyệt', 'trangThaiQC' => $trangThaiQC, 'trangThaiVongDoi' => $trangThaiVongDoi,
                'maBienBanTraHang' => $maBienBanTraHang, 'maKhoHangLoi' => $maKhoHangLoi, 'tenKhoHangLoi' => $tenKhoHangLoi,
                'huongXuLy' => $huongXuLy, 'thongBao' => 'Xác nhận kết quả kiểm tra thành công.'];
        });
    }
}
