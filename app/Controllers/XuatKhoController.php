<?php
namespace App\Controllers;

/**
 * XuatKhoController — module "xuat-kho".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class XuatKhoController extends Controller
{
    /** UC22 — Đề xuất lô hàng xuất kho theo FEFO. GET xuat-kho/fefo · quyền xuat-kho/fefo · view xuat-kho/fefo */
    public function fefo(): void
    {
        // TODO (UC22): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('xuat-kho/fefo', [], [
            'title'      => 'Đề xuất lô hàng xuất kho theo FEFO',
            'module'     => 'xuat-kho',
            'breadcrumb' => [
            ['Xuất kho', 'xuat-kho/yeu-cau'],
            ['Đề xuất lô hàng xuất kho theo FEFO', null]
            ],
        ]);
    }

    /** UC23 — Lập phiếu xuất kho. GET xuat-kho/phieu-xuat · quyền xuat-kho/issue · view xuat-kho/phieu-xuat */
    public function issue(): void
    {
        // TODO (UC23): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('xuat-kho/phieu-xuat', [], [
            'title'      => 'Lập phiếu xuất kho',
            'module'     => 'xuat-kho',
            'breadcrumb' => [
            ['Xuất kho', 'xuat-kho/yeu-cau'],
            ['Lập phiếu xuất kho', null]
            ],
        ]);
    }

    /** UC21 — Phê duyệt yêu cầu xuất kho. GET xuat-kho/yeu-cau/approve · quyền xuat-kho/approve · view xuat-kho/yeu-cau/approve */
    public function approveRequest(): void
    {
        // TODO (UC21): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('xuat-kho/yeu-cau/approve', [], [
            'title'      => 'Phê duyệt yêu cầu xuất kho',
            'module'     => 'xuat-kho',
            'breadcrumb' => [
            ['Yêu cầu xuất kho', 'xuat-kho/yeu-cau'],
            ['Phê duyệt yêu cầu xuất kho', null]
            ],
        ]);
    }

    /** UC18 — Lập yêu cầu xuất kho. GET xuat-kho/yeu-cau/create · quyền xuat-kho/request · view xuat-kho/yeu-cau/create */
    public function createRequest(): void
    {
        // TODO (UC18): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('xuat-kho/yeu-cau/create', [], [
            'title'      => 'Lập yêu cầu xuất kho',
            'module'     => 'xuat-kho',
            'breadcrumb' => [
            ['Yêu cầu xuất kho', 'xuat-kho/yeu-cau'],
            ['Lập yêu cầu xuất kho', null]
            ],
        ]);
    }

    /** UC19 — Sửa yêu cầu xuất kho. GET xuat-kho/yeu-cau/edit · quyền xuat-kho/request · view xuat-kho/yeu-cau/edit */
    public function editRequest(): void
    {
        // TODO (UC19): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('xuat-kho/yeu-cau/edit', [], [
            'title'      => 'Sửa yêu cầu xuất kho',
            'module'     => 'xuat-kho',
            'breadcrumb' => [
            ['Yêu cầu xuất kho', 'xuat-kho/yeu-cau'],
            ['Sửa yêu cầu xuất kho', null]
            ],
        ]);
    }

    /** UC20 — Tra cứu yêu cầu xuất kho. GET xuat-kho/yeu-cau · quyền xuat-kho/view · view xuat-kho/yeu-cau/index */
    public function requests(): void
    {
        // TODO (UC20): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('xuat-kho/yeu-cau/index', [], [
            'title'      => 'Tra cứu yêu cầu xuất kho',
            'module'     => 'xuat-kho',
            'breadcrumb' => [
            ['Yêu cầu xuất kho', null]
            ],
            'actions'    => action_buttons([
                ['xuat-kho', 'request', 'Lập yêu cầu xuất kho', 'xuat-kho/yeu-cau/create', 'btn btn-primary'],
                ['xuat-kho', 'approve', 'Phê duyệt yêu cầu', 'xuat-kho/yeu-cau/approve', 'btn'],
                ['xuat-kho', 'fefo', 'Đề xuất lô FEFO', 'xuat-kho/fefo', 'btn'],
                ['xuat-kho', 'issue', 'Lập phiếu xuất kho', 'xuat-kho/phieu-xuat', 'btn']
            ]),
        ]);
    }
}
