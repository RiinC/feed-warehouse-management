# TÀI LIỆU YÊU CẦU TRIỂN KHAI CODE — FEEDWARE – QUẢN LÝ KHO NHÀ MÁY SẢN XUẤT THỨC ĂN CHĂN NUÔI (MÔ HÌNH MVC)

| | |
|---|---|
| Dự án | `feedware` |
| Mô hình | MVC tự xây dựng bằng PHP thuần (front controller + router), MySQL/MariaDB |
| Đối tượng đọc | Lập trình viên triển khai các Use Case UC01 – UC30 và phần quản trị |
| Cập nhật | 17/11/2026 |

Tài liệu này mô tả **cấu trúc MVC đã dựng sẵn**, **quy ước bắt buộc** khi viết code, và **yêu cầu triển khai cho từng module**. Các Use Case hiện mới có khung (Controller + View tạm); phần còn lại cần được cài đặt theo tài liệu này.

---

## 1. Hiện trạng và phạm vi

### 1.1. Đã hoàn thành (chạy được, đã kiểm thử)

| Hạng mục | Mô tả |
|---|---|
| Lõi MVC (`core/`) | Front controller, Router (kiểm tra đăng nhập + quyền + CSRF), View/Layout, Controller/Model cơ sở, Database (mysqli + prepared statement), Auth, Permission, Menu |
| Đăng nhập / đăng xuất | `AuthController` + Model `TaiKhoan`; đăng xuất bằng POST có CSRF |
| Phân quyền theo vai trò | 5 vai trò; ma trận ở `config/permissions.php`; quyền khai báo trên từng route |
| Menu theo vai trò | Sidebar dạng nhóm mở/đóng, mỗi vai trò một bố cục (`config/menu-layout.php`), tự ẩn mục không có quyền |
| Tổng quan | Lối tắt tới mọi chức năng vai trò được phép dùng |
| Thông báo | Chuông thông báo, danh sách, đánh dấu đã đọc, tự cập nhật 30 giây; API gửi thông báo cho code nghiệp vụ (`ThongBao::notifyUser/notifyRole`) |
| Trang lỗi 403/404/405 | `ErrorController` |
| Khung cho 38 trang nghiệp vụ | 14 Controller + 38 View tạm (hiện "chức năng sẽ được bổ sung") + route + quyền |

### 1.2. Chưa làm (phạm vi tài liệu này)

- Phần code (**Model, giao diện, xử lý form**) cho các bảng nghiệp vụ; CSDL `feed_wms` đã có đủ bảng (`database/schema.sql`) và dữ liệu mẫu (`database/seed_data.sql`).
- Toàn bộ **Model, giao diện, xử lý form (POST)** của các Use Case ở mục 8.
- Quản lý tài khoản (module `tai-khoan`, chỉ Quản trị viên).

### 1.3. Ngoài phạm vi

Ứng dụng di động, API công khai cho hệ thống khác, tích hợp thiết bị (máy quét mã vạch, cân điện tử), đa kho/đa chi nhánh. Nếu cần, bổ sung vào đặc tả riêng.

---

## 2. Kiến trúc MVC

### 2.1. Cấu trúc thư mục

```
feedware/
├── public/                     ← DUY NHẤT thư mục web truy cập được (DocumentRoot trỏ vào đây)
│   ├── index.php               ← front controller: mọi yêu cầu đi qua đây
│   ├── .htaccess               ← chuyển URL về index.php
│   └── assets/                 ← css/, js/, images/
├── app/
│   ├── Controllers/            ← mỗi module một Controller (HangHoaController, XuatKhoController, ...)
│   ├── Models/                 ← truy vấn CSDL + quy tắc dữ liệu (TaiKhoan, NhanVien, ThongBao, ...)
│   ├── Views/
│   │   ├── layouts/            ← main.php (có sidebar), auth.php (không sidebar), sidebar.php, notification-bell.php
│   │   ├── components/         ← mẫu giao diện dùng lại (placeholder.php, ...)
│   │   └── <module>/<trang>.php← giao diện từng trang (hang-hoa/create.php, ...)
│   └── Helpers/                ← hàm dựng giao diện: render_data_table, render_search_form, render_pagination, ...
├── core/                       ← lõi framework: Router, Request, View, Controller, Model, Database, Auth, Permission, Menu, helpers.php
├── config/                     ← app.php, database.php, constants.php, permissions.php, menu.php, menu-layout.php
├── routes/web.php              ← BẢNG ĐỊNH TUYẾN (đường dẫn → Controller → quyền)
├── database/                   ← schema.sql (+ file SQL từng đợt thay đổi)
├── docs/                       ← tài liệu này
└── .htaccess                   ← dành cho XAMPP/Laragon khi chưa trỏ DocumentRoot vào public/
```

`config/`, `core/`, `app/`, `database/` nằm **ngoài** `public/` nên không thể mở trực tiếp từ trình duyệt.

### 2.2. Luồng xử lý một yêu cầu

```
Trình duyệt ─▶ public/index.php ─▶ core/bootstrap.php (autoload, config, session)
                                  ─▶ routes/web.php (đăng ký route)
                                  ─▶ Router::dispatch()
                                        1. tìm route theo "phương thức + đường dẫn"  (không có → 404 / 405)
                                        2. route có module? → bắt buộc đăng nhập      (chưa → về login; JSON → 401)
                                        3. kiểm tra quyền module/action               (không có → 403)
                                        4. POST? → kiểm tra CSRF                      (sai → báo lỗi, về trang trước)
                                        5. gọi Controller::phương_thức()
                                              ├─ gọi Model lấy/ghi dữ liệu
                                              └─ $this->render('module/trang', $data, $page)  → View + Layout
```

Controller **không** cần tự kiểm tra đăng nhập, quyền hay CSRF — Router đã làm trước khi gọi.

### 2.3. Trách nhiệm từng lớp

| Lớp | Được làm | Không được làm |
|---|---|---|
| **Route** (`routes/web.php`) | Khai báo đường dẫn, Controller, quyền | Chứa logic |
| **Controller** | Đọc tham số (`Request`), kiểm tra dữ liệu đầu vào, gọi Model, chọn View, chuyển hướng, `flash`, gửi thông báo | Viết SQL, in HTML |
| **Model** | Truy vấn/ghi CSDL, giao dịch, quy tắc dữ liệu (trùng mã, trạng thái hợp lệ, FEFO...) | Đọc `$_GET/$_POST/$_SESSION`, in HTML, chuyển hướng |
| **View** | Hiển thị biến do Controller truyền; dùng `e()`, `route()`, `can()`, `render_*` | Viết SQL, gọi Model, xử lý nghiệp vụ |
| **Helpers** (`app/Helpers`) | Hàm dựng khối giao diện dùng lại | Truy vấn CSDL |

Quy tắc phụ thuộc: `Controller → Model → Database`. View chỉ nhận dữ liệu từ Controller. `core/` không phụ thuộc `app/` (trừ việc Router gọi `ErrorController`).

---

## 3. Môi trường và cài đặt

### 3.1. Yêu cầu

- PHP **7.4 trở lên** (khuyến nghị 8.1+), bật extension `mysqli`, `mbstring`.
- MySQL 5.7+ / MariaDB 10.4+ (utf8mb4).
- Apache có `mod_rewrite` (XAMPP/Laragon) hoặc Nginx; hoặc chạy thử bằng máy chủ tích hợp của PHP.

