# Hệ thống quản lý kho thức ăn chăn nuôi — bản cập nhật UC06 + QLK01

## 1. Cài đặt (XAMPP)
1. Chép thư mục `quanlykho` vào `C:\xampp\htdocs\`, bật Apache + MySQL.
2. phpMyAdmin > Import **lần lượt**: `database/QLK00.sql` rồi `database/QLK01.sql`
   (QLK02–QLK05 vẫn là database rỗng, import khi cần).
3. Mở `http://localhost/quanlykho/`.

| Tài khoản | Mật khẩu | Vai trò (QLK00) |
|---|---|---|
| `nhanvienkho` | `Nhanvien@123` | Nhân viên kho (UC06, UC10) |
| `quanlykho` | `Quanlykho@123` | Quản lý kho |
| `admin` | `Admin@123` | Quản trị viên |

> `QLK01.sql` chỉ xóa & tạo lại các bảng/view của chính nó (không đụng bảng khác), nên chạy lại được nhiều lần.

## 2. Thay đổi so với bản trước
| File | Nội dung |
|---|---|
| `database/QLK01.sql` | **Viết mới toàn bộ** theo báo cáo "Thiết kế cơ sở dữ liệu" + Từ điển lớp khái niệm |
| `nhan-vien-kho/UC06_tiep_nhan/` | `_uc06.php` (hàm dùng chung), `index.php`, `form.php`, `xac_nhan.php`, `chi_tiet.php` — code UC06 đầy đủ |
| `quan-ly-kho/UC06_tiep_nhan/` | 4 file mỏng, `require` lại code ở thư mục nhan-vien-kho (một nguồn duy nhất) |
| `includes/auth.php` | Thêm `fmt_time()`, bảng nhãn trạng thái mới, cho phép `xac_nhan.php`, `chi_tiet.php` trong `uc_url()` |
| `assets/css/style.css` | Thêm vài lớp CSS (trường lỗi, màn hình xác nhận, dòng bấm được, popup) |
| `assets/js/app.js` | Thêm xử lý popup chi tiết phiếu tiếp nhận (JavaScript thuần, nạp nội dung từ `chi_tiet.php?modal=1`) |
| Các file còn lại | Giữ nguyên bản của bạn |

## 3. QLK01 — bảng và nguồn
Tên bảng/cột/kiểu/ràng buộc/mặc định **giữ nguyên báo cáo**. SQL Server → MySQL: `NVARCHAR→VARCHAR`, `GETDATE()→CURRENT_TIMESTAMP`, `FLOAT→DOUBLE`.

| Nguồn | Bảng / view |
|---|---|
| Báo cáo (nguyên văn) | `Kho`, `KhuVuc`, `ViTriLuuTru`, `HangHoa`, `LoHang`, `DonHang`, `KeHoachSanXuat`, `PhieuGiaoNCC`, `PhieuYeuCauNhapKho`; view `vw_ThongTinHangHoa`, `vw_DanhSachLoHang`, `vw_ChiTietPhieuGiaoNCC`, `vw_DanhSachPhieuYeuCauNhapKho`, `vw_ThongTinKho`, `vw_ThongTinKhuVuc`, `vw_ThongTinViTriLuuTru`, `vw_ThongTinKeHoachSanXuat` |
| Từ điển lớp (đánh dấu `[TỪ ĐIỂN]`) | `PhieuTiepNhan` (maTiepNhan, thoiGianTiepNhan, soLuongThucNhan, trangThaiTiepNhan, ghiChu); thêm vào `LoHang`: tenLoHang, soLuongTiepNhan, donViTinh, thoiGianTiepNhan, trangThaiChatLuong, trangThaiPhanBo, ghiChu |
| **Bổ sung** (đánh dấu `[BỔ SUNG]`, tài liệu chưa có nhưng UC06 bắt buộc cần) | `NhaCungCap`; `ChiTietPhieuGiaoNCC`, `ChiTietPhieuYeuCauNhapKho` (dòng hàng của chứng từ); `ChiTietDonHang`, `ChiTietKeHoachSanXuat` (để cập nhật "số lượng đã nhận"); `ChiTietTiepNhan`; cột khóa ngoại của `PhieuTiepNhan` (maPhieuGiao, maPhieuYeuCauNhap, maNhanVien, maViTri); 2 view trợ giúp `vw_ChungTuChoTiepNhan`, `vw_DongChungTu` |

Các bảng của báo cáo **không bị thêm cột** — mọi phần bổ sung nằm ở bảng riêng. `maNhanVien` không có khóa ngoại vì `NhanVien` ở QLK00 (khác CSDL); tên người nhận được tra từ QLK00.

