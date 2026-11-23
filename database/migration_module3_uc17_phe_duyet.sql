-- FeedWare - MODULE 3 - UC17: bổ sung phê duyệt phiếu điều chuyển.
-- Additive: không xóa dữ liệu, giữ các trạng thái cũ để tương thích dữ liệu mẫu.
USE `feed_wms`;

ALTER TABLE `PhieuDieuChuyen`
  ADD COLUMN IF NOT EXISTS `maNhanVienDuyet` VARCHAR(8) NULL AFTER `maNhanVienThucHien`,
  ADD COLUMN IF NOT EXISTS `ngayDuyet` DATETIME NULL AFTER `ngayYeuCau`,
  ADD COLUMN IF NOT EXISTS `lyDoTuChoi` NVARCHAR(255) NULL AFTER `lyDoDieuChuyen`;

ALTER TABLE `PhieuDieuChuyen`
  DROP CONSTRAINT IF EXISTS `ck_PhieuDieuChuyen_4`;

ALTER TABLE `PhieuDieuChuyen`
  ADD CONSTRAINT `ck_PhieuDieuChuyen_4`
    CHECK (`trangThai` IN ('Chờ duyệt','Đã duyệt','Đã từ chối','Chờ điều chuyển','Đang thực hiện','Đã hoàn tất','Đã hủy')),
  ADD CONSTRAINT `ck_PhieuDieuChuyen_10`
    CHECK (`ngayDuyet` IS NULL OR `ngayDuyet` >= `ngayYeuCau`),
  ADD CONSTRAINT `ck_PhieuDieuChuyen_11`
    CHECK (`trangThai` NOT IN ('Đã duyệt','Đã từ chối') OR (`maNhanVienDuyet` IS NOT NULL AND `ngayDuyet` IS NOT NULL)),
  ADD CONSTRAINT `ck_PhieuDieuChuyen_12`
    CHECK (`trangThai` <> 'Đã từ chối' OR (`lyDoTuChoi` IS NOT NULL AND CHAR_LENGTH(TRIM(`lyDoTuChoi`)) > 0)),
  ADD CONSTRAINT `ck_PhieuDieuChuyen_13`
    CHECK (`maNhanVienDuyet` IS NULL OR `maNhanVienDuyet` <> `maNhanVienYeuCau`),
  ADD CONSTRAINT `fk_PhieuDieuChuyen_6` FOREIGN KEY (`maNhanVienDuyet`) REFERENCES `NhanVien`(`maNhanVien`) ON DELETE RESTRICT ON UPDATE RESTRICT;