### 3.2. Các bước

1. Giải nén dự án.
2. Import lần lượt `database/schema.sql` → `database/seed_data.sql` → `database/thong-bao.sql` (phpMyAdmin → Import, hoặc `mysql -u root < tên_file.sql`). CSDL là `feed_wms`; schema dùng `CREATE TABLE IF NOT EXISTS` nên muốn làm lại từ đầu hãy `DROP DATABASE feed_wms;` trước.
3. Sửa `config/database.php` (host, user, password) nếu khác mặc định.
4. Chạy ứng dụng theo một trong các cách dưới đây.

**Cách A — máy chủ tích hợp PHP (nhanh nhất khi phát triển):**

```bash
php -S localhost:8000 -t public
# mở http://localhost:8000
```

**Cách B — Apache VirtualHost (khuyến nghị):** trỏ `DocumentRoot` vào thư mục `public/`, bật `AllowOverride All`.

```apache
<VirtualHost *:80>
    ServerName kho.local
    DocumentRoot "C:/xampp/htdocs/feedware/public"
    <Directory "C:/xampp/htdocs/feedware/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Cách C — XAMPP/Laragon không đổi DocumentRoot:** mở `http://localhost/feedware/`. File `.htaccess` ở thư mục gốc tự chuyển vào `public/`. Địa chỉ trên thanh trình duyệt sẽ có thêm `/public`.

**Cách D — server không hỗ trợ rewrite:** đặt `'url_rewrite' => false` trong `config/app.php`; URL có dạng `index.php?r=hang-hoa/create`.

**Nginx:**

```nginx
root /var/www/feedware/public;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php-fpm.sock;
                    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; }
```

### 3.3. Tài khoản mẫu

| Vai trò | Tên đăng nhập | Mật khẩu |
|---|---|---|
| Quản trị viên | `admin` | `123456` |
| Ban giám đốc | `giamdoc`, `qlvanhanh` | `123456` |
| Quản lý kho | `qlkho01` … `qlkho06` | `123456` |
| Nhân viên kho | `kho01_nv01` … `kho06_nv02` | `123456` |
| Nhân viên QC | `qc01`, `qc02`, `qc03` | `123456` |

**Bắt buộc đổi mật khẩu mẫu và đặt `'debug' => false` trước khi đưa lên môi trường thật.**

### 3.4. Cấu hình (`config/app.php`)

| Khóa | Ý nghĩa |
|---|---|
| `debug` | `true` hiện chi tiết lỗi PHP (chỉ khi phát triển) |
| `base_url` | Để trống = tự nhận theo vị trí `public/index.php` |
| `url_rewrite` | `true` = URL đẹp; `false` = `index.php?r=...` |
| `per_page` | Số dòng mỗi trang của bảng có phân trang |
| `timezone` | Mặc định `Asia/Ho_Chi_Minh` |

---

## 4. Quy ước lập trình (bắt buộc)

### 4.1. Đặt tên

| Đối tượng | Quy ước | Ví dụ |
|---|---|---|
| Route | chữ thường, gạch nối | `hang-hoa/create`, `xuat-kho/yeu-cau/approve` |
| Controller | `PascalCase` + `Controller`, một Controller cho một module | `HangHoaController` |
| Phương thức Controller | `camelCase`; trang hiển thị: `index/create/edit/detail`; xử lý form: `store/update/stop/approve...` | `stopUsing`, `store` |
| Model | `PascalCase`, trùng tên bảng chính | `HangHoa`, `LoHang` |
| Bảng, cột CSDL | `PascalCase` bảng, `camelCase` cột (theo `schema.sql` hiện có) | `LoHang.maLoHang` |
| View | `app/Views/<module>/<trang>.php`, tên trùng tên route | `app/Views/hang-hoa/create.php` |
| Hằng số | `HOA_CHU` | `ROLE_MANAGER` |

PHP: khai báo kiểu tham số/giá trị trả về khi có thể, mỗi lớp một file, namespace khớp thư mục (`App\Controllers\...`, `App\Models\...`, `Core\...`). Tương thích PHP 7.4 (không dùng `match`, `str_contains`, thuộc tính hằng khai báo trong constructor...).

### 4.2. Bảo mật (không được bỏ qua)

| Yêu cầu | Cách làm |
|---|---|
| Chống SQL injection | **Mọi** truy vấn dùng tham số `?`: `static::all('SELECT ... WHERE maHang = ?', [$ma])`. Cấm nối chuỗi dữ liệu người dùng vào SQL |
| Chống XSS | **Mọi** giá trị in ra HTML qua `e()`. Chỉ `echo` HTML thô khi chính code sinh ra nó đã escape |
| Chống CSRF | Mọi form POST có `<?= csrf_field() ?>`; Router tự kiểm tra. **Không** dùng GET để thay đổi dữ liệu (kể cả đăng xuất, xóa, duyệt) |
| Phân quyền | Khai báo `module/action` đúng trên **từng route**, kể cả route POST. Ẩn nút trong View bằng `can()`/`action_buttons()` chỉ là phần giao diện, không thay cho quyền ở route |
| Kiểm tra dữ liệu | Kiểm tra lại **ở Controller/Model** dù đã có kiểm tra bằng JavaScript (`validation.js`) |
| Truy cập theo bản ghi | Khi nhận `id` từ URL, luôn kiểm tra bản ghi tồn tại và người dùng được phép thao tác với bản ghi đó (ví dụ thông báo chỉ của chính người nhận) |
| Mật khẩu | `password_hash()` / `password_verify()`; không ghi mật khẩu vào log |
| Chuyển hướng | Chỉ chuyển tới route nội bộ (`redirect('route')`); đường dẫn đến từ dữ liệu phải qua `ThongBao::safeLink()` hoặc danh sách cho phép |
| Lỗi | `debug=false` ở môi trường thật; không in thông báo lỗi SQL cho người dùng |

### 4.3. Giao dịch và toàn vẹn dữ liệu

Thao tác ghi nhiều bảng (nhập kho: phiếu + chi tiết + cập nhật tồn; xuất kho; điều chuyển; kiểm kê) **bắt buộc** nằm trong `static::transaction(function () { ... })` của Model. Số lượng tồn không được âm; kiểm tra trong cùng giao dịch (khóa dòng bằng `SELECT ... FOR UPDATE` khi cập nhật tồn).

### 4.4. Thông báo cho người dùng

- Kết quả thao tác: `$this->flash('success'|'error'|'warning'|'info', 'Nội dung')` rồi `$this->redirect('route')` (mẫu Post/Redirect/Get — không render trực tiếp sau POST thành công).
- Lỗi nhập liệu: render lại form kèm `$errors` và giá trị cũ, mã trạng thái 422 là tùy chọn.
- Thông báo cho **người khác** (chuông): `ThongBao::notifyUser(...)` / `ThongBao::notifyRole(...)` — xem mục 7.4 và 8.

### 4.5. Định dạng hiển thị

Ngày giờ: `fmt_datetime()`, `fmt_date()`; số lượng/tiền: `fmt_number()` (đều trong `core/helpers.php`). Ngôn ngữ giao diện: tiếng Việt, có dấu.

---

## 5. Phân quyền

### 5.1. Ma trận quyền hiện tại (`config/permissions.php`)

