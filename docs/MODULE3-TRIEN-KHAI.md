# MODULE 3 - LƯU TRỮ VÀ ĐIỀU CHUYỂN: BÁO CÁO TRIỂN KHAI

Tài liệu này ghi lại thiết kế, thay đổi CSDL, danh sách file, luồng nghiệp vụ và kết quả kiểm thử của Module 3 (UC12-UC17). Các tài liệu cũ trong `docs/` được giữ nguyên.

## 1. Phạm vi đã hoàn thành

- UC12 - Thêm vị trí lưu trữ (Quản lý kho).
- UC13 - Sửa vị trí lưu trữ (Quản lý kho; chỉ sửa tên và sức chứa).
- UC14 - Ngừng sử dụng vị trí lưu trữ (xóa mềm, trạng thái Đã khóa).
- UC15 - Tra cứu vị trí lưu trữ (tìm kiếm, lọc, phân trang).
- UC16 - Gán lô hàng vào vị trí lưu trữ (Nhân viên kho).
- UC17 - Điều chuyển nội bộ có phê duyệt (Nhân viên kho lập, Quản lý kho duyệt).

## 2. Quyết định thiết kế quan trọng

### 2.1. UC16 - sổ phân bổ LuuKho

- Bảng mới `LuuKho` ghi nhận từng lần gán lô vào vị trí. Mỗi dòng là một sự kiện phân bổ (lô, vị trí, số lượng, người thực hiện, thời điểm, ghi chú).
- Thao tác gán KHÔNG ghi `TonKho`, do đó tổng tồn kho của lô không thay đổi. Đây là yêu cầu bắt buộc của nghiệp vụ đã thống nhất.
- Cách xác định số lượng có thể phân bổ:
  - `nhap` = tổng `ChiTietPhieuNhapKho.soLuongChuanKg` của lô thuộc phiếu `Đang xếp kệ` hoặc `Đã nhập kho`.
  - `daPhanBo` = tổng chi tiết nhập của phiếu `Đã nhập kho` + tổng `LuuKho.soLuongPhanBo` của lô.
  - `conLai = nhap - daPhanBo`.
- Chỉ tính chi tiết của phiếu `Đã nhập kho` là phân bổ thực tế: dòng của phiếu `Đang xếp kệ` còn là kế hoạch. Nhờ vậy lô `LO2611060001` (phiếu `PNK2611060001` đang xếp kệ, 100 kg) hiện đúng là lô cần gán.
- Điều kiện lô được hiển thị: QC `Đã đạt chuẩn`, chưa hết hạn, vòng đời `Đã tạo` / `Đang lưu kho` / `Sắp hết hạn`, còn `conLai > 0`, thuộc kho của người dùng.
- Sức chứa vị trí tính bằng `TonKho.soLuongTon + LuuKho.soLuongPhanBo`.
- Khóa dòng: `SELECT ... FOR UPDATE` trên `LoHang` trước, sau đó trên `ViTriLuuTru`; mọi lần gán cùng vị trí bị tuần tự hóa.
- UC16 không cập nhật `PhieuNhapKho`, `ChiTietPhieuNhapKho`, `LoHang` hay `TonKho`.

### 2.2. UC17 - phê duyệt

- Bổ sung quyền `dieu-chuyen/approve` (Quản lý kho) và `dieu-chuyen/execute` (Nhân viên kho).
- Bổ sung cột `maNhanVienDuyet`, `ngayDuyet`, `lyDoTuChoi` và các CHECK vào `PhieuDieuChuyen`.
- Trạng thái mới: `Chờ duyệt`, `Đã duyệt`, `Đã từ chối`; giữ trạng thái cũ `Chờ điều chuyển`, `Đang thực hiện`, `Đã hoàn tất`, `Đã hủy` để tương thích dữ liệu mẫu.
- Không cho tự duyệt ở 3 lớp: giao diện, backend, CSDL (`ck_PhieuDieuChuyen_13`).
- Quản lý chỉ duyệt/xem phiếu thuộc kho mình (`NhanVien.maKho`); quản lý kho khác nhận HTTP 403.
- Phiếu dữ liệu cũ vẫn được thực hiện theo yêu cầu demo; hệ thống vẫn kiểm tra lại tồn nguồn và sức chứa đích.
- Thực hiện trong một transaction: khóa phiếu, khóa 2 vị trí theo thứ tự mã tăng dần, khóa tồn nguồn, kiểm tra lại, trừ nguồn, cộng đích, ghi 2 bút toán, cập nhật phiếu.

