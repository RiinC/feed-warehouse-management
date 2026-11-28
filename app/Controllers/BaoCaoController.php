<?php
namespace App\Controllers;

use App\Models\BaoCao;
use App\Models\LuuTruBaoCao;
use Core\Auth;
use DomainException;
use Throwable;

/** UC30 đọc tồn kho; gửi và xem lịch sử chỉ ghi file JSON. */
class BaoCaoController extends Controller
{
    /** GET bao-cao — trang chính UC30, xử lý trực tiếp bộ lọc và báo cáo. */
    public function index(): void
    {
        $this->inventory();
    }

    /** Trang FEFO dự kiến trong route hiện có; không triển khai nghiệp vụ tại đây. */
    public function expiryFefo(): void
    {
        $this->render('bao-cao/expiry-fefo', [], [
            'title' => 'Báo cáo hạn sử dụng theo FEFO',
            'module' => 'bao-cao',
            'breadcrumb' => [['Báo cáo', 'bao-cao'], ['Báo cáo hạn sử dụng theo FEFO', null]]
        ]);
    }

    // =========================================================
    // UC30 - XEM BÁO CÁO TỒN KHO
    // =========================================================

    private function quyenUC30(): ?string
    {
        $nv = Auth::staffId();
        if (!Auth::check() || $nv === '') {
            $this->flash('error', 'Vui lòng đăng nhập.');
            $this->redirect('login');
            return null;
        }
        // Kiểm tra quyền inventory riêng của UC30, kể cả khi truy cập từ route chính.
        if (!in_array(Auth::role(), ['director', 'manager'], true)
            || !can('bao-cao', 'inventory')) {
            $this->flash('error', 'Bạn không có quyền xem báo cáo tồn kho.');
            $this->redirect('dashboard');
            return null;
        }
        return $nv;
    }

    /** Từ chối tham số dạng mảng; không nhận vai trò/mã nhân viên từ URL. */
    private function thamSoUC30(string $ten): ?string
    {
        $v = $_GET[$ten] ?? '';
        return is_string($v) ? trim($v) : null;
    }

