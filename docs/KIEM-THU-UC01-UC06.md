# BÁO CÁO KIỂM THỬ & NGHIỆM THU — UC01 → UC06

> **Trạng thái: CHƯA đánh dấu hoàn thành.** Phần truy vấn/dữ liệu đã kiểm thử tự động; phần PHP (controller, view, form) mới được rà soát tĩnh vì môi trường phát triển không có PHP/MySQL. Còn các điểm nghiệp vụ cần người dùng xác nhận (mục 4) và danh sách kiểm thử thủ công (mục 3).

## 1. Cách kiểm thử tự động

- `tests/tests.py` nạp `schema.sql` + `seed_data.sql` vào SQLite (bật ràng buộc CHECK và khóa ngoại), **trích nguyên văn các câu SQL từ file PHP** (`app/Models/*.php`) rồi chạy với các tình huống bên dưới. Trình tự các bước của `TiepNhan::receive()` được mô phỏng đúng thứ tự câu lệnh, trong một giao dịch.
- Chạy lại: `python3 tests/tests.py`.
- **Giới hạn:** SQLite ≠ MySQL (so sánh chuỗi không phân biệt dấu/hoa-thường của MySQL, khóa dòng `FOR UPDATE`, đồng thời nhiều người dùng đều không kiểm thử được); các hàm PHP thuần (ghi chú ngừng sử dụng, regex tiêu chí) được mô phỏng bằng Python tương đương.

## 2. Kết quả tự động: 42/42 đạt

