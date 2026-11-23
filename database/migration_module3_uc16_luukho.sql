-- FeedWare - MODULE 3 - UC16: so phan bo vi tri luu tru.
-- Ghi nhan viec gan lo vao vi tri ma KHONG thay doi tong ton kho (TonKho).
-- Moi dong LuuKho la mot lan phan bo, dung lam lich su luu tru.
USE `feed_wms`;

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
  CONSTRAINT `ck_LuuKho_1` CHECK (`soLuongPhanBo` > 0),
  CONSTRAINT `fk_LuuKho_1` FOREIGN KEY (`maLoHang`) REFERENCES `LoHang`(`maLoHang`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_LuuKho_2` FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_LuuKho_3` FOREIGN KEY (`maPhieuNhapKho`) REFERENCES `PhieuNhapKho`(`maPhieuNhapKho`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_LuuKho_4` FOREIGN KEY (`maNhanVienThucHien`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX `idx_LuuKho_lo_tg` (`maLoHang`, `thoiDiemPhanBo`),
  INDEX `idx_LuuKho_vitri` (`maViTri`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
