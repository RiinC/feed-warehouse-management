-- =====================================================================
-- FeedWare - CẤU TRÚC CƠ SỞ DỮ LIỆU DUY NHẤT (schema.sql)
-- Chạy theo thứ tự: schema.sql -> seed_data.sql -> thong-bao.sql
-- File này đã bao gồm toàn bộ bảng/cột của mọi module (kể cả Module 3:
-- LuuKho + phê duyệt phiếu điều chuyển), nên không cần chạy file
-- migration riêng lẻ nào nữa.
-- =====================================================================
CREATE DATABASE IF NOT EXISTS `feed_wms` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `feed_wms`;
SET NAMES utf8mb4;

-- [06] Kho
CREATE TABLE IF NOT EXISTS `Kho` (
  `maKho` VARCHAR(7) NOT NULL,
  `tenKho` NVARCHAR(100) NOT NULL,
  `diaChi` NVARCHAR(200) NULL,
  `loaiKho` NVARCHAR(15) NOT NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Đang hoạt động',
  `ngayTao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`maKho`),
  CONSTRAINT `ck_Kho_1` CHECK (maKho REGEXP '^K(01|02|03|04|05|06)[0-9]{4}$'),
  CONSTRAINT `ck_Kho_2` CHECK (CHAR_LENGTH(TRIM(tenKho)) > 0),
  CONSTRAINT `ck_Kho_3` CHECK (loaiKho IN ('Nguyên liệu', 'Thành phẩm', 'Phụ gia', 'Bao bì', 'Tổng hợp', 'Hàng lỗi')),
  CONSTRAINT `ck_Kho_4` CHECK (trangThai IN ('Đang hoạt động', 'Đang bảo trì', 'Đã đóng cửa'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [02] NhanVien
CREATE TABLE IF NOT EXISTS `NhanVien` (
  `maNhanVien` VARCHAR(8) NOT NULL,
  `hoTen` NVARCHAR(60) NOT NULL,
  `soDienThoai` VARCHAR(15) NOT NULL,
  `email` VARCHAR(100) NULL,
  `chucVu` NVARCHAR(50) NOT NULL,
  `vaiTro` VARCHAR(20) NOT NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Đang làm việc',
  `ngayTao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `maKho` VARCHAR(7) NULL,
  PRIMARY KEY (`maNhanVien`),
  CONSTRAINT `uq_NhanVien_1` UNIQUE (`soDienThoai`),
  CONSTRAINT `uq_NhanVien_2` UNIQUE (`email`),
  CONSTRAINT `ck_NhanVien_1` CHECK (maNhanVien REGEXP '^NV[0-9]{6}$'),
  CONSTRAINT `ck_NhanVien_2` CHECK (CHAR_LENGTH(TRIM(hoTen)) > 0),
  CONSTRAINT `ck_NhanVien_3` CHECK (vaiTro IN ('Quản trị viên', 'Ban giám đốc', 'Quản lý kho', 'Nhân viên kho', 'Nhân viên QC')),
  CONSTRAINT `ck_NhanVien_4` CHECK (trangThai IN ('Đang làm việc', 'Đã nghỉ việc', 'Đang tạm nghỉ')),
  CONSTRAINT `ck_NhanVien_5` CHECK ((vaiTro IN ('Quản lý kho', 'Nhân viên kho') AND maKho IS NOT NULL) OR (vaiTro NOT IN ('Quản lý kho', 'Nhân viên kho') AND maKho IS NULL)),
  CONSTRAINT `fk_NhanVien_1` FOREIGN KEY (`maKho`) REFERENCES `Kho`(`maKho`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_NhanVien_vaiTro (vaiTro, trangThai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [01] TaiKhoan
CREATE TABLE IF NOT EXISTS `TaiKhoan` (
  `maTaiKhoan` VARCHAR(8) NOT NULL,
  `tenDangNhap` VARCHAR(50) NOT NULL,
  `matKhau` VARCHAR(255) NOT NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Đang hoạt động',
  `ngayTao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lanDangNhapCuoi` DATETIME NULL,
  `maNhanVien` VARCHAR(8) NOT NULL,
  PRIMARY KEY (`maTaiKhoan`),
  CONSTRAINT `uq_TaiKhoan_1` UNIQUE (`tenDangNhap`),
  CONSTRAINT `uq_TaiKhoan_2` UNIQUE (`maNhanVien`),
  CONSTRAINT `ck_TaiKhoan_1` CHECK (maTaiKhoan REGEXP '^TK[0-9]{6}$'),
  CONSTRAINT `ck_TaiKhoan_2` CHECK (CHAR_LENGTH(TRIM(tenDangNhap)) > 0),
  CONSTRAINT `ck_TaiKhoan_3` CHECK (trangThai IN ('Chờ kích hoạt', 'Đang hoạt động', 'Đã khóa')),
  CONSTRAINT `fk_TaiKhoan_1` FOREIGN KEY (`maNhanVien`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [03] NhaCungCap
CREATE TABLE IF NOT EXISTS `NhaCungCap` (
  `maNCC` VARCHAR(9) NOT NULL,
  `tenNCC` NVARCHAR(150) NOT NULL,
  `diaChi` NVARCHAR(200) NULL,
  `soDienThoai` VARCHAR(15) NOT NULL,
  `email` VARCHAR(100) NULL,
  `maSoThue` VARCHAR(14) NULL,
  `trangThai` NVARCHAR(18) NOT NULL DEFAULT 'Đang giao dịch',
  PRIMARY KEY (`maNCC`),
  CONSTRAINT `ck_NhaCungCap_1` CHECK (maNCC REGEXP '^NCC[0-9]{6}$'),
  CONSTRAINT `ck_NhaCungCap_2` CHECK (CHAR_LENGTH(TRIM(tenNCC)) > 0),
  CONSTRAINT `ck_NhaCungCap_3` CHECK (maSoThue IS NULL OR maSoThue REGEXP'^([0-9]{10}|[0-9]{10}-[0-9]{3})$'),
  CONSTRAINT `ck_NhaCungCap_4` CHECK (trangThai IN ('Đang giao dịch', 'Đã ngừng giao dịch'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [04] XuongSanXuat
CREATE TABLE IF NOT EXISTS `XuongSanXuat` (
  `maXuong` VARCHAR(9) NOT NULL,
  `tenXuong` NVARCHAR(80) NOT NULL,
  `diaDiem` NVARCHAR(150) NULL,
  `nguoiPhuTrach` NVARCHAR(60) NULL,
  `soDienThoai` VARCHAR(15) NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Đang hoạt động',
  PRIMARY KEY (`maXuong`),
  CONSTRAINT `ck_XuongSanXuat_1` CHECK (maXuong REGEXP '^XSX[0-9]{6}$'),
  CONSTRAINT `ck_XuongSanXuat_2` CHECK (CHAR_LENGTH(TRIM(tenXuong)) > 0),
  CONSTRAINT `ck_XuongSanXuat_3` CHECK (trangThai IN ('Đang hoạt động', 'Đang tạm dừng'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [05] HangHoa
CREATE TABLE IF NOT EXISTS `HangHoa` (
  `maHangHoa` VARCHAR(10) NOT NULL,
  `tenHangHoa` NVARCHAR(120) NOT NULL,
  `loaiHangHoa` NVARCHAR(15) NOT NULL,
  `donViTinhChuan` VARCHAR(5) NOT NULL DEFAULT 'kg',
  `heSoQuyDoi` DOUBLE NOT NULL DEFAULT 1.00,
  `moTa` NVARCHAR(255) NULL,
  `dieuKienBaoQuan` NVARCHAR(150) NULL,
  `hanSuDungTieuChuanNgay` SMALLINT UNSIGNED NULL,
  `trangThai` NVARCHAR(16) NOT NULL DEFAULT 'Đang kinh doanh',
  PRIMARY KEY (`maHangHoa`),
  CONSTRAINT `ck_HangHoa_1` CHECK (maHangHoa REGEXP '^HH(01|02|03|04|05)[0-9]{6}$'),
  CONSTRAINT `ck_HangHoa_2` CHECK (CHAR_LENGTH(TRIM(tenHangHoa)) > 0),
  CONSTRAINT `ck_HangHoa_3` CHECK (loaiHangHoa IN ('Nguyên liệu', 'Thành phẩm', 'Bán thành phẩm', 'Bao bì', 'Phụ gia')),
  CONSTRAINT `ck_HangHoa_4` CHECK (donViTinhChuan IN ('kg', 'bao', 'thùng')),
  CONSTRAINT `ck_HangHoa_5` CHECK (heSoQuyDoi > 0),
  CONSTRAINT `ck_HangHoa_6` CHECK (hanSuDungTieuChuanNgay >= 0),
  CONSTRAINT `ck_HangHoa_7` CHECK (trangThai IN ('Đang kinh doanh', 'Đã ngừng sử dụng')),
  INDEX idx_HangHoa_loai (loaiHangHoa, trangThai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [07] KhuVuc
CREATE TABLE IF NOT EXISTS `KhuVuc` (
  `maKhuVuc` VARCHAR(8) NOT NULL,
  `maKho` VARCHAR(7) NOT NULL,
  `tenKhuVuc` NVARCHAR(80) NOT NULL,
  `loaiKhuVuc` VARCHAR(20) NOT NULL DEFAULT 'Lưu trữ chính',
  `trangThai` NVARCHAR(18) NOT NULL DEFAULT 'Đang hoạt động',
  `moTa` NVARCHAR(200) NULL,
  PRIMARY KEY (`maKhuVuc`),
  CONSTRAINT `ck_KhuVuc_1` CHECK (maKhuVuc REGEXP '^KV(01|02|03|04|05)[0-9]{4}$'),
  CONSTRAINT `ck_KhuVuc_2` CHECK (CHAR_LENGTH(TRIM(tenKhuVuc)) > 0),
  CONSTRAINT `ck_KhuVuc_3` CHECK (loaiKhuVuc IN ('Lưu trữ chính', 'Tiếp nhận tạm', 'Cách ly', 'Đóng gói', 'Xuất hàng')),
  CONSTRAINT `ck_KhuVuc_4` CHECK (trangThai IN ('Đang hoạt động', 'Đã ngừng hoạt động')),
  CONSTRAINT `fk_KhuVuc_1` FOREIGN KEY (`maKho`) REFERENCES `Kho`(`maKho`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_KhuVuc_kho_loai (maKho, loaiKhuVuc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [08] ViTriLuuTru
CREATE TABLE IF NOT EXISTS `ViTriLuuTru` (
  `maViTri` VARCHAR(14) NOT NULL,
  `tenViTri` NVARCHAR(60) NOT NULL,
  `maKhuVuc` VARCHAR(8) NOT NULL,
  `day` VARCHAR(2) NOT NULL,
  `ke` VARCHAR(2) NOT NULL,
  `tang` VARCHAR(2) NOT NULL,
  `o` VARCHAR(2) NOT NULL,
  `sucChuaToiDa` DOUBLE NOT NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Đang để trống',
  PRIMARY KEY (`maViTri`),
  CONSTRAINT `uq_ViTriLuuTru_1` UNIQUE (`maKhuVuc`, `day`, `ke`, `tang`, `o`),
  CONSTRAINT `ck_ViTriLuuTru_1` CHECK (maViTri REGEXP '^VT[0-9]{12}$'),
  CONSTRAINT `ck_ViTriLuuTru_2` CHECK (CHAR_LENGTH(TRIM(tenViTri)) > 0),
  CONSTRAINT `ck_ViTriLuuTru_3` CHECK (day REGEXP '^[0-9]{2}$'),
  CONSTRAINT `ck_ViTriLuuTru_4` CHECK (ke REGEXP '^[0-9]{2}$'),
  CONSTRAINT `ck_ViTriLuuTru_5` CHECK ((ke = '00' AND tang = '00') OR (ke <> '00' AND tang IN ('01', '02'))),
  CONSTRAINT `ck_ViTriLuuTru_6` CHECK (o REGEXP '^[0-9]{2}$'),
  CONSTRAINT `ck_ViTriLuuTru_7` CHECK (sucChuaToiDa > 0),
  CONSTRAINT `ck_ViTriLuuTru_8` CHECK (trangThai IN ('Đang để trống', 'Đang chứa hàng', 'Đã lấp đầy', 'Đang bảo trì', 'Đã khóa')),
  CONSTRAINT `ck_ViTriLuuTru_9` CHECK ((ke = '00' AND tang = '00') OR (ke <> '00' AND tang IN ('01','02'))),
  CONSTRAINT `fk_ViTriLuuTru_1` FOREIGN KEY (`maKhuVuc`) REFERENCES `KhuVuc`(`maKhuVuc`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_ViTri_trangThai (trangThai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [09] PhieuGiaoNCC
CREATE TABLE IF NOT EXISTS `PhieuGiaoNCC` (
  `maPhieuGiao` VARCHAR(12) NOT NULL,
  `maNCC` VARCHAR(9) NOT NULL,
  `soHoaDonNCC` VARCHAR(30) NULL,
  `ngayGiao` DATE NOT NULL,
  `nguoiGiao` NVARCHAR(60) NULL,
  `soBienSoXe` VARCHAR(15) NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Chờ tiếp nhận',
  `ghiChu` NVARCHAR(255) NULL,
  `ngayTao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`maPhieuGiao`),
  CONSTRAINT `ck_PhieuGiaoNCC_1` CHECK (maPhieuGiao REGEXP '^PG[0-9]{10}$'),
  CONSTRAINT `ck_PhieuGiaoNCC_2` CHECK (trangThai IN ('Chờ tiếp nhận', 'Đang tiếp nhận', 'Đã tiếp nhận', 'Đã từ chối', 'Đã hủy')),
  CONSTRAINT `fk_PhieuGiaoNCC_1` FOREIGN KEY (`maNCC`) REFERENCES `NhaCungCap`(`maNCC`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_PhieuGiao_ngay (ngayGiao, trangThai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [10] ChiTietPhieuGiaoNCC
CREATE TABLE IF NOT EXISTS `ChiTietPhieuGiaoNCC` (
  `maChiTietPhieuGiao` BIGINT NOT NULL AUTO_INCREMENT,
  `maPhieuGiao` VARCHAR(12) NOT NULL,
  `maHangHoa` VARCHAR(10) NOT NULL,
  `soLoNCC` VARCHAR(50) NULL,
  `ngaySanXuatNCC` DATE NULL,
  `hanSuDungNCC` DATE NULL,
  `soLuongGiao` DOUBLE NOT NULL,
  `soLuongThucNhan` DOUBLE NULL,
  `donViTinh` VARCHAR(5) NOT NULL,
  `heSoQuyDoiApDung` DOUBLE NOT NULL DEFAULT 1.00,
  PRIMARY KEY (`maChiTietPhieuGiao`),
  CONSTRAINT `uq_ChiTietPhieuGiaoN_1` UNIQUE (`maPhieuGiao`, `maHangHoa`, `soLoNCC`),
  CONSTRAINT `ck_ChiTietPhieuGiaoN_1` CHECK (ngaySanXuatNCC IS NULL OR hanSuDungNCC IS NULL OR hanSuDungNCC >= ngaySanXuatNCC),
  CONSTRAINT `ck_ChiTietPhieuGiaoN_2` CHECK (soLuongGiao > 0),
  CONSTRAINT `ck_ChiTietPhieuGiaoN_3` CHECK (soLuongThucNhan IS NULL OR soLuongThucNhan >= 0),
  CONSTRAINT `ck_ChiTietPhieuGiaoN_4` CHECK (donViTinh IN ('kg','bao','thùng')),
  CONSTRAINT `ck_ChiTietPhieuGiaoN_5` CHECK (heSoQuyDoiApDung > 0),
  CONSTRAINT `fk_ChiTietPhieuGiaoN_1` FOREIGN KEY (`maPhieuGiao`) REFERENCES `PhieuGiaoNCC`(`maPhieuGiao`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_ChiTietPhieuGiaoN_2` FOREIGN KEY (`maHangHoa`) REFERENCES `HangHoa`(`maHangHoa`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [11] PhieuTiepNhan
CREATE TABLE IF NOT EXISTS `PhieuTiepNhan` (
  `maPhieuTiepNhan` VARCHAR(13) NOT NULL,
  `maPhieuGiao` VARCHAR(12) NULL,
  `maXuong` VARCHAR(9) NULL,
  `maViTriTam` VARCHAR(14) NOT NULL,
  `maNhanVienTiepNhan` VARCHAR(8) NOT NULL,
  `ngayTiepNhan` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trangThai` NVARCHAR(20) NOT NULL DEFAULT 'Chờ kiểm định',
  `ghiChu` NVARCHAR(255) NULL,
  PRIMARY KEY (`maPhieuTiepNhan`),
  CONSTRAINT `ck_PhieuTiepNhan_1` CHECK (maPhieuTiepNhan REGEXP '^PTN[0-9]{10}$'),
  CONSTRAINT `ck_PhieuTiepNhan_2` CHECK (trangThai IN ('Chờ kiểm định', 'Đang kiểm định', 'Đã kiểm định', 'Đã từ chối tiếp nhận', 'Đã hủy')),
  CONSTRAINT `ck_PhieuTiepNhan_3` CHECK ((maPhieuGiao IS NOT NULL AND maXuong IS NULL) OR (maPhieuGiao IS NULL AND maXuong IS NOT NULL)),
  CONSTRAINT `fk_PhieuTiepNhan_1` FOREIGN KEY (`maPhieuGiao`) REFERENCES `PhieuGiaoNCC`(`maPhieuGiao`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuTiepNhan_2` FOREIGN KEY (`maXuong`) REFERENCES `XuongSanXuat`(`maXuong`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuTiepNhan_3` FOREIGN KEY (`maViTriTam`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuTiepNhan_4` FOREIGN KEY (`maNhanVienTiepNhan`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_PTN_trangThai (trangThai, ngayTiepNhan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [12] LoHang
CREATE TABLE IF NOT EXISTS `LoHang` (
  `maLoHang` VARCHAR(12) NOT NULL,
  `maHangHoa` VARCHAR(10) NOT NULL,
  `maPhieuTiepNhan` VARCHAR(13) NULL,
  `soLoNhaCungCap` VARCHAR(50) NULL,
  `ngaySanXuat` DATE NOT NULL,
  `ngayHetHan` DATE NOT NULL,
  `soLuongBanDau` DOUBLE NOT NULL,
  `trangThaiQC` NVARCHAR(21) NOT NULL DEFAULT 'Chờ kiểm tra',
  `trangThaiVongDoi` NVARCHAR(15) NOT NULL DEFAULT 'Đã tạo',
  `ngayTao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`maLoHang`),
  CONSTRAINT `ck_LoHang_1` CHECK (maLoHang REGEXP '^LO[0-9]{10}$'),
  CONSTRAINT `ck_LoHang_2` CHECK (ngayHetHan >= ngaySanXuat),
  CONSTRAINT `ck_LoHang_3` CHECK (soLuongBanDau > 0),
  CONSTRAINT `ck_LoHang_4` CHECK (trangThaiQC IN ('Chờ kiểm tra', 'Đã đạt chuẩn', 'Đã kiểm tra không đạt', 'Chờ tái kiểm')),
  CONSTRAINT `ck_LoHang_5` CHECK (trangThaiVongDoi IN ('Đã tạo', 'Đang lưu kho', 'Sắp hết hạn', 'Đang cách ly', 'Đã xuất hết', 'Đã thanh lý', 'Đã tiêu hủy')),
  CONSTRAINT `fk_LoHang_1` FOREIGN KEY (`maHangHoa`) REFERENCES `HangHoa`(`maHangHoa`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_LoHang_2` FOREIGN KEY (`maPhieuTiepNhan`) REFERENCES `PhieuTiepNhan`(`maPhieuTiepNhan`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_LoHang_FEFO (maHangHoa, trangThaiQC, ngayHetHan),
  INDEX idx_LoHang_hetHan (ngayHetHan, trangThaiVongDoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [13] TieuChiKiemTra
CREATE TABLE IF NOT EXISTS `TieuChiKiemTra` (
  `maTieuChi` VARCHAR(8) NOT NULL,
  `tenTieuChi` NVARCHAR(100) NOT NULL,
  `loaiHangHoa` NVARCHAR(15) NOT NULL DEFAULT 'Chung',
  `donViDo` VARCHAR(20) NOT NULL,
  `giaTriToiThieu` DOUBLE NULL,
  `giaTriToiDa` DOUBLE NULL,
  `phuongPhapKiemTra` NVARCHAR(120) NULL,
  PRIMARY KEY (`maTieuChi`),
  CONSTRAINT `ck_TieuChiKiemTra_1` CHECK (maTieuChi REGEXP '^TC[0-9]{6}$'),
  CONSTRAINT `ck_TieuChiKiemTra_2` CHECK (CHAR_LENGTH(TRIM(tenTieuChi)) > 0),
  CONSTRAINT `ck_TieuChiKiemTra_3` CHECK (loaiHangHoa IN ('Nguyên liệu','Thành phẩm','Phụ gia','Chung')),
  CONSTRAINT `ck_TieuChiKiemTra_4` CHECK (CHAR_LENGTH(TRIM(donViDo)) > 0),
  CONSTRAINT `ck_TieuChiKiemTra_5` CHECK (giaTriToiThieu IS NULL OR giaTriToiDa IS NULL OR giaTriToiThieu <= giaTriToiDa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [14] BienBanKiemDinhChatLuong
CREATE TABLE IF NOT EXISTS `BienBanKiemDinhChatLuong` (
  `maBienBan` VARCHAR(16) NOT NULL,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maNhanVienQC` VARCHAR(8) NOT NULL,
  `ngayKiemDinh` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ketLuan` VARCHAR(15) NOT NULL,
  `lyDoKhongDat` NVARCHAR(255) NULL,
  `maNhanVienDuyet` VARCHAR(8) NULL,
  `ngayXacNhan` DATETIME NULL,
  `trangThai` NVARCHAR(19) NOT NULL DEFAULT 'Chờ duyệt',
  `lyDoTuChoiDuyet` TEXT NULL,
  `ghiChu` NVARCHAR(255) NULL,
  PRIMARY KEY (`maBienBan`),
  CONSTRAINT `ck_BienBanKiemDinhCh_1` CHECK (maBienBan REGEXP '^BBKDCL[0-9]{10}$'),
  CONSTRAINT `ck_BienBanKiemDinhCh_2` CHECK (ketLuan IN ('Đạt', 'Không đạt')),
  CONSTRAINT `ck_BienBanKiemDinhCh_3` CHECK (trangThai IN ('Chờ duyệt', 'Đã duyệt', 'Đã từ chối', 'Đã yêu cầu kiểm lại')),
  CONSTRAINT `ck_BienBanKiemDinhCh_4` CHECK (ketLuan <> 'Không đạt' OR (lyDoKhongDat IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoKhongDat)) > 0)),
  CONSTRAINT `ck_BienBanKiemDinhCh_5` CHECK (ngayXacNhan IS NULL OR ngayXacNhan >= ngayKiemDinh),
  CONSTRAINT `ck_BienBanKiemDinhCh_6` CHECK (trangThai = 'Chờ duyệt' OR (maNhanVienDuyet IS NOT NULL AND ngayXacNhan IS NOT NULL)),
  CONSTRAINT `ck_BienBanKiemDinhCh_7` CHECK (trangThai <> 'Đã từ chối' OR (lyDoTuChoiDuyet IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoTuChoiDuyet)) > 0)),
  CONSTRAINT `fk_BienBanKiemDinhCh_1` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_BienBanKiemDinhCh_2` FOREIGN KEY (`maNhanVienQC`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_BienBanKiemDinhCh_3` FOREIGN KEY (`maNhanVienDuyet`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_BBKDCL_trangThai (trangThai, ngayKiemDinh)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [15] KetQuaKiemTra
CREATE TABLE IF NOT EXISTS `KetQuaKiemTra` (
  `maKetQuaKiemTra` BIGINT NOT NULL AUTO_INCREMENT,
  `maBienBan` VARCHAR(16) NOT NULL,
  `maTieuChi` VARCHAR(8) NOT NULL,
  `giaTriDoDuoc` DOUBLE NULL,
  `giaTriDinhTinh` NVARCHAR(150) NULL,
  `danhGia` VARCHAR(15) NOT NULL,
  `ghiChu` NVARCHAR(255) NULL,
  PRIMARY KEY (`maKetQuaKiemTra`),
  CONSTRAINT `uq_KetQuaKiemTra_1` UNIQUE (`maBienBan`, `maTieuChi`),
  CONSTRAINT `ck_KetQuaKiemTra_1` CHECK (danhGia IN ('Đạt', 'Không đạt')),
  CONSTRAINT `ck_KetQuaKiemTra_2` CHECK (giaTriDoDuoc IS NOT NULL OR (giaTriDinhTinh IS NOT NULL AND CHAR_LENGTH(TRIM(giaTriDinhTinh)) > 0)),
  CONSTRAINT `ck_KetQuaKiemTra_3` CHECK (danhGia <> 'Không đạt' OR (ghiChu IS NOT NULL AND CHAR_LENGTH(TRIM(ghiChu)) > 0)),
  CONSTRAINT `fk_KetQuaKiemTra_1` FOREIGN KEY (`maBienBan`) REFERENCES `BienBanKiemDinhChatLuong`(`maBienBan`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_KetQuaKiemTra_2` FOREIGN KEY (`maTieuChi`) REFERENCES `TieuChiKiemTra`(`maTieuChi`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [16] PhieuYeuCauNhapKho
CREATE TABLE IF NOT EXISTS `PhieuYeuCauNhapKho` (
  `maYeuCauNhap` VARCHAR(14) NOT NULL,
  `nguonNhap` VARCHAR(20) NOT NULL,
  `maPhieuGiao` VARCHAR(12) NULL,
  `maXuong` VARCHAR(9) NULL,
  `maNhanVienLap` VARCHAR(8) NOT NULL,
  `ngayYeuCau` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Chờ xử lý',
  `lyDoNhap` NVARCHAR(200) NULL,
  PRIMARY KEY (`maYeuCauNhap`),
  CONSTRAINT `ck_PhieuYeuCauNhapKh_1` CHECK (maYeuCauNhap REGEXP '^YCNK[0-9]{10}$'),
  CONSTRAINT `ck_PhieuYeuCauNhapKh_2` CHECK (nguonNhap IN ('Nhà cung cấp', 'Xưởng sản xuất')),
  CONSTRAINT `ck_PhieuYeuCauNhapKh_3` CHECK (trangThai IN ('Chờ xử lý', 'Đang nhập kho', 'Đã hoàn tất', 'Đã hủy')),
  CONSTRAINT `ck_PhieuYeuCauNhapKh_4` CHECK ((nguonNhap = 'Nhà cung cấp' AND maPhieuGiao IS NOT NULL AND maXuong IS NULL) OR (nguonNhap = 'Xưởng sản xuất' AND maPhieuGiao IS NULL AND maXuong IS NOT NULL)),
  CONSTRAINT `fk_PhieuYeuCauNhapKh_1` FOREIGN KEY (`maPhieuGiao`) REFERENCES `PhieuGiaoNCC`(`maPhieuGiao`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuYeuCauNhapKh_2` FOREIGN KEY (`maXuong`) REFERENCES `XuongSanXuat`(`maXuong`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuYeuCauNhapKh_3` FOREIGN KEY (`maNhanVienLap`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_PYCNK_trangThai (trangThai, ngayYeuCau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [17] ChiTietYeuCauNhapKho
CREATE TABLE IF NOT EXISTS `ChiTietYeuCauNhapKho` (
  `maChiTietYeuCauNhap` BIGINT NOT NULL AUTO_INCREMENT,
  `maYeuCauNhap` VARCHAR(14) NOT NULL,
  `maHangHoa` VARCHAR(10) NOT NULL,
  `soLuongYeuCau` DOUBLE NOT NULL,
  `donViTinh` VARCHAR(5) NOT NULL,
  `ghiChu` NVARCHAR(255) NULL,
  PRIMARY KEY (`maChiTietYeuCauNhap`),
  CONSTRAINT `uq_ChiTietYeuCauNhap_1` UNIQUE (`maYeuCauNhap`, `maHangHoa`),
  CONSTRAINT `ck_ChiTietYeuCauNhap_1` CHECK (soLuongYeuCau > 0),
  CONSTRAINT `ck_ChiTietYeuCauNhap_2` CHECK (donViTinh IN ('kg','bao','thùng')),
  CONSTRAINT `fk_ChiTietYeuCauNhap_1` FOREIGN KEY (`maYeuCauNhap`) REFERENCES `PhieuYeuCauNhapKho`(`maYeuCauNhap`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_ChiTietYeuCauNhap_2` FOREIGN KEY (`maHangHoa`) REFERENCES `HangHoa`(`maHangHoa`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [18] PhieuNhapKho
CREATE TABLE IF NOT EXISTS `PhieuNhapKho` (
  `maPhieuNhapKho` VARCHAR(13) NOT NULL,
  `maYeuCauNhap` VARCHAR(14) NOT NULL,
  `maKho` VARCHAR(7) NOT NULL,
  `maNhanVienThuKho` VARCHAR(8) NOT NULL,
  `ngayNhapKho` DATETIME NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Đang xếp kệ',
  `ghiChu` NVARCHAR(255) NULL,
  PRIMARY KEY (`maPhieuNhapKho`),
  CONSTRAINT `ck_PhieuNhapKho_1` CHECK (maPhieuNhapKho REGEXP '^PNK[0-9]{10}$'),
  CONSTRAINT `ck_PhieuNhapKho_2` CHECK (trangThai IN ('Đang xếp kệ', 'Đã nhập kho', 'Đã hủy')),
  CONSTRAINT `ck_PhieuNhapKho_3` CHECK ((trangThai = 'Đã nhập kho' AND ngayNhapKho IS NOT NULL) OR (trangThai IN ('Đang xếp kệ', 'Đang xếp kệ', 'Đã hủy', 'Đã hủy') AND ngayNhapKho IS NULL)),
  CONSTRAINT `fk_PhieuNhapKho_1` FOREIGN KEY (`maYeuCauNhap`) REFERENCES `PhieuYeuCauNhapKho`(`maYeuCauNhap`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuNhapKho_2` FOREIGN KEY (`maKho`) REFERENCES `Kho`(`maKho`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuNhapKho_3` FOREIGN KEY (`maNhanVienThuKho`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_PNK_ngay (ngayNhapKho, trangThai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [19] ChiTietPhieuNhapKho
CREATE TABLE IF NOT EXISTS `ChiTietPhieuNhapKho` (
  `maChiTietPhieuNhap` BIGINT NOT NULL AUTO_INCREMENT,
  `maPhieuNhapKho` VARCHAR(13) NOT NULL,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maViTri` VARCHAR(14) NOT NULL,
  `soLuongNhap` DOUBLE NOT NULL,
  `donViTinh` VARCHAR(5) NOT NULL,
  `heSoQuyDoiApDung` DOUBLE NOT NULL DEFAULT 1.00,
  `soLuongChuanKg` DOUBLE GENERATED ALWAYS AS (soLuongNhap * heSoQuyDoiApDung) STORED,
  PRIMARY KEY (`maChiTietPhieuNhap`),
  CONSTRAINT `uq_ChiTietPhieuNhapK_1` UNIQUE (`maPhieuNhapKho`, `maLoHang`, `maViTri`),
  CONSTRAINT `ck_ChiTietPhieuNhapK_1` CHECK (soLuongNhap > 0),
  CONSTRAINT `ck_ChiTietPhieuNhapK_2` CHECK (donViTinh IN ('kg','bao','thùng')),
  CONSTRAINT `ck_ChiTietPhieuNhapK_3` CHECK (heSoQuyDoiApDung > 0),
  CONSTRAINT `ck_ChiTietPhieuNhapK_4` CHECK (donViTinh <> 'kg' OR heSoQuyDoiApDung = 1),
  CONSTRAINT `fk_ChiTietPhieuNhapK_1` FOREIGN KEY (`maPhieuNhapKho`) REFERENCES `PhieuNhapKho`(`maPhieuNhapKho`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_ChiTietPhieuNhapK_2` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_ChiTietPhieuNhapK_3` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [20] TonKho
CREATE TABLE IF NOT EXISTS `TonKho` (
  `maTonKho` BIGINT NOT NULL AUTO_INCREMENT,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maViTri` VARCHAR(14) NOT NULL,
  `soLuongTon` DOUBLE NOT NULL DEFAULT 0,
  `soLuongDaGiu` DOUBLE NOT NULL DEFAULT 0.00,
  `ngayCapNhatCuoi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`maTonKho`),
  CONSTRAINT `uq_TonKho_1` UNIQUE (`maLoHang`, `maViTri`),
  CONSTRAINT `ck_TonKho_1` CHECK (soLuongTon >= 0),
  CONSTRAINT `ck_TonKho_2` CHECK (soLuongDaGiu >= 0),
  CONSTRAINT `ck_TonKho_3` CHECK (soLuongDaGiu <= soLuongTon),
  CONSTRAINT `fk_TonKho_1` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_TonKho_2` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_TonKho_vitri (maViTri, soLuongTon)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [20b] LuuKho — sổ phân bổ lô hàng vào vị trí lưu trữ (Module 3 - UC16).
-- Ghi nhận việc gán lô vào vị trí mà KHÔNG thay đổi tổng tồn kho (TonKho).
-- Mỗi dòng LuuKho là một lần phân bổ, dùng làm lịch sử lưu trữ.
CREATE TABLE IF NOT EXISTS `LuuKho` (
  `maLuuKho` BIGINT NOT NULL AUTO_INCREMENT,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maViTri` VARCHAR(14) NOT NULL,
  `maPhieuNhapKho` VARCHAR(13) NULL,
  `soLuongPhanBo` DOUBLE NOT NULL,
  `maNhanVienThucHien` VARCHAR(8) NOT NULL,
  `thoiDiemPhanBo` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ghiChu` NVARCHAR(255) NULL,
  PRIMARY KEY (`maLuuKho`),
  CONSTRAINT `ck_LuuKho_1` CHECK (soLuongPhanBo > 0),
  CONSTRAINT `fk_LuuKho_1` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_LuuKho_2` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_LuuKho_3` FOREIGN KEY (`maPhieuNhapKho`) REFERENCES `PhieuNhapKho`(`maPhieuNhapKho`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_LuuKho_4` FOREIGN KEY (`maNhanVienThucHien`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX `idx_LuuKho_lo_tg` (`maLoHang`, `thoiDiemPhanBo`),
  INDEX `idx_LuuKho_vitri` (`maViTri`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [21] LichSuBienDongTonKho
CREATE TABLE IF NOT EXISTS `LichSuBienDongTonKho` (
  `maBienDong` VARCHAR(16) NOT NULL,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maViTri` VARCHAR(14) NOT NULL,
  `loaiGiaoDich` NVARCHAR(20) NOT NULL,
  `soLuongBienDong` DOUBLE NOT NULL,
  `soLuongTonTruoc` DOUBLE NOT NULL,
  `soLuongTonSau` DOUBLE NOT NULL,
  `maChungTuLienQuan` VARCHAR(20) NOT NULL,
  `maNhanVienThucHien` VARCHAR(8) NOT NULL,
  `thoiGianPhatSinh` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ghiChu` NVARCHAR(255) NULL,
  PRIMARY KEY (`maBienDong`),
  CONSTRAINT `ck_LichSuBienDongTon_1` CHECK (maBienDong REGEXP '^BDTK[0-9]{12}$'),
  CONSTRAINT `ck_LichSuBienDongTon_2` CHECK (loaiGiaoDich IN ('Nhập kho','Xuất kho','Điều chuyển đến','Điều chuyển đi','Điều chỉnh tăng','Điều chỉnh giảm','Hao hụt','NhapKho','XuatKho','DieuChuyenDen','DieuChuyenDi','DieuChinhTang','DieuChinhGiam','HaoHut')),
  CONSTRAINT `ck_LichSuBienDongTon_3` CHECK (soLuongBienDong <> 0),
  CONSTRAINT `ck_LichSuBienDongTon_4` CHECK (soLuongTonTruoc >= 0),
  CONSTRAINT `ck_LichSuBienDongTon_5` CHECK (soLuongTonSau >= 0),
  CONSTRAINT `ck_LichSuBienDongTon_6` CHECK (CHAR_LENGTH(TRIM(maChungTuLienQuan)) > 0),
  CONSTRAINT `ck_LichSuBienDongTon_7` CHECK ((loaiGiaoDich IN ('Nhập kho','Điều chuyển đến','Điều chỉnh tăng','NhapKho','DieuChuyenDen','DieuChinhTang') AND soLuongBienDong > 0) OR (loaiGiaoDich IN ('Xuất kho','Điều chuyển đi','Điều chỉnh giảm','Hao hụt','XuatKho','DieuChuyenDi','DieuChinhGiam','HaoHut') AND soLuongBienDong < 0)),
  CONSTRAINT `ck_LichSuBienDongTon_8` CHECK (ABS(soLuongTonSau - (soLuongTonTruoc + soLuongBienDong)) <= 0.000001),
  CONSTRAINT `fk_LichSuBienDongTon_1` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_LichSuBienDongTon_2` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_LichSuBienDongTon_3` FOREIGN KEY (`maNhanVienThucHien`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_LSBD_lo_tg    (maLoHang, thoiGianPhatSinh),
  INDEX idx_LSBD_vitri_tg (maViTri, thoiGianPhatSinh),
  INDEX idx_LSBD_tg_loai  (thoiGianPhatSinh, loaiGiaoDich),
  INDEX idx_LSBD_chungTu  (maChungTuLienQuan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [22] PhieuDieuChuyen
CREATE TABLE IF NOT EXISTS `PhieuDieuChuyen` (
  `maPhieuDieuChuyen` VARCHAR(13) NOT NULL,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maViTriNguon` VARCHAR(14) NOT NULL,
  `maViTriDich` VARCHAR(14) NOT NULL,
  `soLuongDuKien` DOUBLE NOT NULL,
  `soLuongThucChuyen` DOUBLE NULL,
  `maNhanVienYeuCau` VARCHAR(8) NOT NULL,
  `maNhanVienThucHien` VARCHAR(8) NULL,
  `maNhanVienDuyet` VARCHAR(8) NULL,
  `ngayYeuCau` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ngayDuyet` DATETIME NULL,
  `ngayHoanTat` DATETIME NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Chờ điều chuyển',
  `lyDoDieuChuyen` NVARCHAR(200) NOT NULL,
  `lyDoTuChoi` NVARCHAR(255) NULL,
  PRIMARY KEY (`maPhieuDieuChuyen`),
  CONSTRAINT `ck_PhieuDieuChuyen_1` CHECK (maPhieuDieuChuyen REGEXP '^PDC[0-9]{10}$'),
  CONSTRAINT `ck_PhieuDieuChuyen_2` CHECK (soLuongDuKien > 0),
  CONSTRAINT `ck_PhieuDieuChuyen_3` CHECK (soLuongThucChuyen >= 0),
  CONSTRAINT `ck_PhieuDieuChuyen_4` CHECK (trangThai IN ('Chờ duyệt', 'Đã duyệt', 'Đã từ chối', 'Chờ điều chuyển', 'Đang thực hiện', 'Đã hoàn tất', 'Đã hủy')),
  CONSTRAINT `ck_PhieuDieuChuyen_5` CHECK (CHAR_LENGTH(TRIM(lyDoDieuChuyen)) > 0),
  CONSTRAINT `ck_PhieuDieuChuyen_6` CHECK (maViTriNguon <> maViTriDich),
  CONSTRAINT `ck_PhieuDieuChuyen_7` CHECK (ngayHoanTat IS NULL OR ngayHoanTat >= ngayYeuCau),
  CONSTRAINT `ck_PhieuDieuChuyen_8` CHECK (trangThai <> 'Đã hoàn tất' OR (soLuongThucChuyen IS NOT NULL AND soLuongThucChuyen > 0 AND maNhanVienThucHien IS NOT NULL AND ngayHoanTat IS NOT NULL)),
  CONSTRAINT `ck_PhieuDieuChuyen_9` CHECK (soLuongThucChuyen IS NULL OR soLuongThucChuyen <= soLuongDuKien),
  CONSTRAINT `ck_PhieuDieuChuyen_10` CHECK (ngayDuyet IS NULL OR ngayDuyet >= ngayYeuCau),
  CONSTRAINT `ck_PhieuDieuChuyen_11` CHECK (trangThai NOT IN ('Đã duyệt', 'Đã từ chối') OR (maNhanVienDuyet IS NOT NULL AND ngayDuyet IS NOT NULL)),
  CONSTRAINT `ck_PhieuDieuChuyen_12` CHECK (trangThai <> 'Đã từ chối' OR (lyDoTuChoi IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoTuChoi)) > 0)),
  CONSTRAINT `ck_PhieuDieuChuyen_13` CHECK (maNhanVienDuyet IS NULL OR maNhanVienDuyet <> maNhanVienYeuCau),
  CONSTRAINT `fk_PhieuDieuChuyen_1` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuDieuChuyen_2` FOREIGN KEY (`maViTriNguon`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuDieuChuyen_3` FOREIGN KEY (`maViTriDich`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuDieuChuyen_4` FOREIGN KEY (`maNhanVienYeuCau`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuDieuChuyen_5` FOREIGN KEY (`maNhanVienThucHien`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuDieuChuyen_6` FOREIGN KEY (`maNhanVienDuyet`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_PDC_trangThai (trangThai, ngayYeuCau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [23] DonHang
CREATE TABLE IF NOT EXISTS `DonHang` (
  `maDonHang` VARCHAR(12) NOT NULL,
  `tenKhachHang` NVARCHAR(120) NOT NULL,
  `soDienThoai` VARCHAR(15) NULL,
  `diaChiGiao` NVARCHAR(200) NULL,
  `ngayDatHang` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ngayGiaoDuKien` DATE NOT NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Chờ xuất kho',
  PRIMARY KEY (`maDonHang`),
  CONSTRAINT `ck_DonHang_1` CHECK (maDonHang REGEXP '^DH[0-9]{10}$'),
  CONSTRAINT `ck_DonHang_2` CHECK (CHAR_LENGTH(TRIM(tenKhachHang)) > 0),
  CONSTRAINT `ck_DonHang_3` CHECK (soDienThoai IS NULL OR CHAR_LENGTH(TRIM(soDienThoai)) > 0),
  CONSTRAINT `ck_DonHang_4` CHECK (trangThai IN ('Chờ xuất kho', 'Đang xuất kho', 'Đã xuất đủ', 'Đã hủy')),
  CONSTRAINT `ck_DonHang_5` CHECK (ngayGiaoDuKien >= DATE(ngayDatHang)),
  INDEX idx_DonHang_trangThai (trangThai, ngayGiaoDuKien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [24] ChiTietDonHang
CREATE TABLE IF NOT EXISTS `ChiTietDonHang` (
  `maChiTietDonHang` BIGINT NOT NULL AUTO_INCREMENT,
  `maDonHang` VARCHAR(12) NOT NULL,
  `maHangHoa` VARCHAR(10) NOT NULL,
  `soLuongDat` DOUBLE NOT NULL,
  `soLuongDaXuat` DOUBLE NOT NULL DEFAULT 0.00,
  `donViTinh` VARCHAR(5) NOT NULL,
  PRIMARY KEY (`maChiTietDonHang`),
  CONSTRAINT `uq_ChiTietDonHang_1` UNIQUE (`maDonHang`, `maHangHoa`),
  CONSTRAINT `ck_ChiTietDonHang_1` CHECK (soLuongDat > 0),
  CONSTRAINT `ck_ChiTietDonHang_2` CHECK (soLuongDaXuat >= 0),
  CONSTRAINT `ck_ChiTietDonHang_3` CHECK (donViTinh IN ('kg','bao','thùng')),
  CONSTRAINT `ck_ChiTietDonHang_4` CHECK (soLuongDaXuat <= soLuongDat),
  CONSTRAINT `fk_ChiTietDonHang_1` FOREIGN KEY (`maDonHang`) REFERENCES `DonHang`(`maDonHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_ChiTietDonHang_2` FOREIGN KEY (`maHangHoa`) REFERENCES `HangHoa`(`maHangHoa`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [25] KeHoachSanXuat
CREATE TABLE IF NOT EXISTS `KeHoachSanXuat` (
  `maKeHoach` VARCHAR(14) NOT NULL,
  `maXuong` VARCHAR(9) NOT NULL,
  `ngayBatDau` DATE NOT NULL,
  `ngayKetThuc` DATE NOT NULL,
  `trangThai` NVARCHAR(19) NOT NULL DEFAULT 'Chờ cấp nguyên liệu',
  `ghiChu` NVARCHAR(255) NULL,
  PRIMARY KEY (`maKeHoach`),
  CONSTRAINT `ck_KeHoachSanXuat_1` CHECK (maKeHoach REGEXP '^KHSX[0-9]{10}$'),
  CONSTRAINT `ck_KeHoachSanXuat_2` CHECK (ngayKetThuc >= ngayBatDau),
  CONSTRAINT `ck_KeHoachSanXuat_3` CHECK (trangThai IN ('Chờ cấp nguyên liệu', 'Đang sản xuất', 'Đã hoàn thành', 'Đã hủy')),
  CONSTRAINT `fk_KeHoachSanXuat_1` FOREIGN KEY (`maXuong`) REFERENCES `XuongSanXuat`(`maXuong`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_KHSX_trangThai (trangThai, ngayBatDau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [26] ChiTietKeHoachSanXuat
CREATE TABLE IF NOT EXISTS `ChiTietKeHoachSanXuat` (
  `maChiTietKeHoach` BIGINT NOT NULL AUTO_INCREMENT,
  `maKeHoach` VARCHAR(14) NOT NULL,
  `maHangHoa` VARCHAR(10) NOT NULL,
  `loaiDong` NVARCHAR(20) NOT NULL,
  `soLuongKeHoach` DOUBLE NOT NULL,
  `soLuongThucTe` DOUBLE NOT NULL DEFAULT 0.00,
  `donViTinh` VARCHAR(5) NOT NULL,
  PRIMARY KEY (`maChiTietKeHoach`),
  CONSTRAINT `uq_ChiTietKeHoachSan_1` UNIQUE (`maKeHoach`, `maHangHoa`, `loaiDong`),
  CONSTRAINT `ck_ChiTietKeHoachSan_1` CHECK (loaiDong IN ('Nguyên liệu cần cấp','Thành phẩm dự kiến')),
  CONSTRAINT `ck_ChiTietKeHoachSan_2` CHECK (soLuongKeHoach > 0),
  CONSTRAINT `ck_ChiTietKeHoachSan_3` CHECK (soLuongThucTe >= 0),
  CONSTRAINT `ck_ChiTietKeHoachSan_4` CHECK (donViTinh IN ('kg','bao','thùng')),
  CONSTRAINT `fk_ChiTietKeHoachSan_1` FOREIGN KEY (`maKeHoach`) REFERENCES `KeHoachSanXuat`(`maKeHoach`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_ChiTietKeHoachSan_2` FOREIGN KEY (`maHangHoa`) REFERENCES `HangHoa`(`maHangHoa`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [27] PhieuYeuCauXuatKho
CREATE TABLE IF NOT EXISTS `PhieuYeuCauXuatKho` (
  `maYeuCauXuat` VARCHAR(14) NOT NULL,
  `loaiYeuCauXuat` NVARCHAR(15) NOT NULL,
  `maDonHang` VARCHAR(12) NULL,
  `maKeHoach` VARCHAR(14) NULL,
  `maNhanVienLap` VARCHAR(8) NOT NULL,
  `maNhanVienDuyet` VARCHAR(8) NULL,
  `ngayYeuCau` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ngayDuyet` DATETIME NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Chờ duyệt',
  `lyDoXuat` NVARCHAR(200) NULL,
  `lyDoTuChoi` VARCHAR(200) NULL,
  PRIMARY KEY (`maYeuCauXuat`),
  CONSTRAINT `ck_PhieuYeuCauXuatKh_1` CHECK (maYeuCauXuat REGEXP '^YCXK[0-9]{10}$'),
  CONSTRAINT `ck_PhieuYeuCauXuatKh_2` CHECK (loaiYeuCauXuat IN ('Xuất bán','Xuất sản xuất','Xuất trả NCC','Xuất hủy')),
  CONSTRAINT `ck_PhieuYeuCauXuatKh_3` CHECK (trangThai IN ('Chờ duyệt', 'Đã duyệt', 'Đã từ chối', 'Đang giữ chỗ', 'Đang xuất', 'Đã xuất kho', 'Đã hủy')),
  CONSTRAINT `ck_PhieuYeuCauXuatKh_4` CHECK ((loaiYeuCauXuat = 'Xuất bán' AND maDonHang IS NOT NULL AND maKeHoach IS NULL) OR (loaiYeuCauXuat = 'Xuất sản xuất' AND maDonHang IS NULL AND maKeHoach IS NOT NULL) OR (loaiYeuCauXuat IN ('Xuất trả NCC','Xuất hủy') AND maDonHang IS NULL AND maKeHoach IS NULL)),
  CONSTRAINT `ck_PhieuYeuCauXuatKh_5` CHECK (ngayDuyet IS NULL OR ngayDuyet >= ngayYeuCau),
  CONSTRAINT `ck_PhieuYeuCauXuatKh_6` CHECK (trangThai IN ('Chờ duyệt', 'Đã hủy') OR (maNhanVienDuyet IS NOT NULL AND ngayDuyet IS NOT NULL)),
  CONSTRAINT `ck_PhieuYeuCauXuatKh_7` CHECK (loaiYeuCauXuat NOT IN ('Xuất trả NCC','Xuất hủy') OR (lyDoXuat IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoXuat)) > 0)),
  CONSTRAINT `ck_PhieuYeuCauXuatKh_8` CHECK (trangThai <> 'Đã từ chối' OR (lyDoTuChoi IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoTuChoi)) > 0)),
  CONSTRAINT `fk_PhieuYeuCauXuatKh_1` FOREIGN KEY (`maDonHang`) REFERENCES `DonHang`(`maDonHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuYeuCauXuatKh_2` FOREIGN KEY (`maKeHoach`) REFERENCES `KeHoachSanXuat`(`maKeHoach`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuYeuCauXuatKh_3` FOREIGN KEY (`maNhanVienLap`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuYeuCauXuatKh_4` FOREIGN KEY (`maNhanVienDuyet`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_PYCXK_trangThai (trangThai, ngayYeuCau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [28] ChiTietYeuCauXuatKho
CREATE TABLE IF NOT EXISTS `ChiTietYeuCauXuatKho` (
  `maChiTietYeuCauXuat` BIGINT NOT NULL AUTO_INCREMENT,
  `maYeuCauXuat` VARCHAR(14) NOT NULL,
  `maHangHoa` VARCHAR(10) NOT NULL,
  `soLuongYeuCau` DOUBLE NOT NULL,
  `soLuongDaGiu` DOUBLE NOT NULL DEFAULT 0.00,
  `soLuongThucXuat` DOUBLE NOT NULL DEFAULT 0.00,
  `donViTinh` VARCHAR(5) NOT NULL,
  PRIMARY KEY (`maChiTietYeuCauXuat`),
  CONSTRAINT `uq_ChiTietYeuCauXuat_1` UNIQUE (`maYeuCauXuat`, `maHangHoa`),
  CONSTRAINT `ck_ChiTietYeuCauXuat_1` CHECK (soLuongYeuCau > 0),
  CONSTRAINT `ck_ChiTietYeuCauXuat_2` CHECK (soLuongDaGiu >= 0),
  CONSTRAINT `ck_ChiTietYeuCauXuat_3` CHECK (soLuongThucXuat >= 0),
  CONSTRAINT `ck_ChiTietYeuCauXuat_4` CHECK (donViTinh IN ('kg','bao','thùng')),
  CONSTRAINT `ck_ChiTietYeuCauXuat_5` CHECK (soLuongDaGiu + soLuongThucXuat <= soLuongYeuCau),
  CONSTRAINT `fk_ChiTietYeuCauXuat_1` FOREIGN KEY (`maYeuCauXuat`) REFERENCES `PhieuYeuCauXuatKho`(`maYeuCauXuat`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_ChiTietYeuCauXuat_2` FOREIGN KEY (`maHangHoa`) REFERENCES `HangHoa`(`maHangHoa`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [29] DeXuatLoHang
CREATE TABLE IF NOT EXISTS `DeXuatLoHang` (
  `maDeXuat` VARCHAR(16) NOT NULL,
  `maYeuCauXuat` VARCHAR(14) NOT NULL,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maViTri` VARCHAR(14) NOT NULL,
  `soLuongDeXuat` DOUBLE NOT NULL,
  `soLuongThucLay` DOUBLE NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Đã giữ chỗ',
  `ngayDeXuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`maDeXuat`),
  CONSTRAINT `uq_DeXuatLoHang_1` UNIQUE (`maYeuCauXuat`, `maLoHang`, `maViTri`),
  CONSTRAINT `ck_DeXuatLoHang_1` CHECK (maDeXuat REGEXP '^DXLH[0-9]{12}$'),
  CONSTRAINT `ck_DeXuatLoHang_2` CHECK (soLuongDeXuat > 0),
  CONSTRAINT `ck_DeXuatLoHang_3` CHECK (soLuongThucLay >= 0),
  CONSTRAINT `ck_DeXuatLoHang_4` CHECK (trangThai IN ('Đã giữ chỗ', 'Đã xuất', 'Đã giải phóng')),
  CONSTRAINT `ck_DeXuatLoHang_5` CHECK (soLuongThucLay IS NULL OR soLuongThucLay <= soLuongDeXuat),
  CONSTRAINT `ck_DeXuatLoHang_6` CHECK (trangThai <> 'Đã xuất' OR (soLuongThucLay IS NOT NULL AND soLuongThucLay > 0)),
  CONSTRAINT `fk_DeXuatLoHang_1` FOREIGN KEY (`maYeuCauXuat`) REFERENCES `PhieuYeuCauXuatKho`(`maYeuCauXuat`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_DeXuatLoHang_2` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_DeXuatLoHang_3` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_DXLH_lo_vitri (maLoHang, maViTri, trangThai),
  INDEX idx_DXLH_yc_tt    (maYeuCauXuat, trangThai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [30] PhieuXuatKho
CREATE TABLE IF NOT EXISTS `PhieuXuatKho` (
  `maPhieuXuat` VARCHAR(13) NOT NULL,
  `maYeuCauXuat` VARCHAR(14) NOT NULL,
  `maKho` VARCHAR(7) NOT NULL,
  `maNhanVienThuKho` VARCHAR(8) NOT NULL,
  `ngayXuatKho` DATETIME NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Đang bốc hàng',
  `ghiChu` NVARCHAR(255) NULL,
  PRIMARY KEY (`maPhieuXuat`),
  CONSTRAINT `ck_PhieuXuatKho_1` CHECK (maPhieuXuat REGEXP '^PXK[0-9]{10}$'),
  CONSTRAINT `ck_PhieuXuatKho_2` CHECK (trangThai IN ('Đang bốc hàng', 'Đã xuất kho', 'Đã hủy')),
  CONSTRAINT `ck_PhieuXuatKho_3` CHECK ((trangThai = 'Đã xuất kho' AND ngayXuatKho IS NOT NULL) OR (trangThai IN ('Đang bốc hàng', 'Đang bốc hàng', 'Đã hủy', 'Đã hủy') AND ngayXuatKho IS NULL)),
  CONSTRAINT `fk_PhieuXuatKho_1` FOREIGN KEY (`maYeuCauXuat`) REFERENCES `PhieuYeuCauXuatKho`(`maYeuCauXuat`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuXuatKho_2` FOREIGN KEY (`maKho`) REFERENCES `Kho`(`maKho`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuXuatKho_3` FOREIGN KEY (`maNhanVienThuKho`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_PXK_ngay (ngayXuatKho, trangThai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [31] ChiTietPhieuXuatKho
CREATE TABLE IF NOT EXISTS `ChiTietPhieuXuatKho` (
  `maChiTietPhieuXuat` BIGINT NOT NULL AUTO_INCREMENT,
  `maPhieuXuat` VARCHAR(13) NOT NULL,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maViTri` VARCHAR(14) NOT NULL,
  `soLuongXuat` DOUBLE NOT NULL,
  `donViTinh` VARCHAR(5) NOT NULL,
  `heSoQuyDoiApDung` DOUBLE NOT NULL DEFAULT 1.00,
  `soLuongChuanKg` DOUBLE GENERATED ALWAYS AS (soLuongXuat * heSoQuyDoiApDung) STORED,
  PRIMARY KEY (`maChiTietPhieuXuat`),
  CONSTRAINT `uq_ChiTietPhieuXuatK_1` UNIQUE (`maPhieuXuat`, `maLoHang`, `maViTri`),
  CONSTRAINT `ck_ChiTietPhieuXuatK_1` CHECK (soLuongXuat > 0),
  CONSTRAINT `ck_ChiTietPhieuXuatK_2` CHECK (donViTinh IN ('kg', 'bao', 'thùng')),
  CONSTRAINT `ck_ChiTietPhieuXuatK_3` CHECK (heSoQuyDoiApDung > 0),
  CONSTRAINT `ck_ChiTietPhieuXuatK_4` CHECK (donViTinh <> 'kg' OR heSoQuyDoiApDung = 1),
  CONSTRAINT `fk_ChiTietPhieuXuatK_1` FOREIGN KEY (`maPhieuXuat`) REFERENCES `PhieuXuatKho`(`maPhieuXuat`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_ChiTietPhieuXuatK_2` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_ChiTietPhieuXuatK_3` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [32] BienBanKiemKe
CREATE TABLE IF NOT EXISTS `BienBanKiemKe` (
  `maBienBanKiemKe` VARCHAR(14) NOT NULL,
  `maKho` VARCHAR(7) NOT NULL,
  `maKhuVuc` VARCHAR(8) NULL,
  `maViTri` VARCHAR(14) NULL,
  `maNhanVienTruongDoan` VARCHAR(8) NOT NULL,
  `ngayBatDau` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ngayKetThuc` DATETIME NULL,
  `loaiKiemKe` NVARCHAR(15) NOT NULL,
  `trangThai` NVARCHAR(19) NOT NULL DEFAULT 'Đang kiểm kê',
  `ghiChu` NVARCHAR(255) NULL,
  PRIMARY KEY (`maBienBanKiemKe`),
  CONSTRAINT `ck_BienBanKiemKe_1` CHECK (maBienBanKiemKe REGEXP '^BBKK[0-9]{10}$'),
-- Chấp nhận cả mã chuẩn của code (DinhKy/DotXuat/TheoMatHang) và nhãn cũ trong dữ liệu mẫu.
  CONSTRAINT `ck_BienBanKiemKe_2` CHECK (loaiKiemKe IN ('Định kỳ','Đột xuất','Theo mặt hàng','DinhKy','DotXuat','TheoMatHang')),
-- Chấp nhận cả mã chuẩn của code (Chờ xử lý/Đang xử lý/Hoàn tất/Từ chối) và nhãn cũ trong dữ liệu mẫu.
  CONSTRAINT `ck_BienBanKiemKe_3` CHECK (trangThai IN ('Đang kiểm kê', 'Đã hoàn thành', 'Đã duyệt điều chỉnh', 'Đã hủy', 'Chờ xử lý', 'Đang xử lý', 'Hoàn tất', 'Từ chối')),
  CONSTRAINT `ck_BienBanKiemKe_4` CHECK (ngayKetThuc IS NULL OR ngayKetThuc >= ngayBatDau),
  CONSTRAINT `ck_BienBanKiemKe_5` CHECK ((trangThai IN ('Đã hoàn thành', 'Hoàn tất') AND ngayKetThuc IS NOT NULL) OR (trangThai IN ('Đang kiểm kê', 'Đã duyệt điều chỉnh', 'Đã hủy', 'Chờ xử lý', 'Đang xử lý', 'Từ chối') AND ngayKetThuc IS NULL)),
  CONSTRAINT `fk_BienBanKiemKe_1` FOREIGN KEY (`maKho`) REFERENCES `Kho`(`maKho`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_BienBanKiemKe_2` FOREIGN KEY (`maKhuVuc`) REFERENCES `KhuVuc`(`maKhuVuc`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_BienBanKiemKe_3` FOREIGN KEY (`maNhanVienTruongDoan`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_BienBanKiemKe_4` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_BBKK_trangThai (trangThai, ngayBatDau),
  INDEX idx_BBKK_vitri (maViTri)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [33] KetQuaKiemKe
CREATE TABLE IF NOT EXISTS `KetQuaKiemKe` (
  `maKetQuaKiemKe` VARCHAR(16) NOT NULL,
  `maBienBanKiemKe` VARCHAR(14) NOT NULL,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maViTri` VARCHAR(14) NOT NULL,
  `soLuongSoSach` DOUBLE NOT NULL,
  `soLuongThucTe` DOUBLE NOT NULL,
  `chenhLech` DOUBLE GENERATED ALWAYS AS (soLuongThucTe - soLuongSoSach) STORED,
  `maNhanVienKiemKe` VARCHAR(8) NOT NULL,
  `ghiChu` NVARCHAR(255) NULL,
  PRIMARY KEY (`maKetQuaKiemKe`),
  CONSTRAINT `uq_KetQuaKiemKe_1` UNIQUE (`maBienBanKiemKe`, `maLoHang`, `maViTri`),
  CONSTRAINT `ck_KetQuaKiemKe_1` CHECK (maKetQuaKiemKe REGEXP '^KQKK[0-9]{12}$'),
  CONSTRAINT `ck_KetQuaKiemKe_2` CHECK (soLuongSoSach >= 0),
  CONSTRAINT `ck_KetQuaKiemKe_3` CHECK (soLuongThucTe >= 0),
  CONSTRAINT `fk_KetQuaKiemKe_1` FOREIGN KEY (`maBienBanKiemKe`) REFERENCES `BienBanKiemKe`(`maBienBanKiemKe`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_KetQuaKiemKe_2` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_KetQuaKiemKe_3` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_KetQuaKiemKe_4` FOREIGN KEY (`maNhanVienKiemKe`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [34] ChenhLechKiemKe
CREATE TABLE IF NOT EXISTS `ChenhLechKiemKe` (
  `maChenhLech` VARCHAR(16) NOT NULL,
  `maKetQuaKiemKe` VARCHAR(16) NOT NULL,
  `loaiChenhLech` VARCHAR(20) NOT NULL,
  `soLuongChenhLech` DOUBLE NOT NULL,
  `nguyenNhan` NVARCHAR(20) NULL,
  `moTaChiTiet` NVARCHAR(255) NULL,
  PRIMARY KEY (`maChenhLech`),
  CONSTRAINT `uq_ChenhLechKiemKe_1` UNIQUE (`maKetQuaKiemKe`),
  CONSTRAINT `ck_ChenhLechKiemKe_1` CHECK (maChenhLech REGEXP '^CLKK[0-9]{12}$'),
  CONSTRAINT `ck_ChenhLechKiemKe_2` CHECK (loaiChenhLech IN ('Thừa', 'Thiếu', 'Không chênh lệch')),
  CONSTRAINT `ck_ChenhLechKiemKe_3` CHECK (soLuongChenhLech >= 0),
  CONSTRAINT `ck_ChenhLechKiemKe_4` CHECK (nguyenNhan IS NULL OR nguyenNhan IN ('Hao hụt', 'Sai sót', 'Thất thoát', 'Chưa cập nhật', 'HaoHut', 'SaiSot', 'ThatThoat', 'ChuaCapNhat')),
  CONSTRAINT `ck_ChenhLechKiemKe_5` CHECK ((loaiChenhLech = 'Không chênh lệch' AND soLuongChenhLech = 0) OR (loaiChenhLech IN ('Thừa', 'Thiếu') AND soLuongChenhLech > 0)),
  CONSTRAINT `ck_ChenhLechKiemKe_6` CHECK ((loaiChenhLech = 'Không chênh lệch' AND nguyenNhan IS NULL) OR (loaiChenhLech IN ('Thừa', 'Thiếu') AND nguyenNhan IS NOT NULL)),
  CONSTRAINT `ck_ChenhLechKiemKe_7` CHECK (nguyenNhan <> 'Khác' OR (moTaChiTiet IS NOT NULL AND CHAR_LENGTH(TRIM(moTaChiTiet)) > 0)),
  CONSTRAINT `fk_ChenhLechKiemKe_1` FOREIGN KEY (`maKetQuaKiemKe`) REFERENCES `KetQuaKiemKe`(`maKetQuaKiemKe`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [37] HangBatThuong
CREATE TABLE IF NOT EXISTS `HangBatThuong` (
  `maHangBatThuong` VARCHAR(13) NOT NULL,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maViTri` VARCHAR(14) NOT NULL,
  `loaiBatThuong` NVARCHAR(20) NOT NULL,
  `soLuong` DOUBLE NOT NULL,
  `maNhanVienPhatHien` VARCHAR(8) NOT NULL,
  `ngayPhatHien` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hinhAnhMinhChung` VARCHAR(255) NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Đã ghi nhận',
  `moTa` NVARCHAR(255) NULL,
  PRIMARY KEY (`maHangBatThuong`),
  CONSTRAINT `ck_HangBatThuong_1` CHECK (maHangBatThuong REGEXP '^HBT[0-9]{10}$'),
  CONSTRAINT `ck_HangBatThuong_2` CHECK (loaiBatThuong IN ('Rách vỡ', 'Hư hỏng', 'Hết hạn')),
  CONSTRAINT `ck_HangBatThuong_3` CHECK (soLuong > 0),
  CONSTRAINT `ck_HangBatThuong_4` CHECK (trangThai IN ('Đã ghi nhận', 'Đang cách ly', 'Đã xử lý', 'Đã thanh lý')),
  CONSTRAINT `ck_HangBatThuong_5` CHECK (loaiBatThuong <> 'Khác' OR (moTa IS NOT NULL AND CHAR_LENGTH(TRIM(moTa)) > 0)),
  CONSTRAINT `fk_HangBatThuong_1` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_HangBatThuong_2` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_HangBatThuong_3` FOREIGN KEY (`maNhanVienPhatHien`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_HBT_trangThai (trangThai, ngayPhatHien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [35] PhuongAnXuLy
CREATE TABLE IF NOT EXISTS `PhuongAnXuLy` (
  `maPhuongAn` VARCHAR(14) NOT NULL,
  `maChenhLech` VARCHAR(16) NULL,
  `maHangBatThuong` VARCHAR(13) NULL,
  `bienPhapXuLy` NVARCHAR(23) NOT NULL,
  `moTa` NVARCHAR(255) NULL,
  `maNhanVienDeXuat` VARCHAR(8) NOT NULL,
  `maNhanVienPheDuyet` VARCHAR(8) NULL,
  `ngayDeXuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ngayPheDuyet` DATETIME NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Chờ phê duyệt',
  `yKienPheDuyet` NVARCHAR(255) NULL,
  PRIMARY KEY (`maPhuongAn`),
  CONSTRAINT `ck_PhuongAnXuLy_1` CHECK (maPhuongAn REGEXP '^PAXL[0-9]{10}$'),
  CONSTRAINT `ck_PhuongAnXuLy_2` CHECK (bienPhapXuLy IN ('Điều chỉnh sổ sách tăng','Điều chỉnh sổ sách giảm','Ghi nhận hao hụt','Xuất trả nhà cung cấp','Tiêu hủy','Hạ cấp chất lượng','DieuChinhSoSachTang','DieuChinhSoSachGiam','GhiNhanHaoHut','XuatTraNhaCungCap','TieuHuy','HaCapChatLuong')),
  CONSTRAINT `ck_PhuongAnXuLy_3` CHECK (trangThai IN ('Chờ phê duyệt', 'Đã phê duyệt', 'Đã từ chối', 'Đã thi hành', 'Đã hủy', 'Chờ xử lý', 'Đang xử lý', 'Hoàn tất', 'Từ chối')),
  CONSTRAINT `ck_PhuongAnXuLy_4` CHECK ((maChenhLech IS NOT NULL AND maHangBatThuong IS NULL) OR (maChenhLech IS NULL AND maHangBatThuong IS NOT NULL)),
  CONSTRAINT `ck_PhuongAnXuLy_5` CHECK (ngayPheDuyet IS NULL OR ngayPheDuyet >= ngayDeXuat),
  CONSTRAINT `ck_PhuongAnXuLy_6` CHECK ((trangThai IN ('Chờ phê duyệt', 'Chờ xử lý') AND maNhanVienPheDuyet IS NULL AND ngayPheDuyet IS NULL) OR (trangThai IN ('Đã phê duyệt', 'Đã thi hành', 'Đã từ chối', 'Đã hủy', 'Đang xử lý', 'Hoàn tất', 'Từ chối') AND maNhanVienPheDuyet IS NOT NULL AND ngayPheDuyet IS NOT NULL)),
  CONSTRAINT `ck_PhuongAnXuLy_7` CHECK (trangThai NOT IN ('Đã từ chối', 'Từ chối') OR (yKienPheDuyet IS NOT NULL AND CHAR_LENGTH(TRIM(yKienPheDuyet)) > 0)),
  CONSTRAINT `fk_PhuongAnXuLy_1` FOREIGN KEY (`maChenhLech`) REFERENCES `ChenhLechKiemKe`(`maChenhLech`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhuongAnXuLy_2` FOREIGN KEY (`maHangBatThuong`) REFERENCES `HangBatThuong`(`maHangBatThuong`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhuongAnXuLy_3` FOREIGN KEY (`maNhanVienDeXuat`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhuongAnXuLy_4` FOREIGN KEY (`maNhanVienPheDuyet`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX idx_PAXL_trangThai (trangThai, ngayDeXuat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [36] PhieuDieuChinhTonKho
CREATE TABLE IF NOT EXISTS `PhieuDieuChinhTonKho` (
  `maPhieuDieuChinh` VARCHAR(15) NOT NULL,
  `maPhuongAn` VARCHAR(14) NOT NULL,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maViTri` VARCHAR(14) NOT NULL,
  `loaiDieuChinh` NVARCHAR(5) NOT NULL,
  `soLuongDieuChinh` DOUBLE NOT NULL,
  `maNhanVienThiHanh` VARCHAR(8) NOT NULL,
  `ngayThiHanh` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Đã thi hành',
  `ghiChu` NVARCHAR(255) NULL,
  PRIMARY KEY (`maPhieuDieuChinh`),
  CONSTRAINT `ck_PhieuDieuChinhTon_1` CHECK (maPhieuDieuChinh REGEXP '^PDCTK[0-9]{10}$'),
  CONSTRAINT `ck_PhieuDieuChinhTon_2` CHECK (loaiDieuChinh IN ('Tăng', 'Giảm', 'Tang', 'Giam')),
  CONSTRAINT `ck_PhieuDieuChinhTon_3` CHECK (soLuongDieuChinh > 0),
  CONSTRAINT `ck_PhieuDieuChinhTon_4` CHECK (trangThai IN ('Đã thi hành', 'Đã hủy', 'Hoàn tất')),
  CONSTRAINT `fk_PhieuDieuChinhTon_1` FOREIGN KEY (`maPhuongAn`) REFERENCES `PhuongAnXuLy`(`maPhuongAn`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuDieuChinhTon_2` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuDieuChinhTon_3` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhieuDieuChinhTon_4` FOREIGN KEY (`maNhanVienThiHanh`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [38] BienBanTraHang
CREATE TABLE IF NOT EXISTS `BienBanTraHang` (
  `maBienBanTraHang` VARCHAR(14) NOT NULL,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maPhieuGiao` VARCHAR(12) NULL,
  `soLuongTra` DOUBLE NOT NULL,
  `maNhanVienLap` VARCHAR(8) NOT NULL,
  `ngayTraHang` DATETIME NULL,
  `lyDoTraHang` NVARCHAR(200) NOT NULL,
  `daiDienNhaCungCap` NVARCHAR(60) NULL,
  `trangThai` NVARCHAR(15) NOT NULL DEFAULT 'Chờ xuất trả',
  PRIMARY KEY (`maBienBanTraHang`),
  CONSTRAINT `ck_BienBanTraHang_1` CHECK (maBienBanTraHang REGEXP '^BBTH[0-9]{10}$'),
  CONSTRAINT `ck_BienBanTraHang_2` CHECK (soLuongTra > 0),
  CONSTRAINT `ck_BienBanTraHang_3` CHECK (CHAR_LENGTH(TRIM(lyDoTraHang)) > 0),
  CONSTRAINT `ck_BienBanTraHang_4` CHECK (trangThai IN ('Chờ xuất trả', 'Đã trả hàng', 'Bị từ chối nhận', 'Đã hủy')),
  CONSTRAINT `ck_BienBanTraHang_5` CHECK ((trangThai = 'Đã trả hàng' AND ngayTraHang IS NOT NULL) OR (trangThai IN ('Chờ xuất trả', 'Bị từ chối nhận', 'Đã hủy') AND ngayTraHang IS NULL)),
  CONSTRAINT `fk_BienBanTraHang_1` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_BienBanTraHang_2` FOREIGN KEY (`maPhieuGiao`) REFERENCES `PhieuGiaoNCC`(`maPhieuGiao`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_BienBanTraHang_3` FOREIGN KEY (`maNhanVienLap`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [39] PhamViKiemKe — phạm vi VỊ TRÍ của một biên bản kiểm kê + dấu "đã kiểm tra".
-- Có dòng ở đây không có nghĩa là vị trí có hàng: đây là danh sách vị trí phải đi kiểm tra,
-- nhờ đó biểu diễn được "vị trí trống đã kiểm tra" (khác với "chưa kiểm tra").
CREATE TABLE IF NOT EXISTS `PhamViKiemKe` (
  `maPhamVi` BIGINT NOT NULL AUTO_INCREMENT,
  `maBienBanKiemKe` VARCHAR(14) NOT NULL,
  `maViTri` VARCHAR(14) NOT NULL,
  `trangThaiKiemTra` NVARCHAR(15) NOT NULL DEFAULT 'Chưa kiểm tra',
  `maNhanVienKiemTra` VARCHAR(8) NULL,
  `thoiDiemKiemTra` DATETIME NULL,
  PRIMARY KEY (`maPhamVi`),
  CONSTRAINT `uq_PhamViKiemKe_1` UNIQUE (`maBienBanKiemKe`, `maViTri`),
  CONSTRAINT `ck_PhamViKiemKe_1` CHECK (`trangThaiKiemTra` IN ('Chưa kiểm tra', 'Đã kiểm tra')),
  CONSTRAINT `ck_PhamViKiemKe_2` CHECK (
    (`trangThaiKiemTra` = 'Chưa kiểm tra' AND `maNhanVienKiemTra` IS NULL AND `thoiDiemKiemTra` IS NULL)
    OR (`trangThaiKiemTra` = 'Đã kiểm tra' AND `maNhanVienKiemTra` IS NOT NULL AND `thoiDiemKiemTra` IS NOT NULL)
  ),
  CONSTRAINT `fk_PhamViKiemKe_1` FOREIGN KEY (`maBienBanKiemKe`) REFERENCES `BienBanKiemKe`(`maBienBanKiemKe`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhamViKiemKe_2` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_PhamViKiemKe_3` FOREIGN KEY (`maNhanVienKiemTra`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX `idx_PhamViKiemKe_trangThai` (`maBienBanKiemKe`, `trangThaiKiemTra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [40] SnapshotTonKhoKiemKe — số lượng sổ sách ĐÓNG BĂNG tại thời điểm lập biên bản kiểm kê.
-- Gồm cả dòng TonKho có soLuongTon = 0 để phân biệt "đã kiểm, thực tế 0" với "chưa kiểm".
CREATE TABLE IF NOT EXISTS `SnapshotTonKhoKiemKe` (
  `maSnapshot` BIGINT NOT NULL AUTO_INCREMENT,
  `maBienBanKiemKe` VARCHAR(14) NOT NULL,
  `maLoHang` VARCHAR(12) NOT NULL,
  `maViTri` VARCHAR(14) NOT NULL,
  `soLuongTon` DOUBLE NOT NULL,
  `soLuongDaGiu` DOUBLE NOT NULL DEFAULT 0,
  PRIMARY KEY (`maSnapshot`),
  CONSTRAINT `uq_SnapshotTonKhoKiemKe_1` UNIQUE (`maBienBanKiemKe`, `maLoHang`, `maViTri`),
  CONSTRAINT `ck_SnapshotTonKhoKiemKe_1` CHECK (`soLuongTon` >= 0),
  CONSTRAINT `ck_SnapshotTonKhoKiemKe_2` CHECK (`soLuongDaGiu` >= 0 AND `soLuongDaGiu` <= `soLuongTon`),
  CONSTRAINT `fk_SnapshotTonKhoKiemKe_1` FOREIGN KEY (`maBienBanKiemKe`) REFERENCES `BienBanKiemKe`(`maBienBanKiemKe`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_SnapshotTonKhoKiemKe_2` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_SnapshotTonKhoKiemKe_3` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LichSuBienDongTonKho: chi INSERT, cam UPDATE/DELETE (append-only).
-- DROP/TRUNCATE khong bi ngan boi trigger: han che quyen bang tai khoan ung dung.
DROP TRIGGER IF EXISTS `trg_LSBD_no_update`;
DROP TRIGGER IF EXISTS `trg_LSBD_no_delete`;

DELIMITER $$
CREATE TRIGGER `trg_LSBD_no_update`
BEFORE UPDATE ON `LichSuBienDongTonKho`
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'LichSuBienDongTonKho chi cho phep INSERT, cam UPDATE';
END$$
CREATE TRIGGER `trg_LSBD_no_delete`
BEFORE DELETE ON `LichSuBienDongTonKho`
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'LichSuBienDongTonKho chi cho phep INSERT, cam DELETE';
END$$
DELIMITER ;