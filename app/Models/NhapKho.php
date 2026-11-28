<?php
namespace App\Models;

use Core\Model;
use DomainException;

/** UC10 - Lập phiếu nhập kho cho một lô, tại một vị trí lưu trữ. */
class NhapKho extends Model
{
    private static function khoa(bool $khoa): string
    {
        return $khoa ? ' FOR UPDATE' : '';
    }

    private static function khoPhuTrach(string $maNhanVien, bool $khoa): array
    {
        $nv = static::one('SELECT maNhanVien, hoTen, vaiTro, trangThai, maKho FROM NhanVien WHERE maNhanVien = ?' . static::khoa($khoa), [$maNhanVien]);
        if (!$nv || $nv['vaiTro'] !== 'Nhân viên kho' || $nv['trangThai'] !== 'Đang làm việc' || empty($nv['maKho'])) {
            throw new DomainException('Bạn không có quyền lập phiếu nhập kho.');
        }
        $kho = static::one('SELECT * FROM Kho WHERE maKho = ?' . static::khoa($khoa), [$nv['maKho']]);
        if (!$kho || $kho['trangThai'] !== 'Đang hoạt động' || !in_array($kho['loaiKho'], ['Nguyên liệu', 'Thành phẩm', 'Tổng hợp'], true)) {
            throw new DomainException('Kho phụ trách không thuộc phạm vi nhập kho hoặc không hoạt động.');
        }
        $kho['hoTenNhanVien'] = $nv['hoTen'];
        return $kho;
    }

