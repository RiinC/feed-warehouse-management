<?php
namespace App\Controllers;

/**
 * TaiKhoanController — module "tai-khoan".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class TaiKhoanController extends Controller
{
    /** Quản trị hệ thống — Quản lý tài khoản. GET tai-khoan · quyền tai-khoan/view · view tai-khoan/index */
    public function index(): void
    {
        // TODO (Quản trị hệ thống): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('tai-khoan/index', [], [
            'title'      => 'Quản lý tài khoản',
            'module'     => 'tai-khoan',
            'breadcrumb' => [
            ['Tài khoản', null]
            ],
        ]);
    }
}