| # | UC | Tình huống | Kết quả | Ghi chú |
|---|---|---|---|---|
| 1 | UC01 | Sinh mã tự động đúng định dạng HH+nhóm+6 số, tăng liên tiếp cho từng nhóm hàng (5 nhóm) | ĐẠT | Mã sinh: HH01000037, HH02000031, HH03000019, HH04000019, HH05000019 |
| 2 | UC01 | Hai lần thêm liên tiếp cùng nhóm không trùng mã | ĐẠT | HH01000037 → HH01000038 |
| 3 | UC01 | Hàng mới mặc định "Đang sử dụng"; hạn sử dụng/điều kiện bảo quản để trống được lưu NULL | ĐẠT | Trạng thái mặc định "Đang sử dụng", cột tùy chọn lưu NULL |
| 4 | UC01 | Dữ liệu không hợp lệ bị CSDL từ chối (đơn vị tính ngoài kg/bao/thùng, quy cách ≤ 0, hạn < 0, tên trống) | ĐẠT | CSDL chặn: đơn vị lạ, hệ số ≤0, hạn <0, tên rỗng (lớp bảo vệ thứ hai ngoài kiểm tra ở PHP) |
| 5 | UC02 | Cập nhật tên, đơn vị tính, quy cách, mô tả, hạn; mã hàng giữ nguyên; đọc lại đúng | ĐẠT | Cập nhật đủ trường, mã không đổi, hiển thị lại được sau khi lưu |
| 6 | UC02 | Hàng không tồn tại → find() trả null (3.1.1) | ĐẠT | Trả về null → controller báo "Không tìm thấy hàng hóa" |
| 7 | UC02 | Kích hoạt lại: gỡ ghi chú ngừng sử dụng khỏi mô tả, mô tả gốc còn nguyên (mô phỏng hàm PHP) | ĐẠT | Ghi chú ngừng sử dụng tách/gỡ đúng; mô tả thường không bị ảnh hưởng |
| 8 | UC03 | Hàng đang có phiếu giao chưa tiếp nhận xong bị chặn ngừng sử dụng, nêu rõ lý do (8.1) | ĐẠT | HH01000004 bị chặn: ['1 phiếu giao hàng nhà cung cấp chưa tiếp nhận xong', '1 yêu cầu xuất kho chưa hoàn tất', '1 lô hàng đang chờ kiểm tra chất lượng']; 105/120 hàng không vướng nghiệp vụ dở dang |
| 9 | UC03 | Các câu kiểm tra nghiệp vụ dở dang đều chạy được trên dữ liệu thật (6 loại) | ĐẠT | Các loại vướng mắc phát hiện trong seed: phiếu giao hàng nhà cung cấp chưa tiếp nhận xong (vd HH01000001); yêu cầu xuất kho chưa hoàn tất (vd HH01000001); đơn hàng chưa xuất đủ (vd HH01000001); kế hoạch sản xuất chưa hoàn thành (vd HH01000001); lô hàng đang chờ kiểm tra chất lượng (vd HH01000001); yêu cầu nhập kho chưa hoàn tất (vd HH01000003) |
| 10 | UC03 | Ngừng sử dụng hàng hợp lệ: trạng thái đổi, ngày + lý do được ghi lại | ĐẠT | HH01000005 → Ngừng sử dụng; lý do/ngày ghi ở cuối mô tả |
| 11 | UC03 | Mô tả dài + lý do dài không vượt 255 ký tự của cột moTa | ĐẠT | Mô tả 250 ký tự + lý do 400 ký tự → 255 ký tự (≤255), ghi chú vẫn đọc được |
| 12 | UC04 | Không nhập tiêu chí → tìm toàn bộ (3.1) và phân trang | ĐẠT | Không nhập tiêu chí → toàn bộ 120 hàng; phân trang 10/trang đúng (trang cuối 5) |
| 13 | UC04 | Lọc theo mã/tên, nhóm hàng, trạng thái, kết hợp; không có kết quả (5.1) | ĐẠT | Nguyên liệu=36, đang SD=120, ngừng=0; lọc kết hợp mã/tên/nhóm/trạng thái đúng; không khớp → 0 (hiện "Không tìm thấy hàng hóa phù hợp") |
| 14 | UC04 | Số lượng tồn trong danh sách = chi tiết = tổng TonKho; thời gian nhập/xuất khớp lịch sử biến động | ĐẠT | Tồn HH01000001 = 59195.0 kg (danh sách = chi tiết); lần nhập gần nhất 2026-11-04 15:00:00, lần xuất 2026-11-21 11:00:00 |
| 15 | UC04 | Mỗi hàng hóa chỉ xuất hiện một dòng trong danh sách (không bị nhân bản bởi JOIN tồn kho) | ĐẠT | JOIN tồn kho không làm nhân bản dòng hàng hóa |
| 16 | UC05 | Không nhập tiêu chí → toàn bộ 96 lô, không trùng dòng (3.1) | ĐẠT | 96 lô, mỗi lô một dòng |
| 17 | UC05 | Trạng thái lô suy ra đúng quy tắc (Còn hạn/Hết hạn/Đã xuất hết/Ngừng sử dụng) và bộ lọc trạng thái khớp | ĐẠT | Phân bố trạng thái: {'Còn hạn': 86, 'Ngừng sử dụng': 1, 'Đã xuất hết': 3, 'Hết hạn': 6}; lọc theo từng trạng thái khớp số đếm |
| 18 | UC05 | Lọc theo mã lô, tên hàng, vị trí lưu trữ; không có kết quả (5.1) | ĐẠT | Lọc vị trí VT010301010023: 3 lô; tìm theo mã lô/tên hàng đúng; không khớp → 0 ("Không tìm thấy lô hàng") |
| 19 | UC05 | Chi tiết lô: tổng tồn theo từng vị trí khớp SL tồn; nhà cung cấp; lô không tồn tại → null | ĐẠT | Tổng tồn theo vị trí = SL tồn ở danh sách cho cả 96 lô; 70 lô có tên nhà cung cấp; mã lô lạ → null |
| 20 | UC05 | Tiêu chí tìm kiếm không hợp lệ bị từ chối, yêu cầu nhập lại (5.2) — mô phỏng regex | ĐẠT | Chấp nhận chữ/số/dấu cách/._-; từ chối ký tự đặc biệt và >60 ký tự (mô phỏng regex PHP; Unicode \p{L} tương đương \w) |
| 21 | UC06 | Danh sách chỉ gồm chứng từ còn dòng cần nhận (Chờ tiếp nhận / Đã tiếp nhận một phần / phiếu xưởng); tìm theo số chứng từ | ĐẠT | 3 chứng từ chờ: YCNK2611060005, PG2611060001, PG2611050001 |
| 22 | UC06 | Mọi chứng từ trên danh sách đều mở được màn hình tiếp nhận | ĐẠT | Không còn chứng từ "liệt kê được nhưng bấm vào báo lỗi" (lỗi đã phát hiện và sửa trong lần kiểm thử này) |
| 23 | UC06 | Tiếp nhận ĐỦ hàng (NCC): tạo phiếu "Chờ kiểm định", lô "Chờ kiểm tra", quy đổi kg, cập nhật chứng từ | ĐẠT | PTN2611240001 + lô LO2611240001: 15 bao × 50 = 750 kg, lô "Chờ kiểm tra", phiếu giao → "Đã tiếp nhận", chưa ghi tồn kho, biến khỏi danh sách chờ |
| 24 | UC06 | Tiếp nhận MỘT PHẦN rồi nhận nốt; sau khi đủ không nhận thêm được | ĐẠT | Nhận 10/15 → "Đã tiếp nhận một phần"; nhận nốt 5 → "Đã tiếp nhận"; nhận thêm bị từ chối |
| 25 | UC06 | Chứng từ seed "Đã tiếp nhận một phần": chỉ hiện dòng còn thiếu; hoàn tất khi nhận đủ mọi dòng | ĐẠT | Chứng từ seed đang nhận dở (dòng 5/10 và 0/12): nhận từng dòng, trạng thái chuyển đúng khi đủ cả hai |
| 26 | UC06 | Tiếp nhận từ XƯỞNG sản xuất: phiếu gắn xưởng (không gắn phiếu giao), lô tạo đúng, yêu cầu → "Đang nhập kho" | ĐẠT | PTN2611240001: nguồn xưởng XSX000002 (không có phiếu giao), lô LO2611240001 50 kg, hạn = ngày SX + hạn tiêu chuẩn |
| 27 | UC06 | Lỗi khi lưu giữa chừng → hoàn tác toàn bộ (hậu điều kiện: không tạo phiếu, không tạo lô, không đổi chứng từ) | ĐẠT | Lỗi giữa chừng → không tạo phiếu/lô, không đổi số lượng đã nhận hay tình trạng chứng từ |
| 28 | UC06 | Dữ liệu không hợp lệ (6.1, 3.1.1): SL ≤ 0, vượt quá, không chọn dòng, vị trí/chứng từ không tồn tại | ĐẠT | Bị từ chối: SL=0, SL<0, SL vượt còn lại, không chọn dòng, dòng lạ, vị trí lạ, chứng từ không tồn tại — và không để lại dữ liệu |
| 29 | UC06 | Hàng hóa trên chứng từ đã "Ngừng sử dụng" → không cho tiếp nhận (tiền điều kiện 3) | ĐẠT | Hàng đã "Ngừng sử dụng" trên chứng từ → không cho tiếp nhận (tiền điều kiện 3) |
| 30 | UC06 | Vị trí lưu trữ tạm: đúng khu, đúng kho nhân viên, loại ô không dùng được (tiền điều kiện 4) | ĐẠT | Chỉ khu "Tiếp nhận tạm"; nhân viên kho chỉ thấy kho mình (6/30); ô bảo trì/khóa/lấp đầy bị loại; kho K06 không có vị trí tạm → danh sách rỗng |
| 31 | UC06 | Đánh số phiếu/lô theo ngày, liên tiếp, mỗi dòng một lô | ĐẠT | PTN2611240001,PTN2611240002; lô ['LO2611240001', 'LO2611240002', 'LO2611240003'] — đánh số theo ngày, tăng liên tiếp, một lô cho mỗi dòng |
| 32 | UC05+UC06 | Liên thông: lô vừa tiếp nhận tra cứu được ở UC05 với thông tin đúng | ĐẠT | Lô LO2611240001 vừa tạo tra cứu được ở UC05: "Còn hạn", tồn 0 (chưa nhập kho), nhà cung cấp "Công ty TNHH Alltech Việt Nam", chất lượng "Chờ kiểm tra" |
| 33 | UC01+UC04 | Liên thông: hàng vừa thêm xuất hiện ngay trong tra cứu với tồn = 0 | ĐẠT | Hàng vừa thêm (HH05000019) tra cứu được ngay, tồn 0 |
| 34 | PHÂN QUYỀN KHO · UC05 | Quản lý/Nhân viên mỗi kho chỉ thấy lô hàng thuộc kho mình (tồn ở vị trí của kho hoặc đang ở vị trí tiếp nhận tạm của kho) | ĐẠT | Số lô thấy được theo kho: K01:47, K02:21, K03:32, K04:6, K05:19, K06:0; 0 lô chưa gắn kho nào chỉ quản trị viên thấy; không kho nào thấy lô của kho khác |
| 35 | PHÂN QUYỀN KHO · UC05 | Cách ly dữ liệu giữa các kho (mọi cặp) | ĐẠT | Lô chỉ thuộc kho A không xuất hiện trong kho B (mọi cặp kho) |
| 36 | PHÂN QUYỀN KHO · UC05 | Mở chi tiết lô của kho khác bị từ chối (không lộ dữ liệu); chi tiết chỉ liệt kê vị trí thuộc kho mình | ĐẠT | 125 lượt mở chi tiết trong kho mình đều chỉ hiện vị trí của kho mình; mở lô của kho khác → "Không tìm thấy lô hàng" |
| 37 | PHÂN QUYỀN KHO · UC05 | Lô nằm ở nhiều kho: mỗi kho chỉ thấy phần tồn của kho mình, tổng các phần = tổng toàn hệ thống | ĐẠT | Lô LO2611010001 nằm ở 2 kho: mỗi kho chỉ thấy phần tồn của mình [('K010001', 9700.0), ('K050001', 2010.0)] (tổng 11710.0) |
| 38 | PHÂN QUYỀN KHO · UC05 | Bộ lọc vị trí lưu trữ chỉ có vị trí thuộc kho mình; không lách được bằng tham số | ĐẠT | Ô chọn vị trí chỉ gồm vị trí của kho mình; ép lọc theo vị trí kho khác cũng không ra dữ liệu |
| 39 | PHÂN QUYỀN KHO · UC04 | Số lượng tồn hàng hóa tính theo kho của người dùng; quản trị viên thấy tổng | ĐẠT | Mỗi kho thấy số lượng tồn CỦA KHO MÌNH (danh sách = chi tiết = tổng TonKho tại kho); quản trị viên thấy tổng mọi kho |
| 40 | PHÂN QUYỀN KHO · UC04 | Lần nhập/xuất gần nhất chỉ tính giao dịch tại kho của mình | ĐẠT | Thời gian nhập/xuất gần nhất cũng chỉ tính các giao dịch tại kho của người dùng |
| 41 | PHÂN QUYỀN KHO · UC06 | Tiếp nhận chỉ vào vị trí tạm của kho mình; không lách được bằng cách gửi mã vị trí kho khác | ĐẠT | Vị trí tạm chỉ của kho mình; ép dùng vị trí kho khác (hoặc chưa gán kho) bị từ chối ở tầng model; đúng kho thì nhận được |
| 42 | PHÂN QUYỀN KHO | Tài khoản bị giới hạn nhưng chưa gán kho → không thấy dữ liệu kho nào | ĐẠT | Quản lý/Nhân viên kho chưa được gán kho (maKho rỗng): không thấy lô, vị trí, tồn nào (an toàn mặc định) |

