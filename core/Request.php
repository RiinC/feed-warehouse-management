<?php
namespace Core;

/** Đọc thông tin yêu cầu HTTP: đường dẫn route, tham số GET/POST. */
final class Request
{
    public static function method(): string
    {
        $m = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        return $m === 'HEAD' ? 'GET' : $m;
    }

    /**
     * Đường dẫn route của yêu cầu, bỏ phần gốc ứng dụng: "/qlk/public/hang-hoa/create" -> "hang-hoa/create".
     * Khi tắt rewrite (url_rewrite = false) route nằm trong tham số ?r=.
     */
    public static function path(): string
    {
        static $path = null;
        if ($path !== null) {
            return $path;
        }
        if (isset($_GET['r']) && is_string($_GET['r'])) {
            return $path = trim($_GET['r'], '/');
        }
        $uri = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        $base = base_url();
        $candidates = [$base];
        if (substr($base, -7) === '/public') {
            $candidates[] = substr($base, 0, -7);   // truy cập qua .htaccess ở thư mục gốc (không có /public trên URL)
        }
        $rest = $uri;
        foreach ($candidates as $c) {
            if ($c === '' || $uri === $c || strpos($uri, $c . '/') === 0) {
                $rest = substr($uri, strlen($c));
                break;
            }
        }
        $rest = trim($rest, '/');
        if ($rest === 'index.php' || strpos($rest, 'index.php/') === 0) {
            $rest = trim(substr($rest, 9), '/');
        }
        return $path = $rest;
    }

    /** Giá trị từ query string (GET), đã bỏ khoảng trắng thừa. */
    public static function get(string $key, $default = '')
    {
        $v = $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    /** Giá trị từ form (POST), đã bỏ khoảng trắng thừa. */
    public static function post(string $key, $default = '')
    {
        $v = $_POST[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    /** POST nếu có, nếu không thì GET. */
    public static function input(string $key, $default = '')
    {
        return array_key_exists($key, $_POST) ? self::post($key, $default) : self::get($key, $default);
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int)self::input($key, $default);
    }
}
