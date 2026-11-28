<?php
namespace App\Controllers;

use App\Models\LoHang;
use App\Models\ChatLuong;
use Core\Auth;
use DateTime;
use DomainException;
use Throwable;

class ChatLuongController extends Controller
{
    // =================================================
    // UC07 - TRA CỨU LÔ HÀNG CẦN KIỂM TRA
    // =================================================
    public function index(): void
    {
        // Quản lý kho vào trực tiếp UC09, không hiển thị UC07.
        // Route UC09 và kiemTraQuyenUC09 vẫn kiểm tra quyền confirm.
        if (Auth::role() === 'manager') {
            $this->redirect('chat-luong/confirm-result');
            return;
        }

        $maLoHang = $this->layThamSoUC07('maLoHang');
        $tuNgay = $this->layThamSoUC07('tuNgay');
        $denNgay = $this->layThamSoUC07('denNgay');
        $maChiTiet = $this->layThamSoUC07('maChiTiet');
        $soLuongMoiTrang = 10;
        $trang = max(1, (int) filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
        $tongSoLoHang = 0;
        $tongSoTrang = 1;
        $danhSachLoHang = [];
        $chiTiet = null;
        $thongBao = '';

        if ($maLoHang === null || $tuNgay === null || $denNgay === null || $maChiTiet === null) {
            $thongBao = 'Thông tin tra cứu không hợp lệ.';
        } elseif ($maLoHang !== '' && !$this->kiemTraMaLoUC07($maLoHang)) {
            $thongBao = 'Mã lô hàng không hợp lệ.';
        } elseif ($tuNgay !== '' && !$this->kiemTraNgayUC07($tuNgay)) {
            $thongBao = 'Thời gian bắt đầu không hợp lệ.';
        } elseif ($denNgay !== '' && !$this->kiemTraNgayUC07($denNgay)) {
            $thongBao = 'Thời gian kết thúc không hợp lệ.';
        } elseif ($tuNgay !== '' && $denNgay !== '' && $tuNgay > $denNgay) {
            $thongBao = 'Thời gian bắt đầu không được lớn hơn thời gian kết thúc.';
        } else {
            $tongSoLoHang = LoHang::demLoHangCanKiemTra($maLoHang, $tuNgay, $denNgay);
            $tongSoTrang = max(1, (int) ceil($tongSoLoHang / $soLuongMoiTrang));
            $trang = min($trang, $tongSoTrang);
            $danhSachLoHang = LoHang::traCuuLoHangCanKiemTra($maLoHang, $tuNgay, $denNgay, $trang, $soLuongMoiTrang);
            if (empty($danhSachLoHang)) {
                $thongBao = 'Không tìm thấy lô hàng phù hợp.';
            }
            if ($maChiTiet !== '') {
                if (!$this->kiemTraMaLoUC07($maChiTiet)) {
                    $thongBao = 'Mã lô hàng không hợp lệ.';
                } else {
                    $chiTiet = LoHang::chiTietLoHangCanKiemTra($maChiTiet);
                    if ($chiTiet === null) {
                        $thongBao = 'Lô hàng không tồn tại hoặc không còn chờ kiểm tra chất lượng.';
                        $tongSoLoHang = LoHang::demLoHangCanKiemTra($maLoHang, $tuNgay, $denNgay);
                        $tongSoTrang = max(1, (int) ceil($tongSoLoHang / $soLuongMoiTrang));
                        $trang = min($trang, $tongSoTrang);
                        $danhSachLoHang = LoHang::traCuuLoHangCanKiemTra($maLoHang, $tuNgay, $denNgay, $trang, $soLuongMoiTrang);
                    }
                }
            }
        }
        $this->render('chat-luong/index', [
            'danhSachLoHang' => $danhSachLoHang,
            'chiTiet' => $chiTiet,
            'maLoHang' => $maLoHang ?? '',
            'tuNgay' => $tuNgay ?? '',
            'denNgay' => $denNgay ?? '',
            'maChiTiet' => $maChiTiet ?? '',
            'thongBao' => $thongBao,
            'trang' => $trang,
            'tongSoTrang' => $tongSoTrang,
            'tongSoLoHang' => $tongSoLoHang,
            'soLuongMoiTrang' => $soLuongMoiTrang
        ], [
            'title' => 'Tra cứu lô hàng cần kiểm tra',
            'module' => 'chat-luong',
            'breadcrumb' => [['Kiểm tra chất lượng', null]]
        ]);
    }

    private function layThamSoUC07(string $ten): ?string
    {
        $giaTri = $_GET[$ten] ?? '';
        return is_string($giaTri) ? trim($giaTri) : null;
    }

    private function kiemTraMaLoUC07(string $maLoHang): bool
    {
        return preg_match('/^LO[0-9]{10}$/D', $maLoHang) === 1;
    }

    private function kiemTraNgayUC07(string $ngay): bool
    {
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $ngay) !== 1) {
            return false;
        }
        $date = DateTime::createFromFormat('!Y-m-d', $ngay);
        return $date !== false && $date->format('Y-m-d') === $ngay;
    }

    // =================================================
    // UC08 - GHI NHẬN KẾT QUẢ KIỂM TRA CHẤT LƯỢNG
    // =================================================
    private function kiemTraMaBienBanUC08(string $maBienBan): bool
    {
        return preg_match('/^BBKDCL[0-9]{10}$/D', $maBienBan) === 1;
    }

    private function layThamSoPOSTUC08(string $ten): ?string
    {
        $giaTri = $_POST[$ten] ?? '';
        return is_string($giaTri) ? trim($giaTri) : null;
    }

    public function recordResult(): void
    {
        $maLoHang = $this->layThamSoUC07('maLoHang');
        $maBienBan = $this->layThamSoUC07('maBienBan');
        $chiXem = $this->layThamSoUC07('hanhDong') === 'xem';
        $maNhanVienQC = Auth::staffId();
        if ($maNhanVienQC === null || $maNhanVienQC === '') {
            $this->flash('error', 'Không xác định được nhân viên kiểm tra.');
            $this->redirect('login');
            return;
        }
        if (!can('chat-luong', 'record')) {
            $this->flash('error', 'Bạn không có quyền ghi nhận kết quả kiểm tra.');
            $this->redirect('chat-luong');
            return;
        }
        if ($maLoHang === null || !$this->kiemTraMaLoUC07($maLoHang)) {
            $this->flash('error', 'Mã lô hàng không hợp lệ.');
            $this->redirect('chat-luong');
            return;
        }
        if ($maBienBan === null || ($maBienBan !== '' && !$this->kiemTraMaBienBanUC08($maBienBan))) {
            $this->flash('error', 'Mã biên bản không hợp lệ.');
            $this->redirect('chat-luong');
            return;
        }
        $loHang = LoHang::layLoHangUC08($maLoHang);
        if ($loHang === null) {
            $this->flash('error', 'Lô hàng không còn ở trạng thái chờ kiểm tra chất lượng.');
            $this->redirect('chat-luong');
            return;
        }
        $danhSachBienBan = ChatLuong::layDanhSachBienBanUC08($maLoHang);
        $danhSachTieuChi = LoHang::layTieuChiKiemTraUC08($maLoHang);
        $ketQua = [];
        $loi = [];
        $bienBan = null;
        $phienBan = '';
        if (empty($danhSachTieuChi)) {
            $loi['_chung'] = 'Lô hàng chưa có tiêu chí kiểm tra phù hợp.';
        }
        // Có biên bản chờ duyệt: luôn hiển thị danh sách để chọn sửa/xóa.
        if ($maBienBan === '' && !empty($danhSachBienBan)) {
            $this->renderRecordResultUC08(loHang: $loHang, danhSachTieuChi: $danhSachTieuChi,
                loi: $loi, danhSachBienBan: $danhSachBienBan, canChonBienBan: true);
            return;
        }
        if ($maBienBan !== '') {
            $duLieuBienBan = ChatLuong::layBienBanUC08($maLoHang, $maBienBan);
            if ($duLieuBienBan === null) {
                $this->flash('error', 'Biên bản không tồn tại hoặc không còn ở trạng thái chờ duyệt.');
                $this->redirect('chat-luong');
                return;
            }
            $bienBan = $duLieuBienBan['bienBan'];
            $phienBan = $duLieuBienBan['phienBan'];
            $ketQua = array_intersect_key($duLieuBienBan['ketQua'], array_flip(array_column($danhSachTieuChi, 'maTieuChi')));
            // Biên bản của QC khác (hoặc yêu cầu xem): tuyệt đối không cấp phiên nhập liệu.
            if ($chiXem || ($bienBan['maNhanVienQC'] ?? '') !== $maNhanVienQC ||
                !ChatLuong::coQuyenSuaBienBanUC08($maLoHang, $maBienBan, $maNhanVienQC)) {
                $this->renderRecordResultUC08(loHang: $loHang, danhSachTieuChi: $danhSachTieuChi,
                    ketQua: $ketQua, danhSachBienBan: $danhSachBienBan, bienBan: $bienBan,
                    maBienBan: $maBienBan, chiDoc: true);
                return;
            }
        }
        $maPhienNhap = $this->taoPhienNhapUC08($maLoHang, $maBienBan, $phienBan, $maNhanVienQC);
        $this->renderRecordResultUC08(loHang: $loHang, danhSachTieuChi: $danhSachTieuChi,
            ketQua: $ketQua, loi: $loi, danhSachBienBan: $danhSachBienBan, bienBan: $bienBan,
            maBienBan: $maBienBan, phienBan: $phienBan, maPhienNhap: $maPhienNhap);
    }

    private function taoPhienNhapUC08(string $maLoHang, string $maBienBan, string $phienBan, string $maNhanVienQC): string
    {
        if (!isset($_SESSION['uc08_phien_nhap']) || !is_array($_SESSION['uc08_phien_nhap'])) {
            $_SESSION['uc08_phien_nhap'] = [];
        }
        foreach ($_SESSION['uc08_phien_nhap'] as $ma => $duLieu) {
            if (!is_array($duLieu) || time() - ($duLieu['thoiGianTao'] ?? 0) > 1800) {
                unset($_SESSION['uc08_phien_nhap'][$ma]);
            }
        }
        while (count($_SESSION['uc08_phien_nhap']) >= 20) {
            array_shift($_SESSION['uc08_phien_nhap']);
        }
        $maPhienNhap = bin2hex(random_bytes(32));
        $_SESSION['uc08_phien_nhap'][$maPhienNhap] = [
            'maLoHang' => $maLoHang, 'maBienBan' => $maBienBan, 'phienBan' => $phienBan,
            'maNhanVienQC' => $maNhanVienQC, 'thoiGianTao' => time()
        ];
        return $maPhienNhap;
    }

    private function layPhienNhapUC08(string $maPhienNhap, string $maNhanVienQC): ?array
    {
        if (preg_match('/^[a-f0-9]{64}$/D', $maPhienNhap) !== 1) {
            return null;
        }
        $duLieu = $_SESSION['uc08_phien_nhap'][$maPhienNhap] ?? null;
        if (!is_array($duLieu) || ($duLieu['maNhanVienQC'] ?? '') !== $maNhanVienQC || time() - ($duLieu['thoiGianTao'] ?? 0) > 1800) {
            return null;
        }
        return $duLieu;
    }

    public function storeResult(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!can('chat-luong', 'record')) {
            $this->flash('error', 'Bạn không có quyền ghi nhận kết quả kiểm tra.');
            $this->redirect('chat-luong');
            return;
        }
        $hanhDong = $this->layThamSoPOSTUC08('hanhDong');
        if ($hanhDong === null || !in_array($hanhDong, ['kiemTra', 'quayLai', 'xacNhan'], true)) {
            $this->flash('error', 'Thao tác không hợp lệ.');
            $this->redirect('chat-luong');
            return;
        }
        $maNhanVienQC = Auth::staffId();
        if ($maNhanVienQC === null || $maNhanVienQC === '') {
            $this->flash('error', 'Không xác định được nhân viên kiểm tra.');
            $this->redirect('login');
            return;
        }
        if ($hanhDong === 'kiemTra') {
            $this->kiemTraTruocKhiLuuUC08($maNhanVienQC);
            return;
        }
        $maXacNhan = $this->layThamSoPOSTUC08('maXacNhan');
        if ($maXacNhan === null || preg_match('/^[a-f0-9]{64}$/D', $maXacNhan) !== 1) {
            $this->flash('error', 'Phiên xác nhận không hợp lệ.');
            $this->redirect('chat-luong');
            return;
        }
        $duLieu = $_SESSION['uc08_cho_xac_nhan'][$maXacNhan] ?? null;
        if (!is_array($duLieu) || ($duLieu['maNhanVienQC'] ?? '') !== $maNhanVienQC || time() - ($duLieu['thoiGianTao'] ?? 0) > 1800) {
            $this->flash('error', 'Phiên xác nhận không hợp lệ hoặc đã hết hạn.');
            $this->redirect('chat-luong');
            return;
        }
        $maLoHang = $duLieu['maLoHang'];
        $maBienBan = $duLieu['maBienBan'];
        $phienBan = $duLieu['phienBan'];
        $maPhienNhap = $duLieu['maPhienNhap'];
        $ketQua = $duLieu['ketQua'];
        $phienNhap = $this->layPhienNhapUC08($maPhienNhap, $maNhanVienQC);
        if ($phienNhap === null || $phienNhap['maLoHang'] !== $maLoHang || $phienNhap['maBienBan'] !== $maBienBan || $phienNhap['phienBan'] !== $phienBan) {
            unset($_SESSION['uc08_cho_xac_nhan'][$maXacNhan]);
            $this->flash('error', 'Phiên nhập liệu đã hết hạn hoặc không hợp lệ.');
            $this->redirect('chat-luong');
            return;
        }
        unset($_SESSION['uc08_cho_xac_nhan'][$maXacNhan]);
        // Kiểm tra lại trước cả thao tác quay lại và xác nhận cuối.
        if (!$this->kiemTraQuyenGhiUC08($maLoHang, $maBienBan, $maNhanVienQC, $maPhienNhap)) {
            return;
        }
        if ($hanhDong === 'quayLai') {
            $this->hienThiLaiFormUC08($maLoHang, $maBienBan, $phienBan, $maPhienNhap, $ketQua);
            return;
        }
        try {
            ChatLuong::luuKetQuaUC08($maLoHang, $maNhanVienQC, $ketQua, $maBienBan, $phienBan);
            unset($_SESSION['uc08_phien_nhap'][$maPhienNhap]);
            $this->flash('success', 'Ghi nhận kết quả kiểm tra thành công.');
            $this->redirect('chat-luong');
            return;
        } catch (DomainException $e) {
            $this->hienThiLaiFormUC08($maLoHang, $maBienBan, $phienBan, $maPhienNhap, $ketQua, $e->getMessage());
        } catch (Throwable $e) {
            error_log('UC08 - ' . $e->getMessage());
            $this->hienThiLaiFormUC08($maLoHang, $maBienBan, $phienBan, $maPhienNhap, $ketQua, 'Không thể lưu kết quả kiểm tra. Vui lòng thử lại.');
        }
    }

    // Kiểm tra quyền ghi ở mọi bước POST; Model vẫn khóa và kiểm tra lại khi lưu.
    private function kiemTraQuyenGhiUC08(string $maLoHang, string $maBienBan,
        string $maNhanVienQC, string $maPhienNhap): bool
    {
        if (LoHang::layLoHangUC08($maLoHang) === null) {
            $thongBao = 'Lô hàng không còn ở trạng thái chờ kiểm tra chất lượng.';
        } elseif ($maBienBan !== '' &&
            !ChatLuong::coQuyenSuaBienBanUC08($maLoHang, $maBienBan, $maNhanVienQC)) {
            $thongBao = 'Bạn chỉ được sửa biên bản do mình kiểm định và đang chờ duyệt.';
        } elseif ($maBienBan === '' && !empty(ChatLuong::layDanhSachBienBanUC08($maLoHang))) {
            $thongBao = 'Lô hàng đã có biên bản chờ duyệt. Không được thực hiện kiểm tra mới.';
        } else {
            return true;
        }
        unset($_SESSION['uc08_phien_nhap'][$maPhienNhap]);
        foreach (($_SESSION['uc08_cho_xac_nhan'] ?? []) as $ma => $duLieu) {
            if (is_array($duLieu) && ($duLieu['maPhienNhap'] ?? '') === $maPhienNhap) {
                unset($_SESSION['uc08_cho_xac_nhan'][$ma]);
            }
        }
        $this->flash('error', $thongBao);
        $this->redirect('chat-luong');
        return false;
    }

    private function kiemTraTruocKhiLuuUC08(string $maNhanVienQC): void
    {
        $maPhienNhap = $this->layThamSoPOSTUC08('maPhienNhap');
        $ketQua = $_POST['ketQua'] ?? [];
        if ($maPhienNhap === null || !is_array($ketQua)) {
            $this->flash('error', 'Thông tin kiểm tra không hợp lệ.');
            $this->redirect('chat-luong');
            return;
        }
        $duLieu = $this->layPhienNhapUC08($maPhienNhap, $maNhanVienQC);
        if ($duLieu === null) {
            $this->flash('error', 'Phiên nhập liệu không hợp lệ hoặc đã hết hạn. Vui lòng mở lại lô hàng.');
            $this->redirect('chat-luong');
            return;
        }
        $maLoHang = $duLieu['maLoHang'];
        $maBienBan = $duLieu['maBienBan'];
        $phienBan = $duLieu['phienBan'];
        if (!$this->kiemTraQuyenGhiUC08($maLoHang, $maBienBan, $maNhanVienQC, $maPhienNhap)) {
            return;
        }
        $loHang = LoHang::layLoHangUC08($maLoHang);
        if ($loHang === null) {
            $this->flash('error', 'Lô hàng không còn ở trạng thái chờ kiểm tra chất lượng.');
            $this->redirect('chat-luong');
            return;
        }
        $danhSachBienBan = ChatLuong::layDanhSachBienBanUC08($maLoHang);
        $bienBan = null;
        if ($maBienBan !== '') {
            $duLieuBienBan = ChatLuong::layBienBanUC08($maLoHang, $maBienBan);
            if ($duLieuBienBan === null) {
                $this->flash('error', 'Biên bản không còn ở trạng thái chờ duyệt.');
                $this->redirect('chat-luong');
                return;
            }
            if (!hash_equals($duLieuBienBan['phienBan'], $phienBan)) {
                $this->flash('error', 'Biên bản đã được thay đổi bởi người dùng khác. Vui lòng mở lại biên bản.');
                $this->redirect('chat-luong');
                return;
            }
            $bienBan = $duLieuBienBan['bienBan'];
        } elseif (!empty($danhSachBienBan)) {
            $this->flash('error', 'Lô hàng đã được ghi nhận kết quả bởi người dùng khác.');
            $this->redirect('chat-luong');
            return;
        }
        $danhSachTieuChi = LoHang::layTieuChiKiemTraUC08($maLoHang);
        $loi = ChatLuong::kiemTraDuLieuUC08($danhSachTieuChi, $ketQua);
        if (!empty($loi)) {
            $this->renderRecordResultUC08(loHang: $loHang, danhSachTieuChi: $danhSachTieuChi,
                ketQua: $ketQua, loi: $loi, danhSachBienBan: $danhSachBienBan, bienBan: $bienBan,
                maBienBan: $maBienBan, phienBan: $phienBan, maPhienNhap: $maPhienNhap);
            return;
        }
        if (!isset($_SESSION['uc08_cho_xac_nhan']) || !is_array($_SESSION['uc08_cho_xac_nhan'])) {
            $_SESSION['uc08_cho_xac_nhan'] = [];
        }
        foreach ($_SESSION['uc08_cho_xac_nhan'] as $ma => $xacNhanCu) {
            if (!is_array($xacNhanCu) || time() - ($xacNhanCu['thoiGianTao'] ?? 0) > 1800 || ($xacNhanCu['maPhienNhap'] ?? '') === $maPhienNhap) {
                unset($_SESSION['uc08_cho_xac_nhan'][$ma]);
            }
        }
        $maXacNhan = bin2hex(random_bytes(32));
        $_SESSION['uc08_cho_xac_nhan'][$maXacNhan] = [
            'maLoHang' => $maLoHang, 'maBienBan' => $maBienBan, 'phienBan' => $phienBan,
            'maPhienNhap' => $maPhienNhap, 'maNhanVienQC' => $maNhanVienQC,
            'ketQua' => $ketQua, 'thoiGianTao' => time()
        ];
        $ketLuan = ChatLuong::xacDinhKetLuanUC08($ketQua);
        $this->renderRecordResultUC08(loHang: $loHang, danhSachTieuChi: $danhSachTieuChi,
            ketQua: $ketQua, choXacNhan: true, maXacNhan: $maXacNhan, ketLuan: $ketLuan,
            danhSachBienBan: $danhSachBienBan, bienBan: $bienBan, maBienBan: $maBienBan,
            phienBan: $phienBan, maPhienNhap: $maPhienNhap);
    }

    private function hienThiLaiFormUC08(string $maLoHang, string $maBienBan, string $phienBan,
        string $maPhienNhap, array $ketQua, string $thongBaoLoi = ''): void
    {
        if (!$this->kiemTraQuyenGhiUC08($maLoHang, $maBienBan, (string)(Auth::staffId() ?? ''), $maPhienNhap)) {
            return;
        }
        $loHang = LoHang::layLoHangUC08($maLoHang);
        if ($loHang === null) {
            $this->flash('error', 'Lô hàng không còn ở trạng thái chờ kiểm tra chất lượng.');
            $this->redirect('chat-luong');
            return;
        }
        $danhSachBienBan = ChatLuong::layDanhSachBienBanUC08($maLoHang);
        $bienBan = null;
        if ($maBienBan !== '') {
            $duLieuBienBan = ChatLuong::layBienBanUC08($maLoHang, $maBienBan);
            if ($duLieuBienBan === null) {
                $this->flash('error', 'Biên bản không còn ở trạng thái chờ duyệt.');
                $this->redirect('chat-luong');
                return;
            }
            if (!hash_equals($duLieuBienBan['phienBan'], $phienBan)) {
                $this->flash('error', 'Biên bản đã thay đổi. Vui lòng mở lại để ghi nhận kết quả.');
                $this->redirect('chat-luong');
                return;
            }
            $bienBan = $duLieuBienBan['bienBan'];
        } elseif (!empty($danhSachBienBan)) {
            $this->flash('error', 'Lô hàng đã được ghi nhận kết quả bởi người dùng khác.');
            $this->redirect('chat-luong');
            return;
        }
        $danhSachTieuChi = LoHang::layTieuChiKiemTraUC08($maLoHang);
        $loi = [];
        if ($thongBaoLoi !== '') {
            $loi = ChatLuong::kiemTraDuLieuUC08($danhSachTieuChi, $ketQua);
            $loi['_chung'] = $thongBaoLoi;
        }
        $this->renderRecordResultUC08(loHang: $loHang, danhSachTieuChi: $danhSachTieuChi,
            ketQua: $ketQua, loi: $loi, danhSachBienBan: $danhSachBienBan, bienBan: $bienBan,
            maBienBan: $maBienBan, phienBan: $phienBan, maPhienNhap: $maPhienNhap);
    }

    private function layDanhSachCoTheXoaUC08(string $maLoHang, array $danhSachBienBan, string $maNhanVienQC): array
    {
        $danhSachCoTheXoa = [];
        if ($maLoHang === '' || $maNhanVienQC === '') {
            return $danhSachCoTheXoa;
        }
        foreach ($danhSachBienBan as $bb) {
            $maBienBan = $bb['maBienBan'] ?? '';
            if (($bb['maNhanVienQC'] ?? '') !== $maNhanVienQC || ($bb['trangThai'] ?? '') !== 'Chờ duyệt') {
                continue;
            }
            if (!ChatLuong::coQuyenXoaBienBanUC08($maLoHang, $maBienBan, $maNhanVienQC)) {
                continue;
            }
            $duLieuBienBan = ChatLuong::layBienBanUC08($maLoHang, $maBienBan);
            if ($duLieuBienBan === null || $duLieuBienBan['bienBan']['maNhanVienQC'] !== $maNhanVienQC) {
                continue;
            }
            $danhSachCoTheXoa[$maBienBan] = $duLieuBienBan['phienBan'];
        }
        return $danhSachCoTheXoa;
    }

    private function renderRecordResultUC08(array $loHang, array $danhSachTieuChi, array $ketQua = [],
        array $loi = [], bool $choXacNhan = false, string $maXacNhan = '', string $ketLuan = '',
        array $danhSachBienBan = [], ?array $bienBan = null, string $maBienBan = '',
        string $phienBan = '', string $maPhienNhap = '', bool $canChonBienBan = false, bool $chiDoc = false): void
    {
        if (!empty($danhSachBienBan)) {
            $loHang['trangThaiChatLuong'] = 'Đã kiểm tra, chờ xác nhận';
        }
        $cheDoCapNhat = !$chiDoc && $maBienBan !== '';
        $maNhanVienQC = (string)(Auth::staffId() ?? '');
        $danhSachCoTheXoa = [];
        if (!$choXacNhan && $maNhanVienQC !== '') {
            $danhSachCoTheXoa = $this->layDanhSachCoTheXoaUC08($loHang['maLoHang'] ?? '', $danhSachBienBan, $maNhanVienQC);
        }
        $danhSachCoTheSua = [];
        foreach ($danhSachBienBan as $bb) {
            $ma = $bb['maBienBan'] ?? '';
            if (($bb['maNhanVienQC'] ?? '') === $maNhanVienQC &&
                ChatLuong::coQuyenSuaBienBanUC08($loHang['maLoHang'] ?? '', $ma, $maNhanVienQC)) {
                $danhSachCoTheSua[$ma] = true;
            }
        }
        $coQuyenSua = !$chiDoc && $maBienBan !== '' && isset($danhSachCoTheSua[$maBienBan]);
        $coTheKiemTraMoi = !$chiDoc && $maBienBan === '' && empty($danhSachBienBan) && $maPhienNhap !== '';
        $coQuyenXoa = !$chiDoc && $maBienBan !== '' && isset($danhSachCoTheXoa[$maBienBan]);
        if ($coQuyenXoa && !hash_equals($phienBan, $danhSachCoTheXoa[$maBienBan])) {
            $coQuyenXoa = false;
        }
        $phienBanXoa = $coQuyenXoa ? $phienBan : '';
        $this->render('chat-luong/record-result', [
            'loHang' => $loHang, 'danhSachTieuChi' => $danhSachTieuChi, 'ketQua' => $ketQua,
            'loi' => $loi, 'choXacNhan' => $choXacNhan, 'maXacNhan' => $maXacNhan,
            'ketLuan' => $ketLuan, 'danhSachBienBan' => $danhSachBienBan, 'bienBan' => $bienBan,
            'maBienBan' => $maBienBan, 'phienBan' => $phienBan, 'maPhienNhap' => $maPhienNhap,
            'canChonBienBan' => $canChonBienBan, 'cheDoCapNhat' => $cheDoCapNhat,
            'maNhanVienQC' => $maNhanVienQC, 'danhSachCoTheXoa' => $danhSachCoTheXoa,
            'coQuyenXoa' => $coQuyenXoa, 'phienBanXoa' => $phienBanXoa,
            'chiDoc' => $chiDoc, 'danhSachCoTheSua' => $danhSachCoTheSua,
            'coQuyenSua' => $coQuyenSua, 'coTheKiemTraMoi' => $coTheKiemTraMoi
        ], [
            'title' => 'Ghi nhận kết quả kiểm tra chất lượng', 'module' => 'chat-luong',
            'breadcrumb' => [['Kiểm tra chất lượng', 'chat-luong'], ['Ghi nhận kết quả kiểm tra chất lượng', null]]
        ]);
    }

    public function deleteResult(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo 'Phương thức không được phép.';
            return;
        }
        $maNhanVienQC = Auth::staffId();
        if ($maNhanVienQC === null || $maNhanVienQC === '') {
            $this->flash('error', 'Không xác định được nhân viên kiểm tra.');
            $this->redirect('login');
            return;
        }
        if (!can('chat-luong', 'record')) {
            $this->flash('error', 'Bạn không có quyền thực hiện thao tác này.');
            $this->redirect('chat-luong');
            return;
        }
        $maLoHang = $this->layThamSoPOSTUC08('maLoHang');
        $maBienBan = $this->layThamSoPOSTUC08('maBienBan');
        $phienBan = $this->layThamSoPOSTUC08('phienBan');
        if ($maLoHang === null || !$this->kiemTraMaLoUC07($maLoHang)) {
            $this->flash('error', 'Mã lô hàng không hợp lệ.');
            $this->redirect('chat-luong');
            return;
        }
        if ($maBienBan === null || !$this->kiemTraMaBienBanUC08($maBienBan)) {
            $this->flash('error', 'Mã biên bản kiểm định không hợp lệ.');
            $this->redirect('chat-luong');
            return;
        }
        if ($phienBan === null || preg_match('/^[a-f0-9]{64}$/D', $phienBan) !== 1) {
            $this->flash('error', 'Thông tin biên bản không hợp lệ. Vui lòng tải lại trang.');
            $this->redirect('chat-luong');
            return;
        }
        try {
            if (!ChatLuong::coQuyenXoaBienBanUC08($maLoHang, $maBienBan, $maNhanVienQC)) {
                throw new DomainException('Bạn chỉ được xóa biên bản do mình kiểm tra và đang ở trạng thái chờ duyệt.');
            }
            if (!ChatLuong::xoaBienBanUC08($maLoHang, $maBienBan, $maNhanVienQC, $phienBan)) {
                throw new DomainException('Không thể xóa kết quả kiểm định.');
            }
            $this->huyPhienBienBanUC08($maLoHang, $maBienBan);
            $this->flash('success', 'Xóa kết quả kiểm định thành công.');
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
        } catch (Throwable $e) {
            error_log('UC08 - Xóa biên bản: ' . $e->getMessage());
            $this->flash('error', 'Không thể xóa kết quả kiểm định. Vui lòng thử lại.');
        }
        $this->redirect('chat-luong');
    }

    private function huyPhienBienBanUC08(string $maLoHang, string $maBienBan): void
    {
        foreach (['uc08_phien_nhap', 'uc08_cho_xac_nhan'] as $tenSession) {
            if (isset($_SESSION[$tenSession]) && is_array($_SESSION[$tenSession])) {
                foreach ($_SESSION[$tenSession] as $ma => $duLieu) {
                    if (is_array($duLieu) && ($duLieu['maLoHang'] ?? '') === $maLoHang && ($duLieu['maBienBan'] ?? '') === $maBienBan) {
                        unset($_SESSION[$tenSession][$ma]);
                    }
                }
            }
        }
    }

    // =================================================
    // UC09 - XÁC NHẬN KẾT QUẢ KIỂM TRA CHẤT LƯỢNG
    // =================================================
    private function kiemTraQuyenUC09(): ?string
    {
        $maNhanVienDuyet = Auth::staffId();
        if ($maNhanVienDuyet === null || $maNhanVienDuyet === '') {
            $this->flash('error', 'Không xác định được nhân viên xác nhận.');
            $this->redirect('login');
            return null;
        }
        if (!can('chat-luong', 'confirm')) {
            $this->flash('error', 'Bạn không có quyền xác nhận kết quả kiểm tra chất lượng.');
            $this->redirect('chat-luong');
            return null;
        }
        return $maNhanVienDuyet;
    }

    public function confirmResult(): void
    {
        $maNhanVienDuyet = $this->kiemTraQuyenUC09();
        if ($maNhanVienDuyet === null) {
            return;
        }
        $maLoHang = $this->layThamSoUC07('maLoHang');
        $tuNgay = $this->layThamSoUC07('tuNgay');
        $denNgay = $this->layThamSoUC07('denNgay');
        $maChiTiet = $this->layThamSoUC07('maChiTiet');
        $maBienBan = $this->layThamSoUC07('maBienBan');
        $soLuongMoiTrang = 10;
        $trang = max(1, (int) filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
        $tongSoTrang = 1;
        $tongSoLoHang = 0;
        $danhSachLoHang = [];
        $danhSachBienBan = [];
        $loHang = null;
        $bienBan = null;
        $ketQua = [];
        $phienBan = '';
        $loi = [];
        $thongBao = '';
        $canChonBienBan = false;
        $hienThiFormXacNhan = false;

        if ($maLoHang === null || $tuNgay === null || $denNgay === null || $maChiTiet === null || $maBienBan === null) {
            $thongBao = 'Thông tin tra cứu không hợp lệ.';
        } elseif ($maLoHang !== '' && !$this->kiemTraMaLoUC07($maLoHang)) {
            $thongBao = 'Mã lô hàng không hợp lệ.';
        } elseif ($tuNgay !== '' && !$this->kiemTraNgayUC07($tuNgay)) {
            $thongBao = 'Thời gian bắt đầu không hợp lệ.';
        } elseif ($denNgay !== '' && !$this->kiemTraNgayUC07($denNgay)) {
            $thongBao = 'Thời gian kết thúc không hợp lệ.';
        } elseif ($tuNgay !== '' && $denNgay !== '' && $tuNgay > $denNgay) {
            $thongBao = 'Thời gian bắt đầu không được lớn hơn thời gian kết thúc.';
        } elseif ($maChiTiet !== '' && !$this->kiemTraMaLoUC07($maChiTiet)) {
            $thongBao = 'Mã lô hàng cần xác nhận không hợp lệ.';
        } elseif ($maBienBan !== '' && ($maChiTiet === '' || !$this->kiemTraMaBienBanUC08($maBienBan))) {
            $thongBao = 'Thông tin biên bản kiểm định không hợp lệ.';
        } else {
            try {
                $tongSoLoHang = LoHang::demLoHangChoXacNhanUC09($maNhanVienDuyet, $maLoHang, $tuNgay, $denNgay);
                $tongSoTrang = max(1, (int) ceil($tongSoLoHang / $soLuongMoiTrang));
                $trang = min($trang, $tongSoTrang);
                $danhSachLoHang = LoHang::traCuuLoHangChoXacNhanUC09($maNhanVienDuyet, $maLoHang, $tuNgay, $denNgay, $trang, $soLuongMoiTrang);
                if (empty($danhSachLoHang)) {
                    $thongBao = 'Không tìm thấy lô hàng chờ xác nhận phù hợp.';
                }
                if ($maChiTiet !== '') {
                    $loHang = LoHang::chiTietLoHangUC09($maChiTiet, $maNhanVienDuyet);
                    if ($loHang === null) {
                        $thongBao = 'Lô hàng không còn ở trạng thái chờ xác nhận hoặc không thuộc phạm vi quản lý.';
                    } else {
                        $danhSachBienBan = LoHang::layDanhSachBienBanUC09($maChiTiet, $maNhanVienDuyet);
                        if (count($danhSachBienBan) !== 1) {
                            $thongBao = empty($danhSachBienBan)
                                ? 'Lô hàng không còn biên bản chờ duyệt.'
                                : 'Lô hàng có nhiều biên bản chờ duyệt, không phù hợp quy tắc nghiệp vụ. Cần xử lý dữ liệu trước khi xác nhận.';
                            $loHang = null;
                        } elseif ($maBienBan !== '' && $maBienBan !== $danhSachBienBan[0]['maBienBan']) {
                            $thongBao = 'Biên bản không còn hợp lệ. Vui lòng mở lại lô hàng.';
                            $loHang = null;
                        } else {
                            // Tự động mở biên bản duy nhất, không qua bước chọn.
                            $maBienBan = $danhSachBienBan[0]['maBienBan'];
                            $duLieu = ChatLuong::layDuLieuXacNhanUC09($maChiTiet, $maBienBan, $maNhanVienDuyet);
                            if ($duLieu === null) {
                                $thongBao = 'Biên bản không còn chờ duyệt, chưa có kết quả hoặc dữ liệu đã thay đổi.';
                                $loHang = null;
                            } else {
                                $loHang = $duLieu['loHang'];
                                $bienBan = $duLieu['bienBan'];
                                $ketQua = $duLieu['ketQua'];
                                $phienBan = $duLieu['phienBan'];
                                $hienThiFormXacNhan = $this->layThamSoUC07('hanhDong') === 'xacNhan';
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log('UC09 - Tra cứu: ' . $e->getMessage());
                $thongBao = 'Không thể tải dữ liệu xác nhận chất lượng. Vui lòng thử lại.';
                $danhSachLoHang = [];
                $danhSachBienBan = [];
                $loHang = null;
                $bienBan = null;
                $ketQua = [];
                $phienBan = '';
                $tongSoLoHang = 0;
                $tongSoTrang = 1;
                $trang = 1;
                $canChonBienBan = false;
                $hienThiFormXacNhan = false;
            }
        }
        $this->renderConfirmResultUC09(danhSachLoHang: $danhSachLoHang, loHang: $loHang,
            danhSachBienBan: $danhSachBienBan, bienBan: $bienBan, ketQua: $ketQua, loi: $loi,
            maLoHang: $maLoHang ?? '', tuNgay: $tuNgay ?? '', denNgay: $denNgay ?? '',
            maChiTiet: $maChiTiet ?? '', maBienBan: $maBienBan ?? '', phienBan: $phienBan,
            thongBao: $thongBao, trang: $trang, tongSoTrang: $tongSoTrang,
            tongSoLoHang: $tongSoLoHang, soLuongMoiTrang: $soLuongMoiTrang,
            canChonBienBan: $canChonBienBan, hienThiFormXacNhan: $hienThiFormXacNhan);
    }

    private function kiemTraBienBanDuyNhatUC09(string $maLoHang, string $maBienBan, string $maNhanVienDuyet): void
    {
        $danhSach = LoHang::layDanhSachBienBanUC09($maLoHang, $maNhanVienDuyet);
        if (count($danhSach) !== 1 || $danhSach[0]['maBienBan'] !== $maBienBan) {
            throw new DomainException('Lô hàng không có đúng một biên bản chờ duyệt hợp lệ. Vui lòng kiểm tra lại dữ liệu.');
        }
    }

    private function layThamSoPOSTUC09(string $ten): ?string
    {
        $giaTri = $_POST[$ten] ?? '';
        return is_string($giaTri) ? trim($giaTri) : null;
    }

    public function storeConfirmResult(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo 'Phương thức không được phép.';
            return;
        }
        $maNhanVienDuyet = $this->kiemTraQuyenUC09();
        if ($maNhanVienDuyet === null) {
            return;
        }
        $hanhDong = $this->layThamSoPOSTUC09('hanhDong');
        if ($hanhDong === null || !in_array($hanhDong, ['kiemTra', 'quayLai', 'huy', 'xacNhan'], true)) {
            $this->flash('error', 'Thao tác xác nhận không hợp lệ.');
            $this->redirect('chat-luong/confirm-result');
            return;
        }
        if ($hanhDong === 'kiemTra') {
            $this->kiemTraTruocXacNhanUC09($maNhanVienDuyet);
            return;
        }
        $maXacNhan = $this->layThamSoPOSTUC09('maXacNhan');
        if ($maXacNhan === null || preg_match('/^[a-f0-9]{64}$/D', $maXacNhan) !== 1) {
            $this->flash('error', 'Phiên xác nhận không hợp lệ.');
            $this->redirect('chat-luong/confirm-result');
            return;
        }
        $duLieu = $this->layPhienXacNhanUC09($maXacNhan, $maNhanVienDuyet);
        if ($duLieu === null) {
            $this->flash('error', 'Phiên xác nhận không hợp lệ hoặc đã hết hạn.');
            $this->redirect('chat-luong/confirm-result');
            return;
        }
        $maLoHang = $duLieu['maLoHang'];
        $maBienBan = $duLieu['maBienBan'];
        $ketQuaXacNhan = $duLieu['ketQuaXacNhan'];
        $lyDoKhongDat = $duLieu['lyDoKhongDat'];
        $phienBan = $duLieu['phienBan'];
        unset($_SESSION['uc09_cho_xac_nhan'][$maXacNhan]);
        if ($hanhDong === 'quayLai' || $hanhDong === 'huy') {
            $this->hienThiLaiFormUC09($maLoHang, $maBienBan, $maNhanVienDuyet, $ketQuaXacNhan, $lyDoKhongDat, $phienBan);
            return;
        }
        try {
            $this->kiemTraBienBanDuyNhatUC09($maLoHang, $maBienBan, $maNhanVienDuyet);
            $ketQuaXuLy = ChatLuong::xacNhanKetQuaUC09($maLoHang, $maBienBan, $maNhanVienDuyet, $ketQuaXacNhan, $lyDoKhongDat, $phienBan);
            if (($ketQuaXuLy['thanhCong'] ?? false) !== true) {
                throw new DomainException('Không thể xác nhận kết quả kiểm tra.');
            }
            $this->huyPhienLoHangUC09($maLoHang);
            $thongBao = 'Xác nhận kết quả kiểm tra thành công.';
            $huongXuLy = $ketQuaXuLy['huongXuLy'] ?? '';
            if ($huongXuLy !== '') {
                $thongBao .= ' ' . $huongXuLy;
            }
            $this->flash('success', $thongBao);
            $this->redirect('chat-luong/confirm-result');
            return;
        } catch (DomainException $e) {
            $this->hienThiLaiFormUC09($maLoHang, $maBienBan, $maNhanVienDuyet, $ketQuaXacNhan, $lyDoKhongDat, $phienBan, $e->getMessage());
        } catch (Throwable $e) {
            error_log('UC09 - Xác nhận: ' . $e->getMessage());
            $this->hienThiLaiFormUC09($maLoHang, $maBienBan, $maNhanVienDuyet, $ketQuaXacNhan, $lyDoKhongDat, $phienBan, 'Không thể xác nhận kết quả kiểm tra. Vui lòng thử lại.');
        }
    }

    private function kiemTraTruocXacNhanUC09(string $maNhanVienDuyet): void
    {
        $maLoHang = $this->layThamSoPOSTUC09('maLoHang');
        $maBienBan = $this->layThamSoPOSTUC09('maBienBan');
        $ketQuaXacNhan = $this->layThamSoPOSTUC09('ketQuaXacNhan');
        $lyDoKhongDat = $this->layThamSoPOSTUC09('lyDoKhongDat');
        $phienBan = $this->layThamSoPOSTUC09('phienBan');
        if ($maLoHang === null || !$this->kiemTraMaLoUC07($maLoHang)) {
            $this->flash('error', 'Mã lô hàng không hợp lệ.');
            $this->redirect('chat-luong/confirm-result');
            return;
        }
        if ($maBienBan === null || !$this->kiemTraMaBienBanUC08($maBienBan)) {
            $this->flash('error', 'Mã biên bản kiểm định không hợp lệ.');
            $this->redirect('chat-luong/confirm-result');
            return;
        }
        if ($phienBan === null || preg_match('/^[a-f0-9]{64}$/D', $phienBan) !== 1) {
            $this->flash('error', 'Thông tin kết quả kiểm định không hợp lệ. Vui lòng tải lại trang.');
            $this->redirect('chat-luong/confirm-result');
            return;
        }
        if ($ketQuaXacNhan === null || $lyDoKhongDat === null) {
            $this->flash('error', 'Thông tin xác nhận không hợp lệ.');
            $this->redirect('chat-luong/confirm-result');
            return;
        }
        try {
            $this->kiemTraBienBanDuyNhatUC09($maLoHang, $maBienBan, $maNhanVienDuyet);
            $duLieu = ChatLuong::layDuLieuXacNhanUC09($maLoHang, $maBienBan, $maNhanVienDuyet);
            if ($duLieu === null) {
                $this->flash('error', 'Lô hàng không còn ở trạng thái chờ xác nhận hoặc biên bản không còn hợp lệ.');
                $this->redirect('chat-luong/confirm-result');
                return;
            }
            if (!hash_equals($duLieu['phienBan'], $phienBan)) {
                $this->flash('error', 'Kết quả kiểm định đã thay đổi. Vui lòng mở lại biên bản trước khi xác nhận.');
                $this->redirect('chat-luong/confirm-result');
                return;
            }
            $loi = ChatLuong::kiemTraDuLieuUC09($ketQuaXacNhan, $lyDoKhongDat);
            if ($ketQuaXacNhan === 'Đạt') {
                $lyDoKhongDat = '';
            }
            $danhSachBienBan = LoHang::layDanhSachBienBanUC09($maLoHang, $maNhanVienDuyet);
            if (!empty($loi)) {
                $this->renderConfirmResultUC09(loHang: $duLieu['loHang'], danhSachBienBan: $danhSachBienBan,
                    bienBan: $duLieu['bienBan'], ketQua: $duLieu['ketQua'], loi: $loi,
                    maChiTiet: $maLoHang, maBienBan: $maBienBan, phienBan: $phienBan,
                    ketQuaXacNhan: $ketQuaXacNhan, lyDoKhongDat: $lyDoKhongDat, hienThiFormXacNhan: true);
                return;
            }
            $maXacNhan = $this->taoPhienXacNhanUC09($maLoHang, $maBienBan, $maNhanVienDuyet, $ketQuaXacNhan, $lyDoKhongDat, $phienBan);
            $this->renderConfirmResultUC09(loHang: $duLieu['loHang'], danhSachBienBan: $danhSachBienBan,
                bienBan: $duLieu['bienBan'], ketQua: $duLieu['ketQua'], maChiTiet: $maLoHang,
                maBienBan: $maBienBan, phienBan: $phienBan, ketQuaXacNhan: $ketQuaXacNhan,
                lyDoKhongDat: $lyDoKhongDat, hienThiFormXacNhan: true, choXacNhan: true, maXacNhan: $maXacNhan);
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('chat-luong/confirm-result');
        } catch (Throwable $e) {
            error_log('UC09 - Kiểm tra trước xác nhận: ' . $e->getMessage());
            $this->flash('error', 'Không thể kiểm tra dữ liệu xác nhận. Vui lòng thử lại.');
            $this->redirect('chat-luong/confirm-result');
        }
    }

    private function taoPhienXacNhanUC09(string $maLoHang, string $maBienBan, string $maNhanVienDuyet,
        string $ketQuaXacNhan, string $lyDoKhongDat, string $phienBan): string
    {
        if (!isset($_SESSION['uc09_cho_xac_nhan']) || !is_array($_SESSION['uc09_cho_xac_nhan'])) {
            $_SESSION['uc09_cho_xac_nhan'] = [];
        }
        foreach ($_SESSION['uc09_cho_xac_nhan'] as $ma => $duLieu) {
            if (!is_array($duLieu) || time() - ($duLieu['thoiGianTao'] ?? 0) > 1800 ||
                (($duLieu['maLoHang'] ?? '') === $maLoHang && ($duLieu['maBienBan'] ?? '') === $maBienBan &&
                ($duLieu['maNhanVienDuyet'] ?? '') === $maNhanVienDuyet)) {
                unset($_SESSION['uc09_cho_xac_nhan'][$ma]);
            }
        }
        while (count($_SESSION['uc09_cho_xac_nhan']) >= 20) {
            array_shift($_SESSION['uc09_cho_xac_nhan']);
        }
        $maXacNhan = bin2hex(random_bytes(32));
        $_SESSION['uc09_cho_xac_nhan'][$maXacNhan] = [
            'maLoHang' => $maLoHang, 'maBienBan' => $maBienBan, 'maNhanVienDuyet' => $maNhanVienDuyet,
            'ketQuaXacNhan' => $ketQuaXacNhan, 'lyDoKhongDat' => $lyDoKhongDat,
            'phienBan' => $phienBan, 'thoiGianTao' => time()
        ];
        return $maXacNhan;
    }

    private function layPhienXacNhanUC09(string $maXacNhan, string $maNhanVienDuyet): ?array
    {
        if (preg_match('/^[a-f0-9]{64}$/D', $maXacNhan) !== 1) {
            return null;
        }
        $duLieu = $_SESSION['uc09_cho_xac_nhan'][$maXacNhan] ?? null;
        if (!is_array($duLieu) || ($duLieu['maNhanVienDuyet'] ?? '') !== $maNhanVienDuyet) {
            return null;
        }
        if (time() - ($duLieu['thoiGianTao'] ?? 0) > 1800) {
            unset($_SESSION['uc09_cho_xac_nhan'][$maXacNhan]);
            return null;
        }
        foreach (['maLoHang', 'maBienBan', 'maNhanVienDuyet', 'ketQuaXacNhan', 'lyDoKhongDat', 'phienBan'] as $thuocTinh) {
            if (!isset($duLieu[$thuocTinh]) || !is_string($duLieu[$thuocTinh])) {
                return null;
            }
        }
        return $duLieu;
    }

    private function hienThiLaiFormUC09(string $maLoHang, string $maBienBan, string $maNhanVienDuyet,
        string $ketQuaXacNhan, string $lyDoKhongDat, string $phienBan, string $thongBaoLoi = ''): void
    {
        try {
            if (!LoHang::conChoXacNhanUC09($maLoHang, $maNhanVienDuyet)) {
                $this->flash('error', 'Lô hàng không còn ở trạng thái chờ xác nhận hoặc không thuộc phạm vi quản lý.');
                $this->redirect('chat-luong/confirm-result');
                return;
            }
            $this->kiemTraBienBanDuyNhatUC09($maLoHang, $maBienBan, $maNhanVienDuyet);
            $duLieu = ChatLuong::layDuLieuXacNhanUC09($maLoHang, $maBienBan, $maNhanVienDuyet);
            if ($duLieu === null) {
                $this->flash('error', 'Biên bản không còn tồn tại hoặc không còn ở trạng thái chờ duyệt.');
                $this->redirect('chat-luong/confirm-result');
                return;
            }
            if (!hash_equals($duLieu['phienBan'], $phienBan)) {
                $this->flash('error', 'Kết quả kiểm định đã thay đổi. Vui lòng mở lại biên bản.');
                $this->redirect('chat-luong/confirm-result');
                return;
            }
            $danhSachBienBan = LoHang::layDanhSachBienBanUC09($maLoHang, $maNhanVienDuyet);
            $loi = [];
            if ($thongBaoLoi !== '') {
                $loi = ChatLuong::kiemTraDuLieuUC09($ketQuaXacNhan, $lyDoKhongDat);
                $loi['_chung'] = $thongBaoLoi;
            }
            $this->renderConfirmResultUC09(loHang: $duLieu['loHang'], danhSachBienBan: $danhSachBienBan,
                bienBan: $duLieu['bienBan'], ketQua: $duLieu['ketQua'], loi: $loi, maChiTiet: $maLoHang,
                maBienBan: $maBienBan, phienBan: $phienBan, ketQuaXacNhan: $ketQuaXacNhan,
                lyDoKhongDat: $lyDoKhongDat, hienThiFormXacNhan: true, choXacNhan: false);
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('chat-luong/confirm-result');
        } catch (Throwable $e) {
            error_log('UC09 - Hiển thị lại biểu mẫu: ' . $e->getMessage());
            $this->flash('error', 'Không thể tải lại thông tin xác nhận.');
            $this->redirect('chat-luong/confirm-result');
        }
    }

    private function huyPhienLoHangUC09(string $maLoHang): void
    {
        foreach (['uc08_phien_nhap', 'uc08_cho_xac_nhan', 'uc09_cho_xac_nhan'] as $tenSession) {
            if (!isset($_SESSION[$tenSession]) || !is_array($_SESSION[$tenSession])) {
                continue;
            }
            foreach ($_SESSION[$tenSession] as $ma => $duLieu) {
                if (is_array($duLieu) && ($duLieu['maLoHang'] ?? '') === $maLoHang) {
                    unset($_SESSION[$tenSession][$ma]);
                }
            }
        }
    }

    private function renderConfirmResultUC09(array $danhSachLoHang = [], ?array $loHang = null,
        array $danhSachBienBan = [], ?array $bienBan = null, array $ketQua = [], array $loi = [],
        string $maLoHang = '', string $tuNgay = '', string $denNgay = '', string $maChiTiet = '',
        string $maBienBan = '', string $phienBan = '', string $thongBao = '', int $trang = 1,
        int $tongSoTrang = 1, int $tongSoLoHang = 0, int $soLuongMoiTrang = 10,
        bool $canChonBienBan = false, bool $hienThiFormXacNhan = false, string $ketQuaXacNhan = '',
        string $lyDoKhongDat = '', bool $choXacNhan = false, string $maXacNhan = ''): void
    {
        $maNhanVienDuyet = (string)(Auth::staffId() ?? '');
        $ketLuanQC = $bienBan['ketLuan'] ?? '';
        $loaiHangHoa = $loHang['loaiHangHoa'] ?? '';
        $coTheXacNhan = $loHang !== null && $bienBan !== null && !empty($ketQua) && $phienBan !== '';
        $huongXuLyDuKien = '';
        if ($ketQuaXacNhan === 'Đạt') {
            $huongXuLyDuKien = 'Lô hàng đủ điều kiện về chất lượng để thực hiện lập phiếu nhập kho.';
        } elseif ($ketQuaXacNhan === 'Không đạt') {
            if ($loaiHangHoa === 'Nguyên liệu') {
                $huongXuLyDuKien = 'Hệ thống sẽ lập biên bản trả hàng và chuyển sang chờ xuất trả.';
            } elseif ($loaiHangHoa === 'Thành phẩm') {
                $huongXuLyDuKien = 'Lô thành phẩm sẽ được ghi nhận cách ly và xác định kho hàng lỗi làm kho đích dự kiến.';
            }
        }
        $this->render('chat-luong/confirm-result', [
            'danhSachLoHang' => $danhSachLoHang, 'maLoHang' => $maLoHang, 'tuNgay' => $tuNgay,
            'denNgay' => $denNgay, 'thongBao' => $thongBao, 'trang' => $trang, 'tongSoTrang' => $tongSoTrang,
            'tongSoLoHang' => $tongSoLoHang, 'soLuongMoiTrang' => $soLuongMoiTrang,
            'loHang' => $loHang, 'maChiTiet' => $maChiTiet, 'loaiHangHoa' => $loaiHangHoa,
            'danhSachBienBan' => $danhSachBienBan, 'bienBan' => $bienBan, 'maBienBan' => $maBienBan,
            'canChonBienBan' => $canChonBienBan, 'ketQua' => $ketQua, 'ketLuanQC' => $ketLuanQC,
            'phienBan' => $phienBan, 'maNhanVienDuyet' => $maNhanVienDuyet, 'coTheXacNhan' => $coTheXacNhan,
            'hienThiFormXacNhan' => $hienThiFormXacNhan, 'ketQuaXacNhan' => $ketQuaXacNhan,
            'lyDoKhongDat' => $lyDoKhongDat, 'choXacNhan' => $choXacNhan, 'maXacNhan' => $maXacNhan,
            'huongXuLyDuKien' => $huongXuLyDuKien, 'loi' => $loi
        ], [
            'title' => 'Xác nhận kết quả kiểm tra chất lượng', 'module' => 'chat-luong',
            'breadcrumb' => [['Kiểm tra chất lượng', 'chat-luong'], ['Xác nhận kết quả kiểm tra chất lượng', null]]
        ]);
    }
}