**Kiểm thử đột biến** (cố ý làm hỏng điều kiện lọc kho trong bản sao của code, bộ kiểm thử phải báo lỗi): 4/4 đột biến bị phát hiện — đảo điều kiện kho của lô hàng, bỏ lọc kho khi tính tồn hàng hóa, bỏ kiểm tra kho của vị trí tạm khi lưu, chi tiết lô không lọc vị trí theo kho. Lần đầu có 3/4 đột biến lọt qua vì test tự viết lại điều kiện thay vì lấy từ code PHP; đã sửa để test lấy nguyên đoạn điều kiện từ file PHP.

### Lỗi đã phát hiện và sửa nhờ kiểm thử

1. **Seed**: bảng `PhieuDieuChinhTonKho` ghi nhãn `Giam`/`Tang` (17 dòng) vi phạm `CHECK` → import seed thất bại. Đã sửa thành `Giảm`/`Tăng`. (Trước đó cũng đã sửa nhãn ở `LichSuBienDongTonKho`, `ChenhLechKiemKe`.) Sau sửa, toàn bộ seed nạp được với CHECK + khóa ngoại bật.
2. **UC06 danh sách**: liệt kê phiếu giao 'Đang tiếp nhận' nhưng mọi dòng đã nhận đủ (PG2610250001) và phiếu xưởng không có dòng hàng → bấm "Tiếp nhận" báo lỗi. Đã sửa: chỉ liệt kê chứng từ còn dòng cần tiếp nhận.
3. **UC03 ghi chú ngừng sử dụng**: biểu thức tách ngày/lý do bị hỏng vì giờ có dấu `:` → chi tiết không hiện lý do, sửa hàng đã ngừng làm mất ghi chú. Đã sửa `HangHoa::stopNote()`.
4. **Kiểm thử đột biến**: test phân quyền kho ban đầu chưa bắt được lỗi trong code PHP (xem mục 2) → đã viết lại cách trích điều kiện từ PHP.
5. **Đối chiếu đặc tả**: bỏ hai quy tắc do mình tự thêm (chặn trùng tên hàng; khóa đổi nhóm hàng); mô tả bắt buộc khi thêm (UC01 luồng 5.1); hiển thị thời gian nhập/xuất khi sửa (UC02 bước 4); hiển thị Ngày/Giờ nhận (UC06 bước 4).

