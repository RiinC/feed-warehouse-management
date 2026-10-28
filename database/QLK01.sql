-- =====================================================================
-- QLK01 - CƠ SỞ DỮ LIỆU RIÊNG CỦA UC06 "Tiếp nhận hàng vào kho"  (Actor chính: Nhân viên kho)
-- Nguồn: báo cáo "Thiết kế cơ sở dữ liệu" (Tuần 09) + Từ điển lớp khái niệm.
--
-- QUY ƯỚC
--  * Bảng/cột/kiểu/ràng buộc/giá trị mặc định GIỮ NGUYÊN theo báo cáo (PascalCase / camelCase).
--  * SQL Server -> MySQL: NVARCHAR -> VARCHAR (utf8mb4) | GETDATE() -> CURRENT_TIMESTAMP |
--    FLOAT (SQL Server = 53 bit) -> DOUBLE.
--  * Cột/bảng lấy từ "Từ điển lớp khái niệm" (báo cáo chưa có): đánh dấu [TỪ ĐIỂN].
--  * Bảng/cột CHƯA có trong tài liệu nhưng UC06 bắt buộc phải có: đánh dấu [BỔ SUNG]
--    (NhaCungCap, các bảng ChiTiet*, các cột khóa ngoại của PhieuTiepNhan).
--  * Tài khoản/nhân viên nằm ở QLK00 (khác CSDL) nên maNhanVien ở đây KHÔNG có khóa ngoại.
--
-- Chạy lại nhiều lần được: chỉ xóa & tạo lại các bảng/view của file này, không đụng bảng khác.
-- Chạy: phpMyAdmin > Import  (hoặc: mysql -u root < QLK01.sql)
-- =====================================================================
CREATE DATABASE IF NOT EXISTS QLK01 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE QLK01;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW IF EXISTS vw_DongChungTu, vw_ChungTuChoTiepNhan, vw_ThongTinKeHoachSanXuat, vw_ThongTinViTriLuuTru,
    vw_ThongTinKhuVuc, vw_ThongTinKho, vw_DanhSachPhieuYeuCauNhapKho, vw_ChiTietPhieuGiaoNCC, vw_DanhSachLoHang, vw_ThongTinHangHoa;
DROP TABLE IF EXISTS ChiTietTiepNhan, PhieuTiepNhan, LoHang,
    ChiTietPhieuYeuCauNhapKho, PhieuYeuCauNhapKho, ChiTietPhieuGiaoNCC, PhieuGiaoNCC, NhaCungCap,
    ChiTietKeHoachSanXuat, KeHoachSanXuat, ChiTietDonHang, DonHang, HangHoa, ViTriLuuTru, KhuVuc, Kho;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. Kho -> KhuVuc -> ViTriLuuTru     (tiền điều kiện 4: vị trí lưu trữ "Đang sử dụng")
-- ---------------------------------------------------------------------
CREATE TABLE Kho (
    maKho     VARCHAR(20)  PRIMARY KEY,
    tenKho    VARCHAR(100) NOT NULL,
    loaiKho   VARCHAR(30)  NOT NULL,
    diaChi    VARCHAR(255) NOT NULL,
    trangThai VARCHAR(30)  NOT NULL DEFAULT 'Đang hoạt động',
    moTa      VARCHAR(255) NULL,
    CONSTRAINT CK_Kho_TrangThai CHECK (trangThai IN ('Đang hoạt động','Chờ kích hoạt','Bị khóa','Ngừng hoạt động','Đang bảo trì'))
) ENGINE=InnoDB;

CREATE TABLE KhuVuc (
    maKhuVuc   VARCHAR(20)  PRIMARY KEY,
    tenKhuVuc  VARCHAR(100) NOT NULL,
    loaiKhuVuc VARCHAR(30)  NOT NULL,
    trangThai  VARCHAR(30)  NOT NULL DEFAULT 'Đang hoạt động',
    moTa       VARCHAR(255) NULL,
    maKho      VARCHAR(20)  NOT NULL,
    CONSTRAINT CK_KhuVuc_TrangThai CHECK (trangThai IN ('Đang hoạt động','Đang bảo trì','Cách ly','Khóa kiểm kê','Ngừng hoạt động')),
    CONSTRAINT FK_KhuVuc_Kho FOREIGN KEY (maKho) REFERENCES Kho(maKho)
) ENGINE=InnoDB;

