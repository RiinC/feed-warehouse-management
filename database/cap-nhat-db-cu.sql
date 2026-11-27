-- =====================================================================
-- FeedWare - FILE NÂNG CẤP CƠ SỞ DỮ LIỆU CŨ (cap-nhat-db-cu.sql)
-- Dành cho người ĐÃ CÓ sẵn CSDL `feed_wms` từ trước khi pull code mới
-- (giữ nguyên dữ liệu đang có, KHÔNG cần xóa/import lại từ đầu).
--
-- Lưu ý:
--   - Người CÀI MỚI hoàn toàn: chỉ cần chạy lần lượt 3 tệp:
--       1) database/schema.sql
--       2) database/seed_data.sql
--       3) database/thong-bao.sql
--   - Người ĐÃ CÓ DB CŨ: chạy thêm 1 tệp duy nhất này:
--       mysql -u root --default-character-set=utf8mb4 < database/cap-nhat-db-cu.sql
--     (hoặc Import trực tiếp trong phpMyAdmin). An toàn khi chạy lại nhiều lần.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `feed_wms` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `feed_wms`;
SET NAMES utf8mb4;

-- =====================================================================
-- 1. TẠO CÁC BẢNG MỚI NẾU CHƯA TỒN TẠI
-- =====================================================================

-- Bảng LuuKho (Module 3 - UC16)
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

-- Bảng PhamViKiemKe (UC24-UC27)
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

-- Bảng SnapshotTonKhoKiemKe (UC24-UC27)
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