### 2.3. Phạm vi kho và trạng thái vị trí

- Quản trị viên: không giới hạn kho.
- Quản lý kho và Nhân viên kho: chỉ dữ liệu thuộc `NhanVien.maKho` của chính mình.
- Vị trí mới mặc định `Đang để trống`; ngừng sử dụng = `Đã khóa` (xóa mềm).
- Trạng thái vị trí được làm mới theo `TonKho + LuuKho`; không tự đổi `Đang bảo trì` / `Đã khóa`.

## 3. Thay đổi CSDL

Toàn bộ bảng/cột đã được gộp vào `schema.sql` và `seed_data.sql` cho bản cài mới, kèm thêm 1 file nâng cấp cho máy đã có sẵn CSDL cũ:

```sql
-- 1) Cài mới (chưa có DB feed_wms):
database/schema.sql     -- đã có sẵn bảng LuuKho + 3 cột duyệt của PhieuDieuChuyen
database/seed_data.sql  -- dữ liệu mẫu (đã đồng bộ sức chứa vị trí kệ)
database/thong-bao.sql  -- bảng ThongBao + thông báo mẫu

-- 2) Đã có sẵn DB feed_wms cũ (pull code về, không muốn xóa DB cũ):
database/cap-nhat-db-cu.sql
```

- Bảng `LuuKho` (InnoDB, khóa ngoại tới `LoHang`, `ViTriLuuTru`, `PhieuNhapKho`, `NhanVien`) nằm trong `schema.sql`.
- `PhieuDieuChuyen` có thêm 3 cột: `maNhanVienDuyet`, `ngayDuyet`, `lyDoTuChoi`.
- Mở rộng CHECK trạng thái; thêm CHECK ngày duyệt, bắt buộc người duyệt/ngày duyệt, bắt buộc lý do từ chối, chặn tự duyệt; thêm khóa ngoại người duyệt.
- File `database/cap-nhat-db-cu.sql` kiểm tra `INFORMATION_SCHEMA` trước khi thêm bảng/cột/index/FK/CHECK và nâng sức chứa 7 vị trí kệ, an toàn khi chạy lại nhiều lần.


## 4. Route và phân quyền

| Route | Method | Quyền |
|---|---|---|
| `vi-tri-luu-tru` | GET | vi-tri-luu-tru/view |
| `vi-tri-luu-tru/create` | GET | vi-tri-luu-tru/create |
| `vi-tri-luu-tru/store` | POST | vi-tri-luu-tru/create |
| `vi-tri-luu-tru/edit` | GET | vi-tri-luu-tru/edit |
| `vi-tri-luu-tru/update` | POST | vi-tri-luu-tru/edit |
| `vi-tri-luu-tru/stop-using` | GET | vi-tri-luu-tru/stop |
| `vi-tri-luu-tru/stop` | POST | vi-tri-luu-tru/stop |
| `gan-lo-hang` | GET | gan-lo-hang/view |
| `gan-lo-hang/assign` | POST | gan-lo-hang/assign |
| `dieu-chuyen` | GET | dieu-chuyen/view |
| `dieu-chuyen/create` | GET | dieu-chuyen/create |
| `dieu-chuyen/store` | POST | dieu-chuyen/create |
| `dieu-chuyen/detail` | GET | dieu-chuyen/view |
| `dieu-chuyen/approve` | GET | dieu-chuyen/approve |
| `dieu-chuyen/approve/save` | POST | dieu-chuyen/approve |
| `dieu-chuyen/execute` | POST | dieu-chuyen/execute |
| `dieu-chuyen/cancel` | POST | dieu-chuyen/create |

- Quản lý kho: xem/tạo/sửa/ngừng vị trí; xem gán lô; duyệt điều chuyển trong kho mình.
- Nhân viên kho: chỉ tra cứu vị trí; gán lô; lập/thực hiện/hủy phiếu điều chuyển của mình.
- Quản trị viên: toàn quyền; không được tự duyệt phiếu do chính mình lập.
- QC và Ban giám đốc: không có quyền Module 3 (HTTP 403).

## 5. File đã tạo và chỉnh sửa

### Tạo mới

