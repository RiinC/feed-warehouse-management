<?php
namespace Core;

/**
 * Lớp cha của mọi Model (app/Models). Model chỉ làm việc với CSDL: truy vấn, thêm, sửa, quy tắc dữ liệu.
 * Model KHÔNG xuất HTML, KHÔNG đọc $_GET/$_POST, KHÔNG chuyển hướng.
 */
abstract class Model
{
    protected static function all(string $sql, array $params = []): array
    {
        return Database::all($sql, $params);
    }

    protected static function one(string $sql, array $params = []): ?array
    {
        return Database::one($sql, $params);
    }

    protected static function exec(string $sql, array $params = []): int
    {
        return Database::exec($sql, $params);
    }

    protected static function insertId()
    {
        return Database::insertId();
    }

    protected static function transaction(callable $fn)
    {
        return Database::transaction($fn);
    }
}
