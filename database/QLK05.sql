-- ==============================================================================
-- QLK05: CSDL NGHIỆP VỤ KHO - SCHEMA NỀN & DỮ LIỆU MẪU CHO UC24 KIỂM KÊ KHO
-- Thiết kế theo kiến trúc Modular Monolith & Database-per-Service.
-- Schema chuẩn gồm 12 bảng thực thể thuộc phạm vi nghiệp vụ UC24.
-- Khóa chính, khóa ngoại dạng VARCHAR(15), phân loại hàng hóa rõ ràng.
-- Ràng buộc trạng thái biên bản kiểm kê chuẩn: 'Chờ kiểm kê', 'Đã đối chiếu', 'Đã hủy'.
-- maNhanVien trong BienBanKiemKe mang mã nhân viên từ QLK00.NhanVien (NV01, NV02, NV03).
--
-- LƯU Ý KỸ THUẬT:
--   - Mapping quan hệ Kho -> Khu vực -> Vị trí lưu trữ trong phần dữ liệu mẫu là
--     dữ liệu chuẩn hóa phục vụ demo kỹ thuật và chờ nhóm trưởng xác nhận chính thức.
--   - Toàn bộ dữ liệu mẫu sử dụng INSERT ... ON DUPLICATE KEY UPDATE để hỗ trợ
--     import nhiều lần qua phpMyAdmin hoặc CLI mà không gây lỗi trùng khóa.
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS QLK05
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE QLK05;
SET NAMES utf8mb4;

-- ==============================================================================
-- PHẦN 1: SCHEMA 12 BẢNG THỰC THỂ
-- ==============================================================================

-- 1. Bảng Kho
CREATE TABLE IF NOT EXISTS Kho (
  maKho VARCHAR(15) PRIMARY KEY,
  tenKho VARCHAR(200) NOT NULL,
  loaiKho VARCHAR(100) NOT NULL,
  diaChi VARCHAR(500) NULL,
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Hoạt động',
  moTa VARCHAR(500) NULL,
  CONSTRAINT ck_Kho_TrangThai
    CHECK (trangThai IN ('Hoạt động', 'Ngừng hoạt động'))
) ENGINE=InnoDB;

-- 2. Bảng KhuVuc
CREATE TABLE IF NOT EXISTS KhuVuc (
  maKhuVuc VARCHAR(15) PRIMARY KEY,
  tenKhuVuc VARCHAR(200) NOT NULL,
  loaiKhuVuc VARCHAR(100) NOT NULL,
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Hoạt động',
  moTa VARCHAR(500) NULL,
  maKho VARCHAR(15) NOT NULL,
  CONSTRAINT fk_KhuVuc_Kho FOREIGN KEY (maKho) REFERENCES Kho(maKho),
  CONSTRAINT ck_KhuVuc_TrangThai
    CHECK (trangThai IN ('Hoạt động', 'Ngừng hoạt động'))
) ENGINE=InnoDB;

-- 3. Bảng ViTriLuuTru
CREATE TABLE IF NOT EXISTS ViTriLuuTru (
  maViTri VARCHAR(15) PRIMARY KEY,
  tenViTri VARCHAR(200) NOT NULL,
  sucChuaToiDa FLOAT NOT NULL,
  donViSucChua VARCHAR(50) NOT NULL,
  moTa VARCHAR(500) NULL,
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Hoạt động',
  maKhuVuc VARCHAR(15) NOT NULL,
  CONSTRAINT fk_ViTriLuuTru_KhuVuc FOREIGN KEY (maKhuVuc) REFERENCES KhuVuc(maKhuVuc),
  CONSTRAINT ck_ViTriLuuTru_SucChua CHECK (sucChuaToiDa >= 0),
  CONSTRAINT ck_ViTriLuuTru_TrangThai
    CHECK (trangThai IN ('Hoạt động', 'Ngừng hoạt động'))
) ENGINE=InnoDB;

