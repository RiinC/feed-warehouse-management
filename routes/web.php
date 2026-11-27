<?php
/**
 * BẢNG ĐỊNH TUYẾN — nguồn duy nhất cho: đường dẫn, Controller xử lý, quyền cần có.
 *
 *   $router->get(đường_dẫn, [Controller::class, 'phương_thức'], module, action);
 *   $router->post(...)   // xử lý form; Router tự kiểm tra CSRF cho mọi POST
 *
 * Route có module + action: bắt buộc đăng nhập và phải có quyền (config/permissions.php), nếu không -> 403.
 * Route không khai báo module: công khai (đăng nhập). Biến $router do public/index.php cung cấp.
 */

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ThongBaoController;
use App\Controllers\BaoCaoController;
use App\Controllers\ChatLuongController;
use App\Controllers\DieuChuyenController;
use App\Controllers\GanLoHangController;
use App\Controllers\HangBatThuongController;
use App\Controllers\HangHoaController;
use App\Controllers\KiemKeController;
use App\Controllers\LoHangController;
use App\Controllers\NhapKhoController;
use App\Controllers\TaiKhoanController;
use App\Controllers\TiepNhanController;
use App\Controllers\TonKhoController;
use App\Controllers\ViTriLuuTruController;
use App\Controllers\XuatKhoController;

/** @var Core\Router $router */

// ---- Công khai ----
$router->get('', function () {
    redirect(is_logged_in() ? 'dashboard' : 'login');
});
$router->get('login',   [AuthController::class, 'showLogin']);
$router->post('login',  [AuthController::class, 'login']);

// ---- Đăng xuất, Tổng quan, Thông báo (mọi vai trò đã đăng nhập) ----
$router->post('logout', [AuthController::class, 'logout'], 'thong-bao', 'view');
$router->get('dashboard', [DashboardController::class, 'index'], 'dashboard', 'view');

$router->get('thong-bao',          [ThongBaoController::class, 'index'],   'thong-bao', 'view');
$router->get('thong-bao/open',     [ThongBaoController::class, 'open'],    'thong-bao', 'view');
$router->get('thong-bao/poll',     [ThongBaoController::class, 'poll'],    'thong-bao', 'view', ['json' => true]);
$router->post('thong-bao/read-all', [ThongBaoController::class, 'readAll'], 'thong-bao', 'view');

// ---- bao-cao ----
$router->get('bao-cao', [BaoCaoController::class, 'index'], 'bao-cao', 'view');

// ---- chat-luong ----
$router->get('chat-luong', [ChatLuongController::class, 'index'], 'chat-luong', 'view');

// ---- dieu-chuyen ----
$router->get('dieu-chuyen', [DieuChuyenController::class, 'index'], 'dieu-chuyen', 'view');
$router->get('dieu-chuyen/create', [DieuChuyenController::class, 'create'], 'dieu-chuyen', 'create');
$router->post('dieu-chuyen/create/lot', [DieuChuyenController::class, 'createLot'], 'dieu-chuyen', 'create');
$router->get('dieu-chuyen/create/source', [DieuChuyenController::class, 'createSource'], 'dieu-chuyen', 'create');
$router->post('dieu-chuyen/create/source', [DieuChuyenController::class, 'createSourceSave'], 'dieu-chuyen', 'create');
$router->get('dieu-chuyen/create/destination', [DieuChuyenController::class, 'createDestination'], 'dieu-chuyen', 'create');
$router->post('dieu-chuyen/create/destination', [DieuChuyenController::class, 'createDestinationSave'], 'dieu-chuyen', 'create');
$router->get('dieu-chuyen/create/info', [DieuChuyenController::class, 'createInfo'], 'dieu-chuyen', 'create');
$router->post('dieu-chuyen/create/info', [DieuChuyenController::class, 'createInfoSave'], 'dieu-chuyen', 'create');
$router->get('dieu-chuyen/create/confirm', [DieuChuyenController::class, 'createConfirm'], 'dieu-chuyen', 'create');
$router->post('dieu-chuyen/create/reset', [DieuChuyenController::class, 'createReset'], 'dieu-chuyen', 'create');
$router->post('dieu-chuyen/store', [DieuChuyenController::class, 'store'], 'dieu-chuyen', 'create');
$router->get('dieu-chuyen/detail', [DieuChuyenController::class, 'detail'], 'dieu-chuyen', 'view');
$router->get('dieu-chuyen/approve', [DieuChuyenController::class, 'approve'], 'dieu-chuyen', 'approve');
$router->post('dieu-chuyen/approve/save', [DieuChuyenController::class, 'approveSave'], 'dieu-chuyen', 'approve');
$router->post('dieu-chuyen/execute', [DieuChuyenController::class, 'execute'], 'dieu-chuyen', 'execute');
$router->post('dieu-chuyen/cancel', [DieuChuyenController::class, 'cancel'], 'dieu-chuyen', 'create');