- `app/Models/Kho.php`, `KhuVuc.php`, `ViTriLuuTru.php`, `LoHang.php`, `LuuKho.php`, `TonKho.php`, `LichSuBienDongTonKho.php`, `PhieuDieuChuyen.php`
- `app/Helpers/status-badge.php`
- `app/Views/dieu-chuyen/detail.php`, `app/Views/dieu-chuyen/approve.php`
- `database/schema.sql` (bổ sung `LuuKho` + cột duyệt `PhieuDieuChuyen`), `database/seed_data.sql` (đồng bộ sức chứa 7 vị trí kệ)
- (Đã xóa sau kiểm thử) `tests/module3_test.php`, `tests/uc16_setup.php`, `tests/uc16_worker.php`
- `docs/MODULE3-TRIEN-KHAI.md` (tài liệu này)

### Chỉnh sửa

- `app/Controllers/ViTriLuuTruController.php`, `GanLoHangController.php`, `DieuChuyenController.php`
- `app/Controllers/Controller.php` (thêm `warehouseScope()`, `forbidden()`)
- `app/Models/NhanVien.php` (thêm `find`, `activeManagerIdsByKho`)
- `app/Views/vi-tri-luu-tru/{index,create,edit,stop-using}.php`
- `app/Views/gan-lo-hang/index.php`
- `app/Views/dieu-chuyen/{index,create}.php`
- `routes/web.php`, `config/permissions.php`
- `public/assets/css/pages/storage.css`, `transfer.css`
- `database/schema.sql` (additive)

## 6. Luồng nghiệp vụ thực tế

### UC12-UC15

1. Quản lý kho mở `vi-tri-luu-tru`, bấm Thêm; chọn khu vực, nhập tên, tọa độ, sức chứa.
2. Hệ thống kiểm tra tọa độ, khu vực thuộc kho, sức chứa; sinh mã `VT + dãy + kệ + tầng + ô + 4 số`.
3. Vị trí mới ở `Đang để trống`. Sửa chỉ cho đổi tên/sức chứa, không giảm dưới lượng đang chiếm chỗ.
4. Ngừng sử dụng chỉ thành công khi vị trí không còn tồn, không còn phân bổ và không có giao dịch nhập/xuất/điều chuyển/tiếp nhận chưa xong.
5. Danh sách hiển thị sức chứa, đang lưu trữ (tồn + phân bổ), còn lại, trạng thái; có tìm kiếm/lọc/phân trang.

### UC16

1. Nhân viên kho mở `gan-lo-hang`, thấy danh sách lô đã nhập kho nhưng còn phải gán.
2. Chọn lô, chọn vị trí đích còn sức chứa, nhập số lượng.
3. Hệ thống khóa lô, kiểm tra QC/hạn/trạng thái/số còn lại; khóa vị trí, kiểm tra trạng thái/kho/khu vực/sức chứa.
4. Ghi một dòng `LuuKho`, cập nhật trạng thái vị trí. Không ghi `TonKho`.
5. Lô có thể được gán nhiều lần sang nhiều vị trí cho đến khi đủ số lượng; sau đó biến khỏi danh sách.

### UC17

1. Nhân viên kho lập phiếu: chọn lô tại vị trí nguồn, vị trí đích, số lượng, lý do. Phiếu ở `Chờ duyệt`.
2. Hệ thống gửi thông báo cho Quản lý kho đúng kho nguồn.
3. Quản lý kho xem danh sách chờ duyệt/chi tiết, kiểm tra tồn và sức chứa, duyệt hoặc từ chối (bắt buộc lý do).
4. Nhân viên kho thực hiện phiếu đã duyệt; hệ thống kiểm tra lại tồn/sức chứa, cập nhật hai vị trí trong transaction, ghi 2 bút toán lịch sử, đóng phiếu `Đã hoàn tất`.
5. Danh sách có lọc theo thời gian, trạng thái, mã lô, vị trí nguồn/đích, người lập.


## 7. Kết quả kiểm thử

### 7.1. Kiểm thử nghiệp vụ tự động

Đã kiểm thử trong quá trình phát triển: **52 ca nghiệp vụ, kiểm thử đồng thời và end-to-end**. Thư mục `tests/` và CSDL clone `feed_wms_test` đã được xóa theo yêu cầu bàn giao; ứng dụng chạy trực tiếp trên `feed_wms`.

Bao gồm:

