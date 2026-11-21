<?php
namespace Core;

/** Dựng giao diện: nạp file trong app/Views, bọc trong layout (app/Views/layouts/<tên>.php). */
final class View
{
    /**
     * @param string      $view   Đường dẫn view không có .php, VD 'hang-hoa/create'
     * @param array       $data   Biến truyền vào view
     * @param array       $page   Cấu hình trang: title, subtitle, module, breadcrumb [[nhãn, route|null]...], actions (HTML nút), css
     * @param string|null $layout 'main' (có sidebar) | 'auth' (không có sidebar) | null (chỉ in view)
     * @param array       $shared Dữ liệu chung cho layout (menu, thông báo...) do Controller cấp
     */
    public static function render(string $view, array $data = [], array $page = [], ?string $layout = 'main', array $shared = []): void
    {
        $page += ['title' => '', 'module' => '', 'breadcrumb' => [], 'subtitle' => '', 'actions' => '', 'css' => null];
        if ($page['css'] === null && $page['module'] !== '') {
            $page['css'] = MODULE_CSS[$page['module']] ?? null;
        }
        $content = self::capture($view, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        self::load('layouts/' . $layout, $shared + ['page' => $page, 'content' => $content]);
    }

    /** In một mẫu giao diện nhỏ (app/Views/<tên>.php). */
    public static function partial(string $name, array $data = []): void
    {
        self::load($name, $data);
    }

    private static function capture(string $view, array $data): string
    {
        ob_start();
        try {
            self::load($view, $data);
        } catch (\Throwable $ex) {
            ob_end_clean();
            throw $ex;
        }
        return (string)ob_get_clean();
    }

    private static function load(string $name, array $data): void
    {
        $__file = VIEW_PATH . '/' . $name . '.php';
        if (!is_file($__file)) {
            throw new \RuntimeException('Không tìm thấy view: ' . $name);
        }
        extract($data, EXTR_SKIP);
        include $__file;
    }
}
