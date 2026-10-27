-- UC10 - Nhap kho.
CREATE DATABASE IF NOT EXISTS QLK02 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE QLK02;

-- 1. HangHoa
CREATE TABLE HangHoa (
 maHangHoa VARCHAR(20) NOT NULL PRIMARY KEY,
 tenHangHoa NVARCHAR(150) NOT NULL,
 loaiHangHoa NVARCHAR(100) NOT NULL,
 donViTinh VARCHAR(10) NOT NULL,
 moTa NVARCHAR(500) NULL,
 hanSuDungMacDinh INT NULL,
 dieuKienBaoQuan NVARCHAR(300) NULL,
 trangThai VARCHAR(30) NOT NULL DEFAULT 'Đang sử dụng',
 CONSTRAINT chk_HangHoa_hsd CHECK (hanSuDungMacDinh IS NULL OR hanSuDungMacDinh > 0),
 CONSTRAINT chk_HangHoa_trangThai CHECK (trangThai IN ('Đang sử dụng','Ngừng sử dụng','Tạm ngưng'))
) ENGINE=InnoDB;

-- 2. Kho
CREATE TABLE Kho (
 maKho VARCHAR(20) NOT NULL PRIMARY KEY,
 tenKho NVARCHAR(100) NOT NULL,
 loaiKho NVARCHAR(30) NOT NULL,
 diaChi NVARCHAR(255) NOT NULL,
 trangThai NVARCHAR(30) NOT NULL DEFAULT 'Đang hoạt động',
 moTa NVARCHAR(255) NULL,
 CONSTRAINT chk_Kho_trangThai CHECK (trangThai IN ('Đang hoạt động','Chờ kích hoạt','Bị khóa','Ngừng hoạt động','Đang bảo trì'))
) ENGINE=InnoDB;

-- 3. KhuVuc
CREATE TABLE KhuVuc (
 maKhuVuc VARCHAR(20) NOT NULL PRIMARY KEY,
 tenKhuVuc NVARCHAR(100) NOT NULL,
 loaiKhuVuc NVARCHAR(30) NOT NULL,
 trangThai NVARCHAR(30) NOT NULL DEFAULT 'Đang hoạt động',
 moTa NVARCHAR(255) NULL,
 maKho VARCHAR(20) NOT NULL,
 CONSTRAINT chk_KhuVuc_trangThai CHECK (trangThai IN ('Đang hoạt động','Đang bảo trì','Cách ly','Khóa kiểm kê','Ngừng hoạt động')),
 CONSTRAINT fk_KhuVuc_Kho FOREIGN KEY (maKho) REFERENCES Kho(maKho)
) ENGINE=InnoDB;

-- 4. ViTriLuuTru
CREATE TABLE ViTriLuuTru (
 maViTri VARCHAR(20) NOT NULL PRIMARY KEY,
 tenViTri NVARCHAR(100) NOT NULL,
 sucChuaToiDa FLOAT NOT NULL,
 donViSucChua NVARCHAR(10) NOT NULL DEFAULT 'Kg',
 moTa NVARCHAR(255) NULL,
 trangThai NVARCHAR(30) NOT NULL DEFAULT 'Đang hoạt động',
 maKhuVuc VARCHAR(20) NOT NULL,
 CONSTRAINT chk_ViTri_sucChua CHECK (sucChuaToiDa > 0),
 CONSTRAINT chk_ViTri_donVi CHECK (donViSucChua IN ('Kg','Tấn','Bao','Pallet','m3')),
 CONSTRAINT chk_ViTri_trangThai CHECK (trangThai IN ('Đang hoạt động','Ngừng hoạt động','Bị khóa','Đang bảo trì','Khóa kiểm kê')),
 CONSTRAINT fk_ViTri_KhuVuc FOREIGN KEY (maKhuVuc) REFERENCES KhuVuc(maKhuVuc)
) ENGINE=InnoDB;

