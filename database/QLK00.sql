-- CSDL dung chung: dang nhap, vai tro va phan quyen.
CREATE DATABASE IF NOT EXISTS QLK00 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE QLK00;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS NhanVien (
    maNV VARCHAR(20) PRIMARY KEY,
    hoTen VARCHAR(100) NOT NULL,
    vaiTro VARCHAR(50) NOT NULL,
    quyenHan VARCHAR(200) NULL,
    trangThai VARCHAR(30) NOT NULL DEFAULT 'Đang hoạt động',
    CONSTRAINT CK_NhanVien_TrangThai
        CHECK (trangThai IN ('Đang hoạt động', 'Ngừng hoạt động'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS TaiKhoan (
    maTaiKhoan VARCHAR(20) PRIMARY KEY,
    tenDangNhap VARCHAR(50) NOT NULL UNIQUE,
    matKhau VARCHAR(255) NOT NULL,
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lanDangNhapCuoi DATETIME NULL,
    trangThai VARCHAR(30) NOT NULL DEFAULT 'Đang hoạt động',
    maNhanVien VARCHAR(20) NOT NULL,
    CONSTRAINT FK_TaiKhoan_NhanVien
        FOREIGN KEY (maNhanVien) REFERENCES NhanVien(maNV),
    CONSTRAINT CK_TaiKhoan_TrangThai
        CHECK (trangThai IN ('Đang hoạt động', 'Tạm khóa', 'Ngừng hoạt động'))
) ENGINE=InnoDB;

INSERT INTO NhanVien (maNV, hoTen, vaiTro, quyenHan, trangThai) VALUES
('NV01', 'Nguyễn Văn Quản', 'Quản trị viên', 'UC06,UC10,UC17,UC23,UC24', 'Đang hoạt động'),
('NV02', 'Trần Thị Kho', 'Quản lý kho', 'UC06,UC10,UC17,UC23,UC24', 'Đang hoạt động'),
('NV03', 'Lê Văn Nhân', 'Nhân viên kho', 'UC06,UC10', 'Đang hoạt động')
ON DUPLICATE KEY UPDATE
    hoTen = VALUES(hoTen),
    vaiTro = VALUES(vaiTro),
    quyenHan = VALUES(quyenHan),
    trangThai = VALUES(trangThai);

INSERT INTO TaiKhoan (maTaiKhoan, tenDangNhap, matKhau, trangThai, maNhanVien) VALUES
('TK01', 'admin', '$2y$10$MBntRpt6ZvoTfgmtbGWgw.u/utWHXtLx2FdSpJ3fTQWzxPDKRazUy', 'Đang hoạt động', 'NV01'),
('TK02', 'quanlykho', '$2y$10$2xdbCvP6mktFax1IavSUN.hbzESF06NUoXeSJ9Y/tMSy9t8oMaDPC', 'Đang hoạt động', 'NV02'),
('TK03', 'nhanvienkho', '$2y$10$DqTFf08eVpg857I8vmQ5cOlqRNMQCmhKedNC7KXyO/QfJur3dorq6', 'Đang hoạt động', 'NV03')
ON DUPLICATE KEY UPDATE
    tenDangNhap = VALUES(tenDangNhap),
    matKhau = VALUES(matKhau),
    trangThai = VALUES(trangThai),
    maNhanVien = VALUES(maNhanVien);