## 4. UC06 — luồng đặc tả ↔ code
| Bước | Trang | |
|---|---|---|
| 1–3 danh sách chứng từ chờ tiếp nhận, tìm theo Số chứng từ / Loại chứng từ / Nhà cung cấp–nguồn giao / Từ ngày–Đến ngày | `index.php` | Alt 3.1 tìm lại; Exc 3.1.1 "Không thể tiếp nhận hàng do không có chứng từ giao hàng hợp lệ" |
| 4–6 form tiếp nhận, kiểm tra | `form.php` | Alt 6.1 đánh dấu trường lỗi (thiếu `*`, số lượng ≤ 0); Exc 4.1 "Hàng hóa chưa được khai báo trong hệ thống" |
| 7–8 màn hình xác nhận | `xac_nhan.php` | Có "Quay lại chỉnh sửa" |
| 9–10 lưu + tạo lô | `_uc06.php › uc06_luu()` | Một transaction; lô hàng `trangThaiChatLuong = 'Chờ kiểm tra chất lượng'`; phiếu `trangThaiTiepNhan = 'Chờ kiểm định'`; chứng từ → "Đã tiếp nhận" / "Đã tiếp nhận một phần"; cập nhật `soLuongDaNhan` ở chứng từ, đơn hàng, kế hoạch |
| 11 thông báo | `index.php` | "Tiếp nhận hàng thành công", form được làm sạch |
| Xem lại phiếu đã tiếp nhận | `index.php` → `chi_tiet.php` | Nhấn dòng trong "Phiếu tiếp nhận gần đây" → popup: mã phiếu, ngày/giờ nhận, người nhận, vị trí lưu trữ, tình trạng, ghi chú |
| Exc 10.1 | `xac_nhan.php` | "Tiếp nhận hàng thất bại" + **Thử lại** / **Hủy** |

### Kịch bản thử (đăng nhập `nhanvienkho`)
| Kịch bản | Cách thử |
|---|---|
| Luồng chính | `PGH-2026-0001` → nhập TA001 = 200, TA002 = 100 → chọn vị trí → Tiếp tục → Xác nhận |
| Một phần | `PGH-2026-0003` → nhập số nhỏ hơn "Còn lại" |
| Alt 3.1 / Exc 3.1.1 | Gõ `ABC` vào ô Số chứng từ |
| Lọc theo loại / nguồn giao | Chọn Loại = "Phiếu yêu cầu nhập thành phẩm" → chỉ còn `PYC-2026-0002`; chọn Nguồn giao = một nhà cung cấp → chỉ còn chứng từ của nhà cung cấp đó |
| Lọc theo khoảng ngày | Từ `2026-10-20` đến `2026-10-22` → bảng chờ: ngày giao trong khoảng; bảng phiếu gần đây: ngày nhận trong khoảng. Nhập Từ ngày > Đến ngày → báo lỗi, không áp dụng khoảng ngày |
| Popup chi tiết | Nhấn dòng `PTN2026-0006` trong "Phiếu tiếp nhận gần đây" (Esc / nút Đóng / nhấn nền mờ để đóng) |
| Alt 6.1 | Bỏ trống vị trí hoặc để số lượng trống/0 |
| Exc 4.1 | Chọn `PGH-2026-0004` (có hàng TA009 "Ngừng sử dụng") |
| Exc 10.1 | Ở màn hình xác nhận, vào phpMyAdmin đổi tên bảng `LoHang` thành `LoHang_tam`, bấm Xác nhận → thấy "Tiếp nhận hàng thất bại"; đổi lại tên bảng rồi bấm Thử lại |

## 5. Giả định cần bạn xác nhận
- Bộ lọc dùng chung cho cả hai bảng; khoảng ngày lọc theo `ngayGiao` (bảng chứng từ chờ) và theo `thoiGianTiepNhan` (bảng phiếu gần đây, Đến ngày tính trọn ngày). Bảng phiếu gần đây luôn giữ tối đa 5 phiếu mới nhất; Loại chứng từ và Nguồn giao của phiếu được suy ra từ chứng từ gốc. Dropdown "Nhà cung cấp / nguồn giao" gộp `NhaCungCap.tenNCC` với `lyDoNhap` của phiếu yêu cầu nhập thành phẩm.
- Dữ liệu mẫu `QLK01.sql` có thêm `DH-2026-006`, `PGH-2026-0005..0007`, `PYC-2026-0003`, `PTN2026-0003..0006` (và lô `LH2026-0003..0007`) để thử bộ lọc/popup; kế hoạch `KH-2026-001` dòng TA004 nâng lên 80 (đã nhận 20). **Cần import lại `QLK01.sql`.**
- Số chứng từ = `maPhieuGiao` / `maPhieuYeuCauNhap`; Phiếu yêu cầu nhập chỉ lấy `loaiNhap = 'Nhập thành phẩm'`; "Nguồn giao" của loại này lấy từ `lyDoNhap` (báo cáo không có cột nguồn giao).
- Báo cáo ghi `ViTriLuuTru.trangThai` mặc định "Đang sử dụng" nhưng danh sách là "Đang hoạt động" → CHECK cho phép cả hai; UC06 chỉ chọn vị trí "Đang sử dụng".
- `hanSuDung` của `LoHang` bắt buộc (NOT NULL) nhưng UC06 không thu thập → tính = thời điểm nhận + `HangHoa.hanSuDungMacDinh` ngày (mặc định 90 ngày nếu chưa khai báo).
- Số lượng thực nhận không bị chặn trên theo "Còn lại" (đặc tả chỉ yêu cầu > 0). Mỗi dòng hàng tạo một lô.
- Chưa chuyển các `sp_` / `fn_` trong báo cáo sang MySQL (luồng UC06 chạy bằng PHP + transaction).