- UC12: dữ liệu hợp lệ, sinh mã, trạng thái mặc định, trùng tọa độ, kệ/tầng sai, sức chứa <= 0, khu vực khác kho.
- UC13: sửa tên/sức chứa, chặn giảm dưới lượng đang chứa, chặn vị trí không tồn tại.
- UC14: ngừng vị trí trống, chặn lần 2, phát hiện và chặn vị trí còn hàng.
- UC15: lọc theo kho, phạm vi kho, tìm theo mã, lọc trạng thái ngừng sử dụng.
- UC16: có lô chờ gán, chặn vượt sức chứa, chặn vị trí khóa, gán 60+30+10, chặn vượt phần còn lại, tổng phân bổ 100, tổng tồn kho không đổi, chặn lô hết hạn.
- UC17: tạo phiếu, thông báo đúng quản lý kho, chặn tự duyệt, chặn quản lý kho khác, duyệt đúng người/thời điểm, chặn duyệt hai lần, chặn nhân viên kho khác thực hiện, trừ/cộng đúng, ghi 2 bút toán, chặn thực hiện hai lần, chặn thực hiện phiếu chưa duyệt, chặn từ chối thiếu lý do, từ chối kèm lý do, chặn duyệt phiếu đã từ chối, chặn vượt tồn nguồn, chặn vượt sức chứa đích, thực hiện phiếu dữ liệu cũ, CHECK CSDL chặn tự duyệt, rollback không để lại thay đổi.

### 7.2. Kiểm thử đồng thời UC16

Hai tiến trình PHP cùng gán 40 kg vào một vị trí sức chứa 50 kg: kết quả một tiến trình OK, một tiến trình bị chặn (Vị trí chỉ còn 10 kg); tổng phân bổ cuối cùng là 40 kg. Không vượt sức chứa.

### 7.3. Kiểm thử end-to-end qua Router

- POST thêm vị trí với CSRF hợp lệ: tạo mã `VT010302080098`, trạng thái `Đang để trống`.
- POST sai CSRF: không tạo bản ghi.
- POST gán lô: cập nhật đúng; POST vượt sức chứa: không thay đổi.
- POST tạo phiếu điều chuyển: tạo `PDC2611238002`, gửi thông báo cho `NV000004`.
- POST duyệt: `Đã duyệt`, người duyệt `NV000004`, có ngày duyệt.
- POST thực hiện: `Đã hoàn tất`, nguồn 7400 -> 7350, đích 0 -> 50, 2 bút toán lịch sử.
- Quản lý kho khác xem chi tiết phiếu: HTTP 403.
- Quyền route: Nhân viên kho không vào `dieu-chuyen/approve` (403); Quản lý kho không vào `dieu-chuyen/create` (403); QC không vào `vi-tri-luu-tru` (403); Giám đốc không vào `gan-lo-hang` (403).

## 8. Giả định và giới hạn

1. Chỉ hỗ trợ điều chuyển trong cùng một kho.
2. Số lượng tính theo kg; dữ liệu mẫu có vài lô lệch đơn vị gốc, hệ thống dùng kg và `soLuongChuanKg` khi xác định phân bổ.
3. `LuuKho` là sổ phân bổ vị trí, không phải tồn vật lý; các module khác vẫn dùng `TonKho` làm tồn thực tế. Cần thống nhất với module Nhập kho khi module đó được triển khai để tránh ghi trùng.
4. UC16 không cập nhật trạng thái phiếu nhập; trách nhiệm đóng phiếu thuộc module Nhập kho.
5. Phiếu điều chuyển dữ liệu cũ được phép thực hiện theo yêu cầu demo dù không có người duyệt.
6. Chưa có bảng log phê duyệt riêng; thông tin duyệt lưu trên phiếu.
7. Danh sách lịch sử gán lô hiển thị 15 dòng gần nhất, không phân trang riêng.
8. Mã vị trí được sinh tự động; không cho sửa mã/tọa độ/khu vực sau khi tạo.

## 9. Hướng dẫn chạy

1. Sao lưu CSDL.
2. Chạy 2 file migration trong `database/` (hoặc import `database/schema.sql` nếu cài mới).
3. Đăng nhập Quản lý kho để thêm/sửa/ngừng vị trí; đăng nhập Nhân viên kho để gán lô và lập phiếu điều chuyển; Quản lý kho duyệt.
4. Không còn thư mục `tests/` hay CSDL test trong dự án; kiểm thử trực tiếp trên `feed_wms` bằng tài khoản mẫu.

