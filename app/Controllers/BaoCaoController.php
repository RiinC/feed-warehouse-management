<?php
namespace App\Controllers;

/**
 * BaoCaoController — module "bao-cao".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class BaoCaoController extends Controller
{
    /** UC30 — Báo cáo hạn sử dụng theo FEFO. GET bao-cao/expiry-fefo · quyền bao-cao/view · view bao-cao/expiry-fefo */
    public function expiryFefo(): void
    {
        // TODO (UC30): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('bao-cao/expiry-fefo', [], [
            'title'      => 'Báo cáo hạn sử dụng theo FEFO',
            'module'     => 'bao-cao',
            'breadcrumb' => [
            ['Báo cáo', 'bao-cao'],
            ['Báo cáo hạn sử dụng theo FEFO', null]
            ],
        ]);
    }

    /** UC30 — Xem báo cáo tồn kho. GET bao-cao · quyền bao-cao/view · view bao-cao/index */
    public function index(): void
    {
        // TODO (UC30): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('bao-cao/index', [], [
            'title'      => 'Xem báo cáo tồn kho',
            'module'     => 'bao-cao',
            'breadcrumb' => [
            ['Báo cáo', null]
            ],
        ]);
    }

    /** UC30 — Báo cáo tồn kho. GET bao-cao/inventory · quyền bao-cao/view · view bao-cao/inventory */
    public function inventory(): void
    {
        // TODO (UC30): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('bao-cao/inventory', [], [
            'title'      => 'Báo cáo tồn kho',
            'module'     => 'bao-cao',
            'breadcrumb' => [
            ['Báo cáo', 'bao-cao'],
            ['Báo cáo tồn kho', null]
            ],
        ]);
    }
}