| Module | Hành động | Quản lý kho | Nhân viên kho | NV kiểm kê/QC | Ban giám đốc |
|---|---|:-:|:-:|:-:|:-:|
| `dashboard` | `view` | ✔ | ✔ | ✔ | ✔ |
| `hang-hoa` | `view` | ✔ | ✔ |  |  |
| `hang-hoa` | `create` | ✔ |  |  |  |
| `hang-hoa` | `edit` | ✔ |  |  |  |
| `hang-hoa` | `stop` | ✔ |  |  |  |
| `lo-hang` | `view` | ✔ | ✔ |  |  |
| `tiep-nhan` | `view` | ✔ | ✔ |  |  |
| `tiep-nhan` | `create` |  | ✔ |  |  |
| `chat-luong` | `view` | ✔ |  | ✔ |  |
| `chat-luong` | `record` |  |  | ✔ |  |
| `chat-luong` | `confirm` | ✔ |  | ✔ |  |
| `nhap-kho` | `view` | ✔ | ✔ |  |  |
| `nhap-kho` | `create` |  | ✔ | ✔ |  |
| `nhap-kho` | `history` | ✔ |  | ✔ |  |
| `vi-tri-luu-tru` | `view` | ✔ | ✔ |  |  |
| `vi-tri-luu-tru` | `create` | ✔ |  |  |  |
| `vi-tri-luu-tru` | `edit` | ✔ |  |  |  |
| `vi-tri-luu-tru` | `stop` | ✔ |  |  |  |
| `gan-lo-hang` | `view` | ✔ | ✔ |  |  |
| `gan-lo-hang` | `assign` |  | ✔ |  |  |
| `dieu-chuyen` | `view` | ✔ | ✔ |  |  |
| `dieu-chuyen` | `create` |  | ✔ |  |  |
| `xuat-kho` | `view` | ✔ | ✔ |  |  |
| `xuat-kho` | `request` |  | ✔ |  |  |
| `xuat-kho` | `approve` | ✔ |  |  |  |
| `xuat-kho` | `fefo` |  | ✔ |  |  |
| `xuat-kho` | `issue` |  | ✔ |  |  |
| `kiem-ke` | `view` | ✔ |  | ✔ |  |
| `kiem-ke` | `create` | ✔ |  |  |  |
| `kiem-ke` | `record` | ✔ |  | ✔ |  |
| `kiem-ke` | `reconcile` | ✔ |  |  |  |
| `kiem-ke` | `handle` | ✔ |  |  |  |
| `hang-bat-thuong` | `view` | ✔ |  | ✔ |  |
| `hang-bat-thuong` | `handle` | ✔ |  |  |  |
| `ton-kho` | `view` | ✔ | ✔ | ✔ | ✔ |
| `bao-cao` | `view` | ✔ |  |  | ✔ |
| `thong-bao` | `view` | ✔ | ✔ | ✔ | ✔ |
| `tai-khoan` | `view` |  |  |  |  |

Quản trị viên luôn được phép mọi thao tác (không cần liệt kê). Module `tai-khoan` chỉ dành cho Quản trị viên.

### 5.2. Menu theo vai trò

Bố cục sidebar từng vai trò khai báo ở `config/menu-layout.php` (theo tài liệu "Giao diện quản lý kho"). Quyền **không** khai báo ở đây: mỗi route mang sẵn quyền, mục nào vai trò không có quyền thì tự ẩn, nhóm rỗng tự ẩn. Trang Tổng quan hiển thị thêm các thao tác riêng (khai báo `show => 'dash'` trong `config/menu.php`).

**Nguyên tắc Sidebar tinh gọn.** Sidebar chỉ có nhóm/module chính, mỗi mục mở thẳng trang quản lý chính; nhóm mà vai trò chỉ thấy đúng một mục sẽ tự hiện như mục đơn (`core/Menu.php`). Thao tác Thêm/Sửa/Ngừng sử dụng/Phê duyệt… là nút trong trang chính, hiển thị theo `can(module, action)`; **không** tạo mục Sidebar riêng cho từng thao tác. Breadcrumb (`Tổng quan / Module / Thao tác`) thể hiện cấp điều hướng; cấp cha chỉ thành liên kết khi vai trò có quyền mở. Các route thao tác chưa cài đặt được comment ở cuối `routes/web.php` và liệt kê trong `docs/FEATURE_BACKLOG.md`.

### 5.3. Thêm một chức năng mới — checklist 5 bước

1. **Quyền:** thêm hành động vào `config/permissions.php` (nếu là hành động mới).
2. **Route:** thêm dòng vào `routes/web.php` với đúng `module` + `action`.
3. **Controller:** thêm phương thức; **View:** tạo `app/Views/<module>/<trang>.php`.
4. **Menu:** chỉ thêm vào `config/menu.php` / `config/menu-layout.php` khi là **module/trang quản lý chính** mới. Thao tác (thêm, sửa, ngừng sử dụng…) đặt thành nút trong trang chính, không thêm mục Sidebar.
5. **Kiểm thử:** đăng nhập từng vai trò, xác nhận mục hiện/ẩn đúng và vai trò không có quyền nhận 403.

### 5.4. Lưu ý về quyền cần được chủ dự án xác nhận

Ma trận hiện tại được dựng theo tài liệu "Giao diện quản lý kho". Có một số chỗ cần xác nhận với nghiệp vụ trước khi nghiệm thu:

- Nhân viên **QC** hiện được: xác nhận kết quả kiểm tra, lập phiếu nhập kho, tra cứu lịch sử nhập kho (theo tài liệu giao diện). Các quyền này trước đây thuộc Quản lý/Nhân viên kho.
- Hành động chỉ có trong ma trận mà chưa có trang/route riêng: `gan-lo-hang/assign`, `hang-bat-thuong/handle`. Cần thêm route khi cài đặt UC16 và UC28.
- Một vai trò dùng chung cho "Nhân viên kiểm kê" và "Nhân viên QC" (`ROLE_INVENTORY`). Nếu hai nhóm có quyền khác nhau, cần tách thành hai vai trò (cập nhật `constants.php`, CSDL, ma trận, menu).

---

## 6. Danh mục route

### 6.1. Route GET (trang)