## 10. Kết luận

Module 3 đã được triển khai đầy đủ Model - Controller - Route - View - phân quyền - thông báo, có transaction và khóa dòng cho các thao tác tồn kho, đã kiểm thử 52 ca nghiệp vụ, 1 ca đồng thời và các luồng end-to-end chính. Các điểm cần thống nhất tiếp tục được ghi ở mục 8.


## 11. Cập nhật giao diện và điều chuyển một phần

### 11.1. Bộ lọc phụ thuộc Kho - Khu vực

- `KhuVuc::options()` chỉ trả tên khu vực; `KhuVuc::optionsWithKho()` trả kèm `data-kho` để lọc.
- `render_search_form()` hỗ trợ `attrs` cho select và option dạng `['label' => ..., 'attrs' => [...]]`.
- `public/assets/js/dependent-select.js` lọc option của select con theo `data-dependent-target`, `data-dependent-key` (`kho` hoặc `khuvuc`).
- Áp dụng cho bộ lọc vị trí, form thêm vị trí, chọn vị trí nguồn/đích trong wizard.

### 11.2. UC16 - chọn lô, cố định kho, chọn khu vực rồi vị trí

- Bảng lô chờ gán có radio, highlight dòng, hiển thị thông tin lô ở các ô riêng.
- Kho lấy từ phiếu nhập của lô và cố định; khu vực/vị trí chỉ thuộc kho đó.
- Option vị trí chỉ hiển thị mã + tên; sức chứa còn lại hiển thị ở dòng gợi ý, không nhồi vào option.
- Ô số lượng tự điền bằng “Còn phải gán”; người dùng có thể giảm hoặc gán đủ.
- JS: `public/assets/js/storage.js`.

### 11.3. UC17 - wizard 5 bước

| Bước | Route GET | Route POST |
|---|---|---|
| 1. Chọn lô | `dieu-chuyen/create` | `dieu-chuyen/create/lot` |
| 2. Vị trí nguồn | `dieu-chuyen/create/source` | `dieu-chuyen/create/source` |
| 3. Vị trí đích | `dieu-chuyen/create/destination` | `dieu-chuyen/create/destination` |
| 4. Nhập thông tin | `dieu-chuyen/create/info` | `dieu-chuyen/create/info` |
| 5. Xác nhận | `dieu-chuyen/create/confirm` | `dieu-chuyen/store` |
| Hủy | — | `dieu-chuyen/create/reset` |

- Draft lưu trong `$_SESSION['dc_draft']`, chỉ ghi DB ở bước 5.
- Thanh tiến trình: `app/Views/components/transfer-steps.php`; nút “Hủy và quay lại”.
- Bước 1 dùng bảng radio (không dùng option dài); bước 2-3 chọn khu vực trước, sau đó vị trí; bước 4 hiển thị thông tin lô và số lượng mặc định; bước 5 xem lại theo bảng Thuộc tính/Giá trị.

### 11.4. Điều chuyển một phần (cộng dồn)

- `soLuongThucChuyen` là số đã chuyển lũy kế.
- Lũy kế nhỏ hơn dự kiến: trạng thái `Đang thực hiện`, có thể thực hiện tiếp.
- Lũy kế bằng dự kiến: `Đã hoàn tất`, ghi `ngayHoanTat`.
- Mỗi lần thực hiện đều kiểm tra lại tồn nguồn/sức chứa đích trong transaction và ghi 2 bút toán.
- Chi tiết phiếu hiển thị “Đã chuyển X / Y kg”, “Còn lại”, ô thực hiện mặc định bằng phần còn lại.

### 11.5. Phê duyệt và Xem vị trí

- Trang chi tiết phiếu có 2 nút ngang hàng: Phê duyệt, Từ chối. Modal nhập lý do chỉ hiện khi bấm Từ chối (`public/assets/js/approval.js`).
- Thông tin phiếu tách thành các ô riêng (Người lập, Thời gian lập, Kho, Khu vực, Vị trí...), không nối bằng dấu gạch.
- Thêm route `vi-tri-luu-tru/detail` (quyền view) và nút “Xem” cho cả Quản lý kho và Nhân viên kho; trang hiển thị tồn thực tế, phân bổ và lịch sử biến động.