## 2b. Thay đổi theo yêu cầu bổ sung

**(1) Thời gian không thể thay đổi.** UC03: "Ngày ngừng sử dụng" không còn là ô nhập; hệ thống ghi nhận thời điểm xác nhận (bỏ qua mọi giá trị gửi lên, nên không sửa được kể cả bằng cách can thiệp request). UC06: Ngày nhận/Giờ nhận hiển thị chỉ đọc, ghi theo giờ máy chủ. *Chưa khóa* ngày sản xuất/hạn sử dụng của lô (xem Q15).

**(2) Chữ mờ gợi ý (placeholder)** đã thêm ở: tên hàng, quy cách, hạn sử dụng tiêu chuẩn, điều kiện bảo quản, mô tả, lý do ngừng sử dụng, số lượng thực nhận, ghi chú tiếp nhận (các ô tìm kiếm đã có từ trước).

**(3) Phân quyền dữ liệu theo kho (đơn vị).** Quản lý kho và Nhân viên kho chỉ thao tác trong kho của mình (`NhanVien.maKho`); Quản trị viên, Ban giám đốc, QC không giới hạn theo kho. Kho của dữ liệu được xác định qua vị trí lưu trữ (`ViTriLuuTru → KhuVuc → Kho`). Lớp `app/Models/PhamViKho.php` cung cấp phạm vi; các Model nhận tham số kho nên **lọc nằm ở truy vấn CSDL**, không chỉ ẩn trên giao diện.

