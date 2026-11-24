<?php
/**
 * DANH MỤC CHỨC NĂNG của hệ thống (dùng cho trang Tổng quan và làm nguồn nhãn/icon cho menu).
 *
 * CHỈ khai báo các TRANG QUẢN LÝ CHÍNH của module (lối tắt ở trang Tổng quan). Thao tác Thêm/Sửa/Ngừng sử dụng/
 * Phê duyệt... nằm trong trang chính nên không có lối tắt riêng; các mục đó xem khối "CHỜ TRIỂN KHAI" ở cuối file.
 *
 * Mỗi mục: ['label', 'icon', 'route', 'show']
 *   - route : đường dẫn route (routes/web.php). QUYỀN lấy từ chính route đó (module + action), không khai báo lại ở đây.
 *   - show = 'menu' (mặc định): hiện ở trang Tổng quan; 'dash': chỉ hiện ở trang Tổng quan; 'none': không hiện
 *             (trang cần chọn một bản ghi trước: sửa, ngừng sử dụng, chi tiết...).
 * Bố cục sidebar theo vai trò: config/menu-layout.php.
 */
return [
    [
        'title' => 'Tổng quan',
        'items' => [
            ['label' => 'Tổng quan', 'icon' => 'home', 'route' => 'dashboard'],
        ],
    ],
    [
        'title' => 'Hàng hóa & nhập kho',
        'items' => [
            ['label' => 'Hàng hóa', 'icon' => 'package', 'route' => 'hang-hoa'],
            ['label' => 'Lô hàng', 'icon' => 'layers', 'route' => 'lo-hang'],
            ['label' => 'Tiếp nhận hàng', 'icon' => 'inbox', 'route' => 'tiep-nhan'],
            ['label' => 'Kiểm tra chất lượng', 'icon' => 'clipboard', 'route' => 'chat-luong'],
            ['label' => 'Nhập kho', 'icon' => 'download', 'route' => 'nhap-kho'],
            ['label' => 'Lịch sử nhập kho', 'icon' => 'list', 'route' => 'nhap-kho/history'],
        ],
    ],
    [
        'title' => 'Lưu trữ & điều chuyển',
        'items' => [
            ['label' => 'Vị trí lưu trữ', 'icon' => 'pin', 'route' => 'vi-tri-luu-tru'],
            ['label' => 'Gán lô hàng', 'icon' => 'link', 'route' => 'gan-lo-hang'],
            ['label' => 'Điều chuyển nội bộ', 'icon' => 'repeat', 'route' => 'dieu-chuyen'],
        ],
    ],
    [
        'title' => 'Xuất kho',
        'items' => [
            ['label' => 'Yêu cầu xuất kho', 'icon' => 'upload', 'route' => 'xuat-kho/yeu-cau'],
        ],
    ],
    [
        'title' => 'Kiểm kê & báo cáo',
        'items' => [
            ['label' => 'Kiểm kê kho', 'icon' => 'list', 'route' => 'kiem-ke'],
            ['label' => 'Hàng bất thường', 'icon' => 'alert', 'route' => 'hang-bat-thuong'],
            ['label' => 'Tồn kho', 'icon' => 'archive', 'route' => 'ton-kho'],
            ['label' => 'Báo cáo', 'icon' => 'chart', 'route' => 'bao-cao'],
        ],
    ],
    [
        'title' => 'Hệ thống',
        'items' => [
            ['label' => 'Tài khoản', 'icon' => 'users', 'route' => 'tai-khoan'],
            ['label' => 'Thông báo', 'icon' => 'bell', 'route' => 'thong-bao', 'show' => 'none'],
        ],
    ],
];

/*
=====================================================================================
CHỜ TRIỂN KHAI — lối tắt/mục danh mục của các route thao tác (route tương ứng đang comment ở routes/web.php)
Khi triển khai: bỏ comment route, rồi đưa chức năng vào trang quản lý chính (nút Thêm mới / thao tác theo dòng)
thay vì thêm lại mục Sidebar. Chi tiết: docs/FEATURE_BACKLOG.md
=====================================================================================
  ['label' => 'Ghi nhận kết quả kiểm tra', 'icon' => 'clipboard', 'route' => 'chat-luong/record-result', 'show' => 'dash'],
  ['label' => 'Xác nhận kết quả kiểm tra', 'icon' => 'clipboard', 'route' => 'chat-luong/confirm-result', 'show' => 'dash'],
  ['label' => 'Lập phiếu nhập kho', 'icon' => 'plus', 'route' => 'nhap-kho/create', 'show' => 'dash'],
  ['label' => 'Thêm vị trí lưu trữ', 'icon' => 'plus', 'route' => 'vi-tri-luu-tru/create', 'show' => 'dash'],
  ['label' => 'Sửa vị trí lưu trữ', 'icon' => 'pin', 'route' => 'vi-tri-luu-tru/edit', 'show' => 'none'],
  ['label' => 'Ngừng sử dụng vị trí', 'icon' => 'pin', 'route' => 'vi-tri-luu-tru/stop-using', 'show' => 'none'],
  ['label' => 'Lập phiếu điều chuyển', 'icon' => 'plus', 'route' => 'dieu-chuyen/create', 'show' => 'dash'],
  ['label' => 'Lập yêu cầu xuất kho', 'icon' => 'plus', 'route' => 'xuat-kho/yeu-cau/create', 'show' => 'dash'],
  ['label' => 'Sửa yêu cầu xuất kho', 'icon' => 'upload', 'route' => 'xuat-kho/yeu-cau/edit', 'show' => 'none'],
  ['label' => 'Phê duyệt yêu cầu xuất kho', 'icon' => 'clipboard', 'route' => 'xuat-kho/yeu-cau/approve', 'show' => 'dash'],
  ['label' => 'Đề xuất lô FEFO', 'icon' => 'list', 'route' => 'xuat-kho/fefo'],
  ['label' => 'Phiếu xuất kho', 'icon' => 'clipboard', 'route' => 'xuat-kho/phieu-xuat'],
  ['label' => 'Lập biên bản kiểm kê', 'icon' => 'plus', 'route' => 'kiem-ke/create', 'show' => 'dash'],
  ['label' => 'Ghi nhận kết quả kiểm kê', 'icon' => 'clipboard', 'route' => 'kiem-ke/record-result', 'show' => 'dash'],
  ['label' => 'Đối chiếu số liệu kiểm kê', 'icon' => 'repeat', 'route' => 'kiem-ke/reconcile', 'show' => 'dash'],
  ['label' => 'Xử lý chênh lệch kiểm kê', 'icon' => 'alert', 'route' => 'kiem-ke/handle-discrepancy', 'show' => 'dash'],
  ['label' => 'Báo cáo tồn kho', 'icon' => 'chart', 'route' => 'bao-cao/inventory', 'show' => 'none'],
  ['label' => 'Báo cáo hạn sử dụng (FEFO)', 'icon' => 'chart', 'route' => 'bao-cao/expiry-fefo', 'show' => 'none'],
*/
