<?php
namespace App\Controllers;

use App\Models\LoHang;
use App\Models\NhapKho;
use Core\Auth;
use DateTime;
use DomainException;
use Throwable;

/** Module nhập kho: UC10 lập phiếu và UC11 tra cứu lịch sử. */
class NhapKhoController extends Controller
{
    private function quyenUC10(): ?string
    {
        $nv = Auth::staffId();
        if ($nv === '') {
            $this->flash('error', 'Vui lòng đăng nhập.');
            $this->redirect('login');
            return null;
        }
        if (Auth::role() !== 'warehouse' || !can('nhap-kho', 'create')) {
            $this->flash('error', 'Bạn không có quyền lập phiếu nhập kho.');
            $this->redirect('dashboard');
            return null;
        }
        return $nv;
    }

    private function thamSoUC10(array $nguon, string $ten): ?string
    {
        $v = $nguon[$ten] ?? '';
        return is_string($v) ? trim($v) : null;
    }

    private function maLoHopLeUC10(string $ma): bool
    {
        return preg_match('/^LO[0-9]{10}$/D', $ma) === 1;
    }

    private function ngayHopLeUC10(string $ngay): bool
    {
        if ($ngay === '') return true;
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $ngay)) return false;
        $d = DateTime::createFromFormat('!Y-m-d', $ngay);
        return $d !== false && $d->format('Y-m-d') === $ngay;
    }

    // =================================================
    // UC11 - TRA CỨU LỊCH SỬ NHẬP KHO (chỉ đọc)
    // =================================================
    private function quyenUC11(): ?string
    {
        $nv = Auth::staffId();
        if (!Auth::check() || $nv === '') {
            $this->flash('error', 'Vui lòng đăng nhập.');
            $this->redirect('login');
            return null;
        }
        if (Auth::role() !== 'manager' || !can('nhap-kho', 'history')) {
            $this->flash('error', 'Bạn không có quyền xem lịch sử nhập kho.');
            $this->redirect('dashboard');
            return null;
        }
        return $nv;
    }

    private function thamSoUC11(string $ten): ?string
    {
        $v = $_GET[$ten] ?? '';
        return is_string($v) ? trim($v) : null;
    }

    private function maPhieuHopLeUC11(string $ma): bool
    {
        return preg_match('/^PNK[0-9]{10}$/D', $ma) === 1;
    }

    private function ngayHopLeUC11(string $ngay): bool
    {
        if ($ngay === '') return true;
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $ngay)) return false;
        $d = DateTime::createFromFormat('!Y-m-d', $ngay);
        return $d !== false && $d->format('Y-m-d') === $ngay
            && $ngay >= '1000-01-01' && $ngay <= '9999-12-31';
    }

    /** GET nhap-kho/history; maChiTiet là mã phiếu cần xem, độc lập với bộ lọc. */
    public function history(): void
    {
        $nv = $this->quyenUC11();
        if ($nv === null) return;

        $ma = $this->thamSoUC11('maPhieuNhapKho');
        $tu = $this->thamSoUC11('tuNgay');
        $den = $this->thamSoUC11('denNgay');
        $maChiTiet = $this->thamSoUC11('maChiTiet');
        $maKho = $this->thamSoUC11('maKho');
        $page = $this->thamSoUC11('page');
        $trang = $page === '' ? 1 : filter_var($page, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $limit = 10;
        $tong = 0;
        $soTrang = 1;
        $ds = [];
        $chiTiet = null;
        $kho = null;
        $msg = '';
        try {
            $kho = NhapKho::layKhoPhuTrachUC11($nv);
            if (!$kho) {
                throw new DomainException('Không xác định được kho phụ trách của quản lý.');
            }
            if ($ma === null || $tu === null || $den === null || $maChiTiet === null
                || $maKho === null || $page === null || $trang === false
                || ($ma !== '' && !$this->maPhieuHopLeUC11($ma))
                || ($maChiTiet !== '' && !$this->maPhieuHopLeUC11($maChiTiet))
                || !$this->ngayHopLeUC11($tu) || !$this->ngayHopLeUC11($den)) {
                throw new DomainException('Thông tin lọc không hợp lệ');
            }
            // Không dùng mã kho từ URL để mở rộng phạm vi truy vấn.
            if ($maKho !== '' && $maKho !== $kho['maKho']) {
                throw new DomainException('Bạn không có quyền tra cứu kho này.');
            }
            if ($tu !== '' && $den !== '' && $tu > $den) {
                throw new DomainException('Ngày bắt đầu không được lớn hơn ngày kết thúc');
            }
            $tong = NhapKho::demPhieuNhapKhoUC11($nv, $ma, $tu, $den);
            $soTrang = max(1, (int)ceil($tong / $limit));
            $trang = min($trang, $soTrang);
            $ds = NhapKho::traCuuLichSuNhapKhoUC11($nv, $ma, $tu, $den, $trang, $limit);
            if ($maChiTiet !== '') {
                $chiTiet = NhapKho::chiTietPhieuNhapKhoUC11($maChiTiet, $nv);
                if ($chiTiet === null) {
                    $msg = 'Không tìm thấy phiếu nhập kho hoặc bạn không có quyền xem phiếu này.';
                }
            }
            if ($msg === '' && !$ds && $chiTiet === null) {
                $msg = 'Không tìm thấy phiếu nhập kho phù hợp.';
            }
        } catch (DomainException $e) {
            $msg = $e->getMessage();
        } catch (Throwable $e) {
            error_log('UC11 history: ' . $e->getMessage());
            $msg = 'Không thể tải lịch sử nhập kho. Vui lòng thử lại.';
            $ds = [];
            $chiTiet = null;
            $tong = 0;
            $soTrang = 1;
            $trang = 1;
        }

        $trang = max(1, (int)$trang);
        // View sử dụng các tham số này cho liên kết phân trang, xem chi tiết và quay lại.
        $boLoc = ['maPhieuNhapKho'=>$ma ?? '', 'tuNgay'=>$tu ?? '', 'denNgay'=>$den ?? '', 'page'=>$trang];
        $this->render('nhap-kho/history', [
            'danhSachPhieuNhapKho'=>$ds, 'chiTiet'=>$chiTiet,
            'khoPhuTrach'=>$kho, 'maKho'=>$kho['maKho'] ?? '',
            'maPhieuNhapKho'=>$ma ?? '', 'maChiTiet'=>$maChiTiet ?? '',
            'tuNgay'=>$tu ?? '', 'denNgay'=>$den ?? '',
            'trang'=>$trang, 'tongSoTrang'=>$soTrang, 'tongSoPhieu'=>$tong,
            'soLuongMoiTrang'=>$limit, 'thongBao'=>$msg, 'boLoc'=>$boLoc
        ], [
            'title'=>'Tra cứu lịch sử nhập kho', 'module'=>'nhap-kho',
            'breadcrumb'=>[['Nhập kho','nhap-kho'],['Tra cứu lịch sử nhập kho',null]]
        ]);
    }

    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('login');
            return;
        }
        if (!can('nhap-kho', 'view')) {
            $this->flash('error', 'Bạn không có quyền xem trang nhập kho.');
            $this->redirect('dashboard');
            return;
        }
        $nv = Auth::staffId();
        $duocLapPhieu = Auth::role() === 'warehouse' && $nv !== '' && can('nhap-kho', 'create');
        $ma = $this->thamSoUC10($_GET, 'maLoHang');
        $tu = $this->thamSoUC10($_GET, 'tuNgay');
        $den = $this->thamSoUC10($_GET, 'denNgay');
        $page = $this->thamSoUC10($_GET, 'page');
        $trang = max(1, (int) filter_var($page, FILTER_VALIDATE_INT));
        $limit = 10;
        $tong = 0;
        $soTrang = 1;
        $ds = [];
        $kho = null;
        $msg = '';
        try {
            if (!$duocLapPhieu) throw new DomainException('Bạn không có quyền lập phiếu nhập kho.');
            $kho = LoHang::layKhoPhuTrachUC10($nv);
            if (!$kho || !in_array($kho['loaiKho'], ['Nguyên liệu','Thành phẩm','Tổng hợp'], true)) {
                throw new DomainException('Kho phụ trách không hoạt động hoặc không thuộc phạm vi UC10.');
            }
            if ($ma === null || $tu === null || $den === null || ($ma !== '' && !$this->maLoHopLeUC10($ma)) || !$this->ngayHopLeUC10($tu) || !$this->ngayHopLeUC10($den)) {
                throw new DomainException('Thông tin tra cứu không đúng định dạng.');
            }
            if ($tu !== '' && $den !== '' && $tu > $den) throw new DomainException('Từ ngày không được lớn hơn đến ngày.');
            $tong = LoHang::demLoHangChoNhapKhoUC10($nv, $ma, $tu, $den);
            $soTrang = max(1, (int)ceil($tong / $limit));
            $trang = min($trang, $soTrang);
            $ds = LoHang::traCuuLoHangChoNhapKhoUC10($nv, $ma, $tu, $den, $trang, $limit);
            if (!$ds) $msg = 'Không tìm thấy lô hàng chờ nhập kho phù hợp.';
        } catch (DomainException $e) {
            $msg = $e->getMessage();
        } catch (Throwable $e) {
            error_log('UC10 index: ' . $e->getMessage());
            $msg = 'Không thể tải danh sách lô hàng. Vui lòng thử lại.';
        }
        $this->render('nhap-kho/index', [
            'danhSachLoHang'=>$ds, 'khoPhuTrach'=>$kho, 'maLoHang'=>$ma ?? '',
            'tuNgay'=>$tu ?? '', 'denNgay'=>$den ?? '', 'trang'=>$trang,
            'tongSoTrang'=>$soTrang, 'tongSoLoHang'=>$tong, 'soLuongMoiTrang'=>$limit,
            'thongBao'=>$msg, 'coQuyenLapPhieu'=>$duocLapPhieu
        ], [
            'title'=>'Nhập kho', 'module'=>'nhap-kho',
            'breadcrumb'=>[['Nhập kho',null]],
            'actions'=>action_buttons([
                ['nhap-kho', 'history', 'Lịch sử nhập kho', 'nhap-kho/history', 'btn']
            ])
        ]);
    }

    public function create(): void
    {
        $nv = $this->quyenUC10();
        if ($nv === null) return;
        $ma = $this->thamSoUC10($_GET, 'maLoHang');
        if ($ma === '') {
            $this->redirect('nhap-kho');
            return;
        }
        if ($ma === null || !$this->maLoHopLeUC10($ma)) {
            $this->flash('error', 'Mã lô hàng không hợp lệ.');
            $this->redirect('nhap-kho');
            return;
        }
        $this->renderFormUC10($nv, $ma);
    }

    /** Không lấy kho, nhân viên, chứng từ, ngày hoặc hệ số từ trình duyệt. */
    private function renderFormUC10(string $nv, string $ma, array $nhap = [], array $loi = [], ?array $xacNhan = null, string $token = ''): void
    {
        try {
            $lo = LoHang::chiTietLoHangUC10($ma, $nv);
            if (!$lo) throw new DomainException('Lô hàng đã được nhập kho hoặc không còn đủ điều kiện nhập kho.');
            $kho = LoHang::layKhoPhuTrachUC10($nv);
            if (!$kho) throw new DomainException('Không xác định được kho phụ trách.');
            $viTri = NhapKho::layDanhSachViTriUC10($nv);
            $chungTu = LoHang::layChungTuLienQuanUC10($ma, $nv);
            if (!$chungTu || count($chungTu['danhSachBienBanDat']) !== 1 || count($chungTu['danhSachYeuCauNhap']) !== 1) {
                $loi['_chungTu'] = 'Không xác định được duy nhất bộ chứng từ nhập kho hợp lệ. Cần đối chiếu chứng từ.';
            }
            if (!$viTri) $loi['_viTri'] = 'Kho phụ trách không còn vị trí lưu trữ phù hợp có sức chứa.';
            // Nếu điều kiện thay đổi sau khi kiểm tra, không hiển thị nút xác nhận cũ.
            if ($xacNhan !== null && $loi) {
                unset($_SESSION['uc10_cho_xac_nhan'][$token]);
                $xacNhan = null;
                $token = '';
            }
            $this->render('nhap-kho/create', [
                'loHang'=>$lo, 'khoPhuTrach'=>$kho, 'danhSachViTri'=>$viTri,
                'chungTuLienQuan'=>$chungTu, 'maNhanVien'=>$nv,
                'maViTri'=>$nhap['maViTri'] ?? '',
                'soLuongNhap'=>$nhap['soLuongNhap'] ?? (string)$lo['soLuongTiepNhan'],
                'ghiChu'=>$nhap['ghiChu'] ?? '', 'loi'=>$loi,
                'choXacNhan'=>$xacNhan !== null, 'duLieuXacNhan'=>$xacNhan,
                'maXacNhan'=>$token
            ], ['title'=>'Lập phiếu nhập kho','module'=>'nhap-kho',
                'breadcrumb'=>[['Nhập kho','nhap-kho'],['Lập phiếu nhập kho',null]]]);
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('nhap-kho');
        } catch (Throwable $e) {
            error_log('UC10 form: ' . $e->getMessage());
            $this->flash('error', 'Không thể tải thông tin nhập kho. Vui lòng thử lại.');
            $this->redirect('nhap-kho');
        }
    }

    private function taoPhienUC10(string $nv, array $nhap, array $duLieu): string
    {
        if (!is_array($_SESSION['uc10_cho_xac_nhan'] ?? null)) $_SESSION['uc10_cho_xac_nhan'] = [];
        foreach ($_SESSION['uc10_cho_xac_nhan'] as $key=>$p) {
            if (!is_array($p) || time() - ($p['thoiGian'] ?? 0) > 1800 || (($p['nv'] ?? '') === $nv && ($p['nhap']['maLoHang'] ?? '') === $nhap['maLoHang'])) {
                unset($_SESSION['uc10_cho_xac_nhan'][$key]);
            }
        }
        while (count($_SESSION['uc10_cho_xac_nhan']) >= 20) array_shift($_SESSION['uc10_cho_xac_nhan']);
        $token = bin2hex(random_bytes(32));
        $_SESSION['uc10_cho_xac_nhan'][$token] = ['nv'=>$nv,'nhap'=>$nhap,'duLieu'=>$duLieu,'thoiGian'=>time()];
        return $token;
    }

    private function layPhienUC10(string $token, string $nv): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
        $p = $_SESSION['uc10_cho_xac_nhan'][$token] ?? null;
        if (!is_array($p) || ($p['nv'] ?? '') !== $nv || time() - ($p['thoiGian'] ?? 0) > 1800 || !is_array($p['nhap'] ?? null) || !is_array($p['duLieu'] ?? null)) return null;
        return $p;
    }

    /** POST nhap-kho/create; Router kiểm tra CSRF. */
    public function store(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); return; }
        $nv = $this->quyenUC10();
        if ($nv === null) return;
        $action = $this->thamSoUC10($_POST, 'hanhDong');
        if ($action === 'kiemTra') {
            $this->kiemTraTruocLuuUC10($nv);
            return;
        }
        if (!in_array($action, ['quayLai','xacNhan'], true)) {
            $this->flash('error', 'Thao tác không hợp lệ.');
            $this->redirect('nhap-kho');
            return;
        }
        $token = $this->thamSoUC10($_POST, 'maXacNhan') ?? '';
        $p = $this->layPhienUC10($token, $nv);
        if (!$p) {
            $this->flash('error', 'Phiên xác nhận không hợp lệ hoặc đã hết hạn.');
            $this->redirect('nhap-kho');
            return;
        }
        unset($_SESSION['uc10_cho_xac_nhan'][$token]);
        $n = $p['nhap'];
        $ma = $n['maLoHang'];
        if ($action === 'quayLai') {
            $this->renderFormUC10($nv, $ma, $n);
            return;
        }
        try {
            $kq = NhapKho::lapPhieuNhapKhoUC10($ma, $nv, $n['maViTri'], $n['soLuongNhap'], $n['ghiChu'], $p['duLieu']);
            if (($kq['thanhCong'] ?? false) !== true) throw new DomainException('Không thể lập phiếu nhập kho.');
            $this->flash('success', 'Lập phiếu nhập kho thành công. Mã phiếu: ' . $kq['maPhieuNhapKho']);
            $this->redirect('nhap-kho');
        } catch (DomainException $e) {
            $this->renderFormUC10($nv, $ma, $n, ['_chung'=>$e->getMessage()]);
        } catch (Throwable $e) {
            error_log('UC10 lưu: ' . $e->getMessage());
            $this->renderFormUC10($nv, $ma, $n, ['_chung'=>'Không thể lưu phiếu nhập kho. Vui lòng kiểm tra lại.']);
        }
    }

    private function kiemTraTruocLuuUC10(string $nv): void
    {
        $ma = $this->thamSoUC10($_POST, 'maLoHang');
        if ($ma === null || !$this->maLoHopLeUC10($ma)) {
            $this->flash('error', 'Mã lô hàng không hợp lệ.');
            $this->redirect('nhap-kho');
            return;
        }
        $vt = $this->thamSoUC10($_POST, 'maViTri');
        $sl = $this->thamSoUC10($_POST, 'soLuongNhap');
        $gc = $this->thamSoUC10($_POST, 'ghiChu');
        $n = ['maLoHang'=>$ma,'maViTri'=>$vt ?? '', 'soLuongNhap'=>$sl ?? '', 'ghiChu'=>$gc ?? ''];
        $loi = [];
        if ($vt === null || $vt === '') $loi['maViTri'] = 'Vui lòng chọn vị trí lưu trữ.';
        elseif (!preg_match('/^VT[0-9]{12}$/D', $vt)) $loi['maViTri'] = 'Mã vị trí lưu trữ không hợp lệ.';
        if ($sl === null || $sl === '') $loi['soLuongNhap'] = 'Vui lòng nhập số lượng nhập.';
        elseif (!is_numeric($sl) || !is_finite((float)$sl) || (float)$sl <= 0) $loi['soLuongNhap'] = 'Số lượng nhập không hợp lệ.';
        if ($gc === null || !mb_check_encoding($gc, 'UTF-8') || mb_strlen($gc,'UTF-8') > 255 || strpos($gc,"\0") !== false) $loi['ghiChu'] = 'Ghi chú không hợp lệ hoặc vượt quá 255 ký tự.';
        if ($loi) { $this->renderFormUC10($nv, $ma, $n, $loi); return; }
        try {
            $d = NhapKho::kiemTraNhapKhoUC10($ma, $nv, $vt, $sl, $gc);
            $token = $this->taoPhienUC10($nv, $n, $d);
            $this->renderFormUC10($nv, $ma, $n, [], $d, $token);
        } catch (DomainException $e) {
            $this->renderFormUC10($nv, $ma, $n, ['_chung'=>$e->getMessage()]);
        } catch (Throwable $e) {
            error_log('UC10 kiểm tra: ' . $e->getMessage());
            $this->renderFormUC10($nv, $ma, $n, ['_chung'=>'Không thể kiểm tra thông tin nhập kho. Vui lòng thử lại.']);
        }
    }
}
