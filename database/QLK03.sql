-- UC17: Dieu chuyen hang noi bo.
-- Import file nay vao MySQL/MariaDB de tao schema va du lieu mau QLK03.
CREATE DATABASE IF NOT EXISTS QLK03 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE QLK03;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS HangHoa (
    maHangHoa VARCHAR(20) PRIMARY KEY,
    tenHangHoa VARCHAR(150) NOT NULL,
    loaiHangHoa VARCHAR(100) NOT NULL,
    donViTinh VARCHAR(16) NOT NULL DEFAULT 'Kg',
    kgMoiBao DECIMAL(14,6) NOT NULL DEFAULT 25,
    baoMoiPallet DECIMAL(14,6) NOT NULL DEFAULT 40,
    khoiLuongRieng DECIMAL(14,6) NULL,
    moTa VARCHAR(500) NULL,
    hanSuDungMacDinh INT NULL,
    dieuKienBaoQuan VARCHAR(300) NULL,
    trangThai VARCHAR(30) NOT NULL DEFAULT 'Đang sử dụng',
    CONSTRAINT CK_HangHoa_HanSuDung CHECK (hanSuDungMacDinh IS NULL OR hanSuDungMacDinh > 0),
    CONSTRAINT CK_HangHoa_TrangThai CHECK (trangThai IN ('Đang sử dụng', 'Ngừng sử dụng', 'Tạm ngưng')),
    CONSTRAINT CK_HangHoa_QuyDoi CHECK (kgMoiBao > 0 AND baoMoiPallet > 0 AND (khoiLuongRieng IS NULL OR khoiLuongRieng > 0)),
    CONSTRAINT CK_HangHoa_DonVi CHECK (donViTinh IN ('Kg', 'Tấn', 'Bao', 'Pallet', 'm³'))
) ENGINE=InnoDB;