-- 4. Bảng HangHoa
CREATE TABLE IF NOT EXISTS HangHoa (
  maHang VARCHAR(15) PRIMARY KEY,
  tenHang VARCHAR(500) NOT NULL,
  nhomHang VARCHAR(200) NOT NULL,
  phanLoaiHangHoa VARCHAR(20) NOT NULL DEFAULT 'NGUYEN_LIEU',
  donViTinh VARCHAR(50) NOT NULL,
  quyCachDongGoi VARCHAR(200) NULL,
  moTa VARCHAR(500) NULL,
  trangThaiSuDung VARCHAR(50) NOT NULL DEFAULT 'Đang sử dụng',
  CONSTRAINT ck_HangHoa_PhanLoai
    CHECK (phanLoaiHangHoa IN ('NGUYEN_LIEU', 'THANH_PHAM')),
  CONSTRAINT ck_HangHoa_TrangThaiSuDung
    CHECK (trangThaiSuDung IN ('Đang sử dụng', 'Ngừng sử dụng'))
) ENGINE=InnoDB;

-- 5. Bảng LoHang
CREATE TABLE IF NOT EXISTS LoHang (
  maLoHang VARCHAR(15) PRIMARY KEY,
  tenLoHang VARCHAR(200) NOT NULL,
  soLuongTiepNhan FLOAT NOT NULL,
  donViTinh VARCHAR(50) NOT NULL,
  thoiGianTiepNhan DATETIME NULL,
  ngayNhap DATETIME NULL,
  ngaySanXuat DATETIME NULL,
  hanSuDung DATETIME NULL,
  trangThaiLo VARCHAR(50) NOT NULL DEFAULT 'Đang lưu kho',
  trangThaiChatLuong VARCHAR(50) NOT NULL DEFAULT 'Đạt chuẩn',
  trangThaiPhanBo VARCHAR(50) NOT NULL DEFAULT 'Đã phân bổ',
  ghiChu VARCHAR(500) NULL,
  maHang VARCHAR(15) NOT NULL,
  CONSTRAINT fk_LoHang_HangHoa FOREIGN KEY (maHang) REFERENCES HangHoa(maHang),
  CONSTRAINT ck_LoHang_SoLuong CHECK (soLuongTiepNhan >= 0)
) ENGINE=InnoDB;

-- 6. Bảng TonKho
CREATE TABLE IF NOT EXISTS TonKho (
  maTonKho VARCHAR(15) PRIMARY KEY,
  soLuongTon FLOAT NOT NULL,
  soLuongDaGiu FLOAT NOT NULL DEFAULT 0,
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Đang lưu kho',
  thoiDiemCapNhat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  maLoHang VARCHAR(15) NOT NULL,
  maKho VARCHAR(15) NOT NULL,
  maViTri VARCHAR(15) NOT NULL,
  CONSTRAINT fk_TonKho_LoHang FOREIGN KEY (maLoHang) REFERENCES LoHang(maLoHang),
  CONSTRAINT fk_TonKho_Kho FOREIGN KEY (maKho) REFERENCES Kho(maKho),
  CONSTRAINT fk_TonKho_ViTri FOREIGN KEY (maViTri) REFERENCES ViTriLuuTru(maViTri),
  CONSTRAINT ck_TonKho_SoLuong CHECK (soLuongTon >= 0 AND soLuongDaGiu >= 0)
) ENGINE=InnoDB;

-- 7. Bảng BienBanKiemKe
CREATE TABLE IF NOT EXISTS BienBanKiemKe (
  maBienBanKiemKe VARCHAR(15) PRIMARY KEY,
  ngayLap DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  loaiKiemKe VARCHAR(20) NOT NULL DEFAULT 'NGUYEN_LIEU',
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Chờ kiểm kê',
  tienDoKiemKe FLOAT NOT NULL DEFAULT 0,
  thoiDiemBatDau DATETIME NOT NULL,
  thoiDiemKetThucDuKien DATETIME NOT NULL,
  thoiDiemKetThucThucTe DATETIME NULL,
  ghiChu VARCHAR(500) NULL,
  maKho VARCHAR(15) NOT NULL,
  maKhuVuc VARCHAR(15) NULL,
  maViTri VARCHAR(15) NULL,
  maNhanVien VARCHAR(20) NOT NULL,
  CONSTRAINT fk_BienBanKiemKe_Kho FOREIGN KEY (maKho) REFERENCES Kho(maKho),
  CONSTRAINT fk_BienBanKiemKe_KhuVuc FOREIGN KEY (maKhuVuc) REFERENCES KhuVuc(maKhuVuc),
  CONSTRAINT fk_BienBanKiemKe_ViTri FOREIGN KEY (maViTri) REFERENCES ViTriLuuTru(maViTri),
  CONSTRAINT ck_BienBanKiemKe_TienDo CHECK (tienDoKiemKe BETWEEN 0 AND 100),
  CONSTRAINT ck_BienBanKiemKe_ThoiGian CHECK (thoiDiemKetThucDuKien > thoiDiemBatDau),
  CONSTRAINT ck_BienBanKiemKe_LoaiKiemKe
    CHECK (loaiKiemKe IN ('NGUYEN_LIEU', 'THANH_PHAM')),
  CONSTRAINT ck_BienBanKiemKe_TrangThai
    CHECK (trangThai IN ('Chờ kiểm kê', 'Đã đối chiếu', 'Đã hủy')),
  INDEX idx_BienBanKiemKe_NgayTrangThai (ngayLap, trangThai),
  INDEX idx_BienBanKiemKe_Kho (maKho, maKhuVuc)
) ENGINE=InnoDB;

