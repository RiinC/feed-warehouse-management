<?php
/**
 * FRONT CONTROLLER — mọi yêu cầu web đi qua file này (xem public/.htaccess).
 * Chỉ thư mục public/ được web truy cập; core/, app/, config/, database/ nằm ngoài tầm với của trình duyệt.
 */
require dirname(__DIR__) . '/core/bootstrap.php';

$router = new Core\Router();
require ROOT_PATH . '/routes/web.php';
$router->dispatch();