| # | Route (GET) | Controller::phương_thức | View | Quyền (module / action) | Use Case |
|---|---|---|---|---|---|
| 1 | `dashboard` | `DashboardController::index` | `dashboard/index` | dashboard / view | Tổng quan |
| 2 | `thong-bao` | `ThongBaoController::index` | `thong-bao/index` | thong-bao / view | Thông báo |
| 3 | `thong-bao/open?id=` | `ThongBaoController::open` | `(chuyển hướng)` | thong-bao / view | Thông báo |
| 4 | `thong-bao/poll` | `ThongBaoController::poll` | `(JSON)` | thong-bao / view | Thông báo |
| 5 | `login` | `AuthController::showLogin` | `auth/login` | công khai | Đăng nhập |
| 6 | `bao-cao` | `BaoCaoController::index` | `bao-cao/index` | bao-cao / view | UC30 — Xem báo cáo tồn kho |
| 7 | `bao-cao/expiry-fefo` | `BaoCaoController::expiryFefo` | `bao-cao/expiry-fefo` | bao-cao / view | UC30 — Báo cáo hạn sử dụng theo FEFO |
| 8 | `bao-cao/inventory` | `BaoCaoController::inventory` | `bao-cao/inventory` | bao-cao / view | UC30 — Báo cáo tồn kho |
| 9 | `chat-luong` | `ChatLuongController::index` | `chat-luong/index` | chat-luong / view | UC07 — Tra cứu lô hàng cần kiểm tra |
| 10 | `chat-luong/confirm-result` | `ChatLuongController::confirmResult` | `chat-luong/confirm-result` | chat-luong / confirm | UC09 — Xác nhận kết quả kiểm tra chất lượng |
| 11 | `chat-luong/record-result` | `ChatLuongController::recordResult` | `chat-luong/record-result` | chat-luong / record | UC08 — Ghi nhận kết quả kiểm tra chất lượng |
| 12 | `dieu-chuyen` | `DieuChuyenController::index` | `dieu-chuyen/index` | dieu-chuyen / view | UC17 — Điều chuyển hàng nội bộ |
| 13 | `dieu-chuyen/create` | `DieuChuyenController::create` | `dieu-chuyen/create` | dieu-chuyen / create | UC17 — Lập phiếu điều chuyển |
| 14 | `gan-lo-hang` | `GanLoHangController::index` | `gan-lo-hang/index` | gan-lo-hang / view | UC16 — Gán lô hàng vào vị trí lưu trữ |
| 15 | `hang-bat-thuong` | `HangBatThuongController::index` | `hang-bat-thuong/index` | hang-bat-thuong / view | UC28 — Xử lý hàng bất thường |
| 16 | `hang-hoa` | `HangHoaController::index` | `hang-hoa/index` | hang-hoa / view | UC04 — Tra cứu thông tin hàng hóa |
| 17 | `hang-hoa/create` | `HangHoaController::create` | `hang-hoa/create` | hang-hoa / create | UC01 — Thêm thông tin hàng hóa |
| 18 | `hang-hoa/edit` | `HangHoaController::edit` | `hang-hoa/edit` | hang-hoa / edit | UC02 — Sửa thông tin hàng hóa |
| 19 | `hang-hoa/stop-using` | `HangHoaController::stopUsing` | `hang-hoa/stop-using` | hang-hoa / stop | UC03 — Ngừng sử dụng hàng hóa |
| 20 | `kiem-ke` | `KiemKeController::index` | `kiem-ke/index` | kiem-ke / view | UC24 — Kiểm kê kho |
| 21 | `kiem-ke/create` | `KiemKeController::create` | `kiem-ke/create` | kiem-ke / create | UC24 — Lập biên bản kiểm kê |
| 22 | `kiem-ke/handle-discrepancy` | `KiemKeController::handleDiscrepancy` | `kiem-ke/handle-discrepancy` | kiem-ke / handle | UC27 — Xử lý chênh lệch kiểm kê |
| 23 | `kiem-ke/reconcile` | `KiemKeController::reconcile` | `kiem-ke/reconcile` | kiem-ke / reconcile | UC26 — Đối chiếu số liệu kiểm kê |
| 24 | `kiem-ke/record-result` | `KiemKeController::recordResult` | `kiem-ke/record-result` | kiem-ke / record | UC25 — Ghi nhận kết quả kiểm kê |
| 25 | `lo-hang` | `LoHangController::index` | `lo-hang/index` | lo-hang / view | UC05 — Tra cứu thông tin lô hàng |
| 26 | `lo-hang/detail` | `LoHangController::detail` | `lo-hang/detail` | lo-hang / view | UC05 — Chi tiết lô hàng |
| 27 | `nhap-kho` | `NhapKhoController::index` | `nhap-kho/index` | nhap-kho / view | UC10 — Nhập kho |
| 28 | `nhap-kho/create` | `NhapKhoController::create` | `nhap-kho/create` | nhap-kho / create | UC10 — Lập phiếu nhập kho |
| 29 | `nhap-kho/history` | `NhapKhoController::history` | `nhap-kho/history` | nhap-kho / history | UC11 — Tra cứu lịch sử nhập kho |
| 30 | `tai-khoan` | `TaiKhoanController::index` | `tai-khoan/index` | tai-khoan / view | Quản trị hệ thống — Quản lý tài khoản |
| 31 | `tiep-nhan` | `TiepNhanController::index` | `tiep-nhan/index` | tiep-nhan / view | UC06 — Tiếp nhận hàng vào kho |
| 32 | `tiep-nhan/create` | `TiepNhanController::create` | `tiep-nhan/create` | tiep-nhan / create | UC06 — Tiếp nhận hàng |
| 33 | `ton-kho` | `TonKhoController::index` | `ton-kho/index` | ton-kho / view | UC29 — Tra cứu tồn kho |
| 34 | `vi-tri-luu-tru` | `ViTriLuuTruController::index` | `vi-tri-luu-tru/index` | vi-tri-luu-tru / view | UC15 — Tra cứu vị trí lưu trữ |
| 35 | `vi-tri-luu-tru/create` | `ViTriLuuTruController::create` | `vi-tri-luu-tru/create` | vi-tri-luu-tru / create | UC12 — Thêm thông tin vị trí lưu trữ |
| 36 | `vi-tri-luu-tru/edit` | `ViTriLuuTruController::edit` | `vi-tri-luu-tru/edit` | vi-tri-luu-tru / edit | UC13 — Sửa thông tin vị trí lưu trữ |
| 37 | `vi-tri-luu-tru/stop-using` | `ViTriLuuTruController::stopUsing` | `vi-tri-luu-tru/stop-using` | vi-tri-luu-tru / stop | UC14 — Ngừng sử dụng vị trí lưu trữ |
| 38 | `xuat-kho/fefo` | `XuatKhoController::fefo` | `xuat-kho/fefo` | xuat-kho / fefo | UC22 — Đề xuất lô hàng xuất kho theo FEFO |
| 39 | `xuat-kho/phieu-xuat` | `XuatKhoController::issue` | `xuat-kho/phieu-xuat` | xuat-kho / issue | UC23 — Lập phiếu xuất kho |
| 40 | `xuat-kho/yeu-cau` | `XuatKhoController::requests` | `xuat-kho/yeu-cau/index` | xuat-kho / view | UC20 — Tra cứu yêu cầu xuất kho |
| 41 | `xuat-kho/yeu-cau/approve` | `XuatKhoController::approveRequest` | `xuat-kho/yeu-cau/approve` | xuat-kho / approve | UC21 — Phê duyệt yêu cầu xuất kho |
| 42 | `xuat-kho/yeu-cau/create` | `XuatKhoController::createRequest` | `xuat-kho/yeu-cau/create` | xuat-kho / request | UC18 — Lập yêu cầu xuất kho |
| 43 | `xuat-kho/yeu-cau/edit` | `XuatKhoController::editRequest` | `xuat-kho/yeu-cau/edit` | xuat-kho / request | UC19 — Sửa yêu cầu xuất kho |

### 6.2. Route POST đã có

| Route (POST) | Controller::phương_thức | Quyền | Chức năng |
|---|---|---|---|
| `login` | `AuthController::login` | công khai | Đăng nhập |
| `logout` | `AuthController::logout` | thong-bao / view (mọi vai trò) | Đăng xuất |
| `thong-bao/read-all` | `ThongBaoController::readAll` | thong-bao / view | Đánh dấu đã đọc tất cả |

Các route POST nghiệp vụ (lưu, cập nhật, ngừng sử dụng, duyệt...) **chưa có** và được liệt kê theo từng module ở mục 8.