-- 8. Bảng PhamViKiemKe
CREATE TABLE IF NOT EXISTS PhamViKiemKe (
  maBienBanKiemKe VARCHAR(15) NOT NULL,
  maTonKho VARCHAR(15) NOT NULL,
  soLuongTonChup FLOAT NOT NULL,
  maLoHang VARCHAR(15) NOT NULL,
  maViTri VARCHAR(15) NOT NULL,
  PRIMARY KEY (maBienBanKiemKe, maTonKho),
  CONSTRAINT fk_PhamViKiemKe_BienBan FOREIGN KEY (maBienBanKiemKe) REFERENCES BienBanKiemKe(maBienBanKiemKe) ON DELETE CASCADE,
  CONSTRAINT fk_PhamViKiemKe_TonKho FOREIGN KEY (maTonKho) REFERENCES TonKho(maTonKho),
  CONSTRAINT fk_PhamViKiemKe_LoHang FOREIGN KEY (maLoHang) REFERENCES LoHang(maLoHang),
  CONSTRAINT fk_PhamViKiemKe_ViTri FOREIGN KEY (maViTri) REFERENCES ViTriLuuTru(maViTri),
  CONSTRAINT ck_PhamViKiemKe_SoLuongChup CHECK (soLuongTonChup >= 0)
) ENGINE=InnoDB;

-- 9. Bảng KetQuaKiemKe
CREATE TABLE IF NOT EXISTS KetQuaKiemKe (
  maKetQuaKiemKe VARCHAR(15) PRIMARY KEY,
  soLuongHeThong FLOAT NOT NULL,
  soLuongThucTe FLOAT NOT NULL,
  chenhLech FLOAT NOT NULL,
  tinhTrangHangHoa VARCHAR(100) NOT NULL DEFAULT 'Bình thường',
  thoiDiemKiemKe DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ghiChu VARCHAR(500) NULL,
  maBienBanKiemKe VARCHAR(15) NOT NULL,
  maTonKho VARCHAR(15) NOT NULL,
  maViTri VARCHAR(15) NOT NULL,
  CONSTRAINT fk_KetQuaKiemKe_BienBan FOREIGN KEY (maBienBanKiemKe) REFERENCES BienBanKiemKe(maBienBanKiemKe),
  CONSTRAINT fk_KetQuaKiemKe_TonKho FOREIGN KEY (maTonKho) REFERENCES TonKho(maTonKho),
  CONSTRAINT fk_KetQuaKiemKe_ViTri FOREIGN KEY (maViTri) REFERENCES ViTriLuuTru(maViTri),
  CONSTRAINT ck_KetQuaKiemKe_SoLuong CHECK (soLuongHeThong >= 0 AND soLuongThucTe >= 0),
  INDEX idx_KetQuaKiemKe_BienBan (maBienBanKiemKe)
) ENGINE=InnoDB;

-- 10. Bảng ChenhLechKiemKe
CREATE TABLE IF NOT EXISTS ChenhLechKiemKe (
  maChenhLech VARCHAR(15) PRIMARY KEY,
  soLuongHeThong FLOAT NOT NULL,
  soLuongThucTe FLOAT NOT NULL,
  chenhLech FLOAT NOT NULL,
  nguyenNhan VARCHAR(500) NULL,
  ketLuan VARCHAR(50) NOT NULL,
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Chưa xử lý',
  ghiChu VARCHAR(500) NULL,
  maKetQuaKiemKe VARCHAR(15) NOT NULL,
  maKho VARCHAR(15) NOT NULL,
  CONSTRAINT fk_ChenhLech_KetQua FOREIGN KEY (maKetQuaKiemKe) REFERENCES KetQuaKiemKe(maKetQuaKiemKe),
  CONSTRAINT fk_ChenhLech_Kho FOREIGN KEY (maKho) REFERENCES Kho(maKho),
  CONSTRAINT ck_ChenhLech_SoLuong CHECK (soLuongHeThong >= 0 AND soLuongThucTe >= 0 AND chenhLech <> 0),
  INDEX idx_ChenhLech_TrangThaiKho (trangThai, maKho)
) ENGINE=InnoDB;

