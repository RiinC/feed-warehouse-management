<?php
namespace App\Controllers;

/**
 * HangHoaController — module "hang-hoa".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class HangHoaController extends Controller
{
    /** UC01 — Thêm thông tin hàng hóa. GET hang-hoa/create · quyền hang-hoa/create · view hang-hoa/create */
    public function create(): void
    {
        // TODO (UC01): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('hang-hoa/create', [], [
            'title'      => 'Thêm thông tin hàng hóa',
            'module'     => 'hang-hoa',
            'breadcrumb' => [
            ['Hàng hóa', 'hang-hoa'],
            ['Thêm thông tin hàng hóa', null]
            ],
        ]);
    }

    /** UC02 — Sửa thông tin hàng hóa. GET hang-hoa/edit · quyền hang-hoa/edit · view hang-hoa/edit */
    public function edit(): void
    {
        // TODO (UC02): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('hang-hoa/edit', [], [
            'title'      => 'Sửa thông tin hàng hóa',
            'module'     => 'hang-hoa',
            'breadcrumb' => [
            ['Hàng hóa', 'hang-hoa'],
            ['Sửa thông tin hàng hóa', null]
            ],
        ]);
    }

    /** UC04 — Tra cứu thông tin hàng hóa. GET hang-hoa · quyền hang-hoa/view · view hang-hoa/index */
    public function index(): void
    {
        // TODO (UC04): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('hang-hoa/index', [], [
            'title'      => 'Tra cứu thông tin hàng hóa',
            'module'     => 'hang-hoa',
            'breadcrumb' => [
            ['Hàng hóa', null]
            ],
            'actions'    => action_buttons([
                ['hang-hoa', 'create', 'Thêm hàng hóa', 'hang-hoa/create', 'btn btn-primary']
            ]),
        ]);
    }

    /** UC03 — Ngừng sử dụng hàng hóa. GET hang-hoa/stop-using · quyền hang-hoa/stop · view hang-hoa/stop-using */
    public function stopUsing(): void
    {
        // TODO (UC03): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('hang-hoa/stop-using', [], [
            'title'      => 'Ngừng sử dụng hàng hóa',
            'module'     => 'hang-hoa',
            'breadcrumb' => [
            ['Hàng hóa', 'hang-hoa'],
            ['Ngừng sử dụng hàng hóa', null]
            ],
        ]);
    }
}
