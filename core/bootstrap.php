<?php
/**
 * Khởi động ứng dụng: hằng số đường dẫn, tự nạp lớp (autoload), cấu hình, session, hàm dùng chung.
 * Được nạp một lần bởi public/index.php.
 */
if (defined('APP_BOOTSTRAPPED')) {
    return;
}
define('APP_BOOTSTRAPPED', true);

define('ROOT_PATH', dirname(__DIR__));
define('CORE_PATH', ROOT_PATH . '/core');
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/Views');

// Tự nạp lớp theo namespace: Core\Router -> core/Router.php ; App\Models\ThongBao -> app/Models/ThongBao.php
spl_autoload_register(function (string $class): void {
    $map = ['Core\\' => CORE_PATH . '/', 'App\\' => APP_PATH . '/'];
    foreach ($map as $prefix => $dir) {
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

$GLOBALS['APP_CONFIG'] = require ROOT_PATH . '/config/app.php';
$GLOBALS['DB_CONFIG']  = require ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/constants.php';
require_once CORE_PATH . '/helpers.php';

date_default_timezone_set(config('timezone', 'Asia/Ho_Chi_Minh'));

if (config('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

// Lỗi không bắt được: hiện chi tiết khi debug, ngược lại chỉ hiện thông báo chung
set_exception_handler(function (Throwable $ex): void {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo config('debug')
        ? '<pre>' . e(get_class($ex) . ': ' . $ex->getMessage() . "\n" . $ex->getTraceAsString()) . '</pre>'
        : 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';
});

if (session_status() === PHP_SESSION_NONE) {
    session_name(config('session_name', 'fw_session'));
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    }
    session_start();
}

// Hàm dựng giao diện dùng chung (alert, bảng dữ liệu, phân trang...)
foreach (glob(APP_PATH . '/Helpers/*.php') as $helper) {
    require_once $helper;
}
