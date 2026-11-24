<?php
namespace App\Models;

use Core\Model;

/**
 * UC06 — Tiếp nhận hàng vào kho. Làm việc với PhieuGiaoNCC / ChiTietPhieuGiaoNCC (nguồn nhà cung cấp),
 * PhieuYeuCauNhapKho / ChiTietYeuCauNhapKho (nguồn xưởng sản xuất), PhieuTiepNhan và LoHang.
 *
 * Sau khi tiếp nhận: tạo PhieuTiepNhan 'Chờ kiểm định', mỗi dòng hàng tạo một LoHang
 * (trangThaiQC 'Chờ kiểm tra', soLuongBanDau quy đổi ra kg), cập nhật số lượng đã nhận và tình trạng chứng từ.
 * Chưa ghi TonKho: tồn kho chỉ phát sinh ở bước nhập kho (UC10).
 */
class TiepNhan extends Model
{
    public const NCC = 'ncc';
    public const XUONG = 'xuong';

    /** Số lượng sai lệch nhỏ hơn mức này coi là bằng nhau (số thực). */
    private const EPS = 0.0005;

    /* ------------------------------ Danh sách & chứng từ ------------------------------ */

    /** Chứng từ chờ tiếp nhận (phiếu giao NCC + phiếu yêu cầu nhập thành phẩm từ xưởng) còn dòng hàng cần tiếp nhận; lọc theo số chứng từ. */
    public static function pending(string $q = ''): array
    {
        $p = [];
        $cond1 = '';
        $cond2 = '';
        if ($q !== '') {
            $like = '%' . HangHoa::escapeLike($q) . '%';
            $cond1 = ' AND pg.maPhieuGiao LIKE ?';
            $cond2 = ' AND y.maYeuCauNhap LIKE ?';
            $p = [$like, $like];
        }
        $rows = static::all(
            "SELECT pg.maPhieuGiao AS soChungTu, 'ncc' AS loai, ncc.tenNCC AS nguonGiao, pg.ngayGiao AS ngayGiao, pg.trangThai AS trangThaiGoc
             FROM PhieuGiaoNCC pg JOIN NhaCungCap ncc ON ncc.maNCC = pg.maNCC
             WHERE pg.trangThai IN ('Chờ tiếp nhận', 'Đang tiếp nhận')
               AND EXISTS (SELECT 1 FROM ChiTietPhieuGiaoNCC c WHERE c.maPhieuGiao = pg.maPhieuGiao
                           AND COALESCE(c.soLuongThucNhan, 0) < c.soLuongGiao - 0.0005)$cond1
             UNION ALL
             SELECT y.maYeuCauNhap, 'xuong', xs.tenXuong, DATE(y.ngayYeuCau), y.trangThai
             FROM PhieuYeuCauNhapKho y JOIN XuongSanXuat xs ON xs.maXuong = y.maXuong
             WHERE y.nguonNhap = 'Xưởng sản xuất' AND y.trangThai = 'Chờ xử lý'
               AND EXISTS (SELECT 1 FROM ChiTietYeuCauNhapKho c WHERE c.maYeuCauNhap = y.maYeuCauNhap)$cond2
             ORDER BY ngayGiao DESC, soChungTu",
            $p
        );
        foreach ($rows as &$r) {
            $r['tinhTrang'] = self::tinhTrang($r['trangThaiGoc']);
        }
        return $rows;
    }

    /** Tình trạng hiển thị theo tài liệu UC: Chờ tiếp nhận | Đã tiếp nhận một phần. */
    public static function tinhTrang(string $trangThaiGoc): string
    {
        return $trangThaiGoc === 'Đang tiếp nhận' ? 'Đã tiếp nhận một phần' : 'Chờ tiếp nhận';
    }

