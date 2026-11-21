# FEATURE BACKLOG — chức năng chờ triển khai (FeedWare)

Tài liệu này ghi lại các route/trang con **đã gỡ khỏi Sidebar và khỏi bảng định tuyến** trong đợt tinh gọn điều hướng. Đây **không phải** danh sách chức năng bị hủy: nghiệp vụ vẫn thuộc phạm vi dự án, chỉ là chưa được cài đặt (hiện chỉ có trang giữ chỗ).

## Nguyên tắc điều hướng mới

- **Sidebar**: chỉ liệt kê nhóm/module chính (`config/menu-layout.php`), mỗi mục mở thẳng trang quản lý chính của module.
- **Trang quản lý chính**: nơi đặt mọi thao tác (Thêm mới ở đầu bảng; Xem/Sửa/Ngừng sử dụng… ở từng dòng; Modal/Drawer hoặc trang chi tiết). Nút thao tác hiển thị theo `can(module, action)`.
- **Breadcrumb**: `Tổng quan / <Module> / <Thao tác>` (`app/Helpers/breadcrumb.php`); cấp cha chỉ là liên kết khi vai trò hiện tại có quyền mở.
- **Phân quyền** không đổi: `config/permissions.php` và quyền gắn ở từng route (`routes/web.php`) vẫn là nơi quyết định; ẩn menu chỉ là giao diện, Router vẫn trả 403 ở backend.

## Cách khôi phục một chức năng

1. Bỏ comment dòng route tương ứng trong khối **CHỜ TRIỂN KHAI** cuối `routes/web.php` (Controller và View đã có sẵn khung).
2. Cài đặt Model/Controller/View; đặt nút thao tác vào trang chính của module, bọc bằng `if (can('<module>', '<action>'))`.
3. Với thao tác POST (lưu/sửa/ngừng sử dụng): thêm `$router->post(...)` cùng module + action để Router kiểm tra quyền và CSRF.
4. Xóa dòng tương ứng khỏi tài liệu này.

## Danh sách chờ triển khai

### Quản lý hàng hóa  _(trang chính: `hang-hoa`)_

| UC | Chức năng | Route (đang comment) | Quyền | Vai trò được phép* | Controller → View (còn nguyên) | Gợi ý bố trí trong trang chính |
|---|---|---|---|---|---|---|
| UC01 | Thêm thông tin hàng hóa | `hang-hoa/create` | `hang-hoa/create` | Quản trị viên, Quản lý kho | `HangHoaController::create` → `app/Views/hang-hoa/create.php` | Nút "Thêm mới" phía trên bảng → Modal/Drawer (hoặc trang form) — chỉ hiện khi có quyền |
| UC02 | Sửa thông tin hàng hóa | `hang-hoa/edit` | `hang-hoa/edit` | Quản trị viên, Quản lý kho | `HangHoaController::edit` → `app/Views/hang-hoa/edit.php` | Nút "Sửa" ở từng dòng → Modal/Drawer |
| UC03 | Ngừng sử dụng hàng hóa | `hang-hoa/stop-using` | `hang-hoa/stop` | Quản trị viên, Quản lý kho | `HangHoaController::stopUsing` → `app/Views/hang-hoa/stop-using.php` | Nút "Ngừng sử dụng" ở từng dòng, có hộp thoại xác nhận |

### Quản lý hàng hóa › Lô hàng  _(trang chính: `lo-hang`)_

| UC | Chức năng | Route (đang comment) | Quyền | Vai trò được phép* | Controller → View (còn nguyên) | Gợi ý bố trí trong trang chính |
|---|---|---|---|---|---|---|
| UC05 | Chi tiết lô hàng | `lo-hang/detail` | `lo-hang/view` | Quản trị viên, Quản lý kho, Nhân viên kho | `LoHangController::detail` → `app/Views/lo-hang/detail.php` | Nút/liên kết "Xem chi tiết" ở từng dòng → Drawer hoặc trang chi tiết |

### Nhập hàng vào kho › Tiếp nhận  _(trang chính: `tiep-nhan`)_

| UC | Chức năng | Route (đang comment) | Quyền | Vai trò được phép* | Controller → View (còn nguyên) | Gợi ý bố trí trong trang chính |
|---|---|---|---|---|---|---|
| UC06 | Tiếp nhận hàng | `tiep-nhan/create` | `tiep-nhan/create` | Quản trị viên, Nhân viên kho | `TiepNhanController::create` → `app/Views/tiep-nhan/create.php` | Nút "Thêm mới" phía trên bảng → Modal/Drawer (hoặc trang form) — chỉ hiện khi có quyền |

### Kiểm tra chất lượng  _(trang chính: `chat-luong`)_

| UC | Chức năng | Route (đang comment) | Quyền | Vai trò được phép* | Controller → View (còn nguyên) | Gợi ý bố trí trong trang chính |
|---|---|---|---|---|---|---|
| UC08 | Ghi nhận kết quả kiểm tra chất lượng | `chat-luong/record-result` | `chat-luong/record` | Quản trị viên, QC | `ChatLuongController::recordResult` → `app/Views/chat-luong/record-result.php` | Thao tác "Ghi nhận kết quả" theo dòng/lô cần xử lý |
| UC09 | Xác nhận kết quả kiểm tra chất lượng | `chat-luong/confirm-result` | `chat-luong/confirm` | Quản trị viên, Quản lý kho, QC | `ChatLuongController::confirmResult` → `app/Views/chat-luong/confirm-result.php` | Thao tác "Xác nhận kết quả" theo dòng |

