<?php
/**
 * Cấu hình ứng dụng. Đọc bằng config('khóa') hoặc config('khóa', 'mặc định').
 */
return [
    'name'         => 'FeedWare',
    'tagline'      => 'Quản lý kho nhà máy sản xuất thức ăn chăn nuôi',
    'short_name'   => 'FW',
    'timezone'     => 'Asia/Ho_Chi_Minh',
    // Subdomain hosting dùng URL gốc; localhost vẫn tự nhận thư mục dự án.
    'base_url'     => preg_match('/^warehouse\.leonovar\.io\.vn(?::[0-9]+)?$/i', $_SERVER['HTTP_HOST'] ?? '') ? '/' : '',
    'url_rewrite'  => true,          // true: URL đẹp /hang-hoa/create (cần .htaccess/rewrite). false: index.php?r=hang-hoa/create
    'session_name' => 'fw_session',
    'debug'        => true,          // true: hiện lỗi PHP khi phát triển; false khi chạy thật
    'per_page'     => 10,            // số dòng mỗi trang cho danh sách
];