-- Bảng ThongBao (chuông thông báo) nếu DB cũ chưa chạy thong-bao.sql
CREATE TABLE IF NOT EXISTS `ThongBao` (
  `maThongBao`  BIGINT       NOT NULL AUTO_INCREMENT,
  `maNhanVien`  VARCHAR(8)   NOT NULL COMMENT 'Người nhận',
  `tieuDe`      VARCHAR(150) NOT NULL,
  `noiDung`     VARCHAR(500) NULL,
  `duongDan`    VARCHAR(255) NULL COMMENT 'Route đích (xem routes/web.php), có thể kèm tham số',
  `loai`        VARCHAR(20)  NOT NULL DEFAULT 'info',
  `daDoc`       TINYINT(1)   NOT NULL DEFAULT 0,
  `thoiDiemTao` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `thoiDiemDoc` DATETIME     NULL,
  PRIMARY KEY (`maThongBao`),
  CONSTRAINT `ck_ThongBao_1` CHECK (`loai` IN ('info','success','warning','danger')),
  CONSTRAINT `fk_ThongBao_1` FOREIGN KEY (`maNhanVien`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  INDEX `idx_ThongBao_nguoiNhan` (`maNhanVien`, `daDoc`, `thoiDiemTao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. BỔ SUNG CỘT, INDEX, KHÓA NGOẠI VÀ ĐỒNG BỘ CHECK CONSTRAINTS
--    (Kiểm tra qua INFORMATION_SCHEMA để tương thích cả MariaDB & MySQL)
-- =====================================================================

DROP PROCEDURE IF EXISTS `__fw_add_col_if_missing`;
DROP PROCEDURE IF EXISTS `__fw_add_idx_if_missing`;
DROP PROCEDURE IF EXISTS `__fw_add_fk_if_missing`;
DROP PROCEDURE IF EXISTS `__fw_drop_check_if_exists`;
DROP PROCEDURE IF EXISTS `__fw_upgrade_db`;

DELIMITER $$

CREATE PROCEDURE `__fw_add_col_if_missing`(
  IN p_table VARCHAR(64),
  IN p_col VARCHAR(64),
  IN p_definition TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND LOWER(TABLE_NAME) = LOWER(p_table)
      AND LOWER(COLUMN_NAME) = LOWER(p_col)
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_definition);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE PROCEDURE `__fw_add_idx_if_missing`(
  IN p_table VARCHAR(64),
  IN p_idx VARCHAR(64),
  IN p_definition TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND LOWER(TABLE_NAME) = LOWER(p_table)
      AND LOWER(INDEX_NAME) = LOWER(p_idx)
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_idx, '` ', p_definition);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE PROCEDURE `__fw_add_fk_if_missing`(
  IN p_table VARCHAR(64),
  IN p_fk VARCHAR(64),
  IN p_definition TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND LOWER(TABLE_NAME) = LOWER(p_table)
      AND LOWER(CONSTRAINT_NAME) = LOWER(p_fk)
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD CONSTRAINT `', p_fk, '` ', p_definition);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE PROCEDURE `__fw_drop_check_if_exists`(
  IN p_table VARCHAR(64),
  IN p_ck VARCHAR(64)
)
BEGIN
  IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND LOWER(TABLE_NAME) = LOWER(p_table)
      AND LOWER(CONSTRAINT_NAME) = LOWER(p_ck)
      AND CONSTRAINT_TYPE = 'CHECK'
  ) THEN
    -- MariaDB & MySQL 8 đều hỗ trợ DROP CONSTRAINT cho CHECK trong TABLE_CONSTRAINTS
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` DROP CONSTRAINT `', p_ck, '`');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE PROCEDURE `__fw_upgrade_db`()
BEGIN
  -- 2a. Bổ sung cột còn thiếu
  CALL `__fw_add_col_if_missing`('PhieuDieuChuyen', 'maNhanVienDuyet', '`maNhanVienDuyet` VARCHAR(8) NULL AFTER `maNhanVienThucHien`');
  CALL `__fw_add_col_if_missing`('PhieuDieuChuyen', 'ngayDuyet', '`ngayDuyet` DATETIME NULL AFTER `ngayYeuCau`');
  CALL `__fw_add_col_if_missing`('PhieuDieuChuyen', 'lyDoTuChoi', '`lyDoTuChoi` NVARCHAR(255) NULL AFTER `lyDoDieuChuyen`');

  CALL `__fw_add_col_if_missing`('BienBanKiemKe', 'maViTri', '`maViTri` VARCHAR(14) NULL AFTER `maKhuVuc`');

  CALL `__fw_add_col_if_missing`('PhuongAnXuLy', 'moTa', '`moTa` NVARCHAR(255) NULL AFTER `bienPhapXuLy`');

  -- 2b. Đảm bảo độ dài cột trangThai đủ chứa các nhãn/mã trạng thái mới
  ALTER TABLE `PhieuDieuChuyen` MODIFY COLUMN `trangThai` VARCHAR(20) NOT NULL DEFAULT 'Chờ điều chuyển';
  ALTER TABLE `BienBanKiemKe`   MODIFY COLUMN `trangThai` VARCHAR(20) NOT NULL DEFAULT 'Đang kiểm kê';
  ALTER TABLE `HangBatThuong`   MODIFY COLUMN `trangThai` VARCHAR(20) NOT NULL DEFAULT 'Đang cách ly';
  ALTER TABLE `PhuongAnXuLy`    MODIFY COLUMN `trangThai` VARCHAR(20) NOT NULL DEFAULT 'Chờ xử lý';

  -- 2c. Bổ sung index và khóa ngoại còn thiếu
  CALL `__fw_add_idx_if_missing`('BienBanKiemKe', 'idx_BBKK_vitri', '(`maViTri`)');
  CALL `__fw_add_fk_if_missing`('BienBanKiemKe', 'fk_BienBanKiemKe_4', 'FOREIGN KEY (`maViTri`) REFERENCES `ViTriLuuTru`(`maViTri`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  CALL `__fw_add_fk_if_missing`('PhieuDieuChuyen', 'fk_PhieuDieuChuyen_6', 'FOREIGN KEY (`maNhanVienDuyet`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT');

  -- 2d. Đồng bộ CHECK constraints (chấp nhận cả mã code mới và nhãn cũ)
  -- LoHang
  CALL `__fw_drop_check_if_exists`('LoHang', 'ck_LoHang_4');
  CALL `__fw_drop_check_if_exists`('LoHang', 'ck_LoHang_5');
  ALTER TABLE `LoHang`
    ADD CONSTRAINT `ck_LoHang_4` CHECK (`trangThaiQC` IN ('Chờ kiểm tra', 'Đã đạt chuẩn', 'Đã kiểm tra không đạt', 'Chờ tái kiểm', 'Chờ kiểm định', 'Đạt', 'Không đạt')),
    ADD CONSTRAINT `ck_LoHang_5` CHECK (`trangThaiVongDoi` IN ('Đã tạo', 'Mới tạo', 'Đang lưu kho', 'Sắp hết hạn', 'Hết hạn', 'Đang cách ly', 'Đã xuất hết', 'Đã thanh lý', 'Đã tiêu hủy'));

  -- LichSuBienDongTonKho
  CALL `__fw_drop_check_if_exists`('LichSuBienDongTonKho', 'ck_LichSuBienDongTon_2');
  CALL `__fw_drop_check_if_exists`('LichSuBienDongTonKho', 'ck_LichSuBienDongTon_7');
  ALTER TABLE `LichSuBienDongTonKho`
    ADD CONSTRAINT `ck_LichSuBienDongTon_2` CHECK (`loaiGiaoDich` IN ('Nhập kho','Xuất kho','Điều chuyển đến','Điều chuyển đi','Điều chỉnh tăng','Điều chỉnh giảm','Hao hụt','NhapKho','XuatKho','DieuChuyenDen','DieuChuyenDi','DieuChinhTang','DieuChinhGiam','HaoHut')),
    ADD CONSTRAINT `ck_LichSuBienDongTon_7` CHECK ((`loaiGiaoDich` IN ('Nhập kho','Điều chuyển đến','Điều chỉnh tăng','NhapKho','DieuChuyenDen','DieuChinhTang') AND `soLuongBienDong` > 0) OR (`loaiGiaoDich` IN ('Xuất kho','Điều chuyển đi','Điều chỉnh giảm','Hao hụt','XuatKho','DieuChuyenDi','DieuChinhGiam','HaoHut') AND `soLuongBienDong` < 0));

  -- PhieuDieuChuyen
  CALL `__fw_drop_check_if_exists`('PhieuDieuChuyen', 'ck_PhieuDieuChuyen_4');
  CALL `__fw_drop_check_if_exists`('PhieuDieuChuyen', 'ck_PhieuDieuChuyen_8');
  CALL `__fw_drop_check_if_exists`('PhieuDieuChuyen', 'ck_PhieuDieuChuyen_10');
  CALL `__fw_drop_check_if_exists`('PhieuDieuChuyen', 'ck_PhieuDieuChuyen_11');
  CALL `__fw_drop_check_if_exists`('PhieuDieuChuyen', 'ck_PhieuDieuChuyen_12');
  CALL `__fw_drop_check_if_exists`('PhieuDieuChuyen', 'ck_PhieuDieuChuyen_13');
  ALTER TABLE `PhieuDieuChuyen`
    ADD CONSTRAINT `ck_PhieuDieuChuyen_4`  CHECK (`trangThai` IN ('Chờ duyệt', 'Đã duyệt', 'Đã từ chối', 'Chờ điều chuyển', 'Đang thực hiện', 'Đã hoàn tất', 'Đã hủy', 'Chờ xử lý', 'Đang xử lý', 'Hoàn tất')),
    ADD CONSTRAINT `ck_PhieuDieuChuyen_8`  CHECK (`trangThai` NOT IN ('Đã hoàn tất', 'Hoàn tất') OR (`soLuongThucChuyen` IS NOT NULL AND `soLuongThucChuyen` > 0 AND `maNhanVienThucHien` IS NOT NULL AND `ngayHoanTat` IS NOT NULL)),
    ADD CONSTRAINT `ck_PhieuDieuChuyen_10` CHECK (`ngayDuyet` IS NULL OR `ngayDuyet` >= `ngayYeuCau`),
    ADD CONSTRAINT `ck_PhieuDieuChuyen_11` CHECK (`trangThai` NOT IN ('Đã duyệt', 'Đã từ chối') OR (`maNhanVienDuyet` IS NOT NULL AND `ngayDuyet` IS NOT NULL)),
    ADD CONSTRAINT `ck_PhieuDieuChuyen_12` CHECK (`trangThai` <> 'Đã từ chối' OR (`lyDoTuChoi` IS NOT NULL AND CHAR_LENGTH(TRIM(`lyDoTuChoi`)) > 0)),
    ADD CONSTRAINT `ck_PhieuDieuChuyen_13` CHECK (`maNhanVienDuyet` IS NULL OR `maNhanVienDuyet` <> `maNhanVienYeuCau`);

  -- BienBanKiemKe
  CALL `__fw_drop_check_if_exists`('BienBanKiemKe', 'ck_BienBanKiemKe_2');
  CALL `__fw_drop_check_if_exists`('BienBanKiemKe', 'ck_BienBanKiemKe_3');
  CALL `__fw_drop_check_if_exists`('BienBanKiemKe', 'ck_BienBanKiemKe_5');
  ALTER TABLE `BienBanKiemKe`
    ADD CONSTRAINT `ck_BienBanKiemKe_2` CHECK (`loaiKiemKe` IN ('Định kỳ','Đột xuất','Theo mặt hàng','DinhKy','DotXuat','TheoMatHang')),
    ADD CONSTRAINT `ck_BienBanKiemKe_3` CHECK (`trangThai` IN ('Đang kiểm kê', 'Đã hoàn thành', 'Đã duyệt điều chỉnh', 'Đã hủy', 'Chờ xử lý', 'Đang xử lý', 'Hoàn tất', 'Từ chối')),
    ADD CONSTRAINT `ck_BienBanKiemKe_5` CHECK ((`trangThai` IN ('Đã hoàn thành', 'Hoàn tất') AND `ngayKetThuc` IS NOT NULL) OR (`trangThai` IN ('Đang kiểm kê', 'Đã duyệt điều chỉnh', 'Đã hủy', 'Chờ xử lý', 'Đang xử lý', 'Từ chối') AND `ngayKetThuc` IS NULL));

  -- ChenhLechKiemKe
  CALL `__fw_drop_check_if_exists`('ChenhLechKiemKe', 'ck_ChenhLechKiemKe_4');
  ALTER TABLE `ChenhLechKiemKe`
    ADD CONSTRAINT `ck_ChenhLechKiemKe_4` CHECK (`nguyenNhan` IS NULL OR `nguyenNhan` IN ('Hao hụt', 'Sai sót', 'Thất thoát', 'Chưa cập nhật', 'HaoHut', 'SaiSot', 'ThatThoat', 'ChuaCapNhat'));

  -- HangBatThuong (bổ sung 'Đang xử lý' khi đề xuất/duyệt phương án)
  CALL `__fw_drop_check_if_exists`('HangBatThuong', 'ck_HangBatThuong_4');
  ALTER TABLE `HangBatThuong`
    ADD CONSTRAINT `ck_HangBatThuong_4` CHECK (`trangThai` IN ('Đã ghi nhận', 'Đang cách ly', 'Đang xử lý', 'Đã xử lý', 'Đã thanh lý'));

  -- PhuongAnXuLy
  CALL `__fw_drop_check_if_exists`('PhuongAnXuLy', 'ck_PhuongAnXuLy_2');
  CALL `__fw_drop_check_if_exists`('PhuongAnXuLy', 'ck_PhuongAnXuLy_3');
  CALL `__fw_drop_check_if_exists`('PhuongAnXuLy', 'ck_PhuongAnXuLy_6');
  CALL `__fw_drop_check_if_exists`('PhuongAnXuLy', 'ck_PhuongAnXuLy_7');
  ALTER TABLE `PhuongAnXuLy`
    ADD CONSTRAINT `ck_PhuongAnXuLy_2` CHECK (`bienPhapXuLy` IN ('Điều chỉnh sổ sách tăng','Điều chỉnh sổ sách giảm','Ghi nhận hao hụt','Xuất trả nhà cung cấp','Tiêu hủy','Hạ cấp chất lượng','DieuChinhSoSachTang','DieuChinhSoSachGiam','GhiNhanHaoHut','XuatTraNhaCungCap','TieuHuy','HaCapChatLuong')),
    ADD CONSTRAINT `ck_PhuongAnXuLy_3` CHECK (`trangThai` IN ('Chờ phê duyệt', 'Đã phê duyệt', 'Đã từ chối', 'Đã thi hành', 'Đã hủy', 'Chờ xử lý', 'Đang xử lý', 'Hoàn tất', 'Từ chối')),
    ADD CONSTRAINT `ck_PhuongAnXuLy_6` CHECK ((`trangThai` IN ('Chờ phê duyệt', 'Chờ xử lý') AND `maNhanVienPheDuyet` IS NULL AND `ngayPheDuyet` IS NULL) OR (`trangThai` IN ('Đã phê duyệt', 'Đã thi hành', 'Đã từ chối', 'Đã hủy', 'Đang xử lý', 'Hoàn tất', 'Từ chối') AND `maNhanVienPheDuyet` IS NOT NULL AND `ngayPheDuyet` IS NOT NULL)),
    ADD CONSTRAINT `ck_PhuongAnXuLy_7` CHECK (`trangThai` NOT IN ('Đã từ chối', 'Từ chối') OR (`yKienPheDuyet` IS NOT NULL AND CHAR_LENGTH(TRIM(`yKienPheDuyet`)) > 0));

  -- PhieuDieuChinhTonKho
  CALL `__fw_drop_check_if_exists`('PhieuDieuChinhTonKho', 'ck_PhieuDieuChinhTon_2');
  CALL `__fw_drop_check_if_exists`('PhieuDieuChinhTonKho', 'ck_PhieuDieuChinhTon_4');
  ALTER TABLE `PhieuDieuChinhTonKho`
    ADD CONSTRAINT `ck_PhieuDieuChinhTon_2` CHECK (`loaiDieuChinh` IN ('Tăng', 'Giảm', 'Tang', 'Giam')),
    ADD CONSTRAINT `ck_PhieuDieuChinhTon_4` CHECK (`trangThai` IN ('Đã thi hành', 'Đã hủy', 'Hoàn tất'));
END$$

DELIMITER ;

CALL `__fw_upgrade_db`();

DROP PROCEDURE IF EXISTS `__fw_upgrade_db`;
DROP PROCEDURE IF EXISTS `__fw_drop_check_if_exists`;
DROP PROCEDURE IF EXISTS `__fw_add_fk_if_missing`;
DROP PROCEDURE IF EXISTS `__fw_add_idx_if_missing`;
DROP PROCEDURE IF EXISTS `__fw_add_col_if_missing`;

-- =====================================================================
-- 3. NÂNG SỨC CHỨA 7 VỊ TRÍ KỆ VƯỢT TẢI TRONG DỮ LIỆU MẪU CŨ (NẾU THẤP HƠN)
-- =====================================================================

UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = GREATEST(`sucChuaToiDa`, 52000) WHERE `maViTri` = 'VT010101010001';
UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = GREATEST(`sucChuaToiDa`, 32000) WHERE `maViTri` = 'VT010102010002';
UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = GREATEST(`sucChuaToiDa`, 48000) WHERE `maViTri` = 'VT010201010003';
UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = GREATEST(`sucChuaToiDa`, 28000) WHERE `maViTri` = 'VT010202010004';
UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = GREATEST(`sucChuaToiDa`,  6000) WHERE `maViTri` = 'VT010102010020';
UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = GREATEST(`sucChuaToiDa`, 27000) WHERE `maViTri` = 'VT010201010045';
UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = GREATEST(`sucChuaToiDa`, 27000) WHERE `maViTri` = 'VT010202010046';

-- =====================================================================
-- 4. ĐỒNG BỘ TRIGGER APPEND-ONLY CHO LichSuBienDongTonKho
-- =====================================================================

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

SELECT 'Nang cap CSDL feed_wms thanh cong!' AS ket_qua;
