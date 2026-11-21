<?php
namespace Core;

/**
 * Lớp cha của mọi Controller. Controller: nhận yêu cầu -> kiểm tra dữ liệu -> gọi Model -> chọn View.
 * Quyền và CSRF đã được Router kiểm tra trước khi vào đây.
 */
abstract class Controller
{
    /** Hiển thị view trong layout. Xem Core\View::render để biết ý nghĩa tham số. */
    protected function render(string $view, array $data = [], array $page = [], ?string $layout = 'main'): void
    {
        View::render($view, $data, $page, $layout, $layout === 'main' ? $this->sharedData() : []);
    }

    /** Dữ liệu chung cho layout (menu, thông báo...). Lớp con trong app/ ghi đè. */
    protected function sharedData(): array
    {
        return [];
    }

    protected function redirect(string $route): void
    {
        redirect($route);
    }

    /** Lưu thông báo hiển thị ở lần tải trang kế tiếp: success | error | warning | info. */
    protected function flash(string $type, string $message): void
    {
        flash_set($type, $message);
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function input(string $key, $default = '')
    {
        return Request::input($key, $default);
    }
}