| Dữ liệu | Quy tắc | Căn cứ |
|---|---|---|
| Lô hàng (UC05) | Thấy lô có tồn ở vị trí của kho mình, hoặc đang ở vị trí tiếp nhận tạm của kho mình. Số lượng tồn/vị trí chỉ tính phần thuộc kho mình. Mở chi tiết lô của kho khác → "Không tìm thấy lô hàng". Ô lọc vị trí chỉ có vị trí của kho mình | `TonKho.maViTri`, `PhieuTiepNhan.maViTriTam` |
| Số lượng tồn hàng hóa (UC04) | Tồn và lần nhập/xuất gần nhất chỉ tính tại kho mình | `TonKho`, `LichSuBienDongTonKho.maViTri` |
| Tiếp nhận (UC06) | Chỉ chọn/ghi nhận vào vị trí tạm của kho mình (kiểm tra cả ở tầng form và tầng model) | `KhuVuc.maKho` |
| Tài khoản bị giới hạn nhưng chưa gán kho | Không thấy dữ liệu kho nào (từ chối mặc định) | — |

**Chưa giới hạn theo kho** (CSDL không có cột kho, cần bạn quyết định — Q13, Q14): danh mục hàng hóa (mọi kho cùng thấy; Quản lý kho thêm/sửa/ngừng được hàng hóa mọi nhóm) và chứng từ giao hàng chờ tiếp nhận.

## 3. Kiểm thử thủ công còn phải thực hiện trên PHP + MySQL thật

Chuẩn bị: import `schema.sql`, `seed_data.sql`, `thong-bao.sql`; đăng nhập các tài khoản `admin`, `qlkho01` (Quản lý kho), `kho01_nv01` (Nhân viên kho), `qc01`, `giamdoc` — mật khẩu `123456`.

