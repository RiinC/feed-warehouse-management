<?php
namespace App\Controllers;

/**
 * TiepNhanController — module "tiep-nhan".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class TiepNhanController extends Controller
{
    /** UC06 — Tiếp nhận hàng. GET tiep-nhan/create · quyền tiep-nhan/create · view tiep-nhan/create */
    public function create(): void
    {
        // TODO (UC06): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('tiep-nhan/create', [], [
            'title'      => 'Tiếp nhận hàng',
            'module'     => 'tiep-nhan',
            'breadcrumb' => [
            ['Tiếp nhận hàng', 'tiep-nhan'],
            ['Tiếp nhận hàng', null]
            ],
        ]);
    }

    /** UC06 — Tiếp nhận hàng vào kho. GET tiep-nhan · quyền tiep-nhan/view · view tiep-nhan/index */
    public function index(): void
    {
        // TODO (UC06): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('tiep-nhan/index', [], [
            'title'      => 'Tiếp nhận hàng vào kho',
            'module'     => 'tiep-nhan',
            'breadcrumb' => [
            ['Tiếp nhận hàng', null]
            ],
            'actions'    => action_buttons([
                ['tiep-nhan', 'create', 'Tiếp nhận hàng mới', 'tiep-nhan/create', 'btn btn-primary']
            ]),
        ]);
    }
}
