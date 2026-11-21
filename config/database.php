<?php
/**
 * Kết nối MySQL/MariaDB (XAMPP mặc định). CSDL feed_wms: database/schema.sql + seed_data.sql (+ thong-bao.sql).
 * Truy vấn đi qua Core\Database, chỉ được gọi từ các Model (app/Models).
 */
return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'feed_wms',
    'user'     => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
];
