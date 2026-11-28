<?php
/**
 * Ma trận phân quyền: module -> hành động -> danh sách vai trò được phép.
 * Quản trị viên (ROLE_ADMIN) luôn được phép mọi thao tác nên không cần liệt kê.
 *
 * Hành động dùng chung: view (xem/tra cứu), create, edit, stop (ngừng sử dụng), approve (phê duyệt)...
 * Mỗi route ở routes/web.php khai báo module + action cần có; Router kiểm tra trước khi gọi Controller.
 * Trong View: if (can('hang-hoa', 'edit')) { ... }
 * Thêm vai trò mới: khai báo ở config/constants.php, thêm vào các hành động ở đây và bố cục menu ở config/menu-layout.php.
 */
return [
    'dashboard' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE, ROLE_INVENTORY, ROLE_DIRECTOR]],
    'hang-hoa' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'create' => [ROLE_MANAGER], 'edit' => [ROLE_MANAGER], 'stop' => [ROLE_MANAGER]],
    'lo-hang' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE]],
    'tiep-nhan' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'create' => [ROLE_WAREHOUSE]],
    // UC07, UC08, UC09 - Kiểm tra chất lượng
    'chat-luong' => [
        'view' => [ROLE_MANAGER, ROLE_INVENTORY],
        'record' => [ROLE_INVENTORY],
        'confirm' => [ROLE_MANAGER]
    ],
    // UC10, UC11 - Nhập kho
    'nhap-kho' => [
        'view' => [ROLE_MANAGER, ROLE_WAREHOUSE],
        'create' => [ROLE_WAREHOUSE, ROLE_INVENTORY],
        'history' => [ROLE_MANAGER]
    ],
    'vi-tri-luu-tru' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'create' => [ROLE_MANAGER], 'edit' => [ROLE_MANAGER], 'stop' => [ROLE_MANAGER]],
    'gan-lo-hang' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'assign' => [ROLE_WAREHOUSE]],
    'dieu-chuyen' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'create' => [ROLE_WAREHOUSE]],
    'xuat-kho' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'request' => [ROLE_WAREHOUSE], 'approve' => [ROLE_MANAGER], 'fefo' => [ROLE_WAREHOUSE], 'issue' => [ROLE_WAREHOUSE]],
    'kiem-ke' => ['view' => [ROLE_MANAGER, ROLE_INVENTORY], 'create' => [ROLE_MANAGER], 'record' => [ROLE_MANAGER, ROLE_INVENTORY], 'reconcile' => [ROLE_MANAGER], 'handle' => [ROLE_MANAGER]],
    'hang-bat-thuong' => ['view' => [ROLE_MANAGER, ROLE_INVENTORY], 'handle' => [ROLE_MANAGER]],
    'ton-kho' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE, ROLE_INVENTORY, ROLE_DIRECTOR]],
    // UC30 - Xem, gửi và tiếp nhận báo cáo tồn kho
    'bao-cao' => [
        'view' => [ROLE_MANAGER, ROLE_DIRECTOR],
        'inventory' => [ROLE_DIRECTOR, ROLE_MANAGER],
        'send' => [ROLE_MANAGER],
        'history' => [ROLE_MANAGER, ROLE_DIRECTOR],
        'read' => [ROLE_DIRECTOR],
        'delete' => [ROLE_MANAGER]
    ],
    'thong-bao' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE, ROLE_INVENTORY, ROLE_DIRECTOR]],
    'tai-khoan' => ['view' => []],
];