### Nhập hàng vào kho › Nhập kho  _(trang chính: `nhap-kho`)_

| UC | Chức năng | Route (đang comment) | Quyền | Vai trò được phép* | Controller → View (còn nguyên) | Gợi ý bố trí trong trang chính |
|---|---|---|---|---|---|---|
| UC10 | Lập phiếu nhập kho | `nhap-kho/create` | `nhap-kho/create` | Quản trị viên, Nhân viên kho, QC | `NhapKhoController::create` → `app/Views/nhap-kho/create.php` | Nút "Thêm mới" phía trên bảng → Modal/Drawer (hoặc trang form) — chỉ hiện khi có quyền |

### Quản lý kho › Điều chuyển nội bộ  _(trang chính: `dieu-chuyen`)_

| UC | Chức năng | Route (đang comment) | Quyền | Vai trò được phép* | Controller → View (còn nguyên) | Gợi ý bố trí trong trang chính |
|---|---|---|---|---|---|---|
| UC17 | Lập phiếu điều chuyển | `dieu-chuyen/create` | `dieu-chuyen/create` | Quản trị viên, Nhân viên kho | `DieuChuyenController::create` → `app/Views/dieu-chuyen/create.php` | Nút "Thêm mới" phía trên bảng → Modal/Drawer (hoặc trang form) — chỉ hiện khi có quyền |

### Quản lý vị trí lưu trữ  _(trang chính: `vi-tri-luu-tru`)_

| UC | Chức năng | Route (đang comment) | Quyền | Vai trò được phép* | Controller → View (còn nguyên) | Gợi ý bố trí trong trang chính |
|---|---|---|---|---|---|---|
| UC12 | Thêm thông tin vị trí lưu trữ | `vi-tri-luu-tru/create` | `vi-tri-luu-tru/create` | Quản trị viên, Quản lý kho | `ViTriLuuTruController::create` → `app/Views/vi-tri-luu-tru/create.php` | Nút "Thêm mới" phía trên bảng → Modal/Drawer (hoặc trang form) — chỉ hiện khi có quyền |
| UC13 | Sửa thông tin vị trí lưu trữ | `vi-tri-luu-tru/edit` | `vi-tri-luu-tru/edit` | Quản trị viên, Quản lý kho | `ViTriLuuTruController::edit` → `app/Views/vi-tri-luu-tru/edit.php` | Nút "Sửa" ở từng dòng → Modal/Drawer |
| UC14 | Ngừng sử dụng vị trí lưu trữ | `vi-tri-luu-tru/stop-using` | `vi-tri-luu-tru/stop` | Quản trị viên, Quản lý kho | `ViTriLuuTruController::stopUsing` → `app/Views/vi-tri-luu-tru/stop-using.php` | Nút "Ngừng sử dụng" ở từng dòng, có hộp thoại xác nhận |

### Xuất kho  _(trang chính: `xuat-kho/yeu-cau`)_

| UC | Chức năng | Route (đang comment) | Quyền | Vai trò được phép* | Controller → View (còn nguyên) | Gợi ý bố trí trong trang chính |
|---|---|---|---|---|---|---|
| UC18 | Lập yêu cầu xuất kho | `xuat-kho/yeu-cau/create` | `xuat-kho/request` | Quản trị viên, Nhân viên kho | `XuatKhoController::createRequest` → `app/Views/xuat-kho/yeu-cau/create.php` | Nút "Thêm mới" phía trên bảng → Modal/Drawer (hoặc trang form) — chỉ hiện khi có quyền |
| UC19 | Sửa yêu cầu xuất kho | `xuat-kho/yeu-cau/edit` | `xuat-kho/request` | Quản trị viên, Nhân viên kho | `XuatKhoController::editRequest` → `app/Views/xuat-kho/yeu-cau/edit.php` | Nút "Sửa" ở từng dòng → Modal/Drawer |
| UC21 | Phê duyệt yêu cầu xuất kho | `xuat-kho/yeu-cau/approve` | `xuat-kho/approve` | Quản trị viên, Quản lý kho | `XuatKhoController::approveRequest` → `app/Views/xuat-kho/yeu-cau/approve.php` | Nút "Phê duyệt" ở từng dòng yêu cầu (chỉ người có quyền approve) |
| UC22 | Đề xuất lô hàng xuất kho theo FEFO | `xuat-kho/fefo` | `xuat-kho/fefo` | Quản trị viên, Nhân viên kho | `XuatKhoController::fefo` → `app/Views/xuat-kho/fefo.php` | Khối đề xuất lô FEFO trong luồng lập phiếu xuất (không cần trang riêng) |
| UC23 | Lập phiếu xuất kho | `xuat-kho/phieu-xuat` | `xuat-kho/issue` | Quản trị viên, Nhân viên kho | `XuatKhoController::issue` → `app/Views/xuat-kho/phieu-xuat.php` | Thao tác "Lập phiếu xuất" từ yêu cầu đã duyệt (theo dòng) |

