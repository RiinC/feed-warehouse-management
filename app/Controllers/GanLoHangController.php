<?php
namespace App\Controllers;

/**
 * GanLoHangController — module "gan-lo-hang".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class GanLoHangController extends Controller
{
    /** UC16 — Gán lô hàng vào vị trí lưu trữ. GET gan-lo-hang · quyền gan-lo-hang/view · view gan-lo-hang/index */
    public function index(): void
    {
        // TODO (UC16): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('gan-lo-hang/index', [], [
            'title'      => 'Gán lô hàng vào vị trí lưu trữ',
            'module'     => 'gan-lo-hang',
            'breadcrumb' => [
            ['Gán lô hàng', null]
            ],
        ]);
    }
}
