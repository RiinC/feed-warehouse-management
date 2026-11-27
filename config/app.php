<?php
/**
 * Cấu hình ứng dụng. Đọc bằng config('khóa') hoặc config('khóa', 'mặc định').
 */
return [
    'name'         => 'FeedWare',
    'tagline'      => 'Quản lý kho nhà máy sản xuất thức ăn chăn nuôi',
    'short_name'   => 'FW',
    'timezone'     => 'Asia/Ho_Chi_Minh',
    'base_url'     => '',            // để trống = tự nhận theo vị trí public/index.php (VD /feedware/public)
    'url_rewrite'  => true,          // true: URL đẹp /hang-hoa/create (cần .htaccess/rewrite). false: index.php?r=hang-hoa/create
    'session_name' => 'fw_session',
    'debug'        => true,          // true: hiện lỗi PHP khi phát triển; false khi chạy thật
    'per_page'     => 10,            // số dòng mỗi trang cho danh sách
    'expiry_warning_days' => 30,     // ngưỡng cảnh báo lô sắp hết hạn (UC28/UC30)
];