-- 5. LoHang: uses latest detailed 9-column schema, not old UC10 state schema
CREATE TABLE LoHang (
 maLoHang VARCHAR(20) NOT NULL PRIMARY KEY,
 maHangHoa VARCHAR(20) NOT NULL,
 soLo VARCHAR(50) NOT NULL,
 ngaySanXuat DATETIME NULL,
 hanSuDung DATETIME NOT NULL,
 soLuongTon FLOAT NOT NULL DEFAULT 0,
 viTriLuuTru NVARCHAR(100) NULL,
 tinhTrang VARCHAR(50) NOT NULL DEFAULT 'Bình thường',
 trangThai VARCHAR(30) NOT NULL DEFAULT 'Đang sử dụng',
 CONSTRAINT chk_LoHang_soLuong CHECK (soLuongTon >= 0),
 CONSTRAINT chk_LoHang_tinhTrang CHECK (tinhTrang IN ('Bình thường','Chờ kiểm tra','Cách ly','Hư hỏng','Hết hạn')),
 CONSTRAINT chk_LoHang_trangThai CHECK (trangThai IN ('Đang sử dụng','Ngừng sử dụng')),
 CONSTRAINT fk_LoHang_HangHoa FOREIGN KEY (maHangHoa) REFERENCES HangHoa(maHangHoa)
) ENGINE=InnoDB;

-- 6. BienBanKiemDinhChatLuong
CREATE TABLE BienBanKiemDinhChatLuong (
 maBienBan VARCHAR(20) NOT NULL PRIMARY KEY,
 thoiDiemKiemDinh DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 ketQua NVARCHAR(100) NULL,
 trangThai NVARCHAR(30) NOT NULL DEFAULT 'Đang kiểm định',
 ghiChu NVARCHAR(500) NULL,
 maLoHang VARCHAR(20) NOT NULL,
 CONSTRAINT chk_BBKD_ketQua CHECK (ketQua IS NULL OR ketQua IN ('Đạt','Không đạt')),
 CONSTRAINT chk_BBKD_trangThai CHECK (trangThai IN ('Đang kiểm định','Chờ xác nhận','Đã xác nhận')),
 CONSTRAINT fk_BBKD_LoHang FOREIGN KEY (maLoHang) REFERENCES LoHang(maLoHang)
) ENGINE=InnoDB;

-- 7. PhieuNhapKho: maNhanVien is checked against QLK00 by application,
-- unless exact QLK00.NhanVien schema is confirmed for a cross-database FK.
CREATE TABLE PhieuNhapKho (
 maPhieuNhapKho VARCHAR(20) NOT NULL PRIMARY KEY,
 ngayLapPhieu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 soLuongThucNhap FLOAT NOT NULL DEFAULT 0,
 donViTinh VARCHAR(10) NOT NULL,
 thoiGianLap DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 trangThai NVARCHAR(30) NOT NULL,
 ghiChu NVARCHAR(500) NULL,
 maLoHang VARCHAR(20) NOT NULL,
 maKho VARCHAR(20) NOT NULL,
 maNhanVien VARCHAR(20) NOT NULL,
 CONSTRAINT chk_PNK_soLuong CHECK (soLuongThucNhap >= 0),
 CONSTRAINT chk_PNK_trangThai CHECK (trangThai IN ('Đã lập','Đã nhập kho','Đã hủy')),
 CONSTRAINT fk_PNK_LoHang FOREIGN KEY (maLoHang) REFERENCES LoHang(maLoHang),
 CONSTRAINT fk_PNK_Kho FOREIGN KEY (maKho) REFERENCES Kho(maKho)
) ENGINE=InnoDB;

-- 8. TonKho: 'soLuongDaGiu 0' in source is incomplete. Use >=0 and <=soLuongTon
-- as an explicitly documented assumption, NOT a verbatim conversion.
CREATE TABLE TonKho (
 maTonKho VARCHAR(20) NOT NULL PRIMARY KEY,
 soLuongTon FLOAT NOT NULL DEFAULT 0,
 soLuongDaGiu FLOAT NOT NULL DEFAULT 0,
 thoiDiemCapNhat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 trangThai NVARCHAR(30) NOT NULL DEFAULT 'Đã lập',
 maLoHang VARCHAR(20) NOT NULL,
 maKho VARCHAR(20) NOT NULL,
 maNhanVien VARCHAR(20) NOT NULL,
 CONSTRAINT chk_TonKho_soLuong CHECK (soLuongTon >= 0),
 CONSTRAINT chk_TonKho_soLuongDaGiu CHECK (soLuongDaGiu >= 0 AND soLuongDaGiu <= soLuongTon),
 CONSTRAINT chk_TonKho_trangThai CHECK (trangThai IN ('Đã lập','Đã nhập kho','Đã hủy')),
 CONSTRAINT fk_TonKho_LoHang FOREIGN KEY (maLoHang) REFERENCES LoHang(maLoHang),
 CONSTRAINT fk_TonKho_Kho FOREIGN KEY (maKho) REFERENCES Kho(maKho)
) ENGINE=InnoDB;

