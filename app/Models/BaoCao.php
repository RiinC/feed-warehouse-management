<?php
namespace App\Models;

use Core\Model;
use DomainException;

/** Model báo cáo tồn kho UC30. Chỉ đọc dữ liệu, không cập nhật tồn kho. */
class BaoCao extends Model
{
    private const SAI_SO_UC30 = 0.000001;

    /** Không tin vai trò hoặc kho do trình duyệt gửi lên. */
    private static function nhanVienUC30(string $maNhanVien): array
    {
        $nv = static::one("SELECT maNhanVien, vaiTro, maKho FROM NhanVien
            WHERE maNhanVien = ? AND trangThai = 'Đang làm việc'
              AND vaiTro IN ('Ban giám đốc', 'Quản lý kho')", [$maNhanVien]);
        if (!$nv || ($nv['vaiTro'] === 'Quản lý kho' && empty($nv['maKho']))) {
            throw new DomainException('Bạn không có quyền xem báo cáo tồn kho.');
        }
        return $nv;
    }

    /** Ban giám đốc thấy mọi kho, quản lý chỉ thấy kho được phân công. */
    public static function layDanhSachKhoUC30(string $maNhanVien): array
    {
        $nv = static::nhanVienUC30($maNhanVien);
        $sql = 'SELECT maKho, tenKho, loaiKho, trangThai FROM Kho';
        $params = [];
        if ($nv['vaiTro'] === 'Quản lý kho') {
            $sql .= ' WHERE maKho = ?';
            $params[] = $nv['maKho'];
        }
        return static::all($sql . ' ORDER BY maKho', $params);
    }

    private static function kiemTraKhoUC30(string $maKho, array $nv): array
    {
        if ($maKho === '') {
            throw new DomainException('Vui lòng nhập mã kho.');
        }
        if ($nv['vaiTro'] === 'Quản lý kho' && $maKho !== $nv['maKho']) {
            throw new DomainException('Bạn không có quyền xem báo cáo của kho này.');
        }
        $kho = static::one('SELECT maKho, tenKho, loaiKho, trangThai FROM Kho WHERE maKho = ?', [$maKho]);
        if (!$kho) {
            throw new DomainException('Mã kho không tồn tại.');
        }
        return $kho;
    }

    public static function ngayHopLeUC30(string $ngay): bool
    {
        if ($ngay === '') return true;
        if (!preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $ngay, $m)) return false;
        return (int)$m[1] >= 1000 && checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }

    private static function kiemTraThoiGianUC30(string $tuNgay, string $denNgay): void
    {
        if (!static::ngayHopLeUC30($tuNgay) || !static::ngayHopLeUC30($denNgay)) {
            throw new DomainException('Thông tin thời gian không hợp lệ.');
        }
        if ($tuNgay !== '' && $denNgay !== '' && strcmp($tuNgay, $denNgay) > 0) {
            throw new DomainException('Thời gian bắt đầu không được lớn hơn thời gian kết thúc.');
        }
    }