-- 11. Bảng PhuongAnXuLy
CREATE TABLE IF NOT EXISTS PhuongAnXuLy (
  maPhuongAn VARCHAR(15) PRIMARY KEY,
  hinhThucXuLy VARCHAR(100) NOT NULL,
  noiDungXuLy VARCHAR(500) NOT NULL,
  mucDieuChinhTonKho FLOAT NOT NULL DEFAULT 0,
  trangThaiPheDuyet VARCHAR(50) NOT NULL DEFAULT 'Chờ duyệt',
  thoiDiemXuLy DATETIME NULL,
  ghiChu VARCHAR(500) NULL,
  maChenhLech VARCHAR(15) NOT NULL,
  CONSTRAINT fk_PhuongAnXuLy_ChenhLech FOREIGN KEY (maChenhLech) REFERENCES ChenhLechKiemKe(maChenhLech),
  INDEX idx_PhuongAnXuLy_ChenhLech (maChenhLech)
) ENGINE=InnoDB;

-- 12. Bảng HangBatThuong
CREATE TABLE IF NOT EXISTS HangBatThuong (
  maHangBatThuong VARCHAR(15) PRIMARY KEY,
  tinhTrangBatThuong VARCHAR(100) NOT NULL,
  soLuongBiAnhHuong FLOAT NOT NULL,
  nguyenNhanBatThuong VARCHAR(500) NULL,
  thoiDiemPhatHien DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Chưa xử lý',
  ghiChu VARCHAR(500) NULL,
  maLoHang VARCHAR(15) NOT NULL,
  maTonKho VARCHAR(15) NOT NULL,
  maKetQuaKiemKe VARCHAR(15) NULL,
  maPhuongAn VARCHAR(15) NULL,
  CONSTRAINT fk_HangBatThuong_LoHang FOREIGN KEY (maLoHang) REFERENCES LoHang(maLoHang),
  CONSTRAINT fk_HangBatThuong_TonKho FOREIGN KEY (maTonKho) REFERENCES TonKho(maTonKho),
  CONSTRAINT fk_HangBatThuong_KetQua FOREIGN KEY (maKetQuaKiemKe) REFERENCES KetQuaKiemKe(maKetQuaKiemKe),
  CONSTRAINT fk_HangBatThuong_PhuongAn FOREIGN KEY (maPhuongAn) REFERENCES PhuongAnXuLy(maPhuongAn),
  CONSTRAINT ck_HangBatThuong_SoLuong CHECK (soLuongBiAnhHuong > 0)
) ENGINE=InnoDB;

-- ==============================================================================
-- PHẦN 2: DỮ LIỆU MẪU CHUẨN CHO NGHIỆP VỤ UC24 (SEED DATA)
-- Sử dụng chuẩn mã nhân viên từ QLK00: NV01 (Quản trị viên), NV02 (Quản lý kho), NV03 (Nhân viên kho).
-- Trạng thái biên bản kiểm kê tuân thủ nghiêm ngặt: 'Chờ kiểm kê', 'Đã đối chiếu', 'Đã hủy'.
-- ==============================================================================

-- 1. Danh mục Kho
INSERT INTO Kho (maKho, tenKho, loaiKho, diaChi, trangThai, moTa) VALUES
('KHO01', 'Kho A', 'Kho khô', 'Khu CN Sóng Thần, Dĩ An, Bình Dương', 'Hoạt động', 'Kho chứa nguyên liệu bắp hạt, đậu nành, cám gạo'),
('KHO02', 'Kho B', 'Kho thành phẩm', 'Khu CN VSIP 1, Thuận An, Bình Dương', 'Hoạt động', 'Kho chứa thức ăn chăn nuôi đóng bao')
ON DUPLICATE KEY UPDATE
  tenKho = VALUES(tenKho),
  loaiKho = VALUES(loaiKho),
  diaChi = VALUES(diaChi),
  trangThai = VALUES(trangThai),
  moTa = VALUES(moTa);

