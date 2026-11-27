<?php
namespace App\Controllers;

use App\Models\Kho;
use App\Models\KhuVuc;
use App\Models\LoHang;
use App\Models\LuuKho;
use App\Models\ViTriLuuTru;
use Core\Auth;
use Core\Request;

/** UC16 - Gán lô hàng vào vị trí lưu trữ. Nhân viên kho gán; quản lý kho theo dõi. */
class GanLoHangController extends Controller
{
    public function index(): void
    {
        $scope = $this->warehouseScope();
        if ($scope === '') {
            $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.');
            $this->redirect('dashboard');
            return;
        }
        $f = ['q' => (string)Request::get('q')];
        $page = max(1, Request::int('page', 1));
        $perPage = (int)config('per_page', 10);
        $hpage = max(1, Request::int('hpage', 1));
        [$rows, $total] = LoHang::candidates($f, $page, $perPage, $scope);
        [$history, $hTotal] = LuuKho::history($f, $hpage, 15, $scope);
        $this->render('gan-lo-hang/index', [
            'rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage,
            'history' => $history, 'hTotal' => $hTotal, 'hPage' => $hpage, 'hPerPage' => 15,
            'lotOptions' => LoHang::candidateOptions($scope),
            'positions' => ViTriLuuTru::positionOptions($scope, true), 'areas' => KhuVuc::optionsWithKho($scope),
            'khoOptions' => Kho::activeOptions($scope), 'scope' => $scope,
        ], [
            'title'      => 'Gán lô hàng vào vị trí lưu trữ',
            'module'     => 'gan-lo-hang',
            'breadcrumb' => [['Gán lô hàng', null]],
        ]);
    }

    /** POST gan-lo-hang/assign */
    public function assign(): void
    {
        $scope = $this->warehouseScope();
        if ($scope === '') {
            $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.');
            $this->redirect('dashboard');
            return;
        }
        $qty = parse_qty(Request::post('soLuong'), false, 2);
        $data = [
            'maLoHang' => (string)Request::post('maLoHang'),
            'maViTri' => (string)Request::post('maViTri'),
            'soLuong' => $qty ?? 0.0,
            'ghiChu' => (string)Request::post('ghiChu'),
            'maKho' => $scope,
        ];
        if ($data['maLoHang'] === '' || $data['maViTri'] === '' || $qty === null) {
            $this->flash('error', 'Vui lòng chọn lô, vị trí và nhập số lượng nguyên lớn hơn 0.');
            $this->redirect('gan-lo-hang');
            return;
        }
        try {
            $r = LuuKho::assign($data, Auth::staffId());
            $this->flash('success', 'Đã gán ' . fmt_number($r['soLuong'], 2) . ' kg của lô ' . $r['maLoHang'] . ' vào vị trí ' . $r['maViTri'] . '.');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        }
        $this->redirect('gan-lo-hang');
    }
}