    /**
     * Một chứng từ kèm các dòng hàng CÒN PHẢI TIẾP NHẬN ('dong'), hoặc null nếu không có.
     * Mỗi dòng: id, maHangHoa, tenHangHoa, trangThaiHang, hanChuan (ngày), soLoNCC, nsxNCC, hsdNCC,
     * soLuongGiao, daNhan, conLai, donViTinh, heSo (kg trên 1 đơn vị).
     * 'conHieuLuc' = chứng từ còn ở tình trạng được phép tiếp nhận.
     */
    public static function chungTu(string $so): ?array
    {
        if (preg_match('/^PG\d{10}$/', $so)) {
            $h = static::one(
                "SELECT pg.maPhieuGiao AS soChungTu, pg.ngayGiao, pg.trangThai AS trangThaiGoc, pg.ghiChu,
                        ncc.tenNCC AS nguonGiao, pg.nguoiGiao, pg.soBienSoXe, pg.soHoaDonNCC
                 FROM PhieuGiaoNCC pg JOIN NhaCungCap ncc ON ncc.maNCC = pg.maNCC
                 WHERE pg.maPhieuGiao = ?",
                [$so]
            );
            if (!$h) {
                return null;
            }
            $h['loai'] = self::NCC;
            $h['conHieuLuc'] = in_array($h['trangThaiGoc'], ['Chờ tiếp nhận', 'Đang tiếp nhận'], true);
            $lines = static::all(
                "SELECT c.maChiTietPhieuGiao AS id, c.maHangHoa, h.tenHangHoa, h.trangThai AS trangThaiHang,
                        h.hanSuDungTieuChuanNgay AS hanChuan, c.soLoNCC, c.ngaySanXuatNCC AS nsxNCC, c.hanSuDungNCC AS hsdNCC,
                        c.soLuongGiao, COALESCE(c.soLuongThucNhan, 0) AS daNhan, c.donViTinh, c.heSoQuyDoiApDung AS heSo
                 FROM ChiTietPhieuGiaoNCC c JOIN HangHoa h ON h.maHangHoa = c.maHangHoa
                 WHERE c.maPhieuGiao = ?
                 ORDER BY c.maChiTietPhieuGiao",
                [$so]
            );
        } elseif (preg_match('/^YCNK\d{10}$/', $so)) {
            $h = static::one(
                "SELECT y.maYeuCauNhap AS soChungTu, DATE(y.ngayYeuCau) AS ngayGiao, y.trangThai AS trangThaiGoc, y.lyDoNhap AS ghiChu,
                        xs.tenXuong AS nguonGiao, y.maXuong
                 FROM PhieuYeuCauNhapKho y JOIN XuongSanXuat xs ON xs.maXuong = y.maXuong
                 WHERE y.maYeuCauNhap = ? AND y.nguonNhap = 'Xưởng sản xuất'",
                [$so]
            );
            if (!$h) {
                return null;
            }
            $h['loai'] = self::XUONG;
            $h['conHieuLuc'] = $h['trangThaiGoc'] === 'Chờ xử lý';
            $lines = static::all(
                "SELECT c.maChiTietYeuCauNhap AS id, c.maHangHoa, h.tenHangHoa, h.trangThai AS trangThaiHang,
                        h.hanSuDungTieuChuanNgay AS hanChuan, NULL AS soLoNCC, NULL AS nsxNCC, NULL AS hsdNCC,
                        c.soLuongYeuCau AS soLuongGiao, 0 AS daNhan, c.donViTinh,
                        CASE WHEN c.donViTinh = 'kg' THEN 1 ELSE h.heSoQuyDoi END AS heSo
                 FROM ChiTietYeuCauNhapKho c JOIN HangHoa h ON h.maHangHoa = c.maHangHoa
                 WHERE c.maYeuCauNhap = ?
                 ORDER BY c.maChiTietYeuCauNhap",
                [$so]
            );
        } else {
            return null;
        }
        $h['tinhTrang'] = self::tinhTrang($h['trangThaiGoc']);
        $h['dong'] = [];
        foreach ($lines as $l) {
            $l['id'] = (int)$l['id'];
            $l['soLuongGiao'] = (float)$l['soLuongGiao'];
            $l['daNhan'] = (float)$l['daNhan'];
            $l['heSo'] = (float)$l['heSo'];
            $l['conLai'] = round($l['soLuongGiao'] - $l['daNhan'], 3);
            if ($l['conLai'] > self::EPS) {
                $h['dong'][$l['id']] = $l;
            }
        }
        return $h;
    }

    /**
     * Vị trí lưu trữ tạm hợp lệ: khu vực 'Tiếp nhận tạm' đang hoạt động, ô chưa khóa/bảo trì/lấp đầy.
     * $maKho: chỉ lấy vị trí của kho này (nhân viên kho); null = mọi kho (quản trị viên).
     * Trả về [maViTri => nhãn].
     */
    public static function viTriTam(?string $maKho): array
    {
        $p = [];
        $cond = '';
        if ($maKho !== null && $maKho !== '') {
            $cond = ' AND k.maKho = ?';
            $p[] = $maKho;
        }
        $rows = static::all(
            "SELECT vt.maViTri, CONCAT(k.tenKho, ' › ', kv.tenKhuVuc, ' › ', vt.tenViTri) AS nhan
             FROM ViTriLuuTru vt
             JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
             JOIN Kho k ON k.maKho = kv.maKho
             WHERE kv.loaiKhuVuc = 'Tiếp nhận tạm' AND kv.trangThai = 'Đang hoạt động' AND k.trangThai = 'Đang hoạt động'
               AND vt.trangThai NOT IN ('Đang bảo trì', 'Đã khóa', 'Đã lấp đầy')$cond
             ORDER BY k.maKho, kv.maKhuVuc, vt.maViTri",
            $p
        );
        return array_column($rows, 'nhan', 'maViTri');
    }

