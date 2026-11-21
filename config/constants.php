<?php
/**
 * Hằng số dùng chung: vai trò, nhãn, ánh xạ vai trò từ CSDL, CSS riêng của từng module.
 * Thêm vai trò mới: khai báo ở đây + thêm vai trò vào các mục cần quyền trong config/permissions.php
 * + thêm bố cục menu của vai trò ở config/menu-layout.php (menu và Tổng quan tự lọc theo quyền).
 */

// Khóa vai trò dùng trong code
const ROLE_ADMIN     = 'admin';       // Quản trị viên
const ROLE_MANAGER   = 'manager';     // Quản lý kho
const ROLE_WAREHOUSE = 'warehouse';   // Nhân viên kho
const ROLE_INVENTORY = 'inventory';   // Nhân viên kiểm kê / kiểm tra chất lượng (QC)
const ROLE_DIRECTOR  = 'director';    // Ban giám đốc

// Nhãn hiển thị
const ROLE_LABELS = [
    ROLE_ADMIN     => 'Quản trị viên',
    ROLE_MANAGER   => 'Quản lý kho',
    ROLE_WAREHOUSE => 'Nhân viên kho',
    ROLE_INVENTORY => 'Nhân viên kiểm kê / QC',
    ROLE_DIRECTOR  => 'Ban giám đốc',
];

// Giá trị cột NhanVien.vaiTro trong CSDL -> khóa vai trò
const ROLE_FROM_DB = [
    'Quản trị viên'     => ROLE_ADMIN,
    'Quản lý kho'       => ROLE_MANAGER,
    'Nhân viên kho'     => ROLE_WAREHOUSE,
    'Nhân viên kiểm kê' => ROLE_INVENTORY,
    'Nhân viên QC'      => ROLE_INVENTORY,
    'Ban giám đốc'      => ROLE_DIRECTOR,
];

// File CSS riêng của từng module (public/assets/css/pages/<tên>.css)
const MODULE_CSS = [
    'dashboard'       => 'dashboard',
    'hang-hoa'        => 'products',
    'lo-hang'         => 'products',
    'tiep-nhan'       => 'receiving',
    'chat-luong'      => 'receiving',
    'nhap-kho'        => 'receiving',
    'vi-tri-luu-tru'  => 'storage',
    'gan-lo-hang'     => 'storage',
    'dieu-chuyen'     => 'transfer',
    'xuat-kho'        => 'outbound',
    'kiem-ke'         => 'inventory',
    'hang-bat-thuong' => 'inventory',
    'ton-kho'         => 'inventory',
    'bao-cao'         => 'inventory',
];