// ---- gan-lo-hang ----
$router->get('gan-lo-hang', [GanLoHangController::class, 'index'], 'gan-lo-hang', 'view');
$router->post('gan-lo-hang/assign', [GanLoHangController::class, 'assign'], 'gan-lo-hang', 'assign');

// ---- hang-bat-thuong ----
$router->get('hang-bat-thuong', [HangBatThuongController::class, 'index'], 'hang-bat-thuong', 'view');
$router->get('hang-bat-thuong/create', [HangBatThuongController::class, 'create'], 'hang-bat-thuong', 'record');
$router->get('hang-bat-thuong/detail', [HangBatThuongController::class, 'detail'], 'hang-bat-thuong', 'view');
$router->get('hang-bat-thuong/print', [HangBatThuongController::class, 'printVoucher'], 'hang-bat-thuong', 'view');
$router->post('hang-bat-thuong/store', [HangBatThuongController::class, 'store'], 'hang-bat-thuong', 'record');
$router->post('hang-bat-thuong/detect', [HangBatThuongController::class, 'detect'], 'hang-bat-thuong', 'scan');
$router->post('hang-bat-thuong/propose', [HangBatThuongController::class, 'propose'], 'hang-bat-thuong', 'propose');
$router->post('hang-bat-thuong/cancel', [HangBatThuongController::class, 'cancelProposal'], 'hang-bat-thuong', 'propose');
$router->post('hang-bat-thuong/approve', [HangBatThuongController::class, 'approveProposal'], 'hang-bat-thuong', 'handle');
$router->post('hang-bat-thuong/reject', [HangBatThuongController::class, 'rejectProposal'], 'hang-bat-thuong', 'handle');
$router->post('hang-bat-thuong/execute', [HangBatThuongController::class, 'executeProposal'], 'hang-bat-thuong', 'execute');

// ---- hang-hoa ----
$router->get('hang-hoa', [HangHoaController::class, 'index'], 'hang-hoa', 'view');

// ---- kiem-ke ----
$router->get('kiem-ke', [KiemKeController::class, 'index'], 'kiem-ke', 'view');
$router->get('kiem-ke/create', [KiemKeController::class, 'create'], 'kiem-ke', 'create');
$router->post('kiem-ke/store', [KiemKeController::class, 'store'], 'kiem-ke', 'create');
$router->get('kiem-ke/edit', [KiemKeController::class, 'edit'], 'kiem-ke', 'create');
$router->post('kiem-ke/update', [KiemKeController::class, 'update'], 'kiem-ke', 'create');
$router->get('kiem-ke/detail', [KiemKeController::class, 'detail'], 'kiem-ke', 'view');
$router->get('kiem-ke/print', [KiemKeController::class, 'printVoucher'], 'kiem-ke', 'view');
$router->get('kiem-ke/record-result', [KiemKeController::class, 'recordResult'], 'kiem-ke', 'record');
$router->post('kiem-ke/save-result', [KiemKeController::class, 'saveResult'], 'kiem-ke', 'record');
$router->post('kiem-ke/submit', [KiemKeController::class, 'submitForApproval'], 'kiem-ke', 'record');
$router->post('kiem-ke/return', [KiemKeController::class, 'returnResult'], 'kiem-ke', 'handle');
$router->get('kiem-ke/reconcile', [KiemKeController::class, 'reconcile'], 'kiem-ke', 'reconcile');
$router->post('kiem-ke/save-reconcile', [KiemKeController::class, 'saveReconcile'], 'kiem-ke', 'reconcile');
$router->get('kiem-ke/handle-discrepancy', [KiemKeController::class, 'handleDiscrepancy'], 'kiem-ke', 'handle');
$router->post('kiem-ke/propose', [KiemKeController::class, 'propose'], 'kiem-ke', 'handle');
$router->post('kiem-ke/approve', [KiemKeController::class, 'approveProposal'], 'kiem-ke', 'handle');
$router->post('kiem-ke/reject', [KiemKeController::class, 'rejectProposal'], 'kiem-ke', 'handle');
$router->post('kiem-ke/execute', [KiemKeController::class, 'executeProposal'], 'kiem-ke', 'handle');
$router->post('kiem-ke/close', [KiemKeController::class, 'close'], 'kiem-ke', 'handle');
$router->post('kiem-ke/cancel', [KiemKeController::class, 'cancel'], 'kiem-ke', 'handle');