-- 2. Danh mục Khu vực
INSERT INTO KhuVuc (maKhuVuc, tenKhuVuc, loaiKhuVuc, trangThai, moTa, maKho) VALUES
('KV01', 'A1', 'Lưu trữ khô', 'Hoạt động', 'Khu vực bắp hạt và khô dầu đậu nành', 'KHO01'),
('KV02', 'A2', 'Lưu trữ thông thoáng', 'Hoạt động', 'Khu vực cám gạo sấy và phụ gia khoáng', 'KHO01'),
('KV03', 'B1', 'Kho thành phẩm', 'Hoạt động', 'Khu vực thức ăn đóng bao', 'KHO02')
ON DUPLICATE KEY UPDATE
  tenKhuVuc = VALUES(tenKhuVuc),
  loaiKhuVuc = VALUES(loaiKhuVuc),
  trangThai = VALUES(trangThai),
  moTa = VALUES(moTa),
  maKho = VALUES(maKho);

-- 3. Danh mục Vị trí lưu trữ
-- Ghi chú: Mapping tên vị trí theo khu vực (Khu A1 -> Kệ A1, Khu A2 -> Kệ A2, Khu B1 -> Kệ B1)
-- là dữ liệu chuẩn hóa phục vụ demo kỹ thuật và chờ nhóm trưởng xác nhận chính thức.
INSERT INTO ViTriLuuTru (maViTri, tenViTri, sucChuaToiDa, donViSucChua, moTa, trangThai, maKhuVuc) VALUES
('VT-A101', 'Kệ A1 - Tầng 1', 5000, 'kg', 'Kệ pallet tầng trệt tải trọng lớn', 'Hoạt động', 'KV01'),
('VT-A102', 'Kệ A1 - Tầng 2', 5000, 'kg', 'Kệ pallet tầng 2 tải trọng lớn', 'Hoạt động', 'KV01'),
('VT-A201', 'Kệ A1 - Tầng 3', 6000, 'kg', 'Kệ khu vực đậu nành', 'Hoạt động', 'KV01'),
('VT-B101', 'Kệ A2 - Tầng 1', 4000, 'kg', 'Kệ chứa bao cám sấy', 'Hoạt động', 'KV02'),
('VT-B102', 'Kệ A2 - Tầng 2', 4000, 'kg', 'Kệ chứa bao premix vi lượng', 'Hoạt động', 'KV02'),
('VT-C101', 'Kệ B1 - Tầng 1', 8000, 'kg', 'Khu vực xuất thành phẩm đóng bao', 'Hoạt động', 'KV03')
ON DUPLICATE KEY UPDATE
  tenViTri = VALUES(tenViTri),
  sucChuaToiDa = VALUES(sucChuaToiDa),
  donViSucChua = VALUES(donViSucChua),
  moTa = VALUES(moTa),
  trangThai = VALUES(trangThai),
  maKhuVuc = VALUES(maKhuVuc);

-- 4. Danh mục Hàng hóa (Nguyên liệu vs Thành phẩm)
INSERT INTO HangHoa (maHang, tenHang, nhomHang, phanLoaiHangHoa, donViTinh, quyCachDongGoi, moTa, trangThaiSuDung) VALUES
('HH001', 'Bắp hạt đỏ', 'Nguyên liệu thô', 'NGUYEN_LIEU', 'kg', 'Bao 50kg', 'Độ ẩm < 14%, tạp chất < 2%, nguyên chất', 'Đang sử dụng'),
('HH002', 'Khô dầu đậu nành 48% đạm', 'Nguyên liệu đạm', 'NGUYEN_LIEU', 'kg', 'Bao 50kg', 'Hàm lượng đạm thô tối thiểu 48%', 'Đang sử dụng'),
('HH003', 'Cám gạo sấy khô', 'Cám và phụ phẩm', 'NGUYEN_LIEU', 'kg', 'Bao 40kg', 'Cám gạo sấy đạt chuẩn vi sinh, không vón cục', 'Đang sử dụng'),
('HH004', 'Khoáng vi lượng cho heo thịt', 'Phụ gia vi lượng', 'NGUYEN_LIEU', 'bao', 'Bao 25kg', 'Bổ sung khoáng vi lượng, vitamin tổng hợp', 'Đang sử dụng'),
('HH005', 'Thức ăn heo con', 'Thành phẩm', 'THANH_PHAM', 'bao', 'Bao 25kg', 'Dạng viên 2mm, dùng cho heo con', 'Đang sử dụng')
ON DUPLICATE KEY UPDATE
  tenHang = VALUES(tenHang),
  nhomHang = VALUES(nhomHang),
  phanLoaiHangHoa = VALUES(phanLoaiHangHoa),
  donViTinh = VALUES(donViTinh),
  quyCachDongGoi = VALUES(quyCachDongGoi),
  moTa = VALUES(moTa),
  trangThaiSuDung = VALUES(trangThaiSuDung);