USE QLK02;

START TRANSACTION;

INSERT IGNORE INTO HangHoa (maHangHoa,tenHangHoa,loaiHangHoa,donViTinh,moTa,hanSuDungMacDinh,dieuKienBaoQuan,trangThai) VALUES
('HH001','Thức ăn heo thịt','Thức ăn cho heo','Bao','Bao 25 kg',180,'Bảo quản khô ráo','Đang sử dụng'),
('HH002','Thức ăn gà thịt','Thức ăn gia cầm','Bao','Bao 25 kg',150,'Tránh ẩm và ánh nắng','Đang sử dụng'),
('HH003','Thức ăn cá','Thức ăn thủy sản','Bao','Bao 20 kg',120,'Để nơi thoáng mát','Đang sử dụng');

INSERT IGNORE INTO Kho (maKho,tenKho,loaiKho,diaChi,trangThai,moTa) VALUES
('K001','Kho nguyên liệu A','Kho nguyên liệu','TP. Hồ Chí Minh','Đang hoạt động','Kho mẫu 1'),
('K002','Kho thành phẩm B','Kho thành phẩm','Bình Dương','Đang hoạt động','Kho mẫu 2'),
('K003','Kho thức ăn thủy sản C','Kho thức ăn thủy sản','Đồng Nai','Đang hoạt động','Kho mẫu 3');

INSERT IGNORE INTO KhuVuc (maKhuVuc,tenKhuVuc,loaiKhuVuc,trangThai,moTa,maKho) VALUES
('KV001','Khu nguyên liệu A1','Khu nguyên liệu','Đang hoạt động','Khu vực mẫu 1','K001'),
('KV002','Khu thành phẩm B1','Khu thành phẩm','Đang hoạt động','Khu vực mẫu 2','K002'),
('KV003','Khu thủy sản C1','Khu thức ăn thủy sản','Đang hoạt động','Khu vực mẫu 3','K003');

INSERT IGNORE INTO ViTriLuuTru (maViTri,tenViTri,sucChuaToiDa,donViSucChua,moTa,trangThai,maKhuVuc) VALUES
('VT001','Dãy A - Kệ 01',1000,'Bao','Vị trí mẫu 1','Đang hoạt động','KV001'),
('VT002','Dãy B - Kệ 01',1200,'Bao','Vị trí mẫu 2','Đang hoạt động','KV002'),
('VT003','Dãy C - Kệ 01',800,'Bao','Vị trí mẫu 3','Đang hoạt động','KV003');

INSERT IGNORE INTO LoHang (maLoHang,maHangHoa,soLo,ngaySanXuat,hanSuDung,soLuongTon,viTriLuuTru,tinhTrang,trangThai) VALUES
('LH001','HH001','LOT-HEO-001','2026-09-01 08:00:00','2027-02-28 08:00:00',100,'Dãy A - Kệ 01','Bình thường','Đang sử dụng'),
('LH002','HH002','LOT-GA-002','2026-09-10 08:00:00','2027-02-07 08:00:00',150,'Dãy B - Kệ 01','Bình thường','Đang sử dụng'),
('LH003','HH003','LOT-CA-003','2026-09-20 08:00:00','2027-01-18 08:00:00',80,'Dãy C - Kệ 01','Bình thường','Đang sử dụng');

INSERT IGNORE INTO BienBanKiemDinhChatLuong (maBienBan,thoiDiemKiemDinh,ketQua,trangThai,ghiChu,maLoHang) VALUES
('BBKD001','2026-10-01 09:00:00','Đạt','Đã xác nhận','Đạt yêu cầu kiểm định','LH001'),
('BBKD002','2026-10-02 09:00:00','Đạt','Đã xác nhận','Đạt yêu cầu kiểm định','LH002'),
('BBKD003','2026-10-03 09:00:00','Đạt','Đã xác nhận','Đạt yêu cầu kiểm định','LH003');