    /** Xử lý UC30 cho route chính bao-cao; giữ inventory() để tương thích route cũ. */
    public function inventory(): void
    {
        $nv = $this->quyenUC30();
        if ($nv === null) return;

        $laQuanLyKho = Auth::role() === 'manager';
        $maKho = $this->thamSoUC30('maKho');
        $maKho = $maKho === null ? null : strtoupper($maKho);
        $tuNgay = $this->thamSoUC30('tuNgay');
        $denNgay = $this->thamSoUC30('denNgay');
        $page = $this->thamSoUC30('page');
        $xem = $this->thamSoUC30('xemBaoCao');
        // Lần đầu mở trang chỉ hiển thị bộ lọc. URL phân trang giữ các tham số này.
        $daYeuCau = false;
        foreach (['xemBaoCao', 'maKho', 'tuNgay', 'denNgay', 'page'] as $key) {
            if (array_key_exists($key, $_GET)) {
                $daYeuCau = true;
                break;
            }
        }

        $danhSachKho = [];
        $khoPhuTrach = null;
        $baoCao = null;
        $danhSach = [];
        $tongCong = null;
        $canhBao = [];
        $coCanhBao = false;
        $tongSoHangHoa = 0;
        $tongSoTrang = 1;
        $soLuongMoiTrang = 10;
        $trang = 1;
        $thongBao = '';
        $loi = false;

        try {
            // Model xác minh vai trò, trạng thái và phạm vi kho từ CSDL.
            $danhSachKho = BaoCao::layDanhSachKhoUC30($nv);
            if ($laQuanLyKho) {
                if (count($danhSachKho) !== 1) {
                    throw new DomainException('Không xác định được kho phụ trách của quản lý.');
                }
                $khoPhuTrach = $danhSachKho[0];
                if (!$daYeuCau) $maKho = (string)$khoPhuTrach['maKho'];
            }

            if ($daYeuCau) {
                if ($maKho === null || $tuNgay === null || $denNgay === null
                    || $page === null || $xem === null || ($xem !== '' && $xem !== '1')) {
                    throw new DomainException('Thông tin lọc không hợp lệ.');
                }
                if ($maKho === '') throw new DomainException('Vui lòng nhập mã kho.');
                if ($laQuanLyKho && $maKho !== $khoPhuTrach['maKho']) {
                    throw new DomainException('Bạn không có quyền xem báo cáo của kho này.');
                }
                if (!in_array($maKho, array_column($danhSachKho, 'maKho'), true)) {
                    throw new DomainException('Mã kho không tồn tại.');
                }
                if (!BaoCao::ngayHopLeUC30($tuNgay) || !BaoCao::ngayHopLeUC30($denNgay)) {
                    throw new DomainException('Thông tin thời gian không hợp lệ.');
                }
                if ($tuNgay !== '' && $denNgay !== '' && $tuNgay > $denNgay) {
                    throw new DomainException('Thời gian bắt đầu không được lớn hơn thời gian kết thúc.');
                }
                if ($page !== '') {
                    $p = filter_var($page, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    if ($p === false) throw new DomainException('Số trang không hợp lệ.');
                    $trang = $p;
                }

                // Model kiểm tra lại quyền kho và tính tổng trên toàn bộ kỳ.
                $baoCao = BaoCao::taoBaoCaoTonKhoUC30($nv, $maKho, $tuNgay, $denNgay);
                $tongSoHangHoa = count($baoCao['danhSach']);
                $tongCong = $baoCao['tongCong'];
                $canhBao = $baoCao['canhBao'];
                $coCanhBao = $baoCao['coCanhBao'];
                $tongSoTrang = max(1, (int)ceil($tongSoHangHoa / $soLuongMoiTrang));
                $trang = min($trang, $tongSoTrang);
                $danhSach = array_slice($baoCao['danhSach'], ($trang - 1) * $soLuongMoiTrang, $soLuongMoiTrang);
                // Giữ danh sách đầy đủ cho biểu đồ; bảng vẫn dùng $danhSach đã phân trang.
                if (!$danhSach) $thongBao = 'Không tìm thấy dữ liệu tồn kho phù hợp.';
            }
        } catch (DomainException $e) {
            $thongBao = $e->getMessage();
            $loi = true;
        } catch (Throwable $e) {
            error_log('UC30 inventory: ' . $e->getMessage());
            $thongBao = 'Không thể tải báo cáo tồn kho. Vui lòng thử lại.';
            $loi = true;
        }
        if ($loi) {
            $baoCao = null;
            $danhSach = [];
            $tongCong = null;
            $canhBao = [];
            $coCanhBao = false;
            $tongSoHangHoa = 0;
            $tongSoTrang = 1;
            $trang = 1;
        }
        // Giữ kho đúng của quản lý trên giao diện kể cả khi URL bị sửa.
        if ($laQuanLyKho && $khoPhuTrach !== null) $maKho = (string)$khoPhuTrach['maKho'];
        $boLoc = [
            'xemBaoCao' => '1', 'maKho' => $maKho ?? '',
            'tuNgay' => $tuNgay ?? '', 'denNgay' => $denNgay ?? '', 'page' => $trang
        ];
        $maXacNhanGui = '';
        $coTheGui = $laQuanLyKho && !$loi && !empty($baoCao['danhSach']);
        if ($coTheGui) {
            $maXacNhanGui = $this->luuPhienGui($nv, $baoCao);
        }
        $this->render('bao-cao/index', [
            'danhSachKho' => $danhSachKho,
            'laQuanLyKho' => $laQuanLyKho,
            'khoPhuTrach' => $khoPhuTrach,
            'maKho' => $maKho ?? '',
            'tuNgay' => $tuNgay ?? '', 'denNgay' => $denNgay ?? '',
            'daYeuCau' => $daYeuCau, 'baoCao' => $baoCao,
            'danhSach' => $danhSach, 'tongCong' => $tongCong,
            'canhBao' => $canhBao, 'coCanhBao' => $coCanhBao,
            'tongSoHangHoa' => $tongSoHangHoa, 'tongSoTrang' => $tongSoTrang,
            'soLuongMoiTrang' => $soLuongMoiTrang, 'trang' => $trang,
            'thongBao' => $thongBao, 'loi' => $loi, 'boLoc' => $boLoc,
            'coTheGui' => $coTheGui, 'maXacNhanGui' => $maXacNhanGui
        ], [
            'title' => 'Xem báo cáo tồn kho',
            'module' => 'bao-cao',
            'breadcrumb' => [['Xem báo cáo tồn kho', null]]
        ]);
    }
    // =========================================================
    // GỬI VÀ TRA CỨU BÁO CÁO JSON
    // =========================================================

    /** Route chính sẽ chuyển sang landing ở bước cấu hình route.
     * Giữ index hiện tại hoạt động trong lúc chưa tạo các View mới.
     */
    public function landing(): void
    {
        if ($this->quyenUC30() === null) return;
        if (Auth::role() === 'director' && !array_key_exists('tab', $_GET)) {
            foreach (['xemBaoCao', 'maKho', 'tuNgay', 'denNgay', 'page'] as $key) {
                if (array_key_exists($key, $_GET)) {
                    $this->inventory();
                    return;
                }
            }
            $this->history();
            return;
        }
        $this->inventory();
    }

    /** Bản chụp trên server, không nhận số liệu báo cáo từ POST.
     * Tối đa 5 bản chụp/người dùng/phiên, hiệu lực 30 phút.
     */
    private function luuPhienGui(string $nv, array $baoCao): string
    {
        $ds = $_SESSION['uc30_phien_gui'] ?? [];
        if (!is_array($ds)) $ds = [];
        foreach ($ds as $key => $p) {
            if (!is_array($p) || ($p['nv'] ?? '') !== $nv
                || time() - ($p['thoiGian'] ?? 0) > 1800) unset($ds[$key]);
        }
        while (count($ds) >= 5) array_shift($ds);
        $token = bin2hex(random_bytes(32));
        $ds[$token] = ['nv' => $nv, 'thoiGian' => time(), 'baoCao' => $baoCao];
        $_SESSION['uc30_phien_gui'] = $ds;
        return $token;
    }

    private function thamSoPOST(string $ten): ?string
    {
        $v = $_POST[$ten] ?? '';
        return is_string($v) ? trim($v) : null;
    }

    private function maBaoCaoHopLe(?string $ma): bool
    {
        return $ma !== null && preg_match('/\ABC[0-9]{6}\z/', $ma) === 1;
    }

    /** POST bao-cao/send. Router kiểm tra CSRF (form dùng csrf_field()). */
    public function send(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); return; }
        $nv = $this->quyenUC30();
        if ($nv === null) return;
        if (Auth::role() !== 'manager' || !can('bao-cao', 'send')) {
            $this->flash('error', 'Bạn không có quyền gửi báo cáo.');
            $this->redirect('bao-cao');
            return;
        }
        $token = $this->thamSoPOST('maXacNhanGui');
        $ghiChu = $this->thamSoPOST('ghiChu');
        try {
            if ($token === null || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {
                throw new DomainException('Phiên gửi báo cáo không hợp lệ.');
            }
            $p = $_SESSION['uc30_phien_gui'][$token] ?? null;
            if (!is_array($p) || ($p['nv'] ?? '') !== $nv
                || time() - ($p['thoiGian'] ?? 0) > 1800 || !is_array($p['baoCao'] ?? null)) {
                throw new DomainException('Phiên gửi đã hết hạn. Vui lòng xem lại báo cáo.');
            }
            $bc = $p['baoCao'];
            if (empty($bc['danhSach'])) {
                throw new DomainException('Báo cáo không có dữ liệu để gửi.');
            }
            if ($ghiChu === null || !preg_match('//u', $ghiChu) || preg_match_all('/./us', $ghiChu) > 500) {
                throw new DomainException('Ghi chú không hợp lệ (tối đa 500 ký tự).');
            }
            $ngay = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
            if (($bc['denNgay'] ?? '') > $ngay || ($bc['tuNgay'] ?? '') > $ngay) {
                throw new DomainException('Không gửi báo cáo có kỳ trong tương lai.');
            }
            // Model kiểm tra lại nhân viên, kho và chống trùng dưới khóa file.
            $daGui = LuuTruBaoCao::guiBaoCao($nv, $bc, $ghiChu);
            unset($_SESSION['uc30_phien_gui'][$token]);
            $this->flash('success', 'Báo cáo đã được gửi. Mã báo cáo: ' . $daGui['maBaoCao']);
            $this->redirect('bao-cao/history');
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('bao-cao');
        } catch (Throwable $e) {
            error_log('UC30 send: ' . $e->getMessage());
            $this->flash('error', 'Không thể gửi báo cáo. Vui lòng thử lại.');
            $this->redirect('bao-cao');
        }
    }