---

## 7. Quy trình cài đặt một Use Case (có mẫu code)

Ví dụ minh họa: **UC01 — Thêm thông tin hàng hóa**. Các module khác làm tương tự.

### 7.1. Bước 1 — Bảng CSDL

Thêm vào cuối `database/schema.sql` (và thêm tên bảng vào dòng `DROP TABLE IF EXISTS` ở đầu file, đặt bảng con trước bảng cha). Ví dụ:

```sql
CREATE TABLE HangHoa (
  maHangHoa   VARCHAR(20)  NOT NULL PRIMARY KEY,
  tenHangHoa  VARCHAR(150) NOT NULL,
  donViTinh   VARCHAR(20)  NOT NULL,
  trangThai   VARCHAR(30)  NOT NULL DEFAULT 'Đang sử dụng',
  CONSTRAINT ck_hh_tt CHECK (trangThai IN ('Đang sử dụng','Ngừng sử dụng'))
) ENGINE=InnoDB;
```

Tên bảng/cột phải khớp từ điển khái niệm của nhóm; đây chỉ là ví dụ.

### 7.2. Bước 2 — Model (`app/Models/HangHoa.php`)

```php
<?php
namespace App\Models;

use Core\Model;

class HangHoa extends Model
{
    public static function find(string $ma): ?array
    {
        return static::one('SELECT * FROM HangHoa WHERE maHangHoa = ?', [$ma]);
    }

    /** @return string|null  thông báo lỗi dữ liệu, null nếu hợp lệ */
    public static function validate(array $d): ?string
    {
        if ($d['maHangHoa'] === '' || $d['tenHangHoa'] === '' || $d['donViTinh'] === '') {
            return 'Vui lòng nhập đầy đủ các trường bắt buộc.';
        }
        if (static::find($d['maHangHoa'])) {
            return 'Mã hàng hóa đã tồn tại.';
        }
        return null;
    }

    public static function create(array $d): void
    {
        static::exec(
            'INSERT INTO HangHoa (maHangHoa, tenHangHoa, donViTinh) VALUES (?, ?, ?)',
            [$d['maHangHoa'], $d['tenHangHoa'], $d['donViTinh']]
        );
    }

    /** Tìm kiếm có phân trang: trả về [các dòng, tổng số]. */
    public static function search(string $keyword, int $page, int $perPage): array
    {
        $like = '%' . $keyword . '%';
        $total = (int)static::one('SELECT COUNT(*) AS c FROM HangHoa WHERE tenHangHoa LIKE ? OR maHangHoa LIKE ?', [$like, $like])['c'];
        $rows = static::all(
            'SELECT * FROM HangHoa WHERE tenHangHoa LIKE ? OR maHangHoa LIKE ? ORDER BY maHangHoa LIMIT ? OFFSET ?',
            [$like, $like, $perPage, ($page - 1) * $perPage]
        );
        return [$rows, $total];
    }
}
```

### 7.3. Bước 3 — Route (`routes/web.php`)

```php
$router->get('hang-hoa/create',  [HangHoaController::class, 'create'], 'hang-hoa', 'create');
$router->post('hang-hoa/store',  [HangHoaController::class, 'store'],  'hang-hoa', 'create');
```

### 7.4. Bước 4 — Controller (`app/Controllers/HangHoaController.php`)

```php
use App\Models\HangHoa;
use App\Models\ThongBao;
use Core\Auth;
use Core\Request;

public function create(): void
{
    $this->render('hang-hoa/create', ['old' => [], 'error' => ''], [
        'title' => 'Thêm thông tin hàng hóa', 'module' => 'hang-hoa',
        'breadcrumb' => [['Hàng hóa', 'hang-hoa'], ['Thêm thông tin hàng hóa', null]],
    ]);
}

public function store(): void
{
    $data = [
        'maHangHoa'  => Request::post('maHangHoa'),
        'tenHangHoa' => Request::post('tenHangHoa'),
        'donViTinh'  => Request::post('donViTinh'),
    ];
    if ($error = HangHoa::validate($data)) {
        $this->render('hang-hoa/create', ['old' => $data, 'error' => $error], [
            'title' => 'Thêm thông tin hàng hóa', 'module' => 'hang-hoa',
            'breadcrumb' => [['Hàng hóa', 'hang-hoa'], ['Thêm thông tin hàng hóa', null]],
        ]);
        return;
    }
    HangHoa::create($data);
    $this->flash('success', 'Đã thêm hàng hóa ' . $data['tenHangHoa'] . '.');
    $this->redirect('hang-hoa');
}
```

### 7.5. Bước 5 — View (`app/Views/hang-hoa/create.php`)

```php
<?php /** Biến: $old (giá trị đã nhập), $error. */ ?>
<?php if ($error !== '') { render_alert('error', $error); } ?>
<form method="post" action="<?= e(route('hang-hoa/store')) ?>" class="card" data-validate novalidate>
    <?= csrf_field() ?>
    <div class="field">
        <label for="maHangHoa">Mã hàng hóa</label>
        <input id="maHangHoa" name="maHangHoa" value="<?= e($old['maHangHoa'] ?? '') ?>" required>
    </div>
    <!-- các trường còn lại tương tự -->
    <button type="submit" class="btn btn-primary">Lưu</button>
    <a class="btn" href="<?= e(route('hang-hoa')) ?>">Hủy</a>
</form>
```

### 7.6. Gửi thông báo cho người dùng khác

```php
// Sau khi Nhân viên kho tiếp nhận hàng: báo cho QC kiểm tra
ThongBao::notifyRole(ROLE_INVENTORY, 'Có lô hàng cần kiểm tra chất lượng',
    'Lô ' . $maLo . ' vừa được tiếp nhận.', 'chat-luong', 'warning', Auth::staffId());
```

Đích của thông báo là **route** (có thể kèm tham số, ví dụ `lo-hang/detail?id=LH-001`). Người nhận chỉ được chuyển tới trang đó nếu có quyền; nếu không, hệ thống ở lại danh sách thông báo và hiện lời giải thích.

### 7.7. Giao diện danh sách có tìm kiếm + phân trang

```php
// Controller
$q = Request::get('q'); $page = max(1, Request::int('page', 1)); $per = (int)config('per_page', 10);
[$rows, $total] = HangHoa::search($q, $page, $per);
$this->render('hang-hoa/index', compact('rows', 'total', 'page', 'per'), [...]);

// View
render_search_form(['q' => ['label' => 'Từ khóa']], $_GET);
echo '<div class="card">';
render_data_table(['maHangHoa' => 'Mã', 'tenHangHoa' => 'Tên', 'donViTinh' => 'Đơn vị'], $rows, [
    'stt' => true,
    'actions' => fn($r) => can('hang-hoa', 'edit')
        ? '<a class="btn" href="' . e(route('hang-hoa/edit', ['id' => $r['maHangHoa']])) . '">Sửa</a>' : '',
]);
render_pagination($page, $total, $per);
echo '</div>';
```

Thao tác thay đổi dữ liệu từ danh sách (ngừng sử dụng, duyệt...) dùng **form POST** nhỏ kèm `csrf_field()` và hộp xác nhận (`render_confirm_modal`, thuộc tính `data-confirm`), không dùng liên kết GET.

---

## 8. Yêu cầu triển khai theo module

