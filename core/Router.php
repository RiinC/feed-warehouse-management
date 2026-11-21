<?php
namespace Core;

/**
 * Bộ định tuyến (front controller): ghép "phương thức + đường dẫn" với Controller::phương_thức.
 * Mỗi route mang sẵn quyền (module + action) nên Router kiểm tra đăng nhập, phân quyền và CSRF TRƯỚC khi gọi Controller.
 *
 *   $router->get('hang-hoa/create', [HangHoaController::class, 'create'], 'hang-hoa', 'create');
 *   $router->post('hang-hoa/store', [HangHoaController::class, 'store'],  'hang-hoa', 'create');
 *   $router->get('login', [AuthController::class, 'showLogin']);   // không khai báo module = công khai, không cần đăng nhập
 *
 * $options: ['json' => true] nếu route trả JSON (chưa đăng nhập thì trả 401 thay vì chuyển hướng).
 */
final class Router
{
    private static $instance;
    private $routes = ['GET' => [], 'POST' => []];

    public function __construct()
    {
        self::$instance = $this;
    }

    public static function instance(): self
    {
        return self::$instance;
    }

    public function get(string $path, $handler, ?string $module = null, string $action = 'view', array $options = []): void
    {
        $this->add('GET', $path, $handler, $module, $action, $options);
    }

    public function post(string $path, $handler, ?string $module = null, string $action = 'view', array $options = []): void
    {
        $this->add('POST', $path, $handler, $module, $action, $options);
    }

    private function add(string $method, string $path, $handler, ?string $module, string $action, array $options): void
    {
        $this->routes[$method][trim($path, '/')] = [
            'handler' => $handler, 'module' => $module, 'action' => $action, 'options' => $options,
        ];
    }

    /** Route GET có tồn tại không (đường dẫn có thể kèm ?tham_số). */
    public function has(string $path): bool
    {
        return isset($this->routes['GET'][trim((string)strtok($path, '?'), '/')]);
    }

    /** Quyền khai báo cho route GET: ['module' => ?string, 'action' => string] hoặc null nếu route không tồn tại. */
    public function permissionFor(string $path): ?array
    {
        $r = $this->routes['GET'][trim((string)strtok($path, '?'), '/')] ?? null;
        return $r ? ['module' => $r['module'], 'action' => $r['action']] : null;
    }

    /** Danh sách route (dùng để lập tài liệu / kiểm thử). */
    public function all(): array
    {
        return $this->routes;
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path = Request::path();
        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            $other = $method === 'GET' ? 'POST' : 'GET';
            $this->error(isset($this->routes[$other][$path]) ? 405 : 404);
            return;
        }

        if ($route['module'] !== null) {
            if (!Auth::check()) {
                if (!empty($route['options']['json'])) {
                    $this->json(['error' => 'unauthenticated'], 401);
                    return;
                }
                flash_set('warning', 'Vui lòng đăng nhập để tiếp tục.');
                redirect('login');
            }
            if (!Permission::can($route['module'], $route['action'])) {
                $this->error(403);
                return;
            }
        }

        if ($method === 'POST' && !csrf_verify()) {
            flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
            redirect(Auth::check() ? 'dashboard' : 'login');
        }

        $handler = $route['handler'];
        if (is_array($handler)) {
            [$class, $action] = $handler;
            (new $class())->$action();
        } else {
            $handler();
        }
    }

    private function error(int $code): void
    {
        (new \App\Controllers\ErrorController())->show($code);
    }

    private function json(array $data, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