-- Uses the first existing employee in shared database QLK00.
SET @maNhanVienMau = (SELECT maNV FROM QLK00.nhanvien ORDER BY maNV LIMIT 1);
SELECT @maNhanVienMau AS maNhanVienDuocChon;

INSERT IGNORE INTO PhieuNhapKho (maPhieuNhapKho,ngayLapPhieu,soLuongThucNhap,donViTinh,thoiGianLap,trangThai,ghiChu,maLoHang,maKho,maNhanVien) VALUES
('PN2026-0001','2026-10-04 08:00:00',100,'Thùng','2026-10-04 08:00:00','Đã nhập kho','Phiếu mẫu 1','LH001','K001',@maNhanVienMau),
('PN2026-0002','2026-10-05 08:00:00',150,'Kg','2026-10-05 08:00:00','Đã lập','Phiếu mẫu 2','LH002','K002',@maNhanVienMau),
('PN2026-0003','2026-10-06 08:00:00',80,'Bao','2026-10-06 08:00:00','Đã hủy','Phiếu mẫu 3','LH003','K003',@maNhanVienMau);

INSERT IGNORE INTO TonKho (maTonKho,soLuongTon,soLuongDaGiu,thoiDiemCapNhat,trangThai,maLoHang,maKho,maNhanVien) VALUES
('TK001',100,0,'2026-10-04 08:00:00','Đã nhập kho','LH001','K001',@maNhanVienMau),
('TK002',150,0,'2026-10-05 08:00:00','Đã nhập kho','LH002','K002',@maNhanVienMau),
('TK003',80,0,'2026-10-06 08:00:00','Đã nhập kho','LH003','K003',@maNhanVienMau);

COMMIT;

SELECT 'HangHoa' AS bang, COUNT(*) AS soDong FROM HangHoa
UNION ALL SELECT 'Kho',COUNT(*) FROM Kho
UNION ALL SELECT 'KhuVuc',COUNT(*) FROM KhuVuc
UNION ALL SELECT 'ViTriLuuTru',COUNT(*) FROM ViTriLuuTru
UNION ALL SELECT 'LoHang',COUNT(*) FROM LoHang
UNION ALL SELECT 'BienBanKiemDinhChatLuong',COUNT(*) FROM BienBanKiemDinhChatLuong
UNION ALL SELECT 'PhieuNhapKho',COUNT(*) FROM PhieuNhapKho
UNION ALL SELECT 'TonKho',COUNT(*) FROM TonKho;

USE QLK02;

START TRANSACTION;

INSERT INTO QLK02.LoHang
(maLoHang, maHangHoa, soLo, ngaySanXuat, hanSuDung, soLuongTon, viTriLuuTru, tinhTrang, trangThai)
VALUES
('LH004','HH001','LOT-HEO-014','2026-10-01 08:00:00','2027-10-01 08:00:00',0,NULL,'Bình thường','Đang sử dụng'),
('LH005','HH002','LOT-GA-015','2026-10-02 08:00:00','2027-10-02 08:00:00',0,NULL,'Bình thường','Đang sử dụng'),
('LH006','HH003','LOT-CA-016','2026-10-03 08:00:00','2027-10-03 08:00:00',0,NULL,'Bình thường','Đang sử dụng'),
('LH007','HH001','LOT-HEO-017','2026-10-04 08:00:00','2027-10-04 08:00:00',0,NULL,'Bình thường','Đang sử dụng'),
('LH008','HH002','LOT-GA-018','2026-10-05 08:00:00','2027-10-05 08:00:00',0,NULL,'Bình thường','Đang sử dụng'),
('LH009','HH003','LOT-CA-019','2026-10-06 08:00:00','2027-10-06 08:00:00',0,NULL,'Bình thường','Đang sử dụng'),
('LH010','HH001','LOT-HEO-020','2026-10-07 08:00:00','2027-10-07 08:00:00',0,NULL,'Bình thường','Đang sử dụng'),
('LH011','HH002','LOT-GA-021','2026-10-08 08:00:00','2027-10-08 08:00:00',0,NULL,'Bình thường','Đang sử dụng'),
('LH012','HH003','LOT-CA-022','2026-10-09 08:00:00','2027-10-09 08:00:00',0,NULL,'Bình thường','Đang sử dụng'),
('LH013','HH001','LOT-HEO-023','2026-10-10 08:00:00','2027-10-10 08:00:00',0,NULL,'Bình thường','Đang sử dụng');