    /** GET bao-cao/history — danh sách cùng kho đối với quản lý, toàn bộ đối với giám đốc. */
    public function history(): void
    {
        $nv = $this->quyenUC30();
        if ($nv === null) return;
        if (!can('bao-cao', 'history')) {
            $this->flash('error', 'Bạn không có quyền tra cứu lịch sử báo cáo.');
            $this->redirect('dashboard');
            return;
        }
        $laQuanLyKho = Auth::role() === 'manager';
        $maBaoCao = $this->thamSoUC30('maBaoCao');
        $maKho = $this->thamSoUC30('maKho');
        $trangThai = $this->thamSoUC30('trangThai');
        $tuNgay = $this->thamSoUC30('tuNgay');
        $denNgay = $this->thamSoUC30('denNgay');
        $page = $this->thamSoUC30('page');
        $danhSachKho = []; $danhSach = []; $tongSoBaoCao = 0;
        $trang = 1; $tongSoTrang = 1; $soLuongMoiTrang = 10;
        $thongBao = ''; $loi = false;
        try {
            $danhSachKho = BaoCao::layDanhSachKhoUC30($nv);
            if (in_array(null, [$maBaoCao, $maKho, $trangThai, $tuNgay, $denNgay, $page], true)) {
                throw new DomainException('Bộ lọc không hợp lệ.');
            }
            $maBaoCao = strtoupper($maBaoCao); $maKho = strtoupper($maKho);
            if ($maBaoCao !== '' && !$this->maBaoCaoHopLe($maBaoCao)) throw new DomainException('Mã báo cáo không hợp lệ.');
            if ($laQuanLyKho) {
                if (count($danhSachKho) !== 1) throw new DomainException('Không xác định được kho phụ trách.');
                $maKho = (string)$danhSachKho[0]['maKho'];
            }
            if ($maKho !== '' && !in_array($maKho, array_column($danhSachKho, 'maKho'), true)) throw new DomainException('Mã kho không hợp lệ.');
            $tt = $laQuanLyKho ? ['', 'Đã gửi', 'Đã xem'] : ['', 'Chưa xem', 'Đã xem'];
            if (!in_array($trangThai, $tt, true)) throw new DomainException('Trạng thái không hợp lệ.');
            if (!BaoCao::ngayHopLeUC30($tuNgay) || !BaoCao::ngayHopLeUC30($denNgay) || ($tuNgay !== '' && $denNgay !== '' && $tuNgay > $denNgay)) throw new DomainException('Khoảng ngày gửi không hợp lệ.');
            if ($page !== '') {
                $p = filter_var($page, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                if ($p === false) throw new DomainException('Số trang không hợp lệ.');
                $trang = $p;
            }
            $ds = array_values(array_filter(LuuTruBaoCao::layDanhSach($nv), static function ($r) use ($maBaoCao, $maKho, $trangThai, $tuNgay, $denNgay) {
                $ngay = substr((string)$r['thoiGianGui'], 0, 10);
                return ($maBaoCao === '' || $r['maBaoCao'] === $maBaoCao)
                    && ($maKho === '' || $r['maKho'] === $maKho)
                    && ($trangThai === '' || $r['trangThai'] === $trangThai)
                    && ($tuNgay === '' || $ngay >= $tuNgay) && ($denNgay === '' || $ngay <= $denNgay);
            }));
            $tongSoBaoCao = count($ds);
            $tongSoTrang = max(1, (int)ceil($tongSoBaoCao / $soLuongMoiTrang));
            $trang = min($trang, $tongSoTrang);
            $danhSach = array_slice($ds, ($trang-1)*$soLuongMoiTrang, $soLuongMoiTrang);
            if (!$danhSach) $thongBao = 'Không tìm thấy báo cáo phù hợp.';
        } catch (DomainException $e) {
            $loi = true; $thongBao = $e->getMessage();
        } catch (Throwable $e) {
            error_log('UC30 history: ' . $e->getMessage());
            $loi = true; $thongBao = 'Không thể tải danh sách báo cáo.';
        }
        $boLoc = ['maBaoCao'=>$maBaoCao ?? '', 'maKho'=>$maKho ?? '', 'trangThai'=>$trangThai ?? '', 'tuNgay'=>$tuNgay ?? '', 'denNgay'=>$denNgay ?? '', 'page'=>$trang];
        $this->render('bao-cao/history', array_merge($boLoc, compact('laQuanLyKho','danhSachKho','danhSach','tongSoBaoCao','tongSoTrang','soLuongMoiTrang','thongBao','loi','boLoc')), [
            'title' => $laQuanLyKho ? 'Báo cáo đã gửi' : 'Báo cáo đã nhận', 'module'=>'bao-cao',
            'breadcrumb'=>[['Báo cáo','bao-cao'],[$laQuanLyKho ? 'Báo cáo đã gửi' : 'Báo cáo đã nhận',null]]
        ]);
    }

    /** GET bao-cao/detail?maBaoCao=... — chỉ đọc snapshot. */
    public function detail(): void
    {
        $nv = $this->quyenUC30();
        if ($nv === null) return;
        if (!can('bao-cao', 'history')) { $this->redirect('dashboard'); return; }
        try {
            $ma = $this->thamSoUC30('maBaoCao');
            if (!$this->maBaoCaoHopLe($ma)) throw new DomainException('Mã báo cáo không hợp lệ.');
            $chiTiet = LuuTruBaoCao::layChiTiet($nv, $ma);
            $baoCao = $chiTiet['baoCao'];
            $danhSach = $baoCao['danhSach'];
            $tongCong = $baoCao['tongCong'];
            $canhBao = $baoCao['canhBao'];
            $coCanhBao = $baoCao['coCanhBao'];
            $laQuanLyKho = Auth::role() === 'manager';
            $canDanhDau = !$laQuanLyKho && can('bao-cao','read') && $chiTiet['trangThai'] === 'Chưa xem';
            $this->render('bao-cao/detail', compact('chiTiet','baoCao','danhSach','tongCong','canhBao','coCanhBao','laQuanLyKho','canDanhDau'), [
                'title'=>'Chi tiết báo cáo tồn kho', 'module'=>'bao-cao',
                'breadcrumb'=>[['Báo cáo','bao-cao'],['Lịch sử báo cáo','bao-cao/history'],['Chi tiết',null]]
            ]);
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage()); $this->redirect('bao-cao/history');
        } catch (Throwable $e) {
            error_log('UC30 detail: ' . $e->getMessage());
            $this->flash('error','Không thể mở báo cáo.'); $this->redirect('bao-cao/history');
        }
    }