Quy ước cột "Route POST cần thêm": đặt cùng `module`/`action` với trang tương ứng. "Bảng gợi ý" là đề xuất để nhóm đối chiếu với từ điển khái niệm; **nghiệp vụ chi tiết từng UC lấy theo đặc tả UC của nhóm**, tài liệu này chỉ nêu yêu cầu kỹ thuật chung và các điểm chặn bắt buộc.

### 8.1. Hàng hóa (`HangHoaController` — UC01–UC04)

- **Trang:** `hang-hoa` (tra cứu), `create`, `edit`, `stop-using`.
- **Route POST cần thêm:** `hang-hoa/store` (create), `hang-hoa/update` (edit), `hang-hoa/stop` (stop).
- **Bảng gợi ý:** `HangHoa` (mã, tên, nhóm hàng, đơn vị tính, trạng thái Đang sử dụng/Ngừng sử dụng).
- **Yêu cầu:**
  - Mã hàng hóa duy nhất; kiểm tra trường bắt buộc.
  - Tra cứu theo mã/tên/nhóm/trạng thái, có phân trang.
  - Ngừng sử dụng là **xóa mềm** (đổi trạng thái), không xóa bản ghi; không cho ngừng sử dụng hàng còn tồn kho hoặc đang có yêu cầu xuất kho chưa hoàn tất (hoặc hiện cảnh báo rõ theo đặc tả UC03).
  - Hàng "Ngừng sử dụng" không xuất hiện trong danh sách chọn ở tiếp nhận/nhập/xuất.
- **Nghiệm thu:** chỉ Quản lý kho thêm/sửa/ngừng; Nhân viên kho chỉ tra cứu; trùng mã bị từ chối với thông báo rõ.

### 8.2. Lô hàng (`LoHangController` — UC05)

- **Trang:** `lo-hang` (tra cứu), `lo-hang/detail?id=`.
- **Bảng gợi ý:** `LoHang` (mã lô, hàng hóa, nhà cung cấp, số lượng, ngày sản xuất, hạn sử dụng, trạng thái chất lượng, trạng thái lưu kho).
- **Yêu cầu:** tra cứu theo mã lô/hàng hóa/hạn sử dụng/trạng thái; trang chi tiết hiển thị lịch sử (tiếp nhận, kiểm tra chất lượng, nhập kho, vị trí, điều chuyển, xuất kho) của lô. Lô được tạo ở UC06, không có trang tạo riêng.
- **Nghiệm thu:** `detail` với `id` không tồn tại trả thông báo "không tìm thấy" (không lỗi PHP).

### 8.3. Tiếp nhận hàng (`TiepNhanController` — UC06)

- **Trang:** `tiep-nhan` (danh sách phiếu giao hàng chờ tiếp nhận), `tiep-nhan/create`.
- **Route POST cần thêm:** `tiep-nhan/store` (create).
- **Bảng gợi ý:** `PhieuGiaoNCC`, `PhieuYeuCauNhapKho`, `LoHang`.
- **Yêu cầu:** đối chiếu phiếu giao của nhà cung cấp với hàng thực nhận; ghi nhận chênh lệch số lượng; tạo lô hàng ở trạng thái **chờ kiểm tra chất lượng**; thao tác trong một giao dịch.
- **Thông báo:** gửi vai trò QC (`ROLE_INVENTORY`) "Có lô hàng cần kiểm tra chất lượng" → route `chat-luong`.
- **Nghiệm thu:** lô vừa tiếp nhận xuất hiện ở danh sách chờ kiểm tra của QC; Quản lý chỉ xem.

### 8.4. Kiểm tra chất lượng (`ChatLuongController` — UC07, UC08, UC09)

- **Trang:** `chat-luong` (lô cần kiểm tra), `record-result` (ghi nhận), `confirm-result` (xác nhận).
- **Route POST cần thêm:** `chat-luong/record-result/save` (action `record`), `chat-luong/confirm-result/save` (action `confirm`).
- **Bảng gợi ý:** `KetQuaKiemTra` (lô, tiêu chí, kết quả, người kiểm tra, thời điểm, người xác nhận).
- **Yêu cầu:** kết quả gồm các trạng thái đạt / không đạt (xử lý lô không đạt chuyển sang luồng hàng bất thường theo đặc tả); chỉ lô ở trạng thái "chờ kiểm tra" mới được ghi nhận; chỉ kết quả đã ghi nhận mới được xác nhận; một lô chỉ xác nhận một lần.
- **Thông báo:** sau khi ghi nhận → Quản lý (`ROLE_MANAGER`) "Có lô hàng chờ xác nhận kết quả kiểm tra" → `chat-luong/confirm-result`; sau khi xác nhận đạt → Nhân viên kho "Lô hàng đã đạt, có thể nhập kho" → `nhap-kho`.
- **Nghiệm thu:** lô chưa được xác nhận đạt thì không thể lập phiếu nhập kho.

### 8.5. Nhập kho (`NhapKhoController` — UC10, UC11)

- **Trang:** `nhap-kho`, `nhap-kho/create`, `nhap-kho/history`.
- **Route POST cần thêm:** `nhap-kho/store` (create).
- **Bảng gợi ý:** `PhieuNhapKho`, `ChiTietPhieuNhapKho`, cập nhật `LoHang`/tồn kho.
- **Yêu cầu:** chỉ nhập lô đã xác nhận đạt; sinh mã phiếu duy nhất (VD `PNK` + năm + số thứ tự); cập nhật trạng thái lô và tồn kho trong **một giao dịch**; lịch sử tra cứu theo khoảng ngày, hàng hóa, lô, người lập.
- **Nghiệm thu:** nhập trùng một lô hai lần bị chặn; số liệu tồn tăng đúng một lần.

### 8.6. Vị trí lưu trữ (`ViTriLuuTruController` — UC12–UC15)

- **Trang:** `vi-tri-luu-tru`, `create`, `edit`, `stop-using`.
- **Route POST cần thêm:** `vi-tri-luu-tru/store`, `update`, `stop`.
- **Bảng gợi ý:** `Kho`, `KhuVuc`, `ViTriLuuTru` (kho → khu vực → vị trí; sức chứa; trạng thái).
- **Yêu cầu:** mã vị trí duy nhất trong kho; không ngừng sử dụng vị trí đang chứa hàng; sửa sức chứa không thấp hơn lượng đang chứa.
- **Nghiệm thu:** chỉ Quản lý kho thêm/sửa/ngừng; Nhân viên kho tra cứu.

### 8.7. Gán lô hàng vào vị trí (`GanLoHangController` — UC16)

- **Trang:** `gan-lo-hang`. **Route POST cần thêm:** `gan-lo-hang/assign` (action `assign`, đã có trong ma trận).
- **Bảng gợi ý:** `LuuKho` (lô, vị trí, số lượng, thời điểm).
- **Yêu cầu:** chỉ gán lô đã nhập kho; kiểm tra vị trí đang sử dụng và còn sức chứa; một lô có thể chia nhiều vị trí, tổng số lượng không vượt số lượng lô.

### 8.8. Điều chuyển nội bộ (`DieuChuyenController` — UC17)

