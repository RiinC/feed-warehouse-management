<?php
/**
 * Ma trận phân quyền: module -> hành động -> danh sách vai trò được phép.
 * Quản trị viên (ROLE_ADMIN) luôn được phép mọi thao tác nên không cần liệt kê.
 */
return [
    'dashboard' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE, ROLE_INVENTORY, ROLE_DIRECTOR]],
    'hang-hoa' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'create' => [ROLE_MANAGER], 'edit' => [ROLE_MANAGER], 'stop' => [ROLE_MANAGER]],
    'lo-hang' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE]],
    'tiep-nhan' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'create' => [ROLE_WAREHOUSE]],
    'chat-luong' => ['view' => [ROLE_MANAGER, ROLE_INVENTORY], 'record' => [ROLE_INVENTORY], 'confirm' => [ROLE_MANAGER, ROLE_INVENTORY]],
    'nhap-kho' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'create' => [ROLE_WAREHOUSE, ROLE_INVENTORY], 'history' => [ROLE_MANAGER, ROLE_INVENTORY]],
    'vi-tri-luu-tru' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'create' => [ROLE_MANAGER], 'edit' => [ROLE_MANAGER], 'stop' => [ROLE_MANAGER]],
    'gan-lo-hang' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'assign' => [ROLE_WAREHOUSE]],
    'dieu-chuyen' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'create' => [ROLE_WAREHOUSE], 'approve' => [ROLE_MANAGER], 'execute' => [ROLE_WAREHOUSE]],
    'xuat-kho' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE], 'request' => [ROLE_WAREHOUSE], 'approve' => [ROLE_MANAGER], 'fefo' => [ROLE_WAREHOUSE], 'issue' => [ROLE_WAREHOUSE]],
    'kiem-ke' => ['view' => [ROLE_MANAGER, ROLE_INVENTORY], 'create' => [ROLE_MANAGER], 'record' => [ROLE_MANAGER, ROLE_INVENTORY], 'reconcile' => [ROLE_MANAGER], 'handle' => [ROLE_MANAGER]],
    'hang-bat-thuong' => ['view' => [ROLE_MANAGER, ROLE_INVENTORY], 'handle' => [ROLE_MANAGER]],
    'ton-kho' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE, ROLE_INVENTORY, ROLE_DIRECTOR]],
    'bao-cao' => ['view' => [ROLE_MANAGER, ROLE_DIRECTOR]],
    'thong-bao' => ['view' => [ROLE_MANAGER, ROLE_WAREHOUSE, ROLE_INVENTORY, ROLE_DIRECTOR]],
    'tai-khoan' => ['view' => []],
];