| # | Tình huống | Kết quả mong đợi |
|---|---|---|
| M1 | `qlkho01` mở **Quản lý hàng hóa** → Thêm hàng hóa, bỏ trống tên/nhóm/mô tả → Lưu | Cửa sổ giữ nguyên, các trường thiếu được tô đỏ + thông báo (UC01 5.1) |
| M2 | Nhập đủ → Lưu | "Thêm thông tin hàng hóa thành công", hàng mới ở danh sách, trạng thái "Đang sử dụng", mã `HHxx######` đúng nhóm |
| M3 | Thêm với quy cách `abc`, `0`, `-1`; hạn sử dụng `-5`, `abc` | Báo lỗi từng trường, không lưu |
| M4 | Sửa hàng: đổi tên/đơn vị/quy cách/mô tả → Lưu; nhấn Hủy | Lưu: thông báo + hiện lại chi tiết sau khi cập nhật; Hủy: về danh sách, không đổi |
| M5 | Sửa hàng đang "Đang sử dụng" → chọn trạng thái "Ngừng sử dụng" | Bị từ chối, hướng dẫn dùng chức năng Ngừng sử dụng (**cần xác nhận Q3**) |
| M6 | Ngừng sử dụng hàng HH01000004 (đang có phiếu giao dở) | Bị chặn, nêu lý do (UC03 8.1) |
| M7 | Ngừng sử dụng hàng không vướng nghiệp vụ: bỏ trống lý do → rồi nhập lý do → hộp xác nhận → Hủy / Đồng ý | Bỏ trống: yêu cầu nhập lý do; Hủy: không đổi; Đồng ý: thành công, trạng thái "Ngừng sử dụng", chi tiết hiện ngày + lý do |
| M8 | Sửa hàng đã ngừng → đổi mô tả; rồi đổi trạng thái về "Đang sử dụng" | Mô tả sửa được, ghi chú ngừng còn nguyên; kích hoạt lại thì ghi chú biến mất |
| M9 | Tra cứu hàng hóa: để trống / theo mã / tên có dấu, không dấu, hoa-thường / nhóm / trạng thái / kết hợp / không khớp / ký tự `%` `_` | Đúng kết quả; không khớp: "Không tìm thấy hàng hóa phù hợp"; `%`,`_` tìm như ký tự thường |
| M10 | Tra cứu lô hàng: mã lô, tên hàng, vị trí, từng trạng thái; ký tự lạ `'<>`; chi tiết lô | Đúng kết quả; ký tự lạ: yêu cầu nhập lại; chi tiết có bảng tồn từng vị trí |
| M11 | `kho01_nv01`: Tiếp nhận hàng → mở PG2611060001 → bỏ trống vị trí / SL=0 / SL vượt / xóa ngày hạn → Tiếp tục | Từng lỗi được tô đúng dòng/trường (UC06 6.1) |
| M12 | Nhập hợp lệ → màn hình xác nhận → Xác nhận | "Tiếp nhận hàng thành công", phiếu `PTNyymmdd####`, lô "Chờ kiểm tra", chứng từ chuyển trạng thái, form được làm sạch; `qc01` nhận thông báo |
| M13 | Mở chứng từ rồi ở tab khác tiếp nhận xong, quay lại bấm Xác nhận | Báo không còn chứng từ hợp lệ, không tạo trùng |
| M14 | Tiếp nhận phiếu xưởng YCNK2611060005 | Phiếu gắn xưởng, lô 50 kg, yêu cầu → "Đang nhập kho" |
| M15 | Ngắt MySQL/đổi tên bảng tạm thời khi bấm Xác nhận | "Tiếp nhận hàng thất bại" + nút Thử lại/Hủy; không để lại dữ liệu (UC06 10.1) |
| M16 | Phân quyền: `qc01` mở `/hang-hoa`, `/tiep-nhan`; `kho01_nv01` thử POST `hang-hoa/store`; `giamdoc` mở `/lo-hang`; gửi POST thiếu CSRF | 403 cho các trường hợp không có quyền; POST thiếu CSRF bị từ chối; nút không có quyền không hiển thị |
| M17 | Nhân viên kho kho K01 | Chỉ thấy vị trí tạm của kho mình |
| M18 | Giao diện: cửa sổ trên màn hình hẹp (điện thoại), phím Esc/click nền, tải lại trang khi đang mở cửa sổ | Hiển thị đúng; đóng về danh sách giữ nguyên bộ lọc |
| M19 | Đăng nhập `qlkho01` (Kho nguyên liệu) rồi `qlkho02` (Kho thành phẩm): mở **Lô hàng** và **Quản lý hàng hóa** | Mỗi người chỉ thấy lô của kho mình; cột "Số lượng tồn" của hàng hóa khác nhau giữa hai kho; admin thấy tổng |
| M20 | `qlkho01`: mở trực tiếp `lo-hang?mode=view&id=<mã lô của kho khác>`; thử `lo-hang?viTri=<vị trí kho khác>` | "Không tìm thấy lô hàng"; bộ lọc báo vị trí không hợp lệ, không lộ dữ liệu |
| M21 | `kho01_nv01` tiếp nhận: danh sách vị trí tạm; thử sửa HTML/gửi `vi_tri` của kho khác | Chỉ thấy vị trí kho 1; gửi vị trí kho khác bị từ chối |
| M22 | `kho06_nv01` (Kho hàng lỗi, không có vị trí tiếp nhận tạm) mở tiếp nhận | Danh sách vị trí trống + thông báo; không tiếp nhận được |
| M23 | Ngừng sử dụng hàng: kiểm tra ô "Ngày ngừng sử dụng"; sửa request thêm `ngayNgung=2020-01-01` | Ô bị khóa; thời gian ghi nhận là giờ hệ thống |
| M24 | Xem placeholder các form; đổi cỡ chữ/độ tương phản | Chữ mờ hiện khi ô trống, biến mất khi nhập; không nhầm với giá trị đã nhập |

