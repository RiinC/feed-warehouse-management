# Hệ thống quản lý kho thức ăn chăn nuôi

Ứng dụng PHP quản lý kho, tổ chức chức năng theo mã UC và phân quyền theo vai trò. Mỗi UC có thư mục triển khai riêng và cơ sở dữ liệu riêng; tài khoản, vai trò và quyền truy cập được lưu trong cơ sở dữ liệu dùng chung QLK00.

## Công nghệ

- PHP
- MySQL / MariaDB
- HTML, CSS và JavaScript
- XAMPP dùng để chạy cục bộ

## Chức năng và phân quyền

| Vai trò | Các UC được cấp quyền |
| --- | --- |
| Quản trị viên | UC06, UC10, UC17, UC23, UC24 |
| Quản lý kho | UC06, UC10, UC17, UC23, UC24 |
| Nhân viên kho | UC06, UC10, UC17 |

Các trang UC hiện là **khung giao diện mẫu**. Nhóm có thể triển khai danh sách, biểu mẫu, truy vấn và xử lý nghiệp vụ trực tiếp trong `index.php` và `form.php` của UC tương ứng.

## Cơ sở dữ liệu

| Database | Mục đích |
| --- | --- |
| QLK00 | Đăng nhập, nhân viên, vai trò và quyền |
| QLK01 | UC06 - Tiếp nhận hàng vào kho |
| QLK02 | UC10 - Nhập kho |
| QLK03 | UC17 - Điều chuyển nội bộ |
| QLK04 | UC23 - Xuất kho |
| QLK05 | UC24 - Kiểm kê |

Các file `database/QLK01.sql` đến `database/QLK05.sql` hiện tạo database tương ứng; nhóm có thể bổ sung schema bảng của UC trong file phù hợp. QLK00 có schema `NhanVien`, `TaiKhoan` và dữ liệu tài khoản mẫu.

## Cài đặt trên XAMPP

1. Clone hoặc tải repository vào thư mục `C:\xampp\htdocs\quanlykho`.
2. Mở XAMPP Control Panel và khởi động **Apache** và **MySQL**.
3. Mở phpMyAdmin tại `http://localhost/phpmyadmin`.
4. Import lần lượt các file `database/QLK00.sql`, `database/QLK01.sql`, `database/QLK02.sql`, `database/QLK03.sql`, `database/QLK04.sql`, `database/QLK05.sql`.
   - `QLK00.sql` tạo các bảng dùng cho đăng nhập/phân quyền và chèn tài khoản mẫu.
   - Các file QLK01–QLK05 tạo database riêng cho từng UC.
5. Kiểm tra thông tin kết nối trong `config.php`. Cấu hình mặc định cho XAMPP là `localhost`, người dùng `root`, mật khẩu để trống.
6. Truy cập `http://localhost/quanlykho/login.php`.

Nếu đặt ứng dụng ở thư mục khác dưới web root, cấu hình `BASE_URL` được nhận diện tự động. Cần đảm bảo tài khoản MySQL trong `config.php` có quyền truy cập và tạo các database QLK00–QLK05.

## Tài khoản mẫu

| Vai trò | Tên đăng nhập | Mật khẩu |
| --- | --- | --- |
| Quản trị viên | `admin` | `admin123` |
| Quản lý kho | `quanlykho` | `quanlykho123` |
| Nhân viên kho | `nhanvienkho` | `nhanvienkho123` |

Các mật khẩu mẫu được lưu bằng bcrypt trong QLK00. Ứng dụng cũng hỗ trợ mật khẩu MD5 cũ để chuyển đổi: khi người dùng đăng nhập thành công bằng mật khẩu MD5, ứng dụng tự cập nhật giá trị đó sang bcrypt. **Không tiếp tục dùng MD5 để lưu mật khẩu mới.**

## Cấu trúc thư mục

```text
quanlykho/
├── assets/                 # CSS và JavaScript dùng chung
├── database/               # Script SQL cho QLK00–QLK05
├── includes/               # Giao diện, xác thực và tiện ích dùng chung
├── quan-ly-kho/
│   ├── UC06_tiep_nhan/
│   ├── UC10_nhap_kho/
│   ├── UC17_dieu_chuyen/
│   ├── UC23_xuat_kho/
│   └── UC24_kiem_ke/
├── nhan-vien-kho/
│   ├── UC06_tiep_nhan/
│   ├── UC10_nhap_kho/
│   └── UC17_dieu_chuyen/
├── config.php              # Cấu hình ứng dụng và ánh xạ database
├── index.php               # Tổng quan sau đăng nhập
├── login.php               # Đăng nhập
└── logout.php              # Đăng xuất
```

### Bắt đầu triển khai một UC

1. Làm việc trong đúng thư mục vai trò và UC, ví dụ `quan-ly-kho/UC23_xuat_kho/`.
2. Hiện thực trang danh sách trong `index.php` và biểu mẫu trong `form.php`.
3. Thêm hoặc cập nhật schema UC trong file SQL tương ứng, ví dụ UC23 dùng `database/QLK04.sql`.
4. Kết nối database UC bằng ánh xạ đã khai báo trong `config.php`, ví dụ `$db = db(DB_UC23);`.
5. Giữ các bảng tài khoản, nhân viên và phân quyền ở QLK00; không tạo bản sao các bảng dùng chung trong database UC.

Các thư mục `quan-ly-kho/` và `nhan-vien-kho/` là hai bề mặt triển khai riêng theo vai trò. Nếu cần dùng chung logic giữa các UC hoặc vai trò, hãy thống nhất với nhóm trước khi chuyển logic đó sang `includes/`.

## Ghi chú bảo mật

- Đổi các thông tin đăng nhập mẫu trước khi triển khai ngoài môi trường phát triển.
- Không công khai mật khẩu, thông tin kết nối hoặc dữ liệu thật trong repository.
- Chỉ lưu mật khẩu bằng cơ chế băm an toàn như bcrypt; không lưu mật khẩu thuần hoặc dùng MD5 cho mật khẩu mới.
