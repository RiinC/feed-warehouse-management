<?php
namespace App\Controllers;

/**
 * DieuChuyenController — module "dieu-chuyen".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class DieuChuyenController extends Controller
{
    /** UC17 — Lập phiếu điều chuyển. GET dieu-chuyen/create · quyền dieu-chuyen/create · view dieu-chuyen/create */
    public function create(): void
    {
        // TODO (UC17): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('dieu-chuyen/create', [], [
            'title'      => 'Lập phiếu điều chuyển',
            'module'     => 'dieu-chuyen',
            'breadcrumb' => [
            ['Điều chuyển nội bộ', 'dieu-chuyen'],
            ['Lập phiếu điều chuyển', null]
            ],
        ]);
    }

    /** UC17 — Điều chuyển hàng nội bộ. GET dieu-chuyen · quyền dieu-chuyen/view · view dieu-chuyen/index */
    public function index(): void
    {
        // TODO (UC17): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('dieu-chuyen/index', [], [
            'title'      => 'Điều chuyển hàng nội bộ',
            'module'     => 'dieu-chuyen',
            'breadcrumb' => [
            ['Điều chuyển nội bộ', null]
            ],
            'actions'    => action_buttons([
                ['dieu-chuyen', 'create', 'Lập phiếu điều chuyển', 'dieu-chuyen/create', 'btn btn-primary']
            ]),
        ]);
    }
}
