<?php
namespace App\Controllers;

/**
 * NhapKhoController — module "nhap-kho".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class NhapKhoController extends Controller
{
    /** UC10 — Lập phiếu nhập kho. GET nhap-kho/create · quyền nhap-kho/create · view nhap-kho/create */
    public function create(): void
    {
        // TODO (UC10): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('nhap-kho/create', [], [
            'title'      => 'Lập phiếu nhập kho',
            'module'     => 'nhap-kho',
            'breadcrumb' => [
            ['Nhập kho', 'nhap-kho'],
            ['Lập phiếu nhập kho', null]
            ],
        ]);
    }

    /** UC11 — Tra cứu lịch sử nhập kho. GET nhap-kho/history · quyền nhap-kho/history · view nhap-kho/history */
    public function history(): void
    {
        // TODO (UC11): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('nhap-kho/history', [], [
            'title'      => 'Tra cứu lịch sử nhập kho',
            'module'     => 'nhap-kho',
            'breadcrumb' => [
            ['Nhập kho', 'nhap-kho'],
            ['Tra cứu lịch sử nhập kho', null]
            ],
        ]);
    }

    /** UC10 — Nhập kho. GET nhap-kho · quyền nhap-kho/view · view nhap-kho/index */
    public function index(): void
    {
        // TODO (UC10): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('nhap-kho/index', [], [
            'title'      => 'Nhập kho',
            'module'     => 'nhap-kho',
            'breadcrumb' => [
            ['Nhập kho', null]
            ],
            'actions'    => action_buttons([
                ['nhap-kho', 'create', 'Lập phiếu nhập kho', 'nhap-kho/create', 'btn btn-primary'],
                ['nhap-kho', 'history', 'Lịch sử nhập kho', 'nhap-kho/history', 'btn']
            ]),
        ]);
    }
}