    /** Chỉ các vị trí tiếp nhận tạm đang sử dụng, thuộc kho phân công. */
    public static function layDanhSachViTriUC10(string $maNhanVien): array
    {
        $kho = static::khoPhuTrach($maNhanVien, false);
        return static::all("SELECT vt.maViTri, vt.tenViTri, kv.tenKhuVuc, vt.sucChuaToiDa,
            vt.trangThai, COALESCE(t.tonKg, 0) AS soLuongDangChua,
            GREATEST(0, vt.sucChuaToiDa - COALESCE(t.tonKg, 0)) AS sucChuaConLai
            FROM ViTriLuuTru vt INNER JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
            LEFT JOIN (SELECT maViTri, SUM(soLuongTon) AS tonKg FROM TonKho GROUP BY maViTri) t ON t.maViTri = vt.maViTri
            WHERE kv.maKho = ? AND kv.trangThai = 'Đang hoạt động' AND kv.loaiKhuVuc = 'Tiếp nhận tạm'
              AND vt.trangThai IN ('Đang để trống', 'Đang chứa hàng')
              AND vt.sucChuaToiDa > COALESCE(t.tonKg, 0)
            ORDER BY kv.maKhuVuc, vt.day, vt.ke, vt.tang, vt.o", [$kho['maKho']]);
    }

    private static function soHopLe($giaTri): bool
    {
        return (is_string($giaTri) || is_float($giaTri) || is_int($giaTri))
            && is_numeric($giaTri) && is_finite((float)$giaTri);
    }

    /** Kiểm tra đầu vào; không nhận kho, đơn vị, hệ số hoặc chứng từ từ POST. */
    private static function kiemTraDauVao(string $maLoHang, string $maNhanVien, string $maViTri, $soLuongNhap, string $ghiChu): float
    {
        if (!preg_match('/^LO[0-9]{10}$/D', $maLoHang) || !preg_match('/^NV[0-9]{6}$/D', $maNhanVien)) {
            throw new DomainException('Thông tin lô hàng hoặc nhân viên không hợp lệ.');
        }
        if ($maViTri === '') throw new DomainException('Vui lòng chọn vị trí lưu trữ.');
        if (!preg_match('/^VT[0-9]{12}$/D', $maViTri)) throw new DomainException('Mã vị trí lưu trữ không hợp lệ.');
        if (!static::soHopLe($soLuongNhap) || (float)$soLuongNhap <= 0) throw new DomainException('Số lượng nhập không hợp lệ.');
        if (!mb_check_encoding($ghiChu, 'UTF-8') || mb_strlen($ghiChu, 'UTF-8') > 255 || strpos($ghiChu, "\0") !== false) {
            throw new DomainException('Ghi chú không hợp lệ hoặc vượt quá 255 ký tự.');
        }
        return (float)$soLuongNhap;
    }

    /** Đọc lại tất cả dữ liệu có ảnh hưởng đến việc nhập kho. Khi lưu, dùng khóa hàng. */
    private static function chuanBiUC10(string $maLoHang, string $maNhanVien, string $maViTri, $soLuongNhap, string $ghiChu, bool $khoa): array
    {
        $ghiChu = trim($ghiChu);
        $soLuong = static::kiemTraDauVao($maLoHang, $maNhanVien, $maViTri, $soLuongNhap, $ghiChu);
        // Thứ tự khóa: lô -> nhân viên/kho -> chứng từ -> vị trí -> tồn kho.
        $lo = static::one('SELECT * FROM LoHang WHERE maLoHang = ?' . static::khoa($khoa), [$maLoHang]);
        if (!$lo) throw new DomainException('Lô hàng không tồn tại.');
        if ($lo['trangThaiVongDoi'] !== 'Đã tạo') throw new DomainException('Lô hàng đã được nhập kho hoặc không còn ở trạng thái chờ nhập kho.');
        if ($lo['trangThaiQC'] !== 'Đã đạt chuẩn') throw new DomainException('Lô hàng chưa được xác nhận đạt chất lượng.');
        $kho = static::khoPhuTrach($maNhanVien, $khoa);
        $hh = static::one('SELECT * FROM HangHoa WHERE maHangHoa = ?' . static::khoa($khoa), [$lo['maHangHoa']]);
        if (!$hh || !in_array($hh['loaiHangHoa'], ['Nguyên liệu', 'Thành phẩm'], true) || ($kho['loaiKho'] !== 'Tổng hợp' && $kho['loaiKho'] !== $hh['loaiHangHoa'])) {
            throw new DomainException('Lô hàng không thuộc phạm vi kho bạn phụ trách.');
        }
        $daNhap = static::one("SELECT ct.maChiTietPhieuNhap FROM ChiTietPhieuNhapKho ct INNER JOIN PhieuNhapKho pn ON pn.maPhieuNhapKho = ct.maPhieuNhapKho WHERE ct.maLoHang = ? AND pn.trangThai <> 'Đã hủy' LIMIT 1" . static::khoa($khoa), [$maLoHang]);
        if ($daNhap) throw new DomainException('Lô hàng đã được lập phiếu nhập kho.');
        $banDau = (float)$lo['soLuongBanDau'];
        if (!is_finite($banDau) || $banDau <= 0 || $soLuong > $banDau) throw new DomainException('Số lượng nhập vượt quá số lượng được phép nhập.');
        if ($soLuong < $banDau && preg_match('/[\p{L}\p{N}]/u', $ghiChu) !== 1) throw new DomainException('Vui lòng ghi chú lý do nhập thiếu so với số lượng tiếp nhận.');
        $dvt = $hh['donViTinhChuan'];
        $heSo = $dvt === 'kg' ? 1.0 : (float)$hh['heSoQuyDoi'];
        $kg = $soLuong * $heSo;
        if (!in_array($dvt, ['kg','bao','thùng'], true) || !is_finite($heSo) || $heSo <= 0 || !is_finite($kg) || $kg <= 0) throw new DomainException('Đơn vị tính hoặc hệ số quy đổi không hợp lệ.');
        $ptn = static::one('SELECT * FROM PhieuTiepNhan WHERE maPhieuTiepNhan = ?' . static::khoa($khoa), [$lo['maPhieuTiepNhan']]);
        if (!$ptn || in_array($ptn['trangThai'], ['Đã hủy','Đã từ chối tiếp nhận'], true)) throw new DomainException('Thiếu phiếu tiếp nhận hợp lệ.');
        $bb = static::all("SELECT maBienBan, maNhanVienDuyet, ngayXacNhan FROM BienBanKiemDinhChatLuong WHERE maLoHang = ? AND trangThai = 'Đã duyệt' AND ketLuan = 'Đạt' AND maNhanVienDuyet IS NOT NULL AND ngayXacNhan IS NOT NULL" . static::khoa($khoa), [$maLoHang]);
        if (count($bb) !== 1) throw new DomainException('Cần đúng một biên bản kiểm định đã được xác nhận đạt.');
        // Tự động tìm yêu cầu nhập theo nguồn và mặt hàng; không chọn ngẫu nhiên.
        $yc = static::all("SELECT yc.maYeuCauNhap, yc.trangThai, ct.maChiTietYeuCauNhap, ct.soLuongYeuCau, ct.donViTinh
            FROM PhieuYeuCauNhapKho yc INNER JOIN ChiTietYeuCauNhapKho ct ON ct.maYeuCauNhap = yc.maYeuCauNhap
            WHERE ct.maHangHoa = ? AND yc.trangThai IN ('Chờ xử lý','Đang nhập kho')
            AND ((yc.nguonNhap = 'Nhà cung cấp' AND yc.maPhieuGiao = ?) OR (yc.nguonNhap = 'Xưởng sản xuất' AND yc.maXuong = ?))
            ORDER BY yc.maYeuCauNhap" . static::khoa($khoa), [$lo['maHangHoa'], $ptn['maPhieuGiao'], $ptn['maXuong']]);
        if (count($yc) !== 1) throw new DomainException(count($yc) === 0 ? 'Không tìm thấy phiếu yêu cầu nhập kho hợp lệ.' : 'Có nhiều phiếu yêu cầu nhập phù hợp. Cần đối chiếu chứng từ trước khi nhập kho.');
        $vt = static::one("SELECT vt.*, kv.maKho, kv.tenKhuVuc, kv.trangThai AS trangThaiKhuVuc, kv.loaiKhuVuc FROM ViTriLuuTru vt INNER JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc WHERE vt.maViTri = ?" . static::khoa($khoa), [$maViTri]);
        if (!$vt || $vt['maKho'] !== $kho['maKho'] || $vt['trangThaiKhuVuc'] !== 'Đang hoạt động' || $vt['loaiKhuVuc'] !== 'Tiếp nhận tạm' || !in_array($vt['trangThai'], ['Đang để trống','Đang chứa hàng'], true)) throw new DomainException('Vị trí phải thuộc khu tiếp nhận tạm đang hoạt động trong kho bạn phụ trách.');
        // Tồn kho đã chuẩn hóa kg. Khóa từng dòng thay vì chỉ đọc SUM.
        $ton = static::all('SELECT maTonKho, maLoHang, soLuongTon FROM TonKho WHERE maViTri = ? ORDER BY maTonKho' . static::khoa($khoa), [$maViTri]);
        $dangChua = 0.0;
        $tonCu = null;
        foreach ($ton as $dong) {
            $dangChua += (float)$dong['soLuongTon'];
            if ($dong['maLoHang'] === $maLoHang) $tonCu = $dong;
        }
        $sucChua = (float)$vt['sucChuaToiDa'];
        if (!is_finite($sucChua) || !is_finite($dangChua) || $kg > $sucChua - $dangChua) throw new DomainException('Vị trí lưu trữ không đủ sức chứa. Vui lòng chọn vị trí tiếp nhận tạm khác trong kho phụ trách.');
        // Lô chưa nhập không được có tồn dương ở bất kỳ vị trí nào.
        if (static::one('SELECT maTonKho FROM TonKho WHERE maLoHang = ? AND soLuongTon > 0 LIMIT 1' . static::khoa($khoa), [$maLoHang])) throw new DomainException('Lô hàng đã có tồn kho. Cần kiểm tra dữ liệu trước khi nhập.');
        return ['maLoHang'=>$maLoHang,'maNhanVien'=>$maNhanVien,'maKho'=>$kho['maKho'],'tenKho'=>$kho['tenKho'],
            'hoTenNhanVien'=>$kho['hoTenNhanVien'],'maViTri'=>$maViTri,'tenViTri'=>$vt['tenViTri'],'tenLoHang'=>$hh['tenHangHoa'],
            'soLuongTiepNhan'=>$banDau,'soLuongNhap'=>$soLuong,'donViTinh'=>$dvt,'heSoQuyDoiApDung'=>$heSo,'soLuongChuanKg'=>$kg,
            'sucChuaToiDa'=>$sucChua,'sucChuaConLai'=>$sucChua-$dangChua,'soLuongDangChua'=>$dangChua,'tonCu'=>$tonCu,
            'maPhieuTiepNhan'=>$ptn['maPhieuTiepNhan'],'maPhieuGiao'=>$ptn['maPhieuGiao'],'maXuong'=>$ptn['maXuong'],
            'maBienBan'=>$bb[0]['maBienBan'],'maYeuCauNhap'=>$yc[0]['maYeuCauNhap'],'ghiChu'=>$ghiChu];
    }

    /** Bản xem trước chỉ đọc; Controller lưu dữ liệu xác nhận vào session. */
    public static function kiemTraNhapKhoUC10(string $maLoHang, string $maNhanVien, string $maViTri, $soLuongNhap, string $ghiChu = ''): array
    {
        $duLieu = static::chuanBiUC10($maLoHang,$maNhanVien,$maViTri,$soLuongNhap,$ghiChu,false);
        unset($duLieu['tonCu']);
        return $duLieu;
    }

    private static function taoMaPhieuNhapKho(): string
    {
        return 'PNK' . str_pad((string)random_int(0,9999999999),10,'0',STR_PAD_LEFT);
    }

    private static function taoMaBienDong(): string
    {
        return 'BDTK' . str_pad((string)random_int(0,999999999999),12,'0',STR_PAD_LEFT);
    }

    /** Gọi sau xác nhận. $duLieuDaXacNhan phải lấy từ session phía máy chủ. */
    public static function lapPhieuNhapKhoUC10(string $maLoHang, string $maNhanVien, string $maViTri, $soLuongNhap, string $ghiChu, array $duLieuDaXacNhan): array
    {
        return static::transaction(function () use ($maLoHang,$maNhanVien,$maViTri,$soLuongNhap,$ghiChu,$duLieuDaXacNhan) {
            $d = static::chuanBiUC10($maLoHang,$maNhanVien,$maViTri,$soLuongNhap,$ghiChu,true);
            // Sức chứa có thể thay đổi nhưng vẫn phải đủ; chứng từ, lượng và phân công không được đổi.
            foreach (['maLoHang','maNhanVien','maKho','maViTri','maPhieuTiepNhan','maPhieuGiao','maXuong','maBienBan','maYeuCauNhap','soLuongTiepNhan','soLuongNhap','donViTinh','heSoQuyDoiApDung','soLuongChuanKg','ghiChu'] as $key) {
                if (!array_key_exists($key,$duLieuDaXacNhan) || $duLieuDaXacNhan[$key] !== $d[$key]) throw new DomainException('Thông tin nhập kho đã thay đổi. Vui lòng kiểm tra và xác nhận lại.');
            }
            $ma = static::taoMaPhieuNhapKho();
            $bd = static::taoMaBienDong();
            $kg = $d['soLuongChuanKg'];
            $note = $d['ghiChu'] === '' ? null : $d['ghiChu'];
            static::exec("INSERT INTO PhieuNhapKho (maPhieuNhapKho,maYeuCauNhap,maKho,maNhanVienThuKho,ngayNhapKho,trangThai,ghiChu) VALUES (?,?,?,?,NOW(),'Đã nhập kho',?)", [$ma,$d['maYeuCauNhap'],$d['maKho'],$maNhanVien,$note]);
            static::exec('INSERT INTO ChiTietPhieuNhapKho (maPhieuNhapKho,maLoHang,maViTri,soLuongNhap,donViTinh,heSoQuyDoiApDung) VALUES (?,?,?,?,?,?)', [$ma,$maLoHang,$maViTri,$d['soLuongNhap'],$d['donViTinh'],$d['heSoQuyDoiApDung']]);
            // soLuongChuanKg là GENERATED, không INSERT trực tiếp.
            $truoc = (float)($d['tonCu']['soLuongTon'] ?? 0);
            $sau = $truoc + $kg;
            if ($d['tonCu'] === null) {
                static::exec('INSERT INTO TonKho (maLoHang,maViTri,soLuongTon,soLuongDaGiu) VALUES (?,?,?,0)', [$maLoHang,$maViTri,$sau]);
            } else {
                static::exec('UPDATE TonKho SET soLuongTon = ?, ngayCapNhatCuoi = NOW() WHERE maTonKho = ?', [$sau,$d['tonCu']['maTonKho']]);
            }
            static::exec("INSERT INTO LichSuBienDongTonKho (maBienDong,maLoHang,maViTri,loaiGiaoDich,soLuongBienDong,soLuongTonTruoc,soLuongTonSau,maChungTuLienQuan,maNhanVienThucHien,thoiGianPhatSinh,ghiChu) VALUES (?,?,?,'Nhập kho',?,?,?,?,?,NOW(),?)", [$bd,$maLoHang,$maViTri,$kg,$truoc,$sau,$ma,$maNhanVien,$note]);
            $trangThaiViTri = $d['soLuongDangChua'] + $kg >= $d['sucChuaToiDa'] ? 'Đã lấp đầy' : 'Đang chứa hàng';
            static::exec('UPDATE ViTriLuuTru SET trangThai = ? WHERE maViTri = ?', [$trangThaiViTri,$maViTri]);
            if (static::exec("UPDATE LoHang SET trangThaiVongDoi = 'Đang lưu kho' WHERE maLoHang = ? AND trangThaiVongDoi = 'Đã tạo' AND trangThaiQC = 'Đã đạt chuẩn'", [$maLoHang]) !== 1) throw new DomainException('Lô hàng không còn đủ điều kiện nhập kho.');
            // Một yêu cầu có thể gồm nhiều mặt hàng/lô. Không tự kết thúc toàn bộ yêu cầu.
            static::exec("UPDATE PhieuYeuCauNhapKho SET trangThai = 'Đang nhập kho' WHERE maYeuCauNhap = ? AND trangThai = 'Chờ xử lý'", [$d['maYeuCauNhap']]);
            $phieu = static::one('SELECT ngayNhapKho FROM PhieuNhapKho WHERE maPhieuNhapKho = ?', [$ma]);
            unset($d['tonCu']);
            return array_merge($d,['thanhCong'=>true,'maPhieuNhapKho'=>$ma,'maBienDong'=>$bd,'ngayNhapKho'=>$phieu['ngayNhapKho'],'soLuongTonTruoc'=>$truoc,'soLuongTonSau'=>$sau,'trangThaiLo'=>'Đã nhập kho','trangThaiVongDoi'=>'Đang lưu kho','thongBao'=>'Lập phiếu nhập kho thành công']);
        });
    }

    // =================================================
    // UC11 - TRA CỨU LỊCH SỬ NHẬP KHO (chỉ đọc)
    // =================================================

    /** Kho được phân công của quản lý; kể cả kho đã ngừng hoạt động vẫn xem được lịch sử. */
    public static function layKhoPhuTrachUC11(string $maNhanVien): ?array
    {
        return static::one("SELECT k.maKho, k.tenKho, k.loaiKho, k.trangThai
            FROM NhanVien nv INNER JOIN Kho k ON k.maKho = nv.maKho
            WHERE nv.maNhanVien = ? AND nv.vaiTro = 'Quản lý kho'
              AND nv.trangThai = 'Đang làm việc'", [$maNhanVien]);
    }

    /** Điều kiện dùng chung, không cho phép mở rộng sang kho khác. */
    private static function phamViUC11(): string
    {
        return "pn.trangThai = 'Đã nhập kho' AND pn.ngayNhapKho IS NOT NULL
            AND EXISTS (SELECT 1 FROM NhanVien nv
                WHERE nv.maNhanVien = ? AND nv.vaiTro = 'Quản lý kho'
                  AND nv.trangThai = 'Đang làm việc' AND nv.maKho = pn.maKho)";
    }

    private static function ngayHopLeUC11(string $ngay): bool
    {
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $ngay)) return false;
        $d = \DateTime::createFromFormat('!Y-m-d', $ngay);
        return $d !== false && $d->format('Y-m-d') === $ngay
            && $ngay >= '1000-01-01' && $ngay <= '9999-12-31';
    }

    private static function boLocUC11(string $maNhanVien, string $maPhieuNhapKho,
        string $tuNgay, string $denNgay): array
    {
        if (($maPhieuNhapKho !== '' && !preg_match('/^PNK[0-9]{10}$/D', $maPhieuNhapKho))
            || ($tuNgay !== '' && !static::ngayHopLeUC11($tuNgay))
            || ($denNgay !== '' && !static::ngayHopLeUC11($denNgay))) {
            throw new DomainException('Thông tin lọc không hợp lệ');
        }
        if ($tuNgay !== '' && $denNgay !== '' && $tuNgay > $denNgay) {
            throw new DomainException('Ngày bắt đầu không được lớn hơn ngày kết thúc');
        }
        $sql = static::phamViUC11();
        $params = [$maNhanVien];
        if ($maPhieuNhapKho !== '') {
            $sql .= ' AND pn.maPhieuNhapKho = ?';
            $params[] = $maPhieuNhapKho;
        }
        if ($tuNgay !== '') {
            $sql .= ' AND pn.ngayNhapKho >= ?';
            $params[] = $tuNgay . ' 00:00:00';
        }
        if ($denNgay !== '') {
            // DATETIME của schema hiện tại có độ chính xác đến giây.
            $sql .= ' AND pn.ngayNhapKho <= ?';
            $params[] = $denNgay . ' 23:59:59';
        }
        return [$sql, $params];
    }

    public static function demPhieuNhapKhoUC11(string $maNhanVien,
        string $maPhieuNhapKho = '', string $tuNgay = '', string $denNgay = ''): int
    {
        [$where, $params] = static::boLocUC11($maNhanVien, $maPhieuNhapKho, $tuNgay, $denNgay);
        $row = static::one('SELECT COUNT(*) AS tongSo FROM PhieuNhapKho pn WHERE ' . $where, $params);
        return (int)($row['tongSo'] ?? 0);
    }

    /** Một dòng cho mỗi phiếu, không JOIN chi tiết gây trùng phiếu khi phân trang. */
    public static function traCuuLichSuNhapKhoUC11(string $maNhanVien,
        string $maPhieuNhapKho = '', string $tuNgay = '', string $denNgay = '',
        int $trang = 1, int $soLuongMoiTrang = 10): array
    {
        [$where, $params] = static::boLocUC11($maNhanVien, $maPhieuNhapKho, $tuNgay, $denNgay);
        $soLuongMoiTrang = max(1, min(100, $soLuongMoiTrang));
        $trang = max(1, min($trang, intdiv(PHP_INT_MAX, $soLuongMoiTrang)));
        $offset = ($trang - 1) * $soLuongMoiTrang;
        return static::all("SELECT pn.maPhieuNhapKho, pn.ngayNhapKho,
                DATE(pn.ngayNhapKho) AS ngayLapPhieu, TIME(pn.ngayNhapKho) AS thoiGianLap,
                pn.maKho, pn.trangThai, nvLap.hoTen AS tenNhanVienLap
            FROM PhieuNhapKho pn
            LEFT JOIN NhanVien nvLap ON nvLap.maNhanVien = pn.maNhanVienThuKho
            WHERE " . $where .
            ' ORDER BY pn.ngayNhapKho DESC, pn.maPhieuNhapKho DESC LIMIT ' . $soLuongMoiTrang . ' OFFSET ' . $offset, $params);
    }

    /** Chi tiết phiếu chỉ được truy xuất nếu quản lý có quyền đối với chính kho của phiếu. */
    public static function chiTietPhieuNhapKhoUC11(string $maPhieuNhapKho, string $maNhanVien): ?array
    {
        if (!preg_match('/^PNK[0-9]{10}$/D', $maPhieuNhapKho)) {
            throw new DomainException('Thông tin lọc không hợp lệ');
        }
        $phieu = static::one("SELECT pn.maPhieuNhapKho, pn.maYeuCauNhap, pn.maKho,
                k.tenKho, pn.maNhanVienThuKho, nv.hoTen AS tenNhanVienLap,
                pn.ngayNhapKho, DATE(pn.ngayNhapKho) AS ngayLapPhieu,
                TIME(pn.ngayNhapKho) AS thoiGianLap, pn.trangThai, pn.ghiChu
            FROM PhieuNhapKho pn INNER JOIN Kho k ON k.maKho = pn.maKho
            INNER JOIN NhanVien nv ON nv.maNhanVien = pn.maNhanVienThuKho
            WHERE " . static::phamViUC11() . ' AND pn.maPhieuNhapKho = ?', [$maNhanVien, $maPhieuNhapKho]);
        if ($phieu === null) return null;

        // Dùng dữ liệu trên chứng từ lịch sử, không dùng tồn kho hiện tại để tính số đã nhập.
        $chiTiet = static::all("SELECT ct.maChiTietPhieuNhap, ct.maLoHang,
                hh.maHangHoa, hh.tenHangHoa, ct.maViTri, vt.tenViTri,
                ct.soLuongNhap, ct.donViTinh, ct.heSoQuyDoiApDung, ct.soLuongChuanKg
            FROM ChiTietPhieuNhapKho ct
            INNER JOIN PhieuNhapKho pn ON pn.maPhieuNhapKho = ct.maPhieuNhapKho
            INNER JOIN LoHang lh ON lh.maLoHang = ct.maLoHang
            INNER JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa
            INNER JOIN ViTriLuuTru vt ON vt.maViTri = ct.maViTri
            WHERE " . static::phamViUC11() . ' AND pn.maPhieuNhapKho = ? ORDER BY ct.maChiTietPhieuNhap', [$maNhanVien, $maPhieuNhapKho]);

        // Kiểm tra lại phạm vi nếu phân công hoặc trạng thái thay đổi giữa hai truy vấn.
        if (!static::one('SELECT pn.maPhieuNhapKho FROM PhieuNhapKho pn WHERE ' . static::phamViUC11()
            . ' AND pn.maPhieuNhapKho = ?', [$maNhanVien, $maPhieuNhapKho])) return null;
        $theoDonVi = [];
        $tongKg = 0.0;
        foreach ($chiTiet as $dong) {
            $dvt = $dong['donViTinh'];
            $theoDonVi[$dvt] = ($theoDonVi[$dvt] ?? 0.0) + (float)$dong['soLuongNhap'];
            $tongKg += (float)$dong['soLuongChuanKg'];
        }
        // Nếu khác đơn vị, hiển thị tổng chuẩn kg; vẫn giữ nguyên từng dòng chi tiết.
        $phieu['soLuongThucNhap'] = count($theoDonVi) === 1 ? reset($theoDonVi) : ($chiTiet ? $tongKg : null);
        $phieu['donViTinh'] = count($theoDonVi) === 1 ? array_key_first($theoDonVi) : ($chiTiet ? 'kg' : null);
        $phieu['tongSoLuongChuanKg'] = $chiTiet ? $tongKg : null;
        $phieu['soLuongTheoDonVi'] = $theoDonVi;
        $phieu['chiTiet'] = $chiTiet;
        return $phieu;
    }
}
