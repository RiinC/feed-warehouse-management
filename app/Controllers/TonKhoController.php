<?php
namespace App\Controllers;

use App\Models\DanhMuc;
use App\Models\HangBatThuong;
use App\Models\LichSuBienDongTonKho;
use App\Models\NhanVien;
use App\Models\TonKho;
use Core\Auth;
use Core\Request;

/**
 * TonKhoController — UC29: tra cứu tồn kho theo kho / khu vực / vị trí / mặt hàng / hạn sử dụng.
 * Chỉ đọc: mọi thay đổi số lượng tồn đi qua nghiệp vụ phiếu nhập, xuất, điều chuyển, điều chỉnh.
 */
class TonKhoController extends Controller
{
    private const PER_PAGE = 8;

    /**
     * Phạm vi kho của người dùng:
     * - Admin, Ban giám đốc: xem toàn hệ thống (null).
     * - Nhân viên QC / Kiểm kê: theo kho phụ trách nếu có, nếu không gắn kho cố định -> toàn hệ thống (null).
     * - Quản lý kho, Nhân viên kho: giới hạn đúng kho phụ trách.
     */
    private function khoScope(): ?string
    {
        if (!Auth::check()) {
            return '';
        }
        $role = Auth::role();
        if (in_array($role, [ROLE_ADMIN, ROLE_DIRECTOR], true)) {
            return null;
        }
        $nv = NhanVien::find(Auth::staffId());
        $maKho = trim((string)($nv['maKho'] ?? ''));
        if ($role === ROLE_INVENTORY) {
            return $maKho !== '' ? $maKho : null;
        }
        return $maKho;
    }

    /** UC29 — Tra cứu tồn kho (theo lô - vị trí, tổng hợp mặt hàng, lịch sử biến động). */
    public function index(): void
    {
        $scope = $this->khoScope();
        if ($scope === '') {
            $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.');
            $this->redirect('dashboard');
            return;
        }

        $mode = (string)Request::get('mode', 'lot');
        if (!in_array($mode, ['lot', 'product', 'history'], true)) {
            $mode = 'lot';
        }

        $f = [
            'q'           => trim((string)Request::get('q')),
            'maKho'       => $scope !== null ? $scope : (string)Request::get('maKho'),
            'maKhuVuc'    => (string)Request::get('maKhuVuc'),
            'maViTri'     => (string)Request::get('maViTri'),
            'maHangHoa'   => (string)Request::get('maHangHoa'),
            'loaiHangHoa' => (string)Request::get('loaiHangHoa'),
            'hanSuDung'   => (string)Request::get('hanSuDung'),
            'conHang'     => (string)Request::get('conHang'),
            'sort'        => (string)Request::get('sort'),
            'dir'         => (string)Request::get('dir'),
        ];

        // Mặc định mỗi lần mở trang: HSD gần nhất lên đầu (lô sắp hết hạn xử lý trước).
        if ($f['sort'] === '') {
            $f['sort'] = 'hsd';
            $f['dir']  = 'asc';
        }

        $page = max(1, Request::int('page', 1));
        $rows = [];
        $total = 0;

        if ($mode === 'product') {
            [$rows, $total] = TonKho::searchSummaryByProduct($f, $page, self::PER_PAGE);
        } elseif ($mode === 'history') {
            $rows = LichSuBienDongTonKho::recentScoped($f['maKho'] !== '' ? $f['maKho'] : $scope, $f, 50);
            $total = count($rows);
        } else {
            [$rows, $total] = TonKho::search($f, $page, self::PER_PAGE);
        }

        $this->render('ton-kho/index', [
            'f'             => $f,
            'mode'          => $mode,
            'scope'         => $scope,
            'rows'          => $rows,
            'total'         => $total,
            'page'          => $page,
            'perPage'       => self::PER_PAGE,
            'kho'           => DanhMuc::kho(),
            'khuVucTheoKho' => DanhMuc::khuVucTheoKho(),
            'viTriList'     => DanhMuc::viTriChiTiet($scope),
            'loaiHang'      => DanhMuc::loaiHangHoa(),
            'mucHan'        => TonKho::HAN_SU_DUNG,
        ], [
            'title'      => 'Tra cứu tồn kho',
            'subtitle'   => '',
            'module'     => 'ton-kho',
            'breadcrumb' => [['Tra cứu tồn kho', null]],
        ]);
    }

    /** Chi tiết một dòng tồn kho theo lô & vị trí. */
    public function detail(): void
    {
        $scope = $this->khoScope();
        if ($scope === '') {
            $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.');
            $this->redirect('dashboard');
            return;
        }

        $id = Request::int('id', 0);
        if ($id <= 0) {
            $this->flash('error', 'Thiếu mã dòng tồn kho.');
            $this->redirect('ton-kho');
            return;
        }

        $item = TonKho::findDetail($id);
        if (!$item) {
            $this->flash('error', 'Không tìm thấy dòng tồn kho #' . $id . '.');
            $this->redirect('ton-kho');
            return;
        }

        if ($scope !== null && ($item['maKho'] ?? '') !== $scope) {
            $this->forbidden();
            return;
        }

        $maLoHang = (string)$item['maLoHang'];
        $maViTri = (string)$item['maViTri'];

        $this->render('ton-kho/detail', [
            'item'       => $item,
            'hbtRecords' => HangBatThuong::byStockSlot($maLoHang, $maViTri),
        ], [
            'title'      => '',
            'subtitle'   => '',
            'module'     => 'ton-kho',
            'breadcrumb' => [['Tra cứu tồn kho', 'ton-kho'], [$maLoHang, null]],
        ]);
    }

    /**
     * In bảng kê chi tiết tồn kho theo lô & vị trí — bản A4 độc lập (không layout),
     * cùng cơ chế với kiem-ke/print và hang-bat-thuong/print.
     */
    public function printVoucher(): void
    {
        $scope = $this->khoScope();
        if ($scope === '') {
            $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.');
            $this->redirect('dashboard');
            return;
        }

        $id = Request::int('id', 0);
        if ($id <= 0) {
            $this->flash('error', 'Thiếu mã dòng tồn kho.');
            $this->redirect('ton-kho');
            return;
        }

        $item = TonKho::findDetail($id);
        if (!$item) {
            $this->flash('error', 'Không tìm thấy dòng tồn kho #' . $id . '.');
            $this->redirect('ton-kho');
            return;
        }

        if ($scope !== null && ($item['maKho'] ?? '') !== $scope) {
            $this->forbidden();
            return;
        }

        $maLoHang = (string)$item['maLoHang'];
        $maViTri  = (string)$item['maViTri'];

        $this->render('ton-kho/print', [
            'item'       => $item,
            'hbtRecords' => HangBatThuong::byStockSlot($maLoHang, $maViTri),
            'history'      => LichSuBienDongTonKho::historyForStock($maLoHang, $maViTri, 20),
            'historyLimit' => 20,
            'nguoiLap'   => (string)(Auth::user()['hoTen'] ?? ''),
        ], [
            'title' => 'In bảng kê tồn kho ' . $maLoHang,
        ], null);
    }
}