INSERT INTO QLK02.BienBanKiemDinhChatLuong
(maBienBan, thoiDiemKiemDinh, ketQua, trangThai, ghiChu, maLoHang)
VALUES
('BBKD004','2026-10-11 08:00:00','Đạt','Đã xác nhận','Kiểm định đạt - dữ liệu thử UC10','LH004'),
('BBKD005','2026-10-11 08:10:00','Đạt','Đã xác nhận','Kiểm định đạt - dữ liệu thử UC10','LH005'),
('BBKD006','2026-10-11 08:20:00','Đạt','Đã xác nhận','Kiểm định đạt - dữ liệu thử UC10','LH006'),
('BBKD007','2026-10-11 08:30:00','Đạt','Đã xác nhận','Kiểm định đạt - dữ liệu thử UC10','LH007'),
('BBKD008','2026-10-11 08:40:00','Đạt','Đã xác nhận','Kiểm định đạt - dữ liệu thử UC10','LH008'),
('BBKD009','2026-10-11 08:50:00','Đạt','Đã xác nhận','Kiểm định đạt - dữ liệu thử UC10','LH009'),
('BBKD010','2026-10-11 09:00:00','Đạt','Đã xác nhận','Kiểm định đạt - dữ liệu thử UC10','LH010'),
('BBKD011','2026-10-11 09:10:00','Đạt','Đã xác nhận','Kiểm định đạt - dữ liệu thử UC10','LH011'),
('BBKD012','2026-10-11 09:20:00','Đạt','Đã xác nhận','Kiểm định đạt - dữ liệu thử UC10','LH012'),
('BBKD013','2026-10-11 09:30:00','Đạt','Đã xác nhận','Kiểm định đạt - dữ liệu thử UC10','LH013');

COMMIT;

-- Kiem tra 10 lo moi du dieu kien UC10.
SELECT lh.maLoHang, lh.soLo, hh.tenHangHoa, bb.ketQua, bb.trangThai AS trangThaiKiemDinh
FROM QLK02.LoHang lh
JOIN QLK02.HangHoa hh ON hh.maHangHoa = lh.maHangHoa
JOIN QLK02.BienBanKiemDinhChatLuong bb ON bb.maLoHang = lh.maLoHang
WHERE lh.maLoHang BETWEEN 'LH014' AND 'LH023'
  AND bb.ketQua = 'Đạt' AND bb.trangThai = 'Đã xác nhận'
  AND NOT EXISTS (SELECT 1 FROM QLK02.PhieuNhapKho pn WHERE pn.maLoHang = lh.maLoHang)
ORDER BY lh.maLoHang;

ALTER TABLE QLK02.LoHang
ADD COLUMN soLuongTiepNhan FLOAT NOT NULL DEFAULT 0
AFTER soLo;

ALTER TABLE QLK02.LoHang
ADD CONSTRAINT chk_LoHang_soLuongTiepNhan
CHECK (soLuongTiepNhan >= 0);

UPDATE QLK02.LoHang
SET soLuongTiepNhan = CASE maLoHang
    WHEN 'LH001' THEN 100
    WHEN 'LH002' THEN 150
    WHEN 'LH003' THEN 80
    WHEN 'LH004' THEN 120
    WHEN 'LH005' THEN 200
    WHEN 'LH006' THEN 180
    WHEN 'LH007' THEN 250
    WHEN 'LH008' THEN 160
    WHEN 'LH009' THEN 300
    WHEN 'LH010' THEN 220
    WHEN 'LH011' THEN 140
    WHEN 'LH012' THEN 190
    WHEN 'LH013' THEN 170
    ELSE soLuongTiepNhan
END
WHERE maLoHang IN (
    'LH001','LH002','LH003','LH004','LH005',
    'LH006','LH007','LH008','LH009','LH010',
    'LH011','LH012','LH013'
);