- **Trang:** `dieu-chuyen`, `dieu-chuyen/create`. **Route POST cần thêm:** `dieu-chuyen/store`.
- **Bảng gợi ý:** `PhieuDieuChuyen`, `ChiTietDieuChuyen`.
- **Yêu cầu:** vị trí đi ≠ vị trí đến; số lượng chuyển ≤ số lượng đang có ở vị trí đi; vị trí đến còn sức chứa; cập nhật hai vị trí trong một giao dịch và ghi lịch sử di chuyển.

### 8.9. Xuất kho (`XuatKhoController` — UC18–UC23)

- **Trang:** `xuat-kho/yeu-cau` (tra cứu), `yeu-cau/create`, `yeu-cau/edit`, `yeu-cau/approve`, `xuat-kho/fefo`, `xuat-kho/phieu-xuat`.
- **Route POST cần thêm:** `yeu-cau/store`, `yeu-cau/update` (action `request`), `yeu-cau/approve/save` (action `approve`, gồm duyệt/từ chối kèm lý do), `phieu-xuat/store` (action `issue`).
- **Bảng gợi ý:** `YeuCauXuatKho`, `ChiTietYeuCauXuatKho`, `PhieuXuatKho`, `ChiTietPhieuXuatKho`.
- **Yêu cầu:**
  - Luồng trạng thái yêu cầu: *chờ duyệt → đã duyệt / bị từ chối → đã xuất*. Chỉ sửa yêu cầu ở trạng thái "chờ duyệt".
  - **Đề xuất lô theo FEFO (First Expired – First Out):** với mỗi hàng hóa trong yêu cầu, gợi ý các lô **đã nhập kho, đang còn tồn, chưa hết hạn** theo thứ tự **hạn sử dụng gần nhất trước**; nếu lô đầu không đủ thì lấy tiếp lô kế tiếp cho đủ số lượng. Nhân viên kho được chọn khác đề xuất nhưng phải ghi lý do.
  - Phiếu xuất chỉ lập từ yêu cầu **đã duyệt**; trừ tồn trong **một giao dịch**, không để tồn âm; không xuất lô đã hết hạn.
- **Thông báo:** lập yêu cầu → Quản lý "Yêu cầu xuất kho chờ phê duyệt" → `xuat-kho/yeu-cau/approve`; duyệt xong → người lập yêu cầu "Yêu cầu xuất kho đã được duyệt" → `xuat-kho/phieu-xuat`.
- **Nghiệm thu:** Quản lý không lập được yêu cầu/phiếu; Nhân viên kho không duyệt được; kiểm tra thứ tự FEFO bằng dữ liệu thử có ≥ 3 lô khác hạn.

### 8.10. Kiểm kê (`KiemKeController` — UC24–UC27)

- **Trang:** `kiem-ke`, `create`, `record-result`, `reconcile`, `handle-discrepancy`.
- **Route POST cần thêm:** `kiem-ke/store` (create), `kiem-ke/record-result/save` (record), `kiem-ke/reconcile/save` (reconcile), `kiem-ke/handle-discrepancy/save` (handle).
- **Bảng gợi ý:** `BienBanKiemKe`, `ChiTietKiemKe` (số lượng hệ thống, số lượng thực tế, chênh lệch, hướng xử lý).
- **Yêu cầu:** chụp số liệu hệ thống tại thời điểm lập biên bản; đối chiếu tự tính chênh lệch; chênh lệch phải có phương án xử lý (điều chỉnh tồn có ghi lý do, hoặc chuyển sang hàng bất thường); điều chỉnh tồn trong một giao dịch và ghi nhật ký; biên bản đã đóng không sửa.
- **Thông báo:** lập biên bản → nhân viên kiểm kê "Biên bản kiểm kê mới" → `kiem-ke`.

### 8.11. Hàng bất thường (`HangBatThuongController` — UC28)

- **Trang:** `hang-bat-thuong`. **Route POST cần thêm:** `hang-bat-thuong/handle` (action `handle`, đã có trong ma trận).
- **Bảng gợi ý:** `HangBatThuong` (nguồn phát sinh: kiểm tra chất lượng / kiểm kê / hết hạn, lô, số lượng, hướng xử lý, trạng thái).
- **Yêu cầu:** danh sách tự động nhận hàng phát sinh từ UC09 (không đạt), UC27 (chênh lệch) và lô sắp/đã hết hạn; ghi nhận hướng xử lý (trả nhà cung cấp, hủy, giảm giá...), có người xử lý và thời điểm.

### 8.12. Tồn kho (`TonKhoController` — UC29)

- **Trang:** `ton-kho`.
- **Yêu cầu:** tra cứu theo hàng hóa, lô, kho/khu vực/vị trí, nhóm hàng, hạn sử dụng; hiển thị cảnh báo lô sắp hết hạn (thêm khóa cấu hình số ngày cảnh báo vào `config/app.php`); chỉ đọc. Dùng truy vấn tổng hợp trong Model, không tính trong View.

### 8.13. Báo cáo (`BaoCaoController` — UC30)

- **Trang:** `bao-cao` (xem báo cáo tồn kho), `bao-cao/inventory`, `bao-cao/expiry-fefo`.
- **Yêu cầu:** báo cáo tồn kho theo kho/nhóm hàng/thời điểm; báo cáo hạn sử dụng theo FEFO (nhóm theo mức: đã hết hạn, ≤ 7 ngày, ≤ 30 ngày...); chỉ đọc; xuất Excel/PDF nếu đặc tả yêu cầu. Số liệu Tổng quan (tổng số hàng hóa, tổng tồn, hàng sắp hết hạn, hàng bất thường, biểu đồ tồn theo nhóm hàng, biến động 7 ngày, hoạt động gần đây — theo ảnh trong "Giao diện quản lý kho") bổ sung vào `DashboardController` + `app/Views/dashboard/index.php` qua Model thống kê, hiển thị theo quyền vai trò.
- **Thông báo:** khi có báo cáo định kỳ mới → Ban giám đốc `ROLE_DIRECTOR` → `bao-cao`.

### 8.14. Quản lý tài khoản (`TaiKhoanController` — chỉ Quản trị viên)

- **Trang:** `tai-khoan`. **Route cần thêm:** tạo, sửa, khóa/mở khóa, đặt lại mật khẩu (POST).
- **Yêu cầu:** tên đăng nhập duy nhất; mật khẩu băm `password_hash`; yêu cầu độ mạnh tối thiểu (≥ 8 ký tự, có chữ hoa/thường và số); không cho khóa/xóa chính mình; không cho hạ quyền tài khoản quản trị cuối cùng; gán vai trò theo `ROLE_FROM_DB`.

### 8.15. Thứ tự triển khai đề xuất

1. Hàng hóa → Vị trí lưu trữ (dữ liệu nền, không phụ thuộc module khác)
2. Tiếp nhận hàng → Kiểm tra chất lượng → Nhập kho (một luồng liên tục, kèm thông báo)
3. Gán lô hàng → Điều chuyển → Tồn kho
4. Xuất kho (yêu cầu → duyệt → FEFO → phiếu xuất)
5. Kiểm kê → Hàng bất thường
6. Báo cáo + số liệu Tổng quan
7. Quản lý tài khoản

---

## 9. Kiểm thử và nghiệm thu

### 9.1. Tiêu chí chung cho mọi Use Case

