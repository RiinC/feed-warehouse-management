<?php
namespace App\Controllers;

/**
 * ChatLuongController — module "chat-luong".
 * Mỗi phương thức = một trang (GET). Route + quyền khai báo ở routes/web.php.
 * Khi cài đặt: lấy dữ liệu từ Model (app/Models), truyền vào view (app/Views); xử lý form (POST) thêm phương thức store/update/... và route POST.
 */
class ChatLuongController extends Controller
{
    /** UC09 — Xác nhận kết quả kiểm tra chất lượng. GET chat-luong/confirm-result · quyền chat-luong/confirm · view chat-luong/confirm-result */
    public function confirmResult(): void
    {
        // TODO (UC09): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('chat-luong/confirm-result', [], [
            'title'      => 'Xác nhận kết quả kiểm tra chất lượng',
            'module'     => 'chat-luong',
            'breadcrumb' => [
            ['Kiểm tra chất lượng', 'chat-luong'],
            ['Xác nhận kết quả kiểm tra chất lượng', null]
            ],
        ]);
    }

    /** UC07 — Tra cứu lô hàng cần kiểm tra. GET chat-luong · quyền chat-luong/view · view chat-luong/index */
    public function index(): void
    {
        // TODO (UC07): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('chat-luong/index', [], [
            'title'      => 'Tra cứu lô hàng cần kiểm tra',
            'module'     => 'chat-luong',
            'breadcrumb' => [
            ['Kiểm tra chất lượng', null]
            ],
            'actions'    => action_buttons([
                ['chat-luong', 'record', 'Ghi nhận kết quả kiểm tra', 'chat-luong/record-result', 'btn btn-primary'],
                ['chat-luong', 'confirm', 'Xác nhận kết quả', 'chat-luong/confirm-result', 'btn']
            ]),
        ]);
    }

    /** UC08 — Ghi nhận kết quả kiểm tra chất lượng. GET chat-luong/record-result · quyền chat-luong/record · view chat-luong/record-result */
    public function recordResult(): void
    {
        // TODO (UC08): lấy dữ liệu từ Model, kiểm tra tham số (id...) rồi truyền vào view.
        $this->render('chat-luong/record-result', [], [
            'title'      => 'Ghi nhận kết quả kiểm tra chất lượng',
            'module'     => 'chat-luong',
            'breadcrumb' => [
            ['Kiểm tra chất lượng', 'chat-luong'],
            ['Ghi nhận kết quả kiểm tra chất lượng', null]
            ],
        ]);
    }
}
