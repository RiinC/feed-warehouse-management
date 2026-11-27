<?php
namespace Core;

/** Kết nối MySQL/MariaDB bằng mysqli + prepared statements. Chỉ Model được gọi lớp này. */
final class Database
{
    public $servername;
    public $username;
    public $password;
    public $dbname;
    public $port;
    public $conn;

    private static $instance = null;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';
        $this->servername = $config['host'];
        $this->username = $config['user'];
        $this->password = $config['password'];
        $this->dbname = $config['database'];
        $this->port = (int)($config['port'] ?? 3306);
        $this->connect();
    }

    public function connect(): \mysqli
    {
        if ($this->conn instanceof \mysqli) {
            return $this->conn;
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->conn = new \mysqli(
                $this->servername,
                $this->username,
                $this->password,
                $this->dbname,
                $this->port
            );
            $this->conn->set_charset('utf8mb4');
        } catch (\mysqli_sql_exception $ex) {
            http_response_code(500);
            exit('Không kết nối được cơ sở dữ liệu. Hãy import database/schema.sql và kiểm tra config/database.php.');
        }
        return $this->conn;
    }

    public static function connection(): \mysqli
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->conn;
    }

    public static function run(string $sql, array $params = []): \mysqli_stmt
    {
        $stmt = self::connection()->prepare($sql);
        if ($params) {
            $types = '';
            foreach ($params as $p) {
                $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
            }
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }

    /** Mọi dòng kết quả (mảng kết hợp). */
    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::run($sql, $params);
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        return self::all($sql, $params)[0] ?? null;
    }

    /** INSERT/UPDATE/DELETE: trả về số dòng bị ảnh hưởng. */
    public static function exec(string $sql, array $params = []): int
    {
        $stmt = self::run($sql, $params);
        $n = $stmt->affected_rows;
        $stmt->close();
        return $n;
    }

    public static function insertId()
    {
        return self::connection()->insert_id;
    }

    /** Chạy $fn trong một giao dịch; có lỗi thì rollback và ném lại ngoại lệ. */
    public static function transaction(callable $fn)
    {
        $db = self::connection();
        $db->begin_transaction();
        try {
            $result = $fn();
            $db->commit();
            return $result;
        } catch (\Throwable $ex) {
            $db->rollback();
            throw $ex;
        }
    }
}