- [ ] Route khai báo đúng `module/action`; vai trò không có quyền nhận 403; chưa đăng nhập bị chuyển về trang đăng nhập.
- [ ] Mọi truy vấn dùng tham số `?`; mọi đầu ra HTML qua `e()`.
- [ ] Mọi form POST có `csrf_field()`; không có thao tác ghi bằng GET.
- [ ] Dữ liệu đầu vào được kiểm tra ở server; lỗi hiển thị rõ và giữ lại giá trị đã nhập.
- [ ] Thao tác ghi nhiều bảng nằm trong giao dịch; tồn kho không âm.
- [ ] Sau POST thành công: `flash` + chuyển hướng (không render lại, tránh gửi lại form khi F5).
- [ ] Nút/mục chức năng ẩn đúng theo vai trò (menu, Tổng quan, nút trong trang).
- [ ] Thông báo gửi đúng người, đúng route đích; người nhận không có quyền thì không bị đẩy vào trang 403.
- [ ] Không có cảnh báo/Notice/Deprecated của PHP khi bật `debug`.

### 9.2. Kiểm thử thủ công theo vai trò

| Vai trò | Phải thấy ở sidebar | Phải bị chặn (403) |
|---|---|---|
| Quản trị viên | Tổng quan + 8 mục: Quản lý hàng hóa (Hàng hóa, Lô hàng), Nhập hàng vào kho (Tiếp nhận, Nhập kho), Kiểm tra chất lượng, Quản lý kho (Vị trí lưu trữ, Gán lô hàng, Điều chuyển, Tồn kho), Quản lý xuất kho, Quản lý kiểm kê (Kiểm kê kho, Hàng bất thường), Báo cáo, Hệ thống (Tài khoản, Thông báo) | — |
| Quản lý kho | Quản lý hàng hóa, Quản lý vị trí lưu trữ, Xuất kho, Kiểm kê, Xử lý hàng bất thường, Báo cáo, Thông báo | `tai-khoan`, `ton-kho` không có mục menu nhưng vẫn mở được qua Tổng quan |
| Nhân viên kho | Nhập hàng vào kho (Tiếp nhận, Nhập kho), Lưu kho, Điều chuyển nội bộ, Quản lý xuất kho, Tra cứu (Hàng hóa, Lô hàng, Tồn kho), Thông báo | `chat-luong`, `kiem-ke`, `bao-cao`, `tai-khoan` (nút Thêm/Phê duyệt… không hiện; khi cài đặt POST phải trả 403) |
| NV kiểm kê/QC | Kiểm tra chất lượng, Nhập kho (mở trang lịch sử nhập kho), Thông báo | `hang-hoa`, `xuat-kho/yeu-cau`, `bao-cao`, `tai-khoan` |
| Ban giám đốc | Báo cáo, Thông báo | mọi trang nghiệp vụ khác |

### 9.3. Kiểm thử bảo mật tối thiểu

- Gửi form POST với `csrf_token` sai/thiếu → bị từ chối.
- Đổi `id` trên URL sang bản ghi của người khác (thông báo, phiếu) → không xem/sửa được.
- Nhập `<script>alert(1)</script>` vào các ô văn bản → hiển thị dạng văn bản, không chạy.
- Nhập `' OR 1=1 --` vào ô tìm kiếm → không lỗi SQL, không lộ dữ liệu.
- Mở `config/database.php`, `core/...` qua trình duyệt → không truy cập được (kiểm tra DocumentRoot).

### 9.4. Lỗi thường gặp

| Hiện tượng | Nguyên nhân / cách xử lý |
|---|---|
| "Phiên làm việc đã hết hạn" khi đăng nhập | Mã CSRF không khớp session: tải lại trang (Ctrl+F5), truy cập bằng **một** địa chỉ nhất quán (`localhost` hoặc `127.0.0.1`), kiểm tra thư mục lưu session của PHP có ghi được |
| Mọi URL (trừ `/`) báo 404 trên Apache | `mod_rewrite` chưa bật hoặc `AllowOverride` chưa là `All`; tạm dùng `'url_rewrite' => false` |
| Mất CSS/JS | `DocumentRoot` không trỏ đúng; mở qua `.htaccess` gốc thì URL có `/public` |
| "Không kết nối được cơ sở dữ liệu" | Chưa import `schema.sql` hoặc sai `config/database.php` |
| Giờ thông báo lệch vài giờ | Múi giờ MySQL khác PHP: đặt `default-time-zone='+07:00'` cho MySQL |
| Đăng xuất báo 405 | Đăng xuất là **POST** (nút trên thanh trên cùng); không mở `/logout` bằng liên kết |

---

## 10. Phụ lục

### 10.1. Chuyển đổi từ cấu trúc cũ sang MVC

| Cấu trúc cũ | MVC |
|---|---|
| `login.php`, `logout.php`, `index.php` | `AuthController`, route `''` + `login` + `logout` |
| `modules/<module>/<trang>.php` | `<Module>Controller::<phương_thức>` + `app/Views/<module>/<trang>.php` |
| `actions/*` (xử lý POST) | Phương thức POST trong Controller (`store`, `update`, `stop`...) |
| `includes/auth.php`, `permission.php` | `core/Auth.php`, `core/Permission.php`, `config/permissions.php` |
| `includes/notifications.php` | Model `ThongBao` + `ThongBaoController` |
| `includes/helpers.php` | `core/helpers.php` (dùng chung) + `core/Database.php` (CSDL) |
| `includes/components/*.php` | `app/Helpers/*.php` (hàm) và `app/Views/layouts|components` (mẫu giao diện) |
| `includes/menus/*` | `config/menu.php`, `config/menu-layout.php`, `core/Menu.php` |
| `assets/` | `public/assets/` |
| Đường dẫn dạng `modules/x/y.php` (kể cả trong `ThongBao.duongDan`) | Route `x/y`; dữ liệu cũ vẫn dùng được (`ThongBao::safeLink` tự đổi) |

### 10.2. Hàm dùng chung thường dùng

| Hàm | Công dụng |
|---|---|
| `e($s)` | Escape HTML |
| `route('x/y', ['id' => 1])` | URL của route; `asset('css/a.css')` URL tệp tĩnh |
| `redirect('route')` | Chuyển hướng |
| `csrf_field()` | Trường CSRF cho form POST |
| `can('module', 'action')` | Vai trò hiện tại có quyền không |
| `can_access_route('route')` | Vai trò hiện tại mở được route không |
| `action_buttons([...])` | In các nút chỉ khi có quyền |
| `current_user()`, `current_role()` | Người dùng/vai trò đang đăng nhập |
| `flash_set()` / `$this->flash()` | Thông báo sau chuyển hướng |
| `partial('layouts/x', [...])` | Nhúng mẫu giao diện nhỏ |
| `render_data_table`, `render_search_form`, `render_pagination`, `render_alert`, `render_breadcrumb`, `render_confirm_modal` | Khối giao diện dùng lại (`app/Helpers`) |

### 10.3. Phương thức của Model cơ sở

`static::all($sql, $params)`, `static::one($sql, $params)`, `static::exec($sql, $params)` (trả số dòng ảnh hưởng), `static::insertId()`, `static::transaction(fn)`.

### 10.4. Phương thức của Controller cơ sở

`$this->render($view, $data, $page, $layout)`, `$this->redirect($route)`, `$this->flash($type, $msg)`, `$this->json($data, $status)`. Cấu hình `$page`: `title`, `subtitle`, `module`, `breadcrumb` (`[[nhãn, route|null], ...]`), `actions` (HTML nút), `css`.