    /** Mã kho của nhân viên (null nếu không thuộc kho nào, VD quản trị viên). */
    public static function maKhoCua(string $maNhanVien): ?string
    {
        $r = static::one('SELECT maKho FROM NhanVien WHERE maNhanVien = ?', [$maNhanVien]);
        return $r['maKho'] ?? null;
    }

    /* ------------------------------ Xử lý dòng hàng ------------------------------ */

    /**
     * Ngày sản xuất và hạn sử dụng của lô: ưu tiên giá trị người dùng nhập, rồi giá trị trên chứng từ NCC,
     * rồi tính từ hạn sử dụng tiêu chuẩn của hàng hóa. Trả về [nsx, hsd] dạng Y-m-d.
     * @throws \RuntimeException nếu không xác định được hoặc hạn nhỏ hơn ngày sản xuất.
     */
    public static function resolveDates(array $line, ?string $nsxIn, ?string $hsdIn): array
    {
        $nsx = $nsxIn ?: ($line['nsxNCC'] ?: date('Y-m-d'));
        $hsd = $hsdIn ?: $line['hsdNCC'];
        if (!$hsd && $line['hanChuan'] !== null) {
            $hsd = date('Y-m-d', strtotime($nsx . ' +' . (int)$line['hanChuan'] . ' days'));
        }
        if (!$hsd) {
            throw new \RuntimeException('Chưa xác định được hạn sử dụng của "' . $line['tenHangHoa'] . '". Vui lòng nhập hạn sử dụng.');
        }
        if ($hsd < $nsx) {
            throw new \RuntimeException('Hạn sử dụng của "' . $line['tenHangHoa'] . '" phải lớn hơn hoặc bằng ngày sản xuất.');
        }
        return [$nsx, $hsd];
    }

