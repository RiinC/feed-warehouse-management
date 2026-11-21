<?php
namespace App\Controllers;

use Core\Auth;

/** Trang lỗi 403 / 404 / 405 (do Router gọi). */
class ErrorController extends Controller
{
    private const MESSAGES = [
        403 => ['Không có quyền truy cập', 'Bạn không có quyền sử dụng chức năng này.', 'Vui lòng liên hệ quản trị viên nếu bạn cần được cấp quyền.'],
        404 => ['Không tìm thấy trang', 'Trang bạn tìm không tồn tại.', 'Đường dẫn có thể đã thay đổi hoặc bị nhập sai.'],
        405 => ['Phương thức không được hỗ trợ', 'Yêu cầu này không được phép.', 'Hãy quay lại trang trước và thao tác lại.'],
    ];

    public function show(int $code): void
    {
        http_response_code($code);
        [$title, $heading, $hint] = self::MESSAGES[$code] ?? self::MESSAGES[404];
        $logged = Auth::check();
        $this->render('errors/error', [
            'heading' => $heading, 'hint' => $hint,
            'backRoute' => $logged ? 'dashboard' : 'login',
            'backLabel' => $logged ? 'Về trang Tổng quan' : 'Về trang đăng nhập',
        ], [
            'title'      => $title,
            'breadcrumb' => [[$title, null]],
        ], $logged ? 'main' : 'auth');
    }
}
