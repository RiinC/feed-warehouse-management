<?php
/**
 * BỐ CỤC MENU SIDEBAR THEO TỪNG VAI TRÒ.
 *
 * NGUYÊN TẮC: Sidebar chỉ điều hướng giữa các NHÓM / MODULE CHÍNH (mỗi mục trỏ tới trang quản lý chính).
 * Thao tác nghiệp vụ (Thêm, Sửa, Ngừng sử dụng, Phê duyệt, Ghi nhận, Đối chiếu, Xem chi tiết...) KHÔNG có mục
 * riêng ở đây: chúng được đặt ngay trong trang quản lý chính, hiển thị theo quyền (can(module, action)).
 * Các route thao tác chưa triển khai đang để ở khu "CHỜ TRIỂN KHAI" cuối routes/web.php (xem docs/FEATURE_BACKLOG.md).
 *
 * Mỗi phần tử:
 *   - mục đơn : ['label' => ..., 'icon' => ..., 'route' => 'hang-hoa']
 *   - nhóm    : ['label' => ..., 'icon' => ..., 'items' => [['Nhãn', 'hang-hoa'], ...]]   (bấm để mở/đóng)
 *               Nhóm mà vai trò chỉ thấy đúng 1 mục sẽ tự thành mục đơn (core/Menu.php).
 *
 * Quyền KHÔNG khai báo ở đây: mỗi route mang sẵn quyền (module + action) ở routes/web.php, ma trận ở config/permissions.php.
 * Mục nào vai trò không có quyền sẽ tự ẩn, nhóm rỗng tự ẩn. (Ẩn menu chỉ là giao diện: Router vẫn chặn 403 ở backend.)
 */

$home = ['label' => 'Tổng quan', 'icon' => 'home', 'route' => 'dashboard'];
$system = ['label' => 'Hệ thống', 'icon' => 'settings', 'items' => [
    ['Tài khoản', 'tai-khoan'],   // chỉ Quản trị viên
    ['Thông báo', 'thong-bao'],
]];
// Vai trò không phải Quản trị viên chỉ có Thông báo -> mục đơn, không cần nhóm "Hệ thống"
$notice = ['label' => 'Thông báo', 'icon' => 'bell', 'route' => 'thong-bao'];

return [
    // ---------------- Quản trị viên: toàn bộ nhóm chức năng ----------------
    ROLE_ADMIN => [
        $home,
        ['label' => 'Quản lý hàng hóa', 'icon' => 'package', 'items' => [
            ['Hàng hóa', 'hang-hoa'],
            ['Lô hàng', 'lo-hang'],
        ]],
        ['label' => 'Nhập hàng vào kho', 'icon' => 'inbox', 'items' => [
            ['Tiếp nhận hàng vào kho', 'tiep-nhan'],
            ['Nhập kho', 'nhap-kho'],
        ]],
        ['label' => 'Kiểm tra chất lượng', 'icon' => 'clipboard', 'route' => 'chat-luong'],
        ['label' => 'Quản lý kho', 'icon' => 'archive', 'items' => [
            ['Vị trí lưu trữ', 'vi-tri-luu-tru'],
            ['Gán lô hàng vào vị trí', 'gan-lo-hang'],
            ['Điều chuyển nội bộ', 'dieu-chuyen'],
            ['Tồn kho', 'ton-kho'],
        ]],
        ['label' => 'Quản lý xuất kho', 'icon' => 'upload', 'route' => 'xuat-kho/yeu-cau'],
        ['label' => 'Quản lý kiểm kê', 'icon' => 'list', 'items' => [
            ['Kiểm kê kho', 'kiem-ke'],
            ['Xử lý hàng bất thường', 'hang-bat-thuong'],
        ]],
        ['label' => 'Báo cáo', 'icon' => 'chart', 'route' => 'bao-cao'],
        $system,
    ],

    // ---------------- Quản lý kho ----------------
    ROLE_MANAGER => [
        $home,
        ['label' => 'Quản lý hàng hóa', 'icon' => 'package', 'route' => 'hang-hoa'],
        ['label' => 'Quản lý kho', 'icon' => 'archive', 'items' => [
            ['Vị trí lưu trữ', 'vi-tri-luu-tru'],
            ['Gán lô hàng vào vị trí', 'gan-lo-hang'],
            ['Điều chuyển nội bộ', 'dieu-chuyen'],
        ]],
        ['label' => 'Xuất kho', 'icon' => 'upload', 'route' => 'xuat-kho/yeu-cau'],
        ['label' => 'Kiểm kê', 'icon' => 'clipboard', 'route' => 'kiem-ke'],
        ['label' => 'Xử lý hàng bất thường', 'icon' => 'alert', 'route' => 'hang-bat-thuong'],
        ['label' => 'Báo cáo', 'icon' => 'chart', 'route' => 'bao-cao'],
        $notice,
    ],

    // ---------------- Nhân viên kho ----------------
    ROLE_WAREHOUSE => [
        $home,
        ['label' => 'Nhập hàng vào kho', 'icon' => 'inbox', 'items' => [
            ['Tiếp nhận hàng vào kho', 'tiep-nhan'],
            ['Nhập kho', 'nhap-kho'],
        ]],
        ['label' => 'Lưu kho', 'icon' => 'link', 'route' => 'gan-lo-hang'],
        ['label' => 'Điều chuyển nội bộ', 'icon' => 'repeat', 'route' => 'dieu-chuyen'],
        ['label' => 'Quản lý xuất kho', 'icon' => 'upload', 'route' => 'xuat-kho/yeu-cau'],
        ['label' => 'Tra cứu', 'icon' => 'search', 'items' => [
            ['Hàng hóa', 'hang-hoa'],
            ['Lô hàng', 'lo-hang'],
            ['Tồn kho', 'ton-kho'],
        ]],
        $notice,
    ],

    // ---------------- Nhân viên kiểm kê / QC ----------------
    ROLE_INVENTORY => [
        $home,
        ['label' => 'Kiểm tra chất lượng', 'icon' => 'clipboard', 'route' => 'chat-luong'],
        // Vai trò này không có quyền xem 'nhap-kho' (chỉ 'create' và 'history'): trang chính của họ là lịch sử nhập kho.
        ['label' => 'Nhập kho', 'icon' => 'archive', 'route' => 'nhap-kho/history'],
        $notice,
    ],

    // ---------------- Ban giám đốc ----------------
    ROLE_DIRECTOR => [
        $home,
        ['label' => 'Báo cáo', 'icon' => 'chart', 'route' => 'bao-cao'],
        $notice,
    ],
];