-- maNV tham chieu ma nhan vien dang nhap tu QLK00; hai database duoc quan ly doc lap.
CREATE TABLE IF NOT EXISTS NhanVien (
    maNV VARCHAR(20) PRIMARY KEY,
    hoTen VARCHAR(100) NOT NULL,
    vaiTro VARCHAR(50) NOT NULL,
    trangThai VARCHAR(30) NOT NULL DEFAULT 'Đang hoạt động',
    CONSTRAINT CK_UC17_NhanVien_TrangThai CHECK (trangThai IN ('Đang hoạt động', 'Ngừng hoạt động'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Kho (
    maKho VARCHAR(20) PRIMARY KEY,
    tenKho VARCHAR(100) NOT NULL,
    loaiKho VARCHAR(30) NOT NULL,
    diaChi VARCHAR(255) NOT NULL,
    trangThai VARCHAR(30) NOT NULL DEFAULT 'Đang hoạt động',
    moTa VARCHAR(255) NULL,
    CONSTRAINT CK_Kho_TrangThai CHECK (trangThai IN ('Đang hoạt động', 'Chờ kích hoạt', 'Bị khóa', 'Ngừng hoạt động', 'Đang bảo trì'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS KhuVuc (
    maKhuVuc VARCHAR(20) PRIMARY KEY,
    tenKhuVuc VARCHAR(100) NOT NULL,
    loaiKhuVuc VARCHAR(30) NOT NULL,
    trangThai VARCHAR(30) NOT NULL DEFAULT 'Đang hoạt động',
    moTa VARCHAR(255) NULL,
    maKho VARCHAR(20) NOT NULL,
    CONSTRAINT FK_KhuVuc_Kho FOREIGN KEY (maKho) REFERENCES Kho(maKho),
    CONSTRAINT CK_KhuVuc_TrangThai CHECK (trangThai IN ('Đang hoạt động', 'Đang bảo trì', 'Cách ly', 'Khóa kiểm kê', 'Ngừng hoạt động'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ViTriLuuTru (
    maViTri VARCHAR(20) PRIMARY KEY,
    tenViTri VARCHAR(100) NOT NULL,
    sucChuaToiDa DECIMAL(14,3) NOT NULL,
    donViSucChua VARCHAR(16) NOT NULL DEFAULT 'Kg',
    moTa VARCHAR(255) NULL,
    trangThai VARCHAR(30) NOT NULL DEFAULT 'Đang hoạt động',
    maKhuVuc VARCHAR(20) NOT NULL,
    CONSTRAINT FK_ViTriLuuTru_KhuVuc FOREIGN KEY (maKhuVuc) REFERENCES KhuVuc(maKhuVuc),
    CONSTRAINT CK_ViTriLuuTru_SucChua CHECK (sucChuaToiDa > 0),
    CONSTRAINT CK_ViTriLuuTru_DonVi CHECK (donViSucChua IN ('Kg', 'Tấn', 'Bao', 'Pallet', 'm³')),
    CONSTRAINT CK_ViTriLuuTru_TrangThai CHECK (trangThai IN ('Đang hoạt động', 'Ngừng hoạt động', 'Bị khóa', 'Đang bảo trì', 'Khóa kiểm kê'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS LoHang (
    maLoHang VARCHAR(20) PRIMARY KEY,
    maHangHoa VARCHAR(20) NOT NULL,
    soLo VARCHAR(50) NOT NULL,
    ngaySanXuat DATETIME NULL,
    hanSuDung DATETIME NOT NULL,
    soLuongTon DECIMAL(14,3) NOT NULL DEFAULT 0,
    tinhTrang VARCHAR(50) NOT NULL DEFAULT 'Bình thường',
    trangThai VARCHAR(30) NOT NULL DEFAULT 'Đang sử dụng',
    CONSTRAINT FK_LoHang_HangHoa FOREIGN KEY (maHangHoa) REFERENCES HangHoa(maHangHoa),
    CONSTRAINT CK_LoHang_SoLuong CHECK (soLuongTon >= 0),
    CONSTRAINT CK_LoHang_TrangThai CHECK (trangThai IN ('Đang sử dụng', 'Ngừng sử dụng'))
) ENGINE=InnoDB;

-- TonKho theo tung vi tri, de kiem tra ton va suc chua dich mot cach chinh xac.
CREATE TABLE IF NOT EXISTS TonKho (
    maTonKho VARCHAR(20) PRIMARY KEY,
    soLuongTon DECIMAL(14,3) NOT NULL DEFAULT 0,
    soLuongDaGiu DECIMAL(14,3) NOT NULL DEFAULT 0,
    thoiDiemCapNhat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trangThai VARCHAR(30) NOT NULL DEFAULT 'Đã nhập kho',
    maLoHang VARCHAR(20) NOT NULL,
    maViTri VARCHAR(20) NOT NULL,
    maNV VARCHAR(20) NOT NULL,
    CONSTRAINT FK_TonKho_LoHang FOREIGN KEY (maLoHang) REFERENCES LoHang(maLoHang),
    CONSTRAINT FK_TonKho_ViTri FOREIGN KEY (maViTri) REFERENCES ViTriLuuTru(maViTri),
    CONSTRAINT UQ_TonKho_Lo_ViTri UNIQUE (maLoHang, maViTri),
    CONSTRAINT CK_TonKho_SoLuong CHECK (soLuongTon >= 0 AND soLuongDaGiu >= 0 AND soLuongDaGiu <= soLuongTon),
    CONSTRAINT CK_TonKho_TrangThai CHECK (trangThai IN ('Đã lập', 'Đã nhập kho', 'Đã hủy'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS PhieuDieuChuyen (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
    maPhieuDieuChuyen VARCHAR(30) PRIMARY KEY,
    soLuongDuKien DECIMAL(14,3) NOT NULL,
    soLuongThucTe DECIMAL(14,3) NOT NULL,
    donViTinh VARCHAR(16) NOT NULL,
    heSoQuyDoiKg DECIMAL(14,6) NOT NULL DEFAULT 1,
    soLuongDuKienKg DECIMAL(14,3) NOT NULL DEFAULT 0,
    soLuongThucTeKg DECIMAL(14,3) NOT NULL DEFAULT 0,
    lyDoDieuChuyen VARCHAR(255) NOT NULL,
    thoiDiemLap DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    thoiDiemHoanTat DATETIME NULL,
    trangThai VARCHAR(30) NOT NULL DEFAULT 'Hoàn thành',
    ghiChu VARCHAR(500) NULL,
    maLoHang VARCHAR(20) NOT NULL,
    maViTriNguon VARCHAR(20) NOT NULL,
    maViTriDich VARCHAR(20) NOT NULL,
    maNV VARCHAR(20) NOT NULL,
    maNVXuLy VARCHAR(20) NULL,
    maNVHoanTat VARCHAR(20) NULL,
    thoiDiemXuLy DATETIME NULL,
    lyDoTuChoi VARCHAR(500) NULL,
    tonKhoDaGiu TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT FK_PhieuDieuChuyen_LoHang FOREIGN KEY (maLoHang) REFERENCES LoHang(maLoHang),
    CONSTRAINT FK_PhieuDieuChuyen_ViTriNguon FOREIGN KEY (maViTriNguon) REFERENCES ViTriLuuTru(maViTri),
    CONSTRAINT FK_PhieuDieuChuyen_ViTriDich FOREIGN KEY (maViTriDich) REFERENCES ViTriLuuTru(maViTri),
    CONSTRAINT CK_PhieuDieuChuyen_SoLuong CHECK (soLuongDuKien > 0 AND soLuongThucTe > 0 AND heSoQuyDoiKg > 0 AND soLuongDuKienKg > 0 AND soLuongThucTeKg > 0),
    CONSTRAINT CK_PhieuDieuChuyen_ViTri CHECK (maViTriNguon <> maViTriDich),
    CONSTRAINT CK_PhieuDieuChuyen_TrangThai CHECK (trangThai IN ('Chờ phê duyệt', 'Đã phê duyệt', 'Bị từ chối', 'Đang điều chuyển', 'Hoàn thành', 'Đã hủy')),
    CONSTRAINT CK_PhieuDieuChuyen_ThoiDiem CHECK (thoiDiemHoanTat IS NULL OR thoiDiemHoanTat >= thoiDiemLap),
    INDEX IX_PhieuDieuChuyen_ThoiDiem (thoiDiemLap),
    INDEX IX_PhieuDieuChuyen_TrangThai (trangThai)
) ENGINE=InnoDB;

-- Upgrade existing QLK03 databases as well as supporting fresh imports.
DROP PROCEDURE IF EXISTS migrate_qlk03_units_and_approvals;
DELIMITER $$
CREATE PROCEDURE migrate_qlk03_units_and_approvals()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'HangHoa' AND COLUMN_NAME = 'kgMoiBao'
    ) THEN
        ALTER TABLE HangHoa ADD COLUMN kgMoiBao DECIMAL(14,6) NOT NULL DEFAULT 25;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'HangHoa' AND COLUMN_NAME = 'baoMoiPallet'
    ) THEN
        ALTER TABLE HangHoa ADD COLUMN baoMoiPallet DECIMAL(14,6) NOT NULL DEFAULT 40;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'HangHoa' AND COLUMN_NAME = 'khoiLuongRieng'
    ) THEN
        ALTER TABLE HangHoa ADD COLUMN khoiLuongRieng DECIMAL(14,6) NULL;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'PhieuDieuChuyen' AND COLUMN_NAME = 'heSoQuyDoiKg'
    ) THEN
        ALTER TABLE PhieuDieuChuyen ADD COLUMN heSoQuyDoiKg DECIMAL(14,6) NOT NULL DEFAULT 1;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'PhieuDieuChuyen' AND COLUMN_NAME = 'soLuongDuKienKg'
    ) THEN
        ALTER TABLE PhieuDieuChuyen ADD COLUMN soLuongDuKienKg DECIMAL(14,3) NOT NULL DEFAULT 0;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'PhieuDieuChuyen' AND COLUMN_NAME = 'soLuongThucTeKg'
    ) THEN
        ALTER TABLE PhieuDieuChuyen ADD COLUMN soLuongThucTeKg DECIMAL(14,3) NOT NULL DEFAULT 0;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'PhieuDieuChuyen' AND COLUMN_NAME = 'maNVXuLy'
    ) THEN
        ALTER TABLE PhieuDieuChuyen ADD COLUMN maNVXuLy VARCHAR(20) NULL;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'PhieuDieuChuyen' AND COLUMN_NAME = 'maNVHoanTat'
    ) THEN
        ALTER TABLE PhieuDieuChuyen ADD COLUMN maNVHoanTat VARCHAR(20) NULL;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'PhieuDieuChuyen' AND COLUMN_NAME = 'thoiDiemXuLy'
    ) THEN
        ALTER TABLE PhieuDieuChuyen ADD COLUMN thoiDiemXuLy DATETIME NULL;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'PhieuDieuChuyen' AND COLUMN_NAME = 'lyDoTuChoi'
    ) THEN
        ALTER TABLE PhieuDieuChuyen ADD COLUMN lyDoTuChoi VARCHAR(500) NULL;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'PhieuDieuChuyen' AND COLUMN_NAME = 'tonKhoDaGiu'
    ) THEN
        ALTER TABLE PhieuDieuChuyen ADD COLUMN tonKhoDaGiu TINYINT(1) NOT NULL DEFAULT 0;
    END IF;

    ALTER TABLE LoHang MODIFY soLuongTon DECIMAL(14,3) NOT NULL DEFAULT 0;
    ALTER TABLE ViTriLuuTru MODIFY sucChuaToiDa DECIMAL(14,3) NOT NULL;
    ALTER TABLE TonKho MODIFY soLuongTon DECIMAL(14,3) NOT NULL DEFAULT 0;
    ALTER TABLE TonKho MODIFY soLuongDaGiu DECIMAL(14,3) NOT NULL DEFAULT 0;
    ALTER TABLE PhieuDieuChuyen MODIFY soLuongDuKien DECIMAL(14,3) NOT NULL;
    ALTER TABLE PhieuDieuChuyen MODIFY soLuongThucTe DECIMAL(14,3) NOT NULL;
    ALTER TABLE HangHoa MODIFY donViTinh VARCHAR(16) NOT NULL DEFAULT 'Kg';
    ALTER TABLE ViTriLuuTru MODIFY donViSucChua VARCHAR(16) NOT NULL DEFAULT 'Kg';
    ALTER TABLE PhieuDieuChuyen MODIFY donViTinh VARCHAR(16) NOT NULL;

    UPDATE PhieuDieuChuyen p
    JOIN LoHang l ON l.maLoHang = p.maLoHang
    JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
    SET p.heSoQuyDoiKg = CASE p.donViTinh
            WHEN 'Kg' THEN 1
            WHEN 'Tấn' THEN 1000
            WHEN 'Bao' THEN h.kgMoiBao
            WHEN 'Pallet' THEN h.kgMoiBao * h.baoMoiPallet
            WHEN 'm³' THEN COALESCE(h.khoiLuongRieng, 0)
            ELSE 1
        END,
        p.soLuongDuKienKg = p.soLuongDuKien * CASE p.donViTinh
            WHEN 'Kg' THEN 1
            WHEN 'Tấn' THEN 1000
            WHEN 'Bao' THEN h.kgMoiBao
            WHEN 'Pallet' THEN h.kgMoiBao * h.baoMoiPallet
            WHEN 'm³' THEN COALESCE(h.khoiLuongRieng, 0)
            ELSE 1
        END,
        p.soLuongThucTeKg = p.soLuongThucTe * CASE p.donViTinh
            WHEN 'Kg' THEN 1
            WHEN 'Tấn' THEN 1000
            WHEN 'Bao' THEN h.kgMoiBao
            WHEN 'Pallet' THEN h.kgMoiBao * h.baoMoiPallet
            WHEN 'm³' THEN COALESCE(h.khoiLuongRieng, 0)
            ELSE 1
        END
    WHERE p.soLuongDuKienKg = 0 OR p.soLuongThucTeKg = 0;
END$$
DELIMITER ;
CALL migrate_qlk03_units_and_approvals();
DROP PROCEDURE migrate_qlk03_units_and_approvals;

INSERT IGNORE INTO HangHoa (maHangHoa, tenHangHoa, loaiHangHoa, donViTinh, moTa, hanSuDungMacDinh, dieuKienBaoQuan, trangThai) VALUES
('HH0001', 'Thức ăn hỗn hợp heo', 'Thức ăn chăn nuôi', 'Kg', 'Thức ăn hỗn hợp cho heo thịt', 180, 'Khô ráo, thoáng mát', 'Đang sử dụng'),
('HH0002', 'Cám gà', 'Thức ăn chăn nuôi', 'Kg', 'Thức ăn hỗn hợp cho gà', 180, 'Khô ráo, thoáng mát', 'Đang sử dụng'),
('HH0003', 'Cám gạo', 'Nguyên liệu', 'Kg', 'Nguyên liệu cám gạo', 120, 'Tránh ẩm và ánh nắng trực tiếp', 'Đang sử dụng'),
('HH0004', 'Thức ăn hỗn hợp bò', 'Thức ăn chăn nuôi', 'Kg', 'Thức ăn hỗn hợp cho bò', 180, 'Khô ráo, thoáng mát', 'Đang sử dụng');

INSERT IGNORE INTO NhanVien (maNV, hoTen, vaiTro, trangThai) VALUES
('NV01', 'Nguyễn Văn Quản', 'Quản trị viên', 'Đang hoạt động'),
('NV02', 'Trần Thị Kho', 'Quản lý kho', 'Đang hoạt động'),
('NV03', 'Lê Văn Nhân', 'Nhân viên kho', 'Đang hoạt động');

INSERT IGNORE INTO Kho (maKho, tenKho, loaiKho, diaChi, trangThai, moTa) VALUES
('KHOA', 'Kho A', 'Kho trung tâm', 'Khu công nghiệp Tân Bình', 'Đang hoạt động', 'Kho nguyên liệu và thức ăn thành phẩm'),
('KHOB', 'Kho B', 'Kho vệ tinh', 'Huyện Củ Chi', 'Đang hoạt động', 'Kho vệ tinh phía Bắc'),
('KHOC', 'Kho C', 'Kho vệ tinh', 'Huyện Bình Chánh', 'Đang hoạt động', 'Kho vệ tinh phía Nam');

INSERT IGNORE INTO KhuVuc (maKhuVuc, tenKhuVuc, loaiKhuVuc, trangThai, moTa, maKho) VALUES
('KV-A01', 'Khu A01', 'Khu lưu trữ', 'Đang hoạt động', 'Khu lưu trữ thức ăn bao', 'KHOA'),
('KV-A02', 'Khu A02', 'Khu lưu trữ', 'Đang hoạt động', 'Khu lưu trữ nguyên liệu', 'KHOA'),
('KV-B01', 'Khu B01', 'Khu lưu trữ', 'Đang hoạt động', 'Khu lưu trữ thức ăn bao', 'KHOB'),
('KV-B02', 'Khu B02', 'Khu lưu trữ', 'Đang hoạt động', 'Khu dự phòng', 'KHOB'),
('KV-C01', 'Khu C01', 'Khu lưu trữ', 'Đang hoạt động', 'Khu lưu trữ thức ăn bao', 'KHOC');

INSERT IGNORE INTO ViTriLuuTru (maViTri, tenViTri, sucChuaToiDa, donViSucChua, moTa, trangThai, maKhuVuc) VALUES
('VT-A01-01', 'A-01', 5000, 'Kg', 'Kệ A01-01', 'Đang hoạt động', 'KV-A01'),
('VT-A01-02', 'A-02', 5000, 'Kg', 'Kệ A01-02', 'Đang hoạt động', 'KV-A01'),
('VT-A02-01', 'A-03', 4000, 'Kg', 'Kệ A02-01', 'Đang hoạt động', 'KV-A02'),
('VT-B01-01', 'B-01', 5000, 'Kg', 'Kệ B01-01', 'Đang hoạt động', 'KV-B01'),
('VT-B01-02', 'B-02', 5000, 'Kg', 'Kệ B01-02', 'Đang hoạt động', 'KV-B01'),
('VT-B02-01', 'B-03', 4000, 'Kg', 'Kệ B02-01', 'Đang hoạt động', 'KV-B02'),
('VT-C01-01', 'C-01', 5000, 'Kg', 'Kệ C01-01', 'Đang hoạt động', 'KV-C01'),
('VT-C01-02', 'C-02', 5000, 'Kg', 'Kệ C01-02', 'Đang hoạt động', 'KV-C01');

INSERT IGNORE INTO LoHang (maLoHang, maHangHoa, soLo, ngaySanXuat, hanSuDung, soLuongTon, tinhTrang, trangThai) VALUES
('LH00045', 'HH0001', 'LO-HEO-2026-01', '2026-09-10 08:00:00', '2027-03-09 23:59:59', 5000, 'Bình thường', 'Đang sử dụng'),
('LH00046', 'HH0002', 'LO-GA-2026-02', '2026-09-20 08:00:00', '2027-03-19 23:59:59', 3200, 'Bình thường', 'Đang sử dụng'),
('LH00047', 'HH0003', 'LO-CAM-2026-03', '2026-10-01 08:00:00', '2027-01-29 23:59:59', 2400, 'Bình thường', 'Đang sử dụng'),
('LH00048', 'HH0004', 'LO-BO-2026-04', '2026-10-05 08:00:00', '2027-04-03 23:59:59', 1800, 'Bình thường', 'Đang sử dụng');

INSERT IGNORE INTO TonKho (maTonKho, soLuongTon, soLuongDaGiu, thoiDiemCapNhat, trangThai, maLoHang, maViTri, maNV) VALUES
('TK-A-0001', 3000, 1000, CURRENT_TIMESTAMP, 'Đã nhập kho', 'LH00045', 'VT-A01-01', 'NV02'),
('TK-A-0002', 2000, 0, CURRENT_TIMESTAMP, 'Đã nhập kho', 'LH00045', 'VT-A01-02', 'NV02'),
('TK-A-0003', 1200, 0, CURRENT_TIMESTAMP, 'Đã nhập kho', 'LH00046', 'VT-A02-01', 'NV02'),
('TK-B-0001', 1500, 0, CURRENT_TIMESTAMP, 'Đã nhập kho', 'LH00046', 'VT-B01-01', 'NV02'),
('TK-B-0002', 1200, 0, CURRENT_TIMESTAMP, 'Đã nhập kho', 'LH00047', 'VT-B01-02', 'NV02'),
('TK-B-0003', 1800, 0, CURRENT_TIMESTAMP, 'Đã nhập kho', 'LH00048', 'VT-B02-01', 'NV02'),
('TK-C-0001', 900, 0, CURRENT_TIMESTAMP, 'Đã nhập kho', 'LH00047', 'VT-C01-01', 'NV02');

INSERT IGNORE INTO PhieuDieuChuyen (maPhieuDieuChuyen, soLuongDuKien, soLuongThucTe, donViTinh, heSoQuyDoiKg, soLuongDuKienKg, soLuongThucTeKg, lyDoDieuChuyen, thoiDiemLap, thoiDiemHoanTat, trangThai, ghiChu, maLoHang, maViTriNguon, maViTriDich, maNV) VALUES
('DC000126', 500, 500, 'Kg', 1, 500, 500, 'Điều chuyển vị trí lưu kho', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), 'Hoàn thành', 'Sắp xếp lại hàng hóa trong kho', 'LH00045', 'VT-A01-01', 'VT-A01-02', 'NV02'),
('DC000127', 300, 300, 'Kg', 1, 300, 300, 'Cân bằng tồn kho giữa các kho', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 'Hoàn thành', 'Bổ sung hàng cho kho vệ tinh', 'LH00046', 'VT-A02-01', 'VT-B01-01', 'NV02'),
('DC000128', 200, 200, 'Kg', 1, 200, 200, 'Điều chuyển vị trí lưu kho', DATE_SUB(NOW(), INTERVAL 1 HOUR), NULL, 'Chờ phê duyệt', 'Dữ liệu mẫu: phiếu chờ quản lý phê duyệt', 'LH00045', 'VT-A01-01', 'VT-B01-02', 'NV03'),
('DC000129', 150, 150, 'Kg', 1, 150, 150, 'Cân bằng tồn kho giữa các kho', DATE_SUB(NOW(), INTERVAL 30 MINUTE), NULL, 'Đang điều chuyển', 'Dữ liệu mẫu: hàng đang trên đường điều chuyển', 'LH00046', 'VT-A02-01', 'VT-C01-02', 'NV02');