    /** Mã mới dạng <prefix> + yymmdd + 4 số thứ tự (VD PTN2611230001), khóa dòng để tránh trùng. */
    private static function nextCode(string $table, string $col, string $prefix): string
    {
        $head = $prefix . date('ymd');
        $r = static::one("SELECT MAX($col) AS m FROM $table WHERE $col LIKE ? FOR UPDATE", [$head . '%']);
        $seq = !empty($r['m']) ? (int)substr((string)$r['m'], strlen($head)) + 1 : 1;
        if ($seq > 9999) {
            throw new \RuntimeException('Không thể tạo mã tự động. Vui lòng thử lại sau.');
        }
        return $head . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Ghi nhận tiếp nhận trong một giao dịch: tạo phiếu tiếp nhận, các lô hàng, cập nhật số lượng đã nhận và tình trạng chứng từ.
     * Nếu có lỗi thì không thay đổi gì (rollback).
     *
     * @param array $chon  [id dòng => ['sl' => float, 'nsx' => ?string, 'hsd' => ?string]] — chỉ các dòng được tiếp nhận
     * @return array ['maPhieuTiepNhan' => string, 'lo' => [mã lô...], 'tongKg' => float]
     * @throws \RuntimeException lỗi nghiệp vụ (thông báo hiển thị được cho người dùng)
     */
    public static function receive(string $so, string $maViTri, string $maNhanVien, string $ghiChu, array $chon): array
    {
        return static::transaction(function () use ($so, $maViTri, $maNhanVien, $ghiChu, $chon) {
            // Khóa chứng từ rồi đọc lại để bảo đảm chưa ai tiếp nhận trước đó
            if (strpos($so, 'PG') === 0) {
                static::one('SELECT maPhieuGiao FROM PhieuGiaoNCC WHERE maPhieuGiao = ? FOR UPDATE', [$so]);
            } else {
                static::one('SELECT maYeuCauNhap FROM PhieuYeuCauNhapKho WHERE maYeuCauNhap = ? FOR UPDATE', [$so]);
            }
            $ct = self::chungTu($so);
            if (!$ct || !$ct['conHieuLuc'] || !$ct['dong']) {
                throw new \RuntimeException('Không thể tiếp nhận hàng do không có chứng từ giao hàng hợp lệ');
            }
            if (!$chon) {
                throw new \RuntimeException('Vui lòng chọn ít nhất một dòng hàng để tiếp nhận.');
            }
            if (!static::one(
                "SELECT vt.maViTri FROM ViTriLuuTru vt JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
                 WHERE vt.maViTri = ? AND kv.loaiKhuVuc = 'Tiếp nhận tạm' AND kv.trangThai = 'Đang hoạt động'
                   AND vt.trangThai NOT IN ('Đang bảo trì', 'Đã khóa', 'Đã lấp đầy')",
                [$maViTri]
            )) {
                throw new \RuntimeException('Vị trí lưu trữ tạm không hợp lệ hoặc không còn ở trạng thái sử dụng.');
            }

            // Kiểm tra từng dòng
            foreach ($chon as $id => $in) {
                $line = $ct['dong'][$id] ?? null;
                if (!$line) {
                    throw new \RuntimeException('Có dòng hàng không hợp lệ hoặc đã được tiếp nhận đủ. Vui lòng tải lại chứng từ.');
                }
                if ($line['trangThaiHang'] !== HangHoa::TT_DANG_DUNG) {
                    throw new \RuntimeException('Hàng hóa "' . $line['tenHangHoa'] . '" đã ngừng sử dụng, không thể tiếp nhận.');
                }
                if (!($in['sl'] > 0) || $in['sl'] > $line['conLai'] + self::EPS) {
                    throw new \RuntimeException('Số lượng thực nhận của "' . $line['tenHangHoa'] . '" phải lớn hơn 0 và không vượt quá ' . $line['conLai'] . ' ' . $line['donViTinh'] . ' còn phải nhận.');
                }
            }

            $ma = self::nextCode('PhieuTiepNhan', 'maPhieuTiepNhan', 'PTN');
            static::exec(
                'INSERT INTO PhieuTiepNhan (maPhieuTiepNhan, maPhieuGiao, maXuong, maViTriTam, maNhanVienTiepNhan, ngayTiepNhan, trangThai, ghiChu)
                 VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)',
                [$ma, $ct['loai'] === self::NCC ? $so : null, $ct['loai'] === self::XUONG ? $ct['maXuong'] : null,
                 $maViTri, $maNhanVien, 'Chờ kiểm định', $ghiChu !== '' ? mb_substr($ghiChu, 0, 255) : null]
            );

            $lots = [];
            $tongKg = 0.0;
            foreach ($chon as $id => $in) {
                $line = $ct['dong'][$id];
                [$nsx, $hsd] = self::resolveDates($line, $in['nsx'] ?? null, $in['hsd'] ?? null);
                $kg = round($in['sl'] * $line['heSo'], 3);
                if (!($kg > 0)) {
                    throw new \RuntimeException('Số lượng quy đổi của "' . $line['tenHangHoa'] . '" không hợp lệ.');
                }
                $lo = self::nextCode('LoHang', 'maLoHang', 'LO');
                static::exec(
                    'INSERT INTO LoHang (maLoHang, maHangHoa, maPhieuTiepNhan, soLoNhaCungCap, ngaySanXuat, ngayHetHan, soLuongBanDau, trangThaiQC, trangThaiVongDoi)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$lo, $line['maHangHoa'], $ma, $line['soLoNCC'], $nsx, $hsd, $kg, 'Chờ kiểm tra', 'Đã tạo']
                );
                if ($ct['loai'] === self::NCC) {
                    static::exec(
                        'UPDATE ChiTietPhieuGiaoNCC SET soLuongThucNhan = COALESCE(soLuongThucNhan, 0) + ? WHERE maChiTietPhieuGiao = ?',
                        [(float)$in['sl'], $id]
                    );
                }
                $lots[] = $lo;
                $tongKg += $kg;
            }

            // Cập nhật tình trạng chứng từ
            if ($ct['loai'] === self::NCC) {
                $left = static::one(
                    'SELECT COUNT(*) AS c FROM ChiTietPhieuGiaoNCC WHERE maPhieuGiao = ? AND COALESCE(soLuongThucNhan, 0) < soLuongGiao - ?',
                    [$so, self::EPS]
                );
                static::exec(
                    'UPDATE PhieuGiaoNCC SET trangThai = ? WHERE maPhieuGiao = ?',
                    [((int)($left['c'] ?? 0)) === 0 ? 'Đã tiếp nhận' : 'Đang tiếp nhận', $so]
                );
            } else {
                // Phiếu yêu cầu nhập từ xưởng không lưu số lượng đã nhận từng dòng: tiếp nhận một lần rồi chuyển sang nhập kho
                static::exec("UPDATE PhieuYeuCauNhapKho SET trangThai = 'Đang nhập kho' WHERE maYeuCauNhap = ?", [$so]);
            }

            return ['maPhieuTiepNhan' => $ma, 'lo' => $lots, 'tongKg' => round($tongKg, 3)];
        });
    }
}