-- 5. Danh mục Lô hàng
INSERT INTO LoHang (maLoHang, tenLoHang, soLuongTiepNhan, donViTinh, thoiGianTiepNhan, ngayNhap, ngaySanXuat, hanSuDung, trangThaiLo, trangThaiChatLuong, trangThaiPhanBo, ghiChu, maHang) VALUES
('LH2610-01', 'Lô bắp hạt đỏ tháng 10/2026', 5000, 'kg', '2026-10-01 08:00:00', '2026-10-01 09:30:00', '2026-09-15 00:00:00', '2027-09-15 00:00:00', 'Đang lưu kho', 'Đạt chuẩn', 'Đã phân bổ', 'Kiểm tra độ ẩm đạt chuẩn 13.2%', 'HH001'),
('LH2610-02', 'Lô đậu nành tháng 10/2026', 4000, 'kg', '2026-10-03 10:00:00', '2026-10-03 11:00:00', '2026-09-20 00:00:00', '2027-09-20 00:00:00', 'Đang lưu kho', 'Đạt chuẩn', 'Đã phân bổ', 'Hàm lượng đạm đạt chuẩn 48.5%', 'HH002'),
('LH2610-03', 'Lô cám gạo sấy tháng 10/2026', 3000, 'kg', '2026-10-08 13:30:00', '2026-10-08 14:00:00', '2026-10-01 00:00:00', '2027-04-01 00:00:00', 'Đang lưu kho', 'Đạt chuẩn', 'Đã phân bổ', 'Mùi thơm tự nhiên, độ ẩm 11%', 'HH003'),
('LH2610-04', 'Lô khoáng vi lượng heo thịt tháng 10/2026', 100, 'bao', '2026-10-10 09:00:00', '2026-10-10 10:00:00', '2026-10-05 00:00:00', '2027-10-05 00:00:00', 'Đang lưu kho', 'Đạt chuẩn', 'Đã phân bổ', 'Bao bì nguyên vẹn, còn kẹp chì', 'HH004'),
('LH2610-05', 'Lô thức ăn heo con tháng 10/2026', 200, 'bao', '2026-10-12 15:00:00', '2026-10-12 16:00:00', '2026-10-11 00:00:00', '2027-04-11 00:00:00', 'Đang lưu kho', 'Đạt chuẩn', 'Đã phân bổ', 'Đạt chuẩn xuất bán', 'HH005')
ON DUPLICATE KEY UPDATE
  tenLoHang = VALUES(tenLoHang),
  soLuongTiepNhan = VALUES(soLuongTiepNhan),
  donViTinh = VALUES(donViTinh),
  trangThaiLo = VALUES(trangThaiLo),
  trangThaiChatLuong = VALUES(trangThaiChatLuong),
  trangThaiPhanBo = VALUES(trangThaiPhanBo),
  ghiChu = VALUES(ghiChu),
  maHang = VALUES(maHang);

-- 6. Danh mục Tồn kho
INSERT INTO TonKho (maTonKho, soLuongTon, soLuongDaGiu, trangThai, thoiDiemCapNhat, maLoHang, maKho, maViTri) VALUES
('TK001', 2500, 0, 'Đang lưu kho', '2026-10-15 08:00:00', 'LH2610-01', 'KHO01', 'VT-A101'),
('TK002', 2500, 0, 'Đang lưu kho', '2026-10-15 08:00:00', 'LH2610-01', 'KHO01', 'VT-A102'),
('TK003', 4000, 0, 'Đang lưu kho', '2026-10-15 08:00:00', 'LH2610-02', 'KHO01', 'VT-A201'),
('TK004', 3000, 0, 'Đang lưu kho', '2026-10-15 08:00:00', 'LH2610-03', 'KHO01', 'VT-B101'),
('TK005', 100, 0, 'Đang lưu kho', '2026-10-15 08:00:00', 'LH2610-04', 'KHO01', 'VT-B102'),
('TK006', 200, 0, 'Đang lưu kho', '2026-10-15 08:00:00', 'LH2610-05', 'KHO02', 'VT-C101')
ON DUPLICATE KEY UPDATE
  soLuongTon = VALUES(soLuongTon),
  soLuongDaGiu = VALUES(soLuongDaGiu),
  trangThai = VALUES(trangThai),
  thoiDiemCapNhat = VALUES(thoiDiemCapNhat);