### Kiểm kê  _(trang chính: `kiem-ke`)_

| UC | Chức năng | Route (đang comment) | Quyền | Vai trò được phép* | Controller → View (còn nguyên) | Gợi ý bố trí trong trang chính |
|---|---|---|---|---|---|---|
| UC24 | Lập biên bản kiểm kê | `kiem-ke/create` | `kiem-ke/create` | Quản trị viên, Quản lý kho | `KiemKeController::create` → `app/Views/kiem-ke/create.php` | Nút "Thêm mới" phía trên bảng → Modal/Drawer (hoặc trang form) — chỉ hiện khi có quyền |
| UC25 | Ghi nhận kết quả kiểm kê | `kiem-ke/record-result` | `kiem-ke/record` | Quản trị viên, Quản lý kho, QC | `KiemKeController::recordResult` → `app/Views/kiem-ke/record-result.php` | Thao tác "Ghi nhận kết quả" theo dòng/lô cần xử lý |
| UC26 | Đối chiếu số liệu kiểm kê | `kiem-ke/reconcile` | `kiem-ke/reconcile` | Quản trị viên, Quản lý kho | `KiemKeController::reconcile` → `app/Views/kiem-ke/reconcile.php` | Bước/Tab "Đối chiếu" trong trang Kiểm kê |
| UC27 | Xử lý chênh lệch kiểm kê | `kiem-ke/handle-discrepancy` | `kiem-ke/handle` | Quản trị viên, Quản lý kho | `KiemKeController::handleDiscrepancy` → `app/Views/kiem-ke/handle-discrepancy.php` | Bước/Tab "Xử lý chênh lệch" trong trang Kiểm kê |

### Báo cáo  _(trang chính: `bao-cao`)_

| UC | Chức năng | Route (đang comment) | Quyền | Vai trò được phép* | Controller → View (còn nguyên) | Gợi ý bố trí trong trang chính |
|---|---|---|---|---|---|---|
| UC30 | Báo cáo tồn kho | `bao-cao/inventory` | `bao-cao/view` | Quản trị viên, Quản lý kho, Ban giám đốc | `BaoCaoController::inventory` → `app/Views/bao-cao/inventory.php` | Tab báo cáo "Tồn kho" trong trang Báo cáo |
| UC30 | Báo cáo hạn sử dụng theo FEFO | `bao-cao/expiry-fefo` | `bao-cao/view` | Quản trị viên, Quản lý kho, Ban giám đốc | `BaoCaoController::expiryFefo` → `app/Views/bao-cao/expiry-fefo.php` | Tab báo cáo "Hạn sử dụng (FEFO)" trong trang Báo cáo |

\* Lấy từ `config/permissions.php` tại thời điểm tái cấu trúc; Quản trị viên luôn được phép.

## Mục Sidebar đã loại bỏ (thao tác trở thành nút trong trang chính)

Thêm/Sửa/Ngừng sử dụng hàng hóa · Tra cứu/Chi tiết lô hàng (chi tiết) · Lập phiếu nhập kho · Ghi nhận/Xác nhận kết quả kiểm tra chất lượng · Thêm/Sửa/Ngừng sử dụng vị trí lưu trữ · Lập phiếu điều chuyển · Lập/Sửa/Phê duyệt yêu cầu xuất kho · Đề xuất lô FEFO · Lập phiếu xuất kho · Lập biên bản/Ghi nhận/Đối chiếu/Xử lý chênh lệch kiểm kê · Báo cáo tồn kho/Báo cáo hạn sử dụng (thành tab trong Báo cáo).

## Ghi nhận cần xác nhận thêm (chưa tự suy đoán)

1. **Vai trò QC không có quyền `nhap-kho/view`** (chỉ `create`, `history`): Sidebar của QC trỏ mục "Nhập kho" tới `nhap-kho/history`. Nếu muốn QC vào trang `nhap-kho`, cần quyết định thay đổi ma trận quyền (đang giữ nguyên).
2. **Quyền `kiem-ke` cho vai trò QC** chỉ có `view` và `record`, trong khi schema `feed_wms` không còn vai trò "Nhân viên kiểm kê" riêng; việc ai được kiểm kê cần chốt lại với nghiệp vụ.
3. **Hai trang báo cáo** (`bao-cao/inventory`, `bao-cao/expiry-fefo`) đang được coi là tab của trang Báo cáo; nếu thực tế là hai báo cáo độc lập cần menu riêng thì cần xác nhận.
4. **Xuất kho**: trang chính được chọn là `xuat-kho/yeu-cau` (danh sách yêu cầu xuất kho), vì module không có trang tổng hợp riêng.
5. **Quản lý kho** hiện không có mục Sidebar "Kiểm tra chất lượng"/"Tồn kho" dù có quyền xem (theo bố cục cũ, vẫn truy cập qua Tổng quan) — giữ nguyên.
