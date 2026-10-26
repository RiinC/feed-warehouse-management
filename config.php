<?php
/**
 * Cấu hình hệ thống & kết nối database (mysqli)
 * Sửa DB_USER / DB_PASS nếu MySQL của bạn có tài khoản khác (XAMPP mặc định: root, không mật khẩu).
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_DUNG_CHUNG', 'QLK00');
define('DB_UC06', 'QLK01');
define('DB_UC10', 'QLK02');
define('DB_UC17', 'QLK03');
define('DB_UC23', 'QLK04');
define('DB_UC24', 'QLK05');

define('APP_NAME', 'Hệ thống quản lý kho thức ăn chăn nuôi');
define('ROOT_PATH', str_replace('\\', '/', __DIR__));

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Tự nhận đường dẫn gốc để link hoạt động dù đặt thư mục ở đâu trong htdocs.
$docRoot = rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$base = '';
if ($docRoot !== '' && stripos(ROOT_PATH, $docRoot) === 0) {
    $base = substr(ROOT_PATH, strlen($docRoot));
}
define('BASE_URL', rtrim($base, '/'));

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(string $database = DB_DUNG_CHUNG): mysqli
{
    static $connections = [];
    $allowedDatabases = [
        DB_DUNG_CHUNG,
        DB_UC06,
        DB_UC10,
        DB_UC17,
        DB_UC23,
        DB_UC24,
    ];
    if (!in_array($database, $allowedDatabases, true)) {
        throw new InvalidArgumentException('Cơ sở dữ liệu không được cấu hình: ' . $database);
    }

    if (!isset($connections[$database])) {
        try {
            $connections[$database] = new mysqli(DB_HOST, DB_USER, DB_PASS, $database);
            $connections[$database]->set_charset('utf8mb4');
        } catch (Throwable $ex) {
            http_response_code(500);
            die('<p style="font-family:sans-serif;padding:24px">Không kết nối được cơ sở dữ liệu <b>' . htmlspecialchars($database, ENT_QUOTES, 'UTF-8') .
                '</b>. Hãy bật MySQL trong XAMPP, import file SQL tương ứng trong thư mục <code>database</code> và kiểm tra lại <code>config.php</code>.</p>');
        }
    }
    return $connections[$database];
}

/** Chạy câu lệnh prepared. $types ví dụ 'sii', $params là mảng giá trị tương ứng. */
function db_exec(string $sql, string $types = '', array $params = [], string $database = DB_DUNG_CHUNG): mysqli_stmt
{
    $st = db($database)->prepare($sql);
    if ($types !== '') {
        $st->bind_param($types, ...$params);
    }
    $st->execute();
    return $st;
}

function db_all(string $sql, string $types = '', array $params = [], string $database = DB_DUNG_CHUNG): array
{
    $st = db_exec($sql, $types, $params, $database);
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $rows;
}

function db_one(string $sql, string $types = '', array $params = [], string $database = DB_DUNG_CHUNG): ?array
{
    $rows = db_all($sql, $types, $params, $database);
    return $rows[0] ?? null;
}