// ---- lo-hang ----
$router->get('lo-hang', [LoHangController::class, 'index'], 'lo-hang', 'view');

// ---- nhap-kho ----
$router->get('nhap-kho', [NhapKhoController::class, 'index'], 'nhap-kho', 'view');
$router->get('nhap-kho/history', [NhapKhoController::class, 'history'], 'nhap-kho', 'history');

// ---- tai-khoan ----
$router->get('tai-khoan', [TaiKhoanController::class, 'index'], 'tai-khoan', 'view');

// ---- tiep-nhan ----
$router->get('tiep-nhan', [TiepNhanController::class, 'index'], 'tiep-nhan', 'view');

// ---- ton-kho ----
$router->get('ton-kho', [TonKhoController::class, 'index'], 'ton-kho', 'view');
$router->get('ton-kho/detail', [TonKhoController::class, 'detail'], 'ton-kho', 'view');
$router->get('ton-kho/print', [TonKhoController::class, 'printVoucher'], 'ton-kho', 'view');

// ---- vi-tri-luu-tru ----
$router->get('vi-tri-luu-tru', [ViTriLuuTruController::class, 'index'], 'vi-tri-luu-tru', 'view');
$router->get('vi-tri-luu-tru/detail', [ViTriLuuTruController::class, 'detail'], 'vi-tri-luu-tru', 'view');
$router->get('vi-tri-luu-tru/create', [ViTriLuuTruController::class, 'create'], 'vi-tri-luu-tru', 'create');
$router->post('vi-tri-luu-tru/store', [ViTriLuuTruController::class, 'store'], 'vi-tri-luu-tru', 'create');
$router->get('vi-tri-luu-tru/edit', [ViTriLuuTruController::class, 'edit'], 'vi-tri-luu-tru', 'edit');
$router->post('vi-tri-luu-tru/update', [ViTriLuuTruController::class, 'update'], 'vi-tri-luu-tru', 'edit');
$router->get('vi-tri-luu-tru/stop-using', [ViTriLuuTruController::class, 'stopUsing'], 'vi-tri-luu-tru', 'stop');
$router->post('vi-tri-luu-tru/stop', [ViTriLuuTruController::class, 'stop'], 'vi-tri-luu-tru', 'stop');

// ---- xuat-kho ----
$router->get('xuat-kho/yeu-cau', [XuatKhoController::class, 'requests'], 'xuat-kho', 'view');

// =====================================================================================
// CHỜ TRIỂN KHAI — các route thao tác/trang con (đã gỡ khỏi Sidebar, chưa đăng ký)
// -------------------------------------------------------------------------------------
// Quy ước: mỗi chức năng sau này được tích hợp VÀO TRANG QUẢN LÝ CHÍNH của module (nút Thêm mới
// ở đầu bảng; Xem/Sửa/Ngừng sử dụng... ở từng dòng; Modal/Drawer hoặc trang chi tiết) và hiển thị theo
// quyền bằng can(module, action). Controller (phương thức) và View của các mục dưới đây VẪN CÒN NGUYÊN
// trong app/Controllers, app/Views — chỉ cần bỏ comment dòng route khi triển khai.
// Danh sách đầy đủ + mô tả: docs/FEATURE_BACKLOG.md
// =====================================================================================

// ------------------------------------------------------------------
// CHỜ TRIỂN KHAI - QUẢN LÝ HÀNG HÓA  (trang chính: hang-hoa)
// ------------------------------------------------------------------
// TODO UC01 — Thêm thông tin hàng hóa  [quyền: hang-hoa/create]
// $router->get('hang-hoa/create', [HangHoaController::class, 'create'], 'hang-hoa', 'create');
// TODO UC02 — Sửa thông tin hàng hóa  [quyền: hang-hoa/edit]
// $router->get('hang-hoa/edit', [HangHoaController::class, 'edit'], 'hang-hoa', 'edit');
// TODO UC03 — Ngừng sử dụng hàng hóa  [quyền: hang-hoa/stop]
// $router->get('hang-hoa/stop-using', [HangHoaController::class, 'stopUsing'], 'hang-hoa', 'stop');

// ------------------------------------------------------------------
// CHỜ TRIỂN KHAI - LÔ HÀNG  (trang chính: lo-hang)
// ------------------------------------------------------------------
// TODO UC05 — Chi tiết lô hàng  [quyền: lo-hang/view]
// $router->get('lo-hang/detail', [LoHangController::class, 'detail'], 'lo-hang', 'view');

