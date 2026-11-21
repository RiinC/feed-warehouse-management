# FeedWare

**Hệ thống quản lý kho nhà máy sản xuất thức ăn chăn nuôi**

FeedWare là ứng dụng web nội bộ được xây dựng bằng PHP thuần theo mô hình MVC, hỗ trợ số hóa toàn bộ quy trình từ tiếp nhận hàng, kiểm tra chất lượng, nhập kho, lưu trữ, điều chuyển, xuất kho, kiểm kê đến báo cáo tồn kho và xử lý hàng bất thường.

Hệ thống sử dụng MySQL/MariaDB và giao diện tiếng Việt, không phụ thuộc framework PHP hay package manager bên ngoài.

## Mục lục

- [Tính năng chính](#tính-năng-chính)
- [Vai trò và phân quyền](#vai-trò-và-phân-quyền)
- [Công nghệ sử dụng](#công-nghệ-sử-dụng)
- [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
- [Cài đặt](#cài-đặt)
- [Chạy ứng dụng](#chạy-ứng-dụng)
- [Tài khoản mẫu](#tài-khoản-mẫu)
- [Cấu trúc thư mục](#cấu-trúc-thư-mục)
- [Cấu hình](#cấu-hình)
- [Bảo mật](#bảo-mật)
- [Hướng dẫn sử dụng nhanh](#hướng-dẫn-sử-dụng-nhanh)
- [Khắc phục sự cố](#khắc-phục-sự-cố)

## Tính năng chính

Hệ thống triển khai đầy đủ các use case:

### Quản lý dữ liệu nền

- Thêm, sửa, tra cứu và ngừng sử dụng hàng hóa.
- Tra cứu thông tin lô hàng và xem lịch sử của từng lô.
- Thêm, sửa, tra cứu và ngừng sử dụng vị trí lưu trữ.
- Phân cấp kho, khu vực và vị trí lưu trữ.

### Quy trình nhập hàng

- Tiếp nhận hàng theo phiếu giao của nhà cung cấp.
- Ghi nhận chênh lệch số lượng thực nhận.
- Tạo lô hàng và chuyển lô sang trạng thái chờ kiểm tra chất lượng.
- Ghi nhận và xác nhận kết quả kiểm tra đạt hoặc không đạt.
- Lập phiếu nhập kho cho lô đã được xác nhận đạt.
- Tra cứu lịch sử nhập kho theo khoảng thời gian, hàng hóa, lô hoặc người lập.

### Quản lý tồn kho

- Gán lô hàng vào vị trí lưu trữ.
- Kiểm tra sức chứa trước khi gán hoặc điều chuyển.
- Điều chuyển hàng giữa các vị trí trong kho.
- Tra cứu tồn kho theo hàng hóa, lô, kho, khu vực và vị trí.
- Cảnh báo lô sắp hết hạn và hàng đã hết hạn.
- Cập nhật tồn kho trong giao dịch CSDL để bảo đảm số liệu nhất quán.

### Quy trình xuất kho

- Lập và sửa yêu cầu xuất kho.
- Phê duyệt hoặc từ chối yêu cầu kèm lý do.
- Đề xuất các lô xuất theo nguyên tắc FEFO (First Expired – First Out).
- Lập phiếu xuất kho từ yêu cầu đã được duyệt.
- Ngăn xuất lô hết hạn hoặc làm tồn kho âm.

### Kiểm kê và hàng bất thường

- Lập biên bản kiểm kê và chụp số liệu tồn tại thời điểm lập.
- Ghi nhận số lượng thực tế.
- Tính toán và đối chiếu chênh lệch.
- Xử lý chênh lệch bằng điều chỉnh tồn hoặc chuyển sang hàng bất thường.
- Theo dõi và ghi nhận hướng xử lý hàng bất thường.

### Báo cáo và thông báo

- Tổng quan dữ liệu kho theo vai trò.
- Báo cáo tồn kho theo kho, nhóm hàng và thời điểm.
- Báo cáo hạn sử dụng theo các mức đã hết hạn và sắp hết hạn.
- Chuông thông báo, danh sách thông báo và đánh dấu đã đọc.
- Tự cập nhật thông báo định kỳ.
- Gửi thông báo cho từng người dùng hoặc theo vai trò.

## Vai trò và phân quyền

| Vai trò | Phạm vi chính |
|---|---|
| Quản trị viên | Toàn quyền quản trị hệ thống, tài khoản, nhân viên và nghiệp vụ |
| Quản lý kho | Quản lý hàng hóa, vị trí, nhập/xuất kho, kiểm kê, hàng bất thường và báo cáo |
| Nhân viên kho | Tiếp nhận hàng, nhập kho, gán lô, điều chuyển, xuất kho và tra cứu kho |
| Nhân viên kiểm kê / QC | Kiểm tra chất lượng, ghi nhận kết quả, nhập kho và kiểm kê |
| Ban giám đốc | Xem tổng quan, tồn kho và báo cáo |

Quyền truy cập được kiểm tra ở Router và gắn với từng route. Giao diện chỉ ẩn các nút hoặc mục không được phép; không thay thế kiểm tra phân quyền ở backend.

Ma trận quyền chi tiết được khai báo tại [`config/permissions.php`](config/permissions.php).

## Công nghệ sử dụng

- **Ngôn ngữ:** PHP 7.4+
- **Cơ sở dữ liệu:** MySQL 5.7+ / MariaDB 10.4+
- **Web server:** Apache, Nginx hoặc PHP built-in server
- **Kiến trúc:** MVC tự xây dựng, Front Controller và Router
- **Thư viện:** Không sử dụng framework hoặc thư viện PHP bên ngoài
- **Giao diện:** HTML, CSS và JavaScript thuần

## Yêu cầu hệ thống

- PHP 7.4 trở lên; khuyến nghị PHP 8.1 trở lên.
- Extension PHP `mysqli` và `mbstring`.
- MySQL 5.7+ hoặc MariaDB 10.4+.
- Apache với `mod_rewrite`, hoặc cấu hình tương đương trên Nginx.
- Trình duyệt web hỗ trợ HTML5.

## Cài đặt

### 1. Chuẩn bị mã nguồn

Đặt mã nguồn trong thư mục web, ví dụ XAMPP:

```text
C:\xampp\htdocs\feedware
```

### 2. Tạo cơ sở dữ liệu

Mở phpMyAdmin hoặc MySQL CLI và chạy lần lượt các tệp sau:

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed_data.sql
mysql -u root -p < database/thong-bao.sql
```

Các tệp SQL tạo và nạp dữ liệu cho cơ sở dữ liệu `feed_wms`.

Trong môi trường XAMPP có thể thay thế bằng trang phpMyAdmin: vào **Import**, chọn lần lượt ba tệp SQL theo thứ tự ở trên.

### 3. Cấu hình kết nối CSDL

Cập nhật [`config/database.php`](config/database.php) theo môi trường:

```php
'host'     => '127.0.0.1',
'port'     => 3306,
'database' => 'feed_wms',
'user'     => 'root',
'password' => '',
'charset'  => 'utf8mb4',
```

## Chạy ứng dụng

### Cách 1: PHP built-in server

Từ thư mục gốc của dự án:

```bash
php -S localhost:8000 -t public
```

Mở: `http://localhost:8000`

### Cách 2: Apache VirtualHost

Trỏ `DocumentRoot` tới thư mục `public` và bật `AllowOverride All`.

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

Sau đó truy cập `http://kho.local`.

### Cách 3: XAMPP/Laragon

Nếu không thay đổi `DocumentRoot`, truy cập:

```text
http://localhost/feedware/
```

Đường dẫn này sử dụng file `.htaccess` ở thư mục gốc để chuyển yêu cầu vào thư mục `public`.

### Cách 4: URL không có rewrite

Nếu web server không hỗ trợ URL rewrite, đặt trong [`config/app.php`](config/app.php):

```php
'url_rewrite' => false,
```

URL khi đó có dạng:

```text
index.php?r=hang-hoa
index.php?r=hang-hoa/create
```

## Tài khoản mẫu

| Vai trò | Tên đăng nhập | Mật khẩu |
|---|---|---|
| Quản trị viên | `admin` | `123456` |
| Ban giám đốc | `giamdoc`, `qlvanhanh` | `123456` |
| Quản lý kho | `qlkho01` … `qlkho06` | `123456` |
| Nhân viên kho | `kho01_nv01` … `kho06_nv02` | `123456` |
| Nhân viên QC / kiểm kê | `qc01`, `qc02`, `qc03` | `123456` |

> Trước khi đưa hệ thống vào môi trường thật, cần đổi mật khẩu tài khoản mẫu và đặt `debug => false`.

## Cấu trúc thư mục

```text
feedware/
├── app/
│   ├── Controllers/         # Điều hướng luồng xử lý theo từng module
│   ├── Models/              # Truy vấn CSDL và quy tắc nghiệp vụ
│   ├── Views/
│   │   ├── layouts/         # Layout chính, layout đăng nhập và sidebar
│   │   ├── components/      # Thành phần giao diện dùng lại
│   │   └── <module>/        # Giao diện từng chức năng
│   └── Helpers/             # Hàm dựng bảng, tìm kiếm, phân trang và cảnh báo
├── config/
│   ├── app.php              # Cấu hình ứng dụng
│   ├── database.php         # Cấu hình kết nối CSDL
│   ├── constants.php        # Vai trò và hằng số hệ thống
│   ├── permissions.php      # Ma trận phân quyền
│   ├── menu.php             # Danh mục chức năng
│   └── menu-layout.php      # Bố cục sidebar theo vai trò
├── core/
│   ├── bootstrap.php        # Khởi tạo ứng dụng
│   ├── Router.php           # Khớp route và kiểm tra truy cập
│   ├── Database.php         # Kết nối và truy vấn prepared statement
│   ├── Auth.php             # Phiên đăng nhập
│   ├── Permission.php       # Kiểm tra quyền
│   ├── View.php             # Render view và layout
│   ├── Request.php          # Đọc dữ liệu GET/POST
│   └── helpers.php          # Hàm dùng chung
├── database/
│   ├── schema.sql           # Cấu trúc cơ sở dữ liệu
│   ├── seed_data.sql        # Dữ liệu mẫu
│   └── thong-bao.sql        # Dữ liệu thông báo mẫu
├── docs/
│   ├── TAI-LIEU-TRIEN-KHAI.md
│   └── FEATURE_BACKLOG.md
├── public/
│   ├── index.php            # Front controller
│   ├── .htaccess            # Rewrite URL
│   └── assets/
│       ├── css/
│       ├── js/
│       └── images/
├── routes/
│   └── web.php              # Khai báo route
├── .htaccess
└── README.md
```

Thư mục `public` là DocumentRoot công khai. Các thư mục `app`, `core`, `config`, `database` và `routes` nằm ngoài DocumentRoot để hạn chế truy cập trực tiếp qua trình duyệt.

## Cấu hình

Các tùy chọn chính trong [`config/app.php`](config/app.php):

| Khóa | Ý nghĩa |
|---|---|
| `debug` | Hiện chi tiết lỗi PHP khi phát triển; phải đặt `false` khi triển khai thật |
| `base_url` | Để trống để tự nhận theo vị trí ứng dụng |
| `url_rewrite` | Bật URL đẹp hoặc chế độ `index.php?r=...` |
| `per_page` | Số dòng mặc định trên một trang |
| `timezone` | Múi giờ mặc định, mặc định `Asia/Ho_Chi_Minh` |
| `session_name` | Tên cookie phiên đăng nhập |

Khi chạy thực tế nên kiểm tra múi giờ PHP và MySQL:

```sql
SET GLOBAL time_zone = '+07:00';
```

## Bảo mật

- Truy vấn CSDL sử dụng prepared statement với tham số `?`.
- Nội dung người dùng hiển thị trong HTML được escape bằng hàm `e()`.
- Mọi form POST bắt buộc có mã CSRF.
- Thao tác thay đổi dữ liệu không sử dụng liên kết GET.
- Router kiểm tra đăng nhập, quyền route và mã CSRF.
- Mật khẩu được lưu bằng `password_hash()` và kiểm tra bằng `password_verify()`.
- Ghi nhiều bảng hoặc thay đổi tồn kho được thực hiện trong giao dịch CSDL.
- Số lượng tồn không được âm.
- Đường dẫn thông báo chỉ chuyển tới route hợp lệ theo quyền của người nhận.

## Hướng dẫn sử dụng nhanh

1. Đăng nhập bằng tài khoản phù hợp vai trò.
2. Mở **Tổng quan** để xem các chức năng được quyền sử dụng.
3. Quản lý hàng hóa, lô hàng và vị trí lưu trữ từ các module tương ứng.
4. Thực hiện luồng nhập hàng: tiếp nhận → kiểm tra chất lượng → nhập kho → gán vị trí.
5. Lập yêu cầu xuất kho → quản lý kho phê duyệt → nhân viên kho lập phiếu xuất theo FEFO.
6. Lập biên bản kiểm kê, ghi nhận số liệu thực tế và xử lý chênh lệch.
7. Theo dõi tồn kho, hàng bất thường, hạn sử dụng và báo cáo.

## Khắc phục sự cố

### Không kết nối được cơ sở dữ liệu

- Kiểm tra MySQL/MariaDB đang chạy.
- Xác nhận đã import đủ ba tệp SQL.
- Kiểm tra host, port, tài khoản và mật khẩu trong [`config/database.php`](config/database.php).

### Trang con hoặc URL đẹp bị 404

- Bật `mod_rewrite` của Apache.
- Đặt `AllowOverride All` cho thư mục ứng dụng.
- Kiểm tra file `public/.htaccess`.
- Hoặc đặt `url_rewrite => false` để dùng tham số `index.php?r=...`.

### Mất CSS hoặc JavaScript

- Kiểm tra `DocumentRoot` đang trỏ đến thư mục `public`.
- Đảm bảo thư mục `public/assets` nằm trong vị trí dự án.
- Truy cập bằng một địa chỉ nhất quán: `localhost` hoặc `127.0.0.1`.

### Phiên đăng nhập báo hết hạn

- Tải lại trang với `Ctrl + F5`.
- Kiểm tra thư mục session của PHP có quyền ghi.
- Sử dụng cùng một hostname trong suốt phiên làm việc.
- Kiểm tra mã CSRF và cấu hình session.

### Đăng xuất bị lỗi

Đăng xuất là thao tác POST, nên không nên mở `/logout` bằng địa chỉ trực tiếp hoặc liên kết GET.