    /** POST bao-cao/delete — thu hồi báo cáo chưa xem; Router kiểm tra CSRF. */
    public function delete(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            return;
        }
        $nv = $this->quyenUC30();
        if ($nv === null) return;
        if (Auth::role() !== 'manager' || !can('bao-cao', 'delete')) {
            $this->flash('error', 'Bạn không có quyền xóa báo cáo.');
            $this->redirect('bao-cao/history');
            return;
        }
        try {
            $ma = $this->thamSoPOST('maBaoCao');
            if (!$this->maBaoCaoHopLe($ma)) {
                throw new DomainException('Mã báo cáo không hợp lệ.');
            }
            // Model kiểm tra người gửi, kho phụ trách và trạng thái xem dưới khóa file.
            LuuTruBaoCao::xoaBaoCao($nv, $ma);
            $this->flash('success', 'Đã xóa báo cáo ' . $ma . '.');
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
        } catch (Throwable $e) {
            error_log('UC30 delete: ' . $e->getMessage());
            $this->flash('error', 'Không thể xóa báo cáo. Vui lòng thử lại.');
        }
        $this->redirect('bao-cao/history');
    }

    /** POST bao-cao/read — Router kiểm tra CSRF; GET không ghi trạng thái. */
    public function markRead(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); return; }
        $nv = $this->quyenUC30();
        if ($nv === null) return;
        if (Auth::role() !== 'director' || !can('bao-cao','read')) {
            $this->flash('error','Bạn không có quyền đánh dấu đã xem.'); $this->redirect('bao-cao/history'); return;
        }
        try {
            $ma = $this->thamSoPOST('maBaoCao');
            if (!$this->maBaoCaoHopLe($ma)) throw new DomainException('Mã báo cáo không hợp lệ.');
            LuuTruBaoCao::danhDauDaXem($nv, $ma);
            $this->flash('success','Đã ghi nhận xem báo cáo ' . $ma . '.');
        } catch (DomainException $e) {
            $this->flash('error',$e->getMessage());
        } catch (Throwable $e) {
            error_log('UC30 read: ' . $e->getMessage()); $this->flash('error','Không thể ghi nhận trạng thái đã xem.');
        }
        $this->redirect('bao-cao/history');
    }
}