### 11.6. Admin tạo khu vực mới

- Trong form thêm vị trí, Admin có thêm option “Khác (tạo khu vực mới)”.
- Nhập tên khu vực và loại khu vực; `KhuVuc::createInKho()` sinh mã theo loại (`KV01`...`KV05`) và lưu vào kho đang chọn.
- Không tạo model mới; dùng `KhuVuc`. Nhân viên/quản lý sau đó chọn được khu vực mới trong select.
- Chặn trùng tên khu vực trong cùng kho; chỉ Admin được gửi `__new__` (backend kiểm tra).

### 11.7. Độ rộng bảng và trường bắt buộc

- CSS: `nowrap` cho mã/ngày/số lượng/trạng thái; `wrap` cho tên/vị trí dài; `min-width` bảng 900-920px.
- Nhãn bắt buộc có `<span class=req>*</span>`.
- Placeholder gợi ý tên vị trí, tọa độ, sức chứa.

### 11.8. Dọn dẹp kiểm thử

- Đã xóa thư mục `tests/` và CSDL `feed_wms_test`.
- Dữ liệu test phát sinh trong `feed_wms` đã được dọn; các bảng nghiệp vụ trở về đúng dữ liệu seed (ViTri 98, PhieuDieuChuyen 18, TonKho 103, LuuKho 0).

### 11.9. Lưu ý triển khai

- Mã ứng dụng dùng `ROOT_PATH`/`dirname(__DIR__)`, không hard-code `C:/xampp`.
- Cần cấu hình `config/database.php`, tên CSDL, `base_url`/`url_rewrite`, extension `mysqli`/`mbstring`, quyền ghi session.
- Migration UC17 dùng cú pháp MariaDB `DROP CONSTRAINT`; với MySQL 8 cần dùng biến thể `DROP CHECK` nếu chạy migration trên CSDL đã tồn tại.


## 12. Cập nhật UX bảng và chọn vị trí (đợt 3)

- Bước 1 wizard: nhấp cả dòng để chọn lô (không chỉ radio); các cột Mã lô, Hàng hóa, Hạn sử dụng, Khả dụng, Số vị trí sắp xếp được bằng cách nhấp tiêu đề (asc/desc).
- Phân trang 10 dòng cho bước 1, bước 2 và bước 3 khi dữ liệu vượt 10 dòng; STT tiếp tục đúng theo trang.
- Bước 2 và bước 3 dùng bảng có radio + click dòng để chọn vị trí; bộ lọc khu vực chạy server-side nên bảng bên dưới được lọc đúng theo khu vực đã chọn.
- Bước 3 loại vị trí nguồn khỏi danh sách đích và chỉ hiện vị trí còn sức chứa trong cùng kho.
- Tất cả nội dung xem chi tiết/xem trước nhiều trường dùng bảng 2 cột Nhãn / Giá trị thống nhất (`render_detail_table()`), gồm chi tiết phiếu điều chuyển, chi tiết vị trí, bước 2-3-4-5 wizard và phần thông tin khi sửa vị trí.
- Cột trạng thái có `min-width` và `white-space: nowrap`; badge không xuống dòng. Bảng điều chuyển có min-width 1200px và cuộn ngang.
- File mới: `app/Helpers/detail-table.php`, `public/assets/js/table-select.js`; helper bảng hỗ trợ `sortable`, `sort_key`, `sort_dir`, `stt_start`, `row_attrs`.


## 13. Đồng bộ sức chứa seed và tinh chỉnh form

- Đã xác định `Đang lưu trữ` là tổng `TonKho.soLuongTon` của tất cả lô tại vị trí, không phải lỗi cộng dồn.
- Dữ liệu seed cũ có 7 vị trí bị xếp nhiều lô vượt `sucChuaToiDa`. Đã nâng sức chứa các vị trí này ngay trong `database/seed_data.sql` để không cần file migration riêng.
- Trang chi tiết/sửa vị trí có cảnh báo nếu dữ liệu thực tế vượt sức chứa; khi đó cần điều chuyển bớt hoặc tăng sức chứa có kiểm soát.
- Nút dùng chung có `margin-top: 2px` để không dính chữ/bảng.
- Form sửa vị trí: Tên vị trí và Sức chứa tối đa xếp cùng cột; nút “Khôi phục tên mặc định” nằm bên phải, cùng hàng với ô nhập tên.