// ------------------------------------------------------------------
// CHỜ TRIỂN KHAI - TIẾP NHẬN HÀNG VÀO KHO  (trang chính: tiep-nhan)
// ------------------------------------------------------------------
// TODO UC06 — Tiếp nhận hàng  [quyền: tiep-nhan/create]
// $router->get('tiep-nhan/create', [TiepNhanController::class, 'create'], 'tiep-nhan', 'create');

// ------------------------------------------------------------------
// CHỜ TRIỂN KHAI - KIỂM TRA CHẤT LƯỢNG  (trang chính: chat-luong)
// ------------------------------------------------------------------
// TODO UC08 — Ghi nhận kết quả kiểm tra chất lượng  [quyền: chat-luong/record]
// $router->get('chat-luong/record-result', [ChatLuongController::class, 'recordResult'], 'chat-luong', 'record');
// TODO UC09 — Xác nhận kết quả kiểm tra chất lượng  [quyền: chat-luong/confirm]
// $router->get('chat-luong/confirm-result', [ChatLuongController::class, 'confirmResult'], 'chat-luong', 'confirm');

// ------------------------------------------------------------------
// CHỜ TRIỂN KHAI - NHẬP KHO  (trang chính: nhap-kho)
// ------------------------------------------------------------------
// TODO UC10 — Lập phiếu nhập kho  [quyền: nhap-kho/create]
// $router->get('nhap-kho/create', [NhapKhoController::class, 'create'], 'nhap-kho', 'create');

// ------------------------------------------------------------------
// CHỜ TRIỂN KHAI - ĐIỀU CHUYỂN NỘI BỘ  (trang chính: dieu-chuyen)
// ------------------------------------------------------------------
// TODO UC17 — Lập phiếu điều chuyển  [quyền: dieu-chuyen/create]
// $router->get('dieu-chuyen/create', [DieuChuyenController::class, 'create'], 'dieu-chuyen', 'create');

// ------------------------------------------------------------------
// CHỜ TRIỂN KHAI - QUẢN LÝ VỊ TRÍ LƯU TRỮ  (trang chính: vi-tri-luu-tru)
// ------------------------------------------------------------------
// TODO UC12 — Thêm thông tin vị trí lưu trữ  [quyền: vi-tri-luu-tru/create]
// $router->get('vi-tri-luu-tru/create', [ViTriLuuTruController::class, 'create'], 'vi-tri-luu-tru', 'create');
// TODO UC13 — Sửa thông tin vị trí lưu trữ  [quyền: vi-tri-luu-tru/edit]
// $router->get('vi-tri-luu-tru/edit', [ViTriLuuTruController::class, 'edit'], 'vi-tri-luu-tru', 'edit');
// TODO UC14 — Ngừng sử dụng vị trí lưu trữ  [quyền: vi-tri-luu-tru/stop]
// $router->get('vi-tri-luu-tru/stop-using', [ViTriLuuTruController::class, 'stopUsing'], 'vi-tri-luu-tru', 'stop');

// ------------------------------------------------------------------
// CHỜ TRIỂN KHAI - XUẤT KHO  (trang chính: xuat-kho/yeu-cau)
// ------------------------------------------------------------------
// TODO UC18 — Lập yêu cầu xuất kho  [quyền: xuat-kho/request]
// $router->get('xuat-kho/yeu-cau/create', [XuatKhoController::class, 'createRequest'], 'xuat-kho', 'request');
// TODO UC19 — Sửa yêu cầu xuất kho  [quyền: xuat-kho/request]
// $router->get('xuat-kho/yeu-cau/edit', [XuatKhoController::class, 'editRequest'], 'xuat-kho', 'request');
// TODO UC21 — Phê duyệt yêu cầu xuất kho  [quyền: xuat-kho/approve]
// $router->get('xuat-kho/yeu-cau/approve', [XuatKhoController::class, 'approveRequest'], 'xuat-kho', 'approve');
// TODO UC22 — Đề xuất lô hàng xuất kho theo FEFO  [quyền: xuat-kho/fefo]
// $router->get('xuat-kho/fefo', [XuatKhoController::class, 'fefo'], 'xuat-kho', 'fefo');
// TODO UC23 — Lập phiếu xuất kho  [quyền: xuat-kho/issue]
// $router->get('xuat-kho/phieu-xuat', [XuatKhoController::class, 'issue'], 'xuat-kho', 'issue');

// Báo cáo, kiểm kê, hàng bất thường, tồn kho đã triển khai (UC24–UC30) — route khai báo ở đầu tệp.
