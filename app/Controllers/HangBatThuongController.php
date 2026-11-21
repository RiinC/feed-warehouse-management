<?php
namespace App\Controllers;

/**
 * HangBatThuongController — module "hang-bat-thuong".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class HangBatThuongController extends Controller
{
    /** UC28 — Xử lý hàng bất thường. GET hang-bat-thuong · quyền hang-bat-thuong/view · view hang-bat-thuong/index */
    public function index(): void
    {
        // TODO (UC28): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('hang-bat-thuong/index', [], [
            'title'      => 'Xử lý hàng bất thường',
            'module'     => 'hang-bat-thuong',
            'breadcrumb' => [
            ['Hàng bất thường', null]
            ],
        ]);
    }
}
