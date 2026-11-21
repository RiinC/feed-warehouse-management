<?php
namespace App\Controllers;

/**
 * TonKhoController — module "ton-kho".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class TonKhoController extends Controller
{
    /** UC29 — Tra cứu tồn kho. GET ton-kho · quyền ton-kho/view · view ton-kho/index */
    public function index(): void
    {
        // TODO (UC29): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('ton-kho/index', [], [
            'title'      => 'Tra cứu tồn kho',
            'module'     => 'ton-kho',
            'breadcrumb' => [
            ['Tồn kho', null]
            ],
        ]);
    }
}
