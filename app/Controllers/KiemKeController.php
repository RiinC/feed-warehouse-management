<?php
namespace App\Controllers;

/**
 * KiemKeController — module "kiem-ke".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class KiemKeController extends Controller
{
    /** UC24 — Lập biên bản kiểm kê. GET kiem-ke/create · quyền kiem-ke/create · view kiem-ke/create */
    public function create(): void
    {
        // TODO (UC24): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('kiem-ke/create', [], [
            'title'      => 'Lập biên bản kiểm kê',
            'module'     => 'kiem-ke',
            'breadcrumb' => [
            ['Kiểm kê kho', 'kiem-ke'],
            ['Lập biên bản kiểm kê', null]
            ],
        ]);
    }

    /** UC27 — Xử lý chênh lệch kiểm kê. GET kiem-ke/handle-discrepancy · quyền kiem-ke/handle · view kiem-ke/handle-discrepancy */
    public function handleDiscrepancy(): void
    {
        // TODO (UC27): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('kiem-ke/handle-discrepancy', [], [
            'title'      => 'Xử lý chênh lệch kiểm kê',
            'module'     => 'kiem-ke',
            'breadcrumb' => [
            ['Kiểm kê kho', 'kiem-ke'],
            ['Xử lý chênh lệch kiểm kê', null]
            ],
        ]);
    }

    /** UC24 — Kiểm kê kho. GET kiem-ke · quyền kiem-ke/view · view kiem-ke/index */
    public function index(): void
    {
        // TODO (UC24): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('kiem-ke/index', [], [
            'title'      => 'Kiểm kê kho',
            'module'     => 'kiem-ke',
            'breadcrumb' => [
            ['Kiểm kê kho', null]
            ],
            'actions'    => action_buttons([
                ['kiem-ke', 'create', 'Lập biên bản kiểm kê', 'kiem-ke/create', 'btn btn-primary'],
                ['kiem-ke', 'record', 'Ghi nhận kết quả', 'kiem-ke/record-result', 'btn'],
                ['kiem-ke', 'reconcile', 'Đối chiếu số liệu', 'kiem-ke/reconcile', 'btn'],
                ['kiem-ke', 'handle', 'Xử lý chênh lệch', 'kiem-ke/handle-discrepancy', 'btn']
            ]),
        ]);
    }

    /** UC26 — Đối chiếu số liệu kiểm kê. GET kiem-ke/reconcile · quyền kiem-ke/reconcile · view kiem-ke/reconcile */
    public function reconcile(): void
    {
        // TODO (UC26): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('kiem-ke/reconcile', [], [
            'title'      => 'Đối chiếu số liệu kiểm kê',
            'module'     => 'kiem-ke',
            'breadcrumb' => [
            ['Kiểm kê kho', 'kiem-ke'],
            ['Đối chiếu số liệu kiểm kê', null]
            ],
        ]);
    }

    /** UC25 — Ghi nhận kết quả kiểm kê. GET kiem-ke/record-result · quyền kiem-ke/record · view kiem-ke/record-result */
    public function recordResult(): void
    {
        // TODO (UC25): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('kiem-ke/record-result', [], [
            'title'      => 'Ghi nhận kết quả kiểm kê',
            'module'     => 'kiem-ke',
            'breadcrumb' => [
            ['Kiểm kê kho', 'kiem-ke'],
            ['Ghi nhận kết quả kiểm kê', null]
            ],
        ]);
    }
}
