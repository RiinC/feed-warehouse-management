<?php
namespace App\Controllers;

/**
 * ViTriLuuTruController — module "vi-tri-luu-tru".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class ViTriLuuTruController extends Controller
{
    /** UC12 — Thêm thông tin vị trí lưu trữ. GET vi-tri-luu-tru/create · quyền vi-tri-luu-tru/create · view vi-tri-luu-tru/create */
    public function create(): void
    {
        // TODO (UC12): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('vi-tri-luu-tru/create', [], [
            'title'      => 'Thêm thông tin vị trí lưu trữ',
            'module'     => 'vi-tri-luu-tru',
            'breadcrumb' => [
            ['Vị trí lưu trữ', 'vi-tri-luu-tru'],
            ['Thêm thông tin vị trí lưu trữ', null]
            ],
        ]);
    }

    /** UC13 — Sửa thông tin vị trí lưu trữ. GET vi-tri-luu-tru/edit · quyền vi-tri-luu-tru/edit · view vi-tri-luu-tru/edit */
    public function edit(): void
    {
        // TODO (UC13): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('vi-tri-luu-tru/edit', [], [
            'title'      => 'Sửa thông tin vị trí lưu trữ',
            'module'     => 'vi-tri-luu-tru',
            'breadcrumb' => [
            ['Vị trí lưu trữ', 'vi-tri-luu-tru'],
            ['Sửa thông tin vị trí lưu trữ', null]
            ],
        ]);
    }

    /** UC15 — Tra cứu vị trí lưu trữ. GET vi-tri-luu-tru · quyền vi-tri-luu-tru/view · view vi-tri-luu-tru/index */
    public function index(): void
    {
        // TODO (UC15): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('vi-tri-luu-tru/index', [], [
            'title'      => 'Tra cứu vị trí lưu trữ',
            'module'     => 'vi-tri-luu-tru',
            'breadcrumb' => [
            ['Vị trí lưu trữ', null]
            ],
            'actions'    => action_buttons([
                ['vi-tri-luu-tru', 'create', 'Thêm vị trí lưu trữ', 'vi-tri-luu-tru/create', 'btn btn-primary']
            ]),
        ]);
    }

    /** UC14 — Ngừng sử dụng vị trí lưu trữ. GET vi-tri-luu-tru/stop-using · quyền vi-tri-luu-tru/stop · view vi-tri-luu-tru/stop-using */
    public function stopUsing(): void
    {
        // TODO (UC14): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('vi-tri-luu-tru/stop-using', [], [
            'title'      => 'Ngừng sử dụng vị trí lưu trữ',
            'module'     => 'vi-tri-luu-tru',
            'breadcrumb' => [
            ['Vị trí lưu trữ', 'vi-tri-luu-tru'],
            ['Ngừng sử dụng vị trí lưu trữ', null]
            ],
        ]);
    }
}