## 4. Điểm cần người dùng xác nhận (đặc tả chưa rõ hoặc mâu thuẫn với CSDL)

Hiện code đang áp dụng phương án ghi trong cột "Đang làm"; chưa coi là đã chốt.

| Q | Vấn đề | Đang làm | Cần quyết định |
|---|---|---|---|
| Q1 | UC01 yêu cầu trường **"Thời gian nhập"** (và liệt vào các trường bắt buộc), nhưng bảng `HangHoa` không có cột này | Bỏ qua trường này; thời gian nhập/xuất ở chi tiết lấy từ lịch sử biến động tồn kho | Trường này nghĩa là gì và lưu ở đâu? |
| Q2 | **Quy cách đóng gói / Đơn vị tính**: UC02 hiển thị "Bao-25kg, Thùng-20kg"; CSDL có `donViTinhChuan` (kg/bao/thùng) + `heSoQuyDoi`. Seed có thành phẩm đơn vị `kg` nhưng hệ số 20 | Quy cách = số kg trên 1 đơn vị (`heSoQuyDoi`), nhập tay | Quy cách là số hay chuỗi? Hệ số 20 với đơn vị kg có ý nghĩa gì? |
| Q3 | UC02 cho sửa **trạng thái sử dụng**, UC03 mới là nơi nhập lý do và kiểm tra nghiệp vụ | Ở UC02 chỉ cho kích hoạt lại; chuyển sang "Ngừng sử dụng" bắt buộc qua UC03 | Đúng ý chưa, hay UC02 được ngừng trực tiếp? |
| Q4 | UC03: **ngày & lý do ngừng sử dụng** không có cột trong CSDL; "nghiệp vụ chưa hoàn tất" chưa định nghĩa | Ghi vào cuối cột `moTa`; chặn khi còn phiếu giao/yêu cầu nhập/xuất, đơn hàng, kế hoạch sản xuất chưa xong hoặc lô chờ QC | Có thêm 2 cột vào `HangHoa` không (đề xuất)? Danh sách chặn đã đúng chưa? |
| Q5 | UC05: định nghĩa **trạng thái lô** và **Ngày nhập** | Ngừng sử dụng = đã thanh lý/tiêu hủy; Hết hạn = quá HSD; Ngày nhập = lần nhập kho đầu, nếu chưa có thì ngày tiếp nhận | Đúng ý chưa? "Tiêu chí không hợp lệ" là gì (hiện: ký tự ngoài chữ/số/`._-`, >60 ký tự)? |
| Q6 | UC06 tiền điều kiện "đã được Quản lý kho **điều phối**" | Mọi phiếu giao NCC 'Chờ/Đang tiếp nhận' và phiếu yêu cầu nhập từ xưởng 'Chờ xử lý' | "Điều phối" được ghi nhận ở đâu trong CSDL? |
| Q7 | UC06 bước 5: **Ghi chú cho từng dòng** hàng — CSDL chỉ có ghi chú ở cấp phiếu tiếp nhận | Chỉ có 1 ô ghi chú chung | Thêm cột ghi chú cho dòng, hay gộp vào ghi chú phiếu? |
| Q8 | UC06: **nhận vượt** số lượng còn phải nhận; **ngày sản xuất/HSD** của lô (CSDL bắt buộc, spec không nói) | Từ chối nhận vượt; có ô NSX/HSD (lấy từ phiếu giao, hoặc ngày nhận + hạn tiêu chuẩn) | Cho phép nhận vượt không? Quy tắc NSX/HSD đúng chưa? |
| Q9 | UC06 hậu điều kiện "số lượng đã nhận cập nhật trên **Đơn hàng mua / Kế hoạch sản xuất**" | Chỉ cập nhật `ChiTietPhieuGiaoNCC.soLuongThucNhan`; CSDL không có đơn hàng mua và không liên kết phiếu xưởng với kế hoạch cụ thể | Cập nhật vào bảng/cột nào? |
| Q10 | UC06: **vị trí tạm "Đang sử dụng"** — CSDL không có giá trị này (có: Đang để trống/Đang chứa hàng/Đã lấp đầy/Đang bảo trì/Đã khóa) | Khu "Tiếp nhận tạm" thuộc kho nhân viên, loại ô bảo trì/khóa/lấp đầy | Đúng ý chưa? Có kiểm tra sức chứa không? |
| Q11 | UC02: **Mô tả** có bắt buộc khi sửa không (UC01 liệt mô tả vào trường phải nhập) | Bắt buộc khi thêm, tùy chọn khi sửa | Chốt giúp |
| Q12 | Vai trò: UC05 ghi "Quản lý kho" ở bảng luồng nhưng Actor chính là Nhân viên kho | Cả hai vai trò được tra cứu hàng hóa/lô hàng; Ban giám đốc, QC không | Đúng ý chưa? |
| Q13 | **Danh mục hàng hóa** không có cột kho. Hiện mọi kho cùng thấy danh mục; Quản lý kho thêm/sửa/ngừng được hàng hóa mọi nhóm. Seed: tồn kho khớp loại kho (Kho nguyên liệu chỉ chứa nguyên liệu…) nhưng khu tiếp nhận tạm kho phụ gia vẫn nhận cả nguyên liệu và bao bì | Không giới hạn quyền ghi trên danh mục | Có giới hạn theo nhóm hàng ↔ loại kho không? Kho "Tổng hợp"/"Hàng lỗi" xử lý thế nào? |
| Q14 | **Chứng từ giao hàng** chờ tiếp nhận không có cột kho | Mọi kho thấy mọi chứng từ; hàng nhận vào kho nào thì lô thuộc kho đó | Có chỉ định kho nhận cho từng chứng từ không? Có cho hai kho cùng nhận một chứng từ không? |
| Q15 | "Thời gian không thể thay đổi": đã áp dụng cho Ngày ngừng sử dụng (UC03) và Ngày/Giờ nhận (UC06). Ngày sản xuất/hạn sử dụng của lô vẫn sửa được | Giữ nhập được (CSDL bắt buộc có) | Có khóa khi chứng từ đã ghi sẵn không? Còn trường thời gian nào khác cần khóa? |
| Q16 | Kho K05 "Tổng hợp", K06 "Hàng lỗi": K06 không có vị trí tiếp nhận tạm; 8 lô chưa gắn kho nào (không tồn, không qua phiếu tiếp nhận) chỉ quản trị viên thấy | Như mô tả | Đúng ý chưa? |