-- 7. Biên bản kiểm kê mẫu (Mã nhân viên: NV01, NV02, NV03; Trạng thái: 'Chờ kiểm kê', 'Đã đối chiếu', 'Đã hủy')
INSERT INTO BienBanKiemKe (maBienBanKiemKe, ngayLap, loaiKiemKe, trangThai, tienDoKiemKe, thoiDiemBatDau, thoiDiemKetThucDuKien, thoiDiemKetThucThucTe, ghiChu, maKho, maKhuVuc, maViTri, maNhanVien) VALUES
('BB2610-0001', '2026-10-20 08:00:00', 'NGUYEN_LIEU', 'Đã đối chiếu', 100, '2026-10-20 08:30:00', '2026-10-20 16:30:00', '2026-10-20 16:00:00', 'Kiểm kê định kỳ tháng 10/2026 Khu Vực Bắp & Đậu', 'KHO01', 'KV01', NULL, 'NV02'),
('BB2610-0002', '2026-10-22 09:00:00', 'NGUYEN_LIEU', 'Chờ kiểm kê', 0, '2026-10-22 09:30:00', '2026-10-22 17:30:00', NULL, 'Kiểm kê định kỳ khu A2', 'KHO01', 'KV02', NULL, 'NV02'),
('BB2610-0003', '2026-10-24 14:00:00', 'NGUYEN_LIEU', 'Đã hủy', 0, '2026-10-24 14:30:00', '2026-10-24 18:00:00', NULL, 'Hủy do điều chuyển đột xuất sang ca đêm', 'KHO01', 'KV01', NULL, 'NV02'),
('BB2610-0004', '2026-10-26 08:30:00', 'THANH_PHAM', 'Chờ kiểm kê', 0, '2026-10-26 09:00:00', '2026-10-26 17:00:00', NULL, 'Kiểm kê kho thành phẩm thức ăn chăn nuôi', 'KHO02', 'KV03', NULL, 'NV01')
ON DUPLICATE KEY UPDATE
  ngayLap = VALUES(ngayLap),
  loaiKiemKe = VALUES(loaiKiemKe),
  trangThai = VALUES(trangThai),
  tienDoKiemKe = VALUES(tienDoKiemKe),
  thoiDiemBatDau = VALUES(thoiDiemBatDau),
  thoiDiemKetThucDuKien = VALUES(thoiDiemKetThucDuKien),
  thoiDiemKetThucThucTe = VALUES(thoiDiemKetThucThucTe),
  ghiChu = VALUES(ghiChu),
  maKho = VALUES(maKho),
  maKhuVuc = VALUES(maKhuVuc),
  maNhanVien = VALUES(maNhanVien);

-- 8. Phạm vi kiểm kê (Chụp tồn kho tại thời điểm lập)
INSERT INTO PhamViKiemKe (maBienBanKiemKe, maTonKho, soLuongTonChup, maLoHang, maViTri) VALUES
('BB2610-0001', 'TK001', 2500, 'LH2610-01', 'VT-A101'),
('BB2610-0001', 'TK002', 2500, 'LH2610-01', 'VT-A102'),
('BB2610-0001', 'TK003', 4000, 'LH2610-02', 'VT-A201'),
('BB2610-0002', 'TK004', 3000, 'LH2610-03', 'VT-B101'),
('BB2610-0002', 'TK005', 100, 'LH2610-04', 'VT-B102'),
('BB2610-0004', 'TK006', 200, 'LH2610-05', 'VT-C101')
ON DUPLICATE KEY UPDATE
  soLuongTonChup = VALUES(soLuongTonChup),
  maLoHang = VALUES(maLoHang),
  maViTri = VALUES(maViTri);