-- Báo cáo: mặc định N'Đang sử dụng' nhưng danh sách ghi "Đang hoạt động" -> danh sách thêm 'Đang sử dụng' (UC06 dùng giá trị này)
CREATE TABLE ViTriLuuTru (
    maViTri      VARCHAR(20)  PRIMARY KEY,
    tenViTri     VARCHAR(100) NOT NULL,
    sucChuaToiDa DOUBLE       NOT NULL,
    donViSucChua VARCHAR(10)  NOT NULL DEFAULT 'Kg',
    moTa         VARCHAR(255) NULL,
    trangThai    VARCHAR(30)  NOT NULL DEFAULT 'Đang sử dụng',
    maKhuVuc     VARCHAR(20)  NOT NULL,
    CONSTRAINT CK_ViTriLuuTru_SucChua    CHECK (sucChuaToiDa > 0),
    CONSTRAINT CK_ViTriLuuTru_DonVi      CHECK (donViSucChua IN ('Kg','Tấn','Bao','Pallet','m3')),
    CONSTRAINT CK_ViTriLuuTru_TrangThai  CHECK (trangThai IN ('Đang sử dụng','Đang hoạt động','Ngừng hoạt động','Bị khóa','Đang bảo trì','Khóa kiểm kê')),
    CONSTRAINT FK_ViTriLuuTru_KhuVuc FOREIGN KEY (maKhuVuc) REFERENCES KhuVuc(maKhuVuc)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. HangHoa                           (tiền điều kiện 3: hàng hóa "Đang sử dụng")
-- ---------------------------------------------------------------------
CREATE TABLE HangHoa (
    maHangHoa        VARCHAR(20)  PRIMARY KEY,
    tenHangHoa       VARCHAR(150) NOT NULL,
    loaiHangHoa      VARCHAR(100) NOT NULL,
    donViTinh        VARCHAR(10)  NOT NULL,
    moTa             VARCHAR(500) NULL,
    hanSuDungMacDinh INT          NULL,
    dieuKienBaoQuan  VARCHAR(300) NULL,
    trangThai        VARCHAR(30)  NOT NULL DEFAULT 'Đang sử dụng',
    CONSTRAINT CK_HangHoa_HanSuDung CHECK (hanSuDungMacDinh > 0),
    CONSTRAINT CK_HangHoa_TrangThai CHECK (trangThai IN ('Đang sử dụng','Ngừng sử dụng','Tạm ngưng'))
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. LoHang: cột của báo cáo + cột [TỪ ĐIỂN] (tenLoHang, soLuongTiepNhan, donViTinh, thoiGianTiepNhan,
--    trangThaiChatLuong, trangThaiPhanBo, ghiChu). Hậu điều kiện UC06: trangThaiChatLuong = 'Chờ kiểm tra chất lượng'
-- ---------------------------------------------------------------------
CREATE TABLE LoHang (
    maLoHang           VARCHAR(20)  PRIMARY KEY,
    maHangHoa          VARCHAR(20)  NOT NULL,
    soLo               VARCHAR(50)  NOT NULL,
    ngaySanXuat        DATETIME     NULL,
    hanSuDung          DATETIME     NOT NULL,
    soLuongTon         DOUBLE       NOT NULL DEFAULT 0,
    viTriLuuTru        VARCHAR(100) NULL,
    tinhTrang          VARCHAR(50)  NOT NULL DEFAULT 'Bình thường',
    trangThai          VARCHAR(30)  NOT NULL DEFAULT 'Đang sử dụng',
    tenLoHang          VARCHAR(255) NULL,                                           -- [TỪ ĐIỂN]
    soLuongTiepNhan    DOUBLE       NULL,                                           -- [TỪ ĐIỂN]
    donViTinh          VARCHAR(10)  NULL,                                           -- [TỪ ĐIỂN]
    thoiGianTiepNhan   DATETIME     NULL,                                           -- [TỪ ĐIỂN]
    trangThaiChatLuong VARCHAR(50)  NOT NULL DEFAULT 'Chờ kiểm tra chất lượng',     -- [TỪ ĐIỂN]
    trangThaiPhanBo    VARCHAR(50)  NOT NULL DEFAULT 'Chưa phân bổ',                -- [TỪ ĐIỂN]
    ghiChu             VARCHAR(500) NULL,                                           -- [TỪ ĐIỂN]
    CONSTRAINT CK_LoHang_SoLuongTon CHECK (soLuongTon >= 0),
    CONSTRAINT CK_LoHang_TinhTrang  CHECK (tinhTrang IN ('Bình thường','Chờ kiểm tra','Cách ly','Hư hỏng','Hết hạn')),
    CONSTRAINT CK_LoHang_TrangThai  CHECK (trangThai IN ('Đang sử dụng','Ngừng sử dụng')),
    CONSTRAINT FK_LoHang_HangHoa FOREIGN KEY (maHangHoa) REFERENCES HangHoa(maHangHoa)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. Nguồn giao hàng
-- ---------------------------------------------------------------------
CREATE TABLE NhaCungCap (                                   -- [BỔ SUNG] báo cáo chỉ tham chiếu NhaCungCap(maNCC)
    maNCC     VARCHAR(20)  PRIMARY KEY,
    tenNCC    VARCHAR(200) NOT NULL,
    trangThai VARCHAR(30)  NOT NULL DEFAULT 'Đang hoạt động'
) ENGINE=InnoDB;

CREATE TABLE DonHang (
    maDonHang      VARCHAR(15)  PRIMARY KEY,
    ngayDatHang    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ngayGiaoDuKien DATETIME     NULL,
    trangThai      VARCHAR(50)  NOT NULL DEFAULT 'Chờ xử lý',
    ghiChu         VARCHAR(500) NULL,
    CONSTRAINT CK_DonHang_NgayGiao  CHECK (ngayGiaoDuKien >= ngayDatHang),
    CONSTRAINT CK_DonHang_TrangThai CHECK (trangThai IN ('Chờ xử lý','Đang thực hiện','Đã hoàn tất','Đã hủy'))
) ENGINE=InnoDB;

-- [BỔ SUNG] báo cáo không có cột số lượng; cần để "cập nhật số lượng đã nhận trên Đơn hàng" (hậu điều kiện UC06)
CREATE TABLE ChiTietDonHang (
    maChiTiet     INT AUTO_INCREMENT PRIMARY KEY,
    maDonHang     VARCHAR(15) NOT NULL,
    maHangHoa     VARCHAR(20) NOT NULL,
    soLuongDat    DOUBLE      NOT NULL,
    soLuongDaNhan DOUBLE      NOT NULL DEFAULT 0,
    CONSTRAINT UQ_ChiTietDonHang UNIQUE (maDonHang, maHangHoa),
    CONSTRAINT CK_ChiTietDonHang_SoLuong CHECK (soLuongDat > 0),
    CONSTRAINT FK_ChiTietDonHang_DonHang FOREIGN KEY (maDonHang) REFERENCES DonHang(maDonHang) ON DELETE CASCADE,
    CONSTRAINT FK_ChiTietDonHang_HangHoa FOREIGN KEY (maHangHoa) REFERENCES HangHoa(maHangHoa)
) ENGINE=InnoDB;

CREATE TABLE KeHoachSanXuat (
    maKeHoach       VARCHAR(20)  PRIMARY KEY,
    ngayLap         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    thoiGianBatDau  DATETIME     NOT NULL,
    thoiGianKetThuc DATETIME     NOT NULL,
    trangThai       VARCHAR(30)  NOT NULL DEFAULT 'Chờ phê duyệt',
    ghiChu          VARCHAR(255) NULL,
    maDonHang       VARCHAR(20)  NOT NULL,
    CONSTRAINT CK_KeHoach_ThoiGian  CHECK (thoiGianBatDau <= thoiGianKetThuc),
    CONSTRAINT CK_KeHoach_TrangThai CHECK (trangThai IN ('Chờ phê duyệt','Đã phê duyệt','Bị từ chối','Đã lên lịch','Đang sản xuất','Tạm dừng','Hoàn thành','Đã hủy')),
    CONSTRAINT FK_KeHoach_DonHang FOREIGN KEY (maDonHang) REFERENCES DonHang(maDonHang)
) ENGINE=InnoDB;

CREATE TABLE ChiTietKeHoachSanXuat (                        -- [BỔ SUNG] như ChiTietDonHang
    maChiTiet      INT AUTO_INCREMENT PRIMARY KEY,
    maKeHoach      VARCHAR(20) NOT NULL,
    maHangHoa      VARCHAR(20) NOT NULL,
    soLuongKeHoach DOUBLE      NOT NULL,
    soLuongDaNhan  DOUBLE      NOT NULL DEFAULT 0,
    CONSTRAINT UQ_ChiTietKeHoach UNIQUE (maKeHoach, maHangHoa),
    CONSTRAINT CK_ChiTietKeHoach_SoLuong CHECK (soLuongKeHoach > 0),
    CONSTRAINT FK_ChiTietKeHoach_KeHoach FOREIGN KEY (maKeHoach) REFERENCES KeHoachSanXuat(maKeHoach) ON DELETE CASCADE,
    CONSTRAINT FK_ChiTietKeHoach_HangHoa FOREIGN KEY (maHangHoa) REFERENCES HangHoa(maHangHoa)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. Chứng từ giao hàng (tiền điều kiện 2): Phiếu giao hàng NCC / Phiếu yêu cầu nhập kho (nhập thành phẩm)
--    "Tình trạng" trong đặc tả = cột trangThai
-- ---------------------------------------------------------------------
CREATE TABLE PhieuGiaoNCC (
    maPhieuGiao  VARCHAR(20)  PRIMARY KEY,
    maNCC        VARCHAR(20)  NOT NULL,
    ngayGiao     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nguoiGiao    VARCHAR(100) NULL,
    soChungTuNCC VARCHAR(50)  NULL,
    trangThai    VARCHAR(30)  NOT NULL DEFAULT 'Chờ tiếp nhận',
    ghiChu       VARCHAR(500) NULL,
    CONSTRAINT CK_PhieuGiaoNCC_TrangThai CHECK (trangThai IN ('Chờ tiếp nhận','Đã tiếp nhận một phần','Đã tiếp nhận','Từ chối','Đã hủy')),
    CONSTRAINT FK_PhieuGiaoNCC_NhaCungCap FOREIGN KEY (maNCC) REFERENCES NhaCungCap(maNCC)
) ENGINE=InnoDB;

CREATE TABLE ChiTietPhieuGiaoNCC (                          -- [BỔ SUNG] dòng hàng của phiếu giao
    maChiTiet     INT AUTO_INCREMENT PRIMARY KEY,
    maPhieuGiao   VARCHAR(20) NOT NULL,
    maHangHoa     VARCHAR(20) NOT NULL,
    maDonHang     VARCHAR(15) NULL,                         -- đơn hàng được tiếp nhận cho dòng này
    soLuongGiao   DOUBLE      NOT NULL,
    soLuongDaNhan DOUBLE      NOT NULL DEFAULT 0,
    CONSTRAINT UQ_ChiTietPhieuGiao UNIQUE (maPhieuGiao, maHangHoa),
    CONSTRAINT CK_ChiTietPhieuGiao_SoLuong CHECK (soLuongGiao > 0),
    CONSTRAINT FK_ChiTietPhieuGiao_Phieu   FOREIGN KEY (maPhieuGiao) REFERENCES PhieuGiaoNCC(maPhieuGiao) ON DELETE CASCADE,
    CONSTRAINT FK_ChiTietPhieuGiao_HangHoa FOREIGN KEY (maHangHoa) REFERENCES HangHoa(maHangHoa),
    CONSTRAINT FK_ChiTietPhieuGiao_DonHang FOREIGN KEY (maDonHang) REFERENCES DonHang(maDonHang)
) ENGINE=InnoDB;

CREATE TABLE PhieuYeuCauNhapKho (
    maPhieuYeuCauNhap VARCHAR(20)  PRIMARY KEY,
    ngayLap           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    maNhanVien        VARCHAR(20)  NOT NULL,                -- QLK00.NhanVien(maNV), không có FK (khác CSDL)
    loaiNhap          VARCHAR(50)  NOT NULL,
    lyDoNhap          VARCHAR(200) NOT NULL,
    trangThai         VARCHAR(30)  NOT NULL DEFAULT 'Chờ tiếp nhận',
    ghiChu            VARCHAR(500) NULL,
    CONSTRAINT CK_PhieuYeuCauNhap_Loai      CHECK (loaiNhap IN ('Nhập từ nhà cung cấp','Nhập thành phẩm')),
    CONSTRAINT CK_PhieuYeuCauNhap_TrangThai CHECK (trangThai IN ('Chờ tiếp nhận','Đã tiếp nhận một phần','Đã tiếp nhận','Từ chối','Đã hủy'))
) ENGINE=InnoDB;

CREATE TABLE ChiTietPhieuYeuCauNhapKho (                    -- [BỔ SUNG] dòng hàng của phiếu yêu cầu
    maChiTiet         INT AUTO_INCREMENT PRIMARY KEY,
    maPhieuYeuCauNhap VARCHAR(20) NOT NULL,
    maHangHoa         VARCHAR(20) NOT NULL,
    maKeHoach         VARCHAR(20) NULL,                     -- kế hoạch sản xuất được tiếp nhận cho dòng này
    soLuongYeuCau     DOUBLE      NOT NULL,
    soLuongDaNhan     DOUBLE      NOT NULL DEFAULT 0,
    CONSTRAINT UQ_ChiTietPhieuYeuCau UNIQUE (maPhieuYeuCauNhap, maHangHoa),
    CONSTRAINT CK_ChiTietPhieuYeuCau_SoLuong CHECK (soLuongYeuCau > 0),
    CONSTRAINT FK_ChiTietPhieuYeuCau_Phieu   FOREIGN KEY (maPhieuYeuCauNhap) REFERENCES PhieuYeuCauNhapKho(maPhieuYeuCauNhap) ON DELETE CASCADE,
    CONSTRAINT FK_ChiTietPhieuYeuCau_HangHoa FOREIGN KEY (maHangHoa) REFERENCES HangHoa(maHangHoa),
    CONSTRAINT FK_ChiTietPhieuYeuCau_KeHoach FOREIGN KEY (maKeHoach) REFERENCES KeHoachSanXuat(maKeHoach)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. UC06: PhieuTiepNhan [TỪ ĐIỂN: maTiepNhan, thoiGianTiepNhan, soLuongThucNhan, trangThaiTiepNhan, ghiChu]
--    Đặc tả: Mã phiếu tiếp nhận, Ngày nhận + Giờ nhận (= thoiGianTiepNhan), Người nhận (= maNhanVien),
--    Nhà cung cấp (theo chứng từ), Số chứng từ (= maPhieuGiao | maPhieuYeuCauNhap), Vị trí lưu trữ (= maViTri), Ghi chú
-- ---------------------------------------------------------------------
CREATE TABLE PhieuTiepNhan (
    maTiepNhan        VARCHAR(20)  PRIMARY KEY,
    thoiGianTiepNhan  DATETIME     NOT NULL,
    soLuongThucNhan   DOUBLE       NOT NULL,                -- tổng số lượng thực nhận của các dòng hàng
    trangThaiTiepNhan VARCHAR(30)  NOT NULL DEFAULT 'Chờ kiểm định',   -- hậu điều kiện UC06
    ghiChu            VARCHAR(500) NULL,
    maPhieuGiao       VARCHAR(20)  NULL,                    -- [BỔ SUNG] khóa ngoại
    maPhieuYeuCauNhap VARCHAR(20)  NULL,                    -- [BỔ SUNG] khóa ngoại
    maNhanVien        VARCHAR(20)  NOT NULL,                -- [BỔ SUNG] người nhận = QLK00.NhanVien(maNV), không FK
    maViTri           VARCHAR(20)  NOT NULL,                -- [BỔ SUNG] khóa ngoại
    CONSTRAINT CK_PhieuTiepNhan_SoLuong CHECK (soLuongThucNhan > 0),
    CONSTRAINT FK_PhieuTiepNhan_PhieuGiao FOREIGN KEY (maPhieuGiao)       REFERENCES PhieuGiaoNCC(maPhieuGiao),
    CONSTRAINT FK_PhieuTiepNhan_PhieuYeuCau FOREIGN KEY (maPhieuYeuCauNhap) REFERENCES PhieuYeuCauNhapKho(maPhieuYeuCauNhap),
    CONSTRAINT FK_PhieuTiepNhan_ViTri FOREIGN KEY (maViTri) REFERENCES ViTriLuuTru(maViTri)
) ENGINE=InnoDB;

CREATE TABLE ChiTietTiepNhan (                              -- [BỔ SUNG] danh sách hàng hóa tiếp nhận
    maChiTiet       INT AUTO_INCREMENT PRIMARY KEY,
    maTiepNhan      VARCHAR(20)  NOT NULL,
    maHangHoa       VARCHAR(20)  NOT NULL,
    maLoHang        VARCHAR(20)  NOT NULL,                  -- lô hàng tự tạo ở bước 10
    soLuongThucNhan DOUBLE       NOT NULL,
    ghiChu          VARCHAR(255) NULL,
    trangThai       VARCHAR(30)  NOT NULL DEFAULT 'Chờ kiểm định',
    CONSTRAINT CK_ChiTietTiepNhan_SoLuong CHECK (soLuongThucNhan > 0),
    CONSTRAINT FK_ChiTietTiepNhan_Phieu   FOREIGN KEY (maTiepNhan) REFERENCES PhieuTiepNhan(maTiepNhan) ON DELETE CASCADE,
    CONSTRAINT FK_ChiTietTiepNhan_HangHoa FOREIGN KEY (maHangHoa)  REFERENCES HangHoa(maHangHoa),
    CONSTRAINT FK_ChiTietTiepNhan_LoHang  FOREIGN KEY (maLoHang)   REFERENCES LoHang(maLoHang)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. VIEW theo báo cáo (vw_DanhSachPhieuYeuCauNhapKho không có hoTen vì NhanVien nằm ở QLK00)
-- ---------------------------------------------------------------------
CREATE VIEW vw_ThongTinHangHoa AS
SELECT maHangHoa, tenHangHoa, loaiHangHoa, donViTinh, moTa, hanSuDungMacDinh, dieuKienBaoQuan, trangThai FROM HangHoa;

CREATE VIEW vw_DanhSachLoHang AS
SELECT l.maLoHang, l.soLo, l.maHangHoa, h.tenHangHoa, l.ngaySanXuat, l.hanSuDung, l.soLuongTon, l.viTriLuuTru, l.tinhTrang, l.trangThai
FROM LoHang l JOIN HangHoa h ON h.maHangHoa = l.maHangHoa;

CREATE VIEW vw_ChiTietPhieuGiaoNCC AS
SELECT maPhieuGiao, maNCC, ngayGiao, nguoiGiao, soChungTuNCC, trangThai, ghiChu FROM PhieuGiaoNCC;

CREATE VIEW vw_DanhSachPhieuYeuCauNhapKho AS
SELECT maPhieuYeuCauNhap, ngayLap, maNhanVien, loaiNhap, lyDoNhap, trangThai, ghiChu FROM PhieuYeuCauNhapKho;

CREATE VIEW vw_ThongTinKho AS
SELECT maKho, tenKho, loaiKho, diaChi, trangThai, moTa FROM Kho;

CREATE VIEW vw_ThongTinKhuVuc AS
SELECT k.maKhuVuc, k.tenKhuVuc, k.loaiKhuVuc, k.trangThai, k.moTa, k.maKho, o.tenKho
FROM KhuVuc k JOIN Kho o ON o.maKho = k.maKho;

CREATE VIEW vw_ThongTinViTriLuuTru AS
SELECT v.maViTri, v.tenViTri, v.sucChuaToiDa, v.donViSucChua, v.trangThai, v.moTa, v.maKhuVuc, k.tenKhuVuc, k.maKho, o.tenKho
FROM ViTriLuuTru v JOIN KhuVuc k ON k.maKhuVuc = v.maKhuVuc JOIN Kho o ON o.maKho = k.maKho;

CREATE VIEW vw_ThongTinKeHoachSanXuat AS
SELECT maKeHoach, ngayLap, thoiGianBatDau, thoiGianKetThuc, trangThai, ghiChu, maDonHang FROM KeHoachSanXuat;

-- [BỔ SUNG] Danh sách chứng từ chờ tiếp nhận: Số chứng từ, Nhà cung cấp/Nguồn giao, Ngày giao, Tình trạng (bước 2)
CREATE VIEW vw_ChungTuChoTiepNhan AS
SELECT g.maPhieuGiao AS soChungTu, 'Phiếu giao hàng NCC' AS loaiChungTu, n.tenNCC AS nguonGiao,
       g.ngayGiao AS ngayGiao, g.trangThai AS tinhTrang
FROM PhieuGiaoNCC g JOIN NhaCungCap n ON n.maNCC = g.maNCC
WHERE g.trangThai IN ('Chờ tiếp nhận','Đã tiếp nhận một phần')
UNION ALL
SELECT y.maPhieuYeuCauNhap, 'Phiếu yêu cầu nhập thành phẩm', y.lyDoNhap, y.ngayLap, y.trangThai
FROM PhieuYeuCauNhapKho y
WHERE y.loaiNhap = 'Nhập thành phẩm' AND y.trangThai IN ('Chờ tiếp nhận','Đã tiếp nhận một phần');

-- [BỔ SUNG] Dòng hàng của mọi chứng từ giao hàng
CREATE VIEW vw_DongChungTu AS
SELECT maPhieuGiao AS soChungTu, maHangHoa, soLuongGiao AS soLuongChungTu, soLuongDaNhan, maDonHang AS maDonHang, NULL AS maKeHoach
FROM ChiTietPhieuGiaoNCC
UNION ALL
SELECT maPhieuYeuCauNhap, maHangHoa, soLuongYeuCau, soLuongDaNhan, NULL, maKeHoach
FROM ChiTietPhieuYeuCauNhapKho;

-- ===================== DỮ LIỆU MẪU =====================
INSERT INTO Kho (maKho, tenKho, loaiKho, diaChi) VALUES
('K01','Kho thành phẩm A','Kho thành phẩm','KCN Tân Bình, TP. Hồ Chí Minh'),
('K02','Kho nguyên liệu B','Kho nguyên liệu','KCN Vĩnh Lộc, TP. Hồ Chí Minh');

INSERT INTO KhuVuc (maKhuVuc, tenKhuVuc, loaiKhuVuc, maKho) VALUES
('KV01','Khu tiếp nhận A','Khu tiếp nhận','K01'),
('KV02','Khu tiếp nhận nguyên liệu B','Khu tiếp nhận','K02'),
('KV03','Khu chờ kiểm tra chất lượng','Khu chờ kiểm tra chất lượng','K01');

INSERT INTO ViTriLuuTru (maViTri, tenViTri, sucChuaToiDa, donViSucChua, trangThai, maKhuVuc) VALUES
('VT01','Khu tạm A - Kệ 01',5000,'Kg','Đang sử dụng','KV01'),
('VT02','Khu tạm A - Kệ 02',5000,'Kg','Đang sử dụng','KV01'),
('VT03','Khu tạm B - Sàn',20,'Tấn','Đang sử dụng','KV02'),
('VT04','Khu tạm C - Kệ 09',3000,'Kg','Đang bảo trì','KV03');

INSERT INTO HangHoa (maHangHoa, tenHangHoa, loaiHangHoa, donViTinh, moTa, hanSuDungMacDinh, dieuKienBaoQuan, trangThai) VALUES
('TA001','Cám heo tăng trọng 551','Thức ăn cho heo','Bao','Cám viên cho heo thịt giai đoạn vỗ béo, bao 25kg',90,'Nơi khô ráo, thoáng mát, tránh ẩm','Đang sử dụng'),
('TA002','Cám heo con tập ăn 550S','Thức ăn cho heo','Bao','Cám cho heo con tập ăn, bao 25kg',90,'Nơi khô ráo, tránh ánh nắng trực tiếp','Đang sử dụng'),
('TA003','Thức ăn hỗn hợp gà đẻ','Thức ăn cho gia cầm','Bao','Thức ăn hỗn hợp dạng viên cho gà đẻ trứng, bao 25kg',120,'Nơi khô ráo, thoáng mát','Đang sử dụng'),
('TA004','Thức ăn gà thịt giai đoạn 1','Thức ăn cho gia cầm','Bao','Thức ăn gà thịt 0-21 ngày tuổi, bao 25kg',90,'Nơi khô ráo, thoáng mát','Đang sử dụng'),
('TA005','Thức ăn viên cho bò sữa','Thức ăn cho gia súc','Bao','Thức ăn viên bổ sung cho bò sữa, bao 40kg',120,'Nơi khô ráo, tránh ẩm','Đang sử dụng'),
('TA006','Bột cá nguyên liệu 60% đạm','Nguyên liệu chế biến','Bao','Bột cá hàm lượng đạm 60%, bao 50kg',180,'Kho mát, tránh ẩm mốc','Đang sử dụng'),
('TA007','Bắp hạt nghiền','Nguyên liệu chế biến','Tấn','Bắp hạt nghiền, giao xá',180,'Kho thoáng, độ ẩm dưới 14%','Đang sử dụng'),
('TA008','Khô dầu đậu nành','Nguyên liệu chế biến','Bao','Khô dầu đậu nành, bao 50kg',180,'Kho mát, tránh ẩm','Đang sử dụng'),
('TA009','Premix vitamin cho gia cầm','Nguyên liệu chế biến','Bao','Premix vitamin, bao 10kg',90,'Nơi khô ráo, tránh ánh sáng','Ngừng sử dụng');
-- TA009 'Ngừng sử dụng': dùng để thử Exception 4.1 (chứng từ PGH-2026-0004)

INSERT INTO NhaCungCap (maNCC, tenNCC) VALUES
('NCC01','Công ty CP Chăn nuôi Việt Tiến'),
('NCC02','Công ty TNHH Dinh dưỡng Nam Á'),
('NCC03','Công ty CP Nguyên liệu Hòa Phát'),
('NCC04','Nhà máy Thức ăn Đồng Tâm');

INSERT INTO DonHang (maDonHang, ngayDatHang, ngayGiaoDuKien, trangThai) VALUES
('DH-2026-001','2026-10-10 08:00:00','2026-10-20 00:00:00','Đang thực hiện'),
('DH-2026-002','2026-10-01 08:00:00','2026-10-08 00:00:00','Đang thực hiện'),
('DH-2026-003','2026-10-15 08:00:00','2026-10-22 00:00:00','Đang thực hiện'),
('DH-2026-004','2026-10-18 08:00:00','2026-10-23 00:00:00','Đang thực hiện'),
('DH-2026-005','2026-10-03 08:00:00','2026-10-30 00:00:00','Đang thực hiện'),
('DH-2026-006','2026-10-09 08:00:00','2026-10-26 00:00:00','Đã hoàn tất');
INSERT INTO ChiTietDonHang (maDonHang, maHangHoa, soLuongDat, soLuongDaNhan) VALUES
('DH-2026-001','TA001',200,0),
('DH-2026-001','TA002',100,0),
('DH-2026-002','TA006',150,100),
('DH-2026-003','TA005',45,0),
('DH-2026-003','TA008',90,0),
('DH-2026-004','TA009',30,0),
('DH-2026-004','TA007',5,0),
('DH-2026-006','TA001',80,80),
('DH-2026-006','TA005',40,40),
('DH-2026-006','TA002',60,60),
('DH-2026-006','TA007',12,12);

INSERT INTO KeHoachSanXuat (maKeHoach, ngayLap, thoiGianBatDau, thoiGianKetThuc, trangThai, maDonHang) VALUES
('KH-2026-001','2026-10-05 08:00:00','2026-10-06 07:00:00','2026-10-25 17:00:00','Đang sản xuất','DH-2026-005');
INSERT INTO ChiTietKeHoachSanXuat (maKeHoach, maHangHoa, soLuongKeHoach, soLuongDaNhan) VALUES
('KH-2026-001','TA003',130,50),
('KH-2026-001','TA004',80,20);

-- Chứng từ giao hàng (PGH-2026-0004 chứa hàng 'Ngừng sử dụng' để thử Exception 4.1)
INSERT INTO PhieuGiaoNCC (maPhieuGiao, maNCC, ngayGiao, nguoiGiao, soChungTuNCC, trangThai) VALUES
('PGH-2026-0001','NCC01','2026-10-20 08:00:00','Nguyễn Văn Giao','VT-NCC-0881','Chờ tiếp nhận'),
('PGH-2026-0002','NCC03','2026-10-08 08:00:00','Phạm Minh Tuấn','VT-NCC-0790','Đã tiếp nhận một phần'),
('PGH-2026-0003','NCC02','2026-10-22 08:00:00','Võ Hoàng Long','VT-NCC-0925','Chờ tiếp nhận'),
('PGH-2026-0004','NCC04','2026-10-23 08:00:00','Đặng Quốc Bảo','VT-NCC-0931','Chờ tiếp nhận'),
('PGH-2026-0005','NCC01','2026-10-15 08:00:00','Nguyễn Văn Giao','VT-NCC-0842','Đã tiếp nhận'),
('PGH-2026-0006','NCC04','2026-10-19 08:00:00','Đặng Quốc Bảo','VT-NCC-0903','Đã tiếp nhận'),
('PGH-2026-0007','NCC03','2026-10-25 08:00:00','Phạm Minh Tuấn','VT-NCC-0950','Đã tiếp nhận');
INSERT INTO ChiTietPhieuGiaoNCC (maPhieuGiao, maHangHoa, maDonHang, soLuongGiao, soLuongDaNhan) VALUES
('PGH-2026-0001','TA001','DH-2026-001',200,0),
('PGH-2026-0001','TA002','DH-2026-001',100,0),
('PGH-2026-0002','TA006','DH-2026-002',150,100),
('PGH-2026-0003','TA005','DH-2026-003',45,0),
('PGH-2026-0003','TA008','DH-2026-003',90,0),
('PGH-2026-0004','TA009','DH-2026-004',30,0),
('PGH-2026-0004','TA007','DH-2026-004',5,0),
('PGH-2026-0005','TA001','DH-2026-006',80,80),
('PGH-2026-0005','TA005','DH-2026-006',40,40),
('PGH-2026-0006','TA002','DH-2026-006',60,60),
('PGH-2026-0007','TA007','DH-2026-006',12,12);

INSERT INTO PhieuYeuCauNhapKho (maPhieuYeuCauNhap, ngayLap, maNhanVien, loaiNhap, lyDoNhap, trangThai) VALUES
('PYC-2026-0001','2026-10-12 08:00:00','NV02','Nhập thành phẩm','Nhập thành phẩm từ Xưởng sản xuất thức ăn số 1 (KH-2026-001)','Đã tiếp nhận'),
('PYC-2026-0002','2026-10-24 08:00:00','NV02','Nhập thành phẩm','Nhập thành phẩm từ Xưởng sản xuất thức ăn số 1 (KH-2026-001)','Chờ tiếp nhận'),
('PYC-2026-0003','2026-10-20 08:00:00','NV02','Nhập thành phẩm','Nhập thành phẩm từ Xưởng đóng bao thành phẩm (KH-2026-001)','Đã tiếp nhận');
INSERT INTO ChiTietPhieuYeuCauNhapKho (maPhieuYeuCauNhap, maHangHoa, maKeHoach, soLuongYeuCau, soLuongDaNhan) VALUES
('PYC-2026-0001','TA003','KH-2026-001',50,50),
('PYC-2026-0002','TA003','KH-2026-001',80,0),
('PYC-2026-0002','TA004','KH-2026-001',60,0),
('PYC-2026-0003','TA004','KH-2026-001',20,20);

-- Phiếu tiếp nhận + lô hàng đã có (khớp với số lượng đã nhận ở trên)
INSERT INTO LoHang (maLoHang, maHangHoa, soLo, hanSuDung, viTriLuuTru, tinhTrang, tenLoHang, soLuongTiepNhan, donViTinh, thoiGianTiepNhan) VALUES
('LH2026-0001','TA006','LH2026-0001','2027-04-06 09:15:00','Khu tạm A - Kệ 01','Chờ kiểm tra','Lô Bột cá nguyên liệu 60% đạm',100,'Bao','2026-10-08 09:15:00'),
('LH2026-0002','TA003','LH2026-0002','2027-02-09 14:30:00','Khu tạm A - Kệ 02','Chờ kiểm tra','Lô Thức ăn hỗn hợp gà đẻ',50,'Bao','2026-10-12 14:30:00'),
('LH2026-0003','TA001','LH2026-0003','2027-01-13 10:20:00','Khu tạm B - Sàn','Chờ kiểm tra','Lô Cám heo tăng trọng 551',80,'Bao','2026-10-15 10:20:00'),
('LH2026-0004','TA005','LH2026-0004','2027-02-12 10:20:00','Khu tạm B - Sàn','Chờ kiểm tra','Lô Thức ăn viên cho bò sữa',40,'Bao','2026-10-15 10:20:00'),
('LH2026-0005','TA002','LH2026-0005','2027-01-17 15:40:00','Khu tạm A - Kệ 02','Chờ kiểm tra','Lô Cám heo con tập ăn 550S',60,'Bao','2026-10-19 15:40:00'),
('LH2026-0006','TA004','LH2026-0006','2027-01-19 09:05:00','Khu tạm A - Kệ 01','Chờ kiểm tra','Lô Thức ăn gà thịt giai đoạn 1',20,'Bao','2026-10-21 09:05:00'),
('LH2026-0007','TA007','LH2026-0007','2027-04-24 08:40:00','Khu tạm B - Sàn','Chờ kiểm tra','Lô Bắp hạt nghiền',12,'Tấn','2026-10-26 08:40:00');
INSERT INTO PhieuTiepNhan (maTiepNhan, thoiGianTiepNhan, soLuongThucNhan, ghiChu, maPhieuGiao, maPhieuYeuCauNhap, maNhanVien, maViTri) VALUES
('PTN2026-0001','2026-10-08 09:15:00',100,'Giao đợt 1','PGH-2026-0002',NULL,'NV03','VT01'),
('PTN2026-0002','2026-10-12 14:30:00',50,NULL,NULL,'PYC-2026-0001','NV03','VT02'),
('PTN2026-0003','2026-10-15 10:20:00',120,'Hàng đủ, bao bì nguyên vẹn','PGH-2026-0005',NULL,'NV03','VT03'),
('PTN2026-0004','2026-10-19 15:40:00',60,NULL,'PGH-2026-0006',NULL,'NV02','VT02'),
('PTN2026-0005','2026-10-21 09:05:00',20,'Nhập từ xưởng đóng bao',NULL,'PYC-2026-0003','NV03','VT01'),
('PTN2026-0006','2026-10-26 08:40:00',12,'Hàng giao xá, đã cân kiểm','PGH-2026-0007',NULL,'NV03','VT03');
INSERT INTO ChiTietTiepNhan (maTiepNhan, maHangHoa, maLoHang, soLuongThucNhan) VALUES
('PTN2026-0001','TA006','LH2026-0001',100),
('PTN2026-0002','TA003','LH2026-0002',50),
('PTN2026-0003','TA001','LH2026-0003',80),
('PTN2026-0003','TA005','LH2026-0004',40),
('PTN2026-0004','TA002','LH2026-0005',60),
('PTN2026-0005','TA004','LH2026-0006',20),
('PTN2026-0006','TA007','LH2026-0007',12);
