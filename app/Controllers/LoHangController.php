<?php
namespace App\Controllers;

/**
 * LoHangController — module "lo-hang".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class LoHangController extends Controller
{
    /** UC05 — Chi tiết lô hàng. GET lo-hang/detail · quyền lo-hang/view · view lo-hang/detail */
    public function detail(): void
    {
        // TODO (UC05): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('lo-hang/detail', [], [
            'title'      => 'Chi tiết lô hàng',
            'module'     => 'lo-hang',
            'breadcrumb' => [
            ['Lô hàng', 'lo-hang'],
            ['Chi tiết lô hàng', null]
            ],
        ]);
    }

    /** UC05 — Tra cứu thông tin lô hàng. GET lo-hang · quyền lo-hang/view · view lo-hang/index */
    public function index(): void
    {
        // TODO (UC05): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('lo-hang/index', [], [
            'title'      => 'Tra cứu thông tin lô hàng',
            'module'     => 'lo-hang',
            'breadcrumb' => [
            ['Lô hàng', null]
            ],
        ]);
    }
}