    /** Mỗi biến động là một bút toán tại một cặp lô - vị trí, đơn vị kg. */
    private static function layLichSuUC30(string $maKho, string $hienTai): array
    {
        return static::all("SELECT bd.maBienDong, bd.maLoHang, bd.maViTri,
                bd.loaiGiaoDich, bd.soLuongBienDong, bd.soLuongTonTruoc,
                bd.soLuongTonSau, bd.maChungTuLienQuan, bd.thoiGianPhatSinh,
                hh.maHangHoa, hh.tenHangHoa
            FROM LichSuBienDongTonKho bd
            INNER JOIN ViTriLuuTru vt ON vt.maViTri = bd.maViTri
            INNER JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
            INNER JOIN LoHang lh ON lh.maLoHang = bd.maLoHang
            INNER JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa
            WHERE kv.maKho = ? AND bd.thoiGianPhatSinh <= ?
            ORDER BY bd.thoiGianPhatSinh ASC, bd.maBienDong ASC", [$maKho, $hienTai]);
    }

    private static function layTonHienTaiUC30(string $maKho): array
    {
        return static::all("SELECT tk.maLoHang, tk.maViTri, tk.soLuongTon,
                tk.ngayCapNhatCuoi, hh.maHangHoa, hh.tenHangHoa
            FROM TonKho tk
            INNER JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
            INNER JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
            INNER JOIN LoHang lh ON lh.maLoHang = tk.maLoHang
            INNER JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa
            WHERE kv.maKho = ?", [$maKho]);
    }

    private static function trongKyUC30(string $tg, ?string $batDau, string $ketThuc): bool
    {
        return ($batDau === null || strcmp($tg, $batDau) >= 0)
            && strcmp($tg, $ketThuc) <= 0;
    }

    /** Chỉ loại cặp điều chuyển cùng kho nếu cùng chứng từ, lô, số lượng,
     *  khác vị trí, đúng một bút toán đi và một bút toán đến, cả hai trong kỳ.
     *  Không loại điều chuyển khác kho hoặc cặp nằm ở hai kỳ khác nhau.
     */
    private static function capNoiBoUC30(array $lichSu, ?string $batDau, string $ketThuc): array
    {
        $nhom = [];
        foreach ($lichSu as $bd) {
            if (!in_array($bd['loaiGiaoDich'], ['Điều chuyển đi', 'Điều chuyển đến'], true)) continue;
            $key = $bd['maChungTuLienQuan'] . '|' . $bd['maLoHang'];
            $nhom[$key][] = $bd;
        }
        $boQua = [];
        foreach ($nhom as $cap) {
            if (count($cap) !== 2) continue;
            [$a, $b] = $cap;
            if ($a['loaiGiaoDich'] === $b['loaiGiaoDich'] || $a['maViTri'] === $b['maViTri']) continue;
            $di = $a['loaiGiaoDich'] === 'Điều chuyển đi' ? $a : $b;
            $den = $a['loaiGiaoDich'] === 'Điều chuyển đến' ? $a : $b;
            $slDi = (float)$di['soLuongBienDong'];
            $slDen = (float)$den['soLuongBienDong'];
            if ($slDi >= 0 || $slDen <= 0 || abs($slDi + $slDen) > self::SAI_SO_UC30) continue;
            if (!static::trongKyUC30($di['thoiGianPhatSinh'], $batDau, $ketThuc)
                || !static::trongKyUC30($den['thoiGianPhatSinh'], $batDau, $ketThuc)) continue;
            $boQua[$di['maBienDong']] = true;
            $boQua[$den['maBienDong']] = true;
        }
        return $boQua;
    }

    private static function ganCanhBaoUC30(array &$canhBao, string $maLo, string $maViTri, string $noiDung): void
    {
        $canhBao[] = ['maLoHang' => $maLo, 'maViTri' => $maViTri, 'noiDung' => $noiDung];
    }

    /** Cộng theo hàng hóa. NULL biểu thị không đủ dữ liệu xác định, không phải 0. */
    private static function congDongUC30(array &$baoCao, array $nguon, ?float $dau, ?float $nhap,
        ?float $xuat, ?float $cuoi, bool $canhBao): void
    {
        $ma = $nguon['maHangHoa'];
        if (!isset($baoCao[$ma])) {
            $baoCao[$ma] = ['maHangHoa' => $ma, 'tenHangHoa' => $nguon['tenHangHoa'],
                'donViTinh' => 'kg', 'tonDauKy' => 0.0, 'nhapTrongKy' => 0.0,
                'xuatTrongKy' => 0.0, 'tonCuoiKy' => 0.0, 'coCanhBao' => false];
        }
        foreach (['tonDauKy' => $dau, 'nhapTrongKy' => $nhap,
            'xuatTrongKy' => $xuat, 'tonCuoiKy' => $cuoi] as $cot => $sl) {
            $baoCao[$ma][$cot] = $sl === null || $baoCao[$ma][$cot] === null
                ? null : $baoCao[$ma][$cot] + $sl;
        }
        $baoCao[$ma]['coCanhBao'] = $baoCao[$ma]['coCanhBao'] || $canhBao;
    }

    /**
     * Trả dữ liệu đầy đủ để Controller phân trang sau khi tính tổng toàn bộ.
     * $maNhanVien phải lấy từ Auth::staffId(), không lấy từ GET/POST.
     * Không lọc theo trạng thái hiện tại của kho, vị trí, hàng hóa, lô hàng.
     * Không suy ra lịch sử từ số lượng tồn hiện tại nếu không có chứng cứ.
     */
    public static function taoBaoCaoTonKhoUC30(string $maNhanVien, string $maKho,
        string $tuNgay = '', string $denNgay = ''): array
    {
        $maKho = strtoupper(trim($maKho));
        $tuNgay = trim($tuNgay);
        $denNgay = trim($denNgay);
        // Cùng một giao dịch đọc để tránh lệch do phát sinh nhập/xuất giữa các truy vấn
        // với cấu hình REPEATABLE READ mặc định của MariaDB/InnoDB.
        return static::transaction(function () use ($maNhanVien, $maKho, $tuNgay, $denNgay) {
            $nv = static::nhanVienUC30($maNhanVien);
            $kho = static::kiemTraKhoUC30($maKho, $nv);
            static::kiemTraThoiGianUC30($tuNgay, $denNgay);
            $hienTai = (string)static::one('SELECT CURRENT_TIMESTAMP AS hienTai')['hienTai'];
            $batDau = $tuNgay === '' ? null : $tuNgay . ' 00:00:00';
            $ketThuc = $denNgay === '' ? $hienTai : min($denNgay . ' 23:59:59', $hienTai);
            $denHienTai = $ketThuc === $hienTai;
            $baoCao = [];
            $canhBao = [];
            $boQua = [];
            if ($batDau === null || strcmp($batDau, $ketThuc) <= 0) {
                $lichSu = static::layLichSuUC30($maKho, $hienTai);
                $ton = static::layTonHienTaiUC30($maKho);
                $boQua = static::capNoiBoUC30($lichSu, $batDau, $ketThuc);
                $nhom = [];
                $tonTheoViTri = [];
                foreach ($lichSu as $bd) {
                    $nhom[$bd['maLoHang'] . '|' . $bd['maViTri']][] = $bd;
                }
                foreach ($ton as $tk) {
                    $tonTheoViTri[$tk['maLoHang'] . '|' . $tk['maViTri']] = $tk;
                }
                foreach ($nhom as $key => $ds) {
                    $dauTien = $ds[0];
                    $maLo = $dauTien['maLoHang'];
                    $maViTri = $dauTien['maViTri'];
                    $tonDau = (float)$dauTien['soLuongTonTruoc'];
                    $nhap = 0.0;
                    $xuat = 0.0;
                    $tonThucTheoSo = null;
                    $coTrongKy = false;
                    $coTruocKy = false;
                    $coCanhBao = false;
                    $soCanhBaoTruoc = count($canhBao);
                    // Nếu bút toán đầu tiên nằm sau kỳ, chỉ biết chắc trường hợp tồn trước bằng 0.
                    if (strcmp($dauTien['thoiGianPhatSinh'], $ketThuc) > 0) {
                        if ($tonDau > self::SAI_SO_UC30) {
                            static::ganCanhBaoUC30($canhBao, $maLo, $maViTri,
                                'Lịch sử bắt đầu sau kỳ báo cáo và có tồn trước khác 0; không xác định được tồn của kỳ.');
                            static::congDongUC30($baoCao, $dauTien, null, null, null, null, true);
                        }
                        unset($tonTheoViTri[$key]);
                        continue;
                    }
                    if ($tonDau > self::SAI_SO_UC30) {
                        static::ganCanhBaoUC30($canhBao, $maLo, $maViTri,
                            'Bút toán đầu tiên có tồn trước khác 0; tồn ban đầu được suy từ số dư ghi nhận, cần đối chiếu lịch sử.');
                    }
                    $truoc = null;
                    foreach ($ds as $bd) {
                        $tg = $bd['thoiGianPhatSinh'];
                        if (strcmp($tg, $ketThuc) > 0) break;
                        $slTruoc = (float)$bd['soLuongTonTruoc'];
                        $slSau = (float)$bd['soLuongTonSau'];
                        $delta = (float)$bd['soLuongBienDong'];
                        if (($truoc !== null && abs($truoc - $slTruoc) > self::SAI_SO_UC30)
                            || abs($slSau - ($slTruoc + $delta)) > self::SAI_SO_UC30) {
                            static::ganCanhBaoUC30($canhBao, $maLo, $maViTri,
                                'Chuỗi số dư không liên tục tại ' . $bd['maBienDong'] . '; báo cáo cần đối chiếu.');
                        }
                        $truoc = $slSau;
                        $tonThucTheoSo = $slSau;
                        if ($batDau !== null && strcmp($tg, $batDau) < 0) {
                            // Số dư gần nhất trước kỳ là tồn đầu kỳ, không cộng lại lịch sử cũ.
                            $tonDau = $slSau;
                            $coTruocKy = true;
                            continue;
                        }
                        $coTrongKy = true;
                        if (isset($boQua[$bd['maBienDong']])) continue;
                        if ($delta > 0) $nhap += $delta;
                        if ($delta < 0) $xuat -= $delta;
                    }
                    $cuoi = $tonDau + $nhap - $xuat;
                    // Bút toán nội bộ bị loại theo cặp, nên so sánh số dư theo vị trí
                    // phải cộng lại delta nội bộ riêng tại vị trí đó.
                    $deltaNoiBo = 0.0;
                    foreach ($ds as $bd) {
                        if (isset($boQua[$bd['maBienDong']])) $deltaNoiBo += (float)$bd['soLuongBienDong'];
                    }
                    $cuoiTheoViTri = $cuoi + $deltaNoiBo;
                    if ($tonThucTheoSo !== null && abs($cuoiTheoViTri - $tonThucTheoSo) > self::SAI_SO_UC30) {
                        static::ganCanhBaoUC30($canhBao, $maLo, $maViTri,
                            'Tồn cuối kỳ tính từ biến động khác số dư cuối trên sổ lịch sử.');
                    }
                    if ($denHienTai) {
                        $tonHienTai = (float)($tonTheoViTri[$key]['soLuongTon'] ?? 0);
                        if (abs($cuoiTheoViTri - $tonHienTai) > self::SAI_SO_UC30) {
                            static::ganCanhBaoUC30($canhBao, $maLo, $maViTri,
                                'Tồn cuối kỳ tính từ lịch sử khác số lượng trong TonKho.');
                        }
                    }
                    unset($tonTheoViTri[$key]);
                    $coCanhBao = count($canhBao) > $soCanhBaoTruoc;
                    // Giữ hàng đã xuất hết trong kỳ, hàng có tồn và hàng cần cảnh báo.
                    if ($coTrongKy || abs($tonDau) > self::SAI_SO_UC30
                        || abs($cuoiTheoViTri) > self::SAI_SO_UC30 || $coCanhBao) {
                        // Dồn delta nội bộ vào tồn cuối của từng vị trí; chúng triệt tiêu
                        // khi gộp các vị trí cùng kho và cùng hàng hóa.
                        static::congDongUC30($baoCao, $dauTien, $tonDau, $nhap, $xuat, $cuoiTheoViTri, $coCanhBao);
                    }
                }
                // Tồn có trong TonKho nhưng không có lịch sử: không tạo số nhập/xuất giả.
                foreach ($tonTheoViTri as $tk) {
                    $sl = (float)$tk['soLuongTon'];
                    if ($sl <= self::SAI_SO_UC30) continue;
                    static::ganCanhBaoUC30($canhBao, $tk['maLoHang'], $tk['maViTri'],
                        'Có tồn hiện tại nhưng thiếu lịch sử biến động; không xác định được tồn đầu, nhập và xuất trong kỳ.');
                    static::congDongUC30($baoCao, $tk, null, null, null, $denHienTai ? $sl : null, true);
                }
            }
            ksort($baoCao, SORT_STRING);
            $danhSach = array_values($baoCao);
            $tongCong = ['tonDauKy' => 0.0, 'nhapTrongKy' => 0.0, 'xuatTrongKy' => 0.0, 'tonCuoiKy' => 0.0];
            foreach ($danhSach as &$dong) {
                foreach ($tongCong as $cot => $sl) {
                    // Không biến sai số rất nhỏ thành số âm -0 trên báo cáo.
                    if ($dong[$cot] !== null && abs($dong[$cot]) <= self::SAI_SO_UC30) $dong[$cot] = 0.0;
                    $tongCong[$cot] = $sl === null || $dong[$cot] === null ? null : $sl + $dong[$cot];
                }
            }
            unset($dong);
            return ['kho' => $kho, 'tuNgay' => $tuNgay, 'denNgay' => $denNgay,
                'thoiGianBatDau' => $batDau, 'thoiGianKetThuc' => $ketThuc,
                'danhSach' => $danhSach, 'tongCong' => $tongCong,
                'tongSoHangHoa' => count($danhSach), 'donViTinh' => 'kg',
                'soCapDieuChuyenNoiBo' => intdiv(count($boQua), 2),
                'canhBao' => $canhBao, 'coCanhBao' => count($canhBao) > 0];
        });
    }
}