## 5. Vấn đề còn tồn tại

- Chưa chạy được ứng dụng PHP thật (xem mục 3).
- `donViTinhChuan`/`heSoQuyDoi` có thể gây nhầm lẫn hiển thị (Q2).
- UC03 "không được sử dụng cho nghiệp vụ mới": mới áp dụng ở UC06; các nghiệp vụ khác (xuất kho, kế hoạch...) chưa lập trình nên chưa chặn.
- Quy cách tìm kiếm có dấu/không dấu phụ thuộc collation MySQL `utf8mb4_unicode_ci` (chưa kiểm thử).
- Nếu mở trang tiếp nhận ở hai nơi cùng lúc, người sau bị từ chối nhờ khóa dòng + kiểm tra lại (đã có trong code, chưa thử với MySQL thật).

## 6. Kết luận nghiệm thu

| UC | Kỹ thuật (dữ liệu/SQL) | Giao diện/PHP | Nghiệp vụ |
|---|---|---|---|
| UC01 | Đạt | Chưa chạy thật | Chờ Q1, Q2, Q11, Q13 |
| UC02 | Đạt | Chưa chạy thật | Chờ Q2, Q3, Q11, Q13 |
| UC03 | Đạt | Chưa chạy thật | Chờ Q4, Q13 |
| UC04 | Đạt | Chưa chạy thật | Chờ Q1, Q2, Q12 |
| UC05 | Đạt | Chưa chạy thật | Chờ Q5, Q12, Q16 |
| UC06 | Đạt | Chưa chạy thật | Chờ Q6–Q10, Q14, Q15 |