-- 9. Kết quả kiểm kê (Dữ liệu mẫu cho BB2610-0001 đã đối chiếu)
INSERT INTO KetQuaKiemKe (maKetQuaKiemKe, soLuongHeThong, soLuongThucTe, chenhLech, tinhTrangHangHoa, thoiDiemKiemKe, ghiChu, maBienBanKiemKe, maTonKho, maViTri) VALUES
('KQ2610-0001', 2500, 2500, 0, 'Bình thường', '2026-10-20 09:30:00', 'Số lượng nguyên vẹn', 'BB2610-0001', 'TK001', 'VT-A101'),
('KQ2610-0002', 2500, 2480, -20, 'Rách bao', '2026-10-20 10:45:00', 'Phát hiện bao rách rơi vãi', 'BB2610-0001', 'TK002', 'VT-A102'),
('KQ2610-0003', 4000, 4015, 15, 'Bình thường', '2026-10-20 14:15:00', 'Thừa 15kg do sai số cân đóng gói', 'BB2610-0001', 'TK003', 'VT-A201')
ON DUPLICATE KEY UPDATE
  soLuongThucTe = VALUES(soLuongThucTe),
  chenhLech = VALUES(chenhLech),
  tinhTrangHangHoa = VALUES(tinhTrangHangHoa),
  ghiChu = VALUES(ghiChu);

-- 10. Chênh lệch kiểm kê (Chỉ phát sinh cho các dòng lệch thực tế khác 0)
INSERT INTO ChenhLechKiemKe (maChenhLech, soLuongHeThong, soLuongThucTe, chenhLech, nguyenNhan, ketLuan, trangThai, ghiChu, maKetQuaKiemKe, maKho) VALUES
('CL2610-0001', 2500, 2480, -20, 'Bao bì rách trong quá trình xe nâng di chuyển', 'Thiếu', 'Chưa xử lý', 'Cần thu gom và cân lại phần hao hụt', 'KQ2610-0002', 'KHO01'),
('CL2610-0002', 4000, 4015, 15, 'Sai số gộp cân đóng bao từ nhà máy', 'Thừa', 'Chưa xử lý', 'Ghi nhận thừa theo thực tế', 'KQ2610-0003', 'KHO01')
ON DUPLICATE KEY UPDATE
  nguyenNhan = VALUES(nguyenNhan),
  ketLuan = VALUES(ketLuan),
  trangThai = VALUES(trangThai),
  ghiChu = VALUES(ghiChu);

-- 11. Phương án xử lý (Dữ liệu mẫu phương án giải quyết chênh lệch)
INSERT INTO PhuongAnXuLy (maPhuongAn, hinhThucXuLy, noiDungXuLy, mucDieuChinhTonKho, trangThaiPheDuyet, thoiDiemXuLy, ghiChu, maChenhLech) VALUES
('PA2610-0001', 'Ghi nhận hao hụt', 'Điều chỉnh giảm tồn kho 20kg do rách bao rơi vãi', -20, 'Chờ duyệt', '2026-10-21 09:00:00', 'Đề xuất quản lý kho duyệt phương án xử lý', 'CL2610-0001')
ON DUPLICATE KEY UPDATE
  hinhThucXuLy = VALUES(hinhThucXuLy),
  noiDungXuLy = VALUES(noiDungXuLy),
  mucDieuChinhTonKho = VALUES(mucDieuChinhTonKho),
  trangThaiPheDuyet = VALUES(trangThaiPheDuyet),
  thoiDiemXuLy = VALUES(thoiDiemXuLy),
  ghiChu = VALUES(ghiChu);

-- 12. Hàng bất thường phát hiện tại KQ2610-0002
INSERT INTO HangBatThuong (maHangBatThuong, tinhTrangBatThuong, soLuongBiAnhHuong, nguyenNhanBatThuong, thoiDiemPhatHien, trangThai, ghiChu, maLoHang, maTonKho, maKetQuaKiemKe, maPhuongAn) VALUES
('HBT2610-0001', 'Rách bao', 20, 'Rách mép bao khi bốc dỡ', '2026-10-20 10:45:00', 'Chưa xử lý', 'Chờ xử lý đóng bao lại', 'LH2610-01', 'TK002', 'KQ2610-0002', 'PA2610-0001')
ON DUPLICATE KEY UPDATE
  tinhTrangBatThuong = VALUES(tinhTrangBatThuong),
  soLuongBiAnhHuong = VALUES(soLuongBiAnhHuong),
  nguyenNhanBatThuong = VALUES(nguyenNhanBatThuong),
  ghiChu = VALUES(ghiChu),
  maPhuongAn = VALUES(maPhuongAn);
