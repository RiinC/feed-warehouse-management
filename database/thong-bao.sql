-- FeedWare: bảng ThongBao (chuông thông báo) cho CSDL feed_wms.
-- Chạy SAU schema.sql và seed_data.sql:  mysql -u root < database/thong-bao.sql
USE `feed_wms`;
SET NAMES utf8mb4;

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

-- Thông báo mẫu để thử chuông (thông báo thật do code gọi ThongBao::notifyUser / notifyRole)
INSERT INTO `ThongBao` (`maNhanVien`,`tieuDe`,`noiDung`,`duongDan`,`loai`,`daDoc`,`thoiDiemTao`,`thoiDiemDoc`) VALUES
('NV000001','Chào mừng đến với FeedWare','Bạn đang dùng tài khoản quản trị viên.','dashboard','success',0,NOW() - INTERVAL 2 MINUTE,NULL),
('NV000002','Báo cáo tồn kho đã sẵn sàng','Xem báo cáo tồn kho mới nhất.','bao-cao','info',0,NOW() - INTERVAL 1 HOUR,NULL),
('NV000004','Có lô hàng chờ xác nhận kết quả kiểm tra','Lô hàng đã có kết quả kiểm tra chất lượng.','chat-luong','warning',0,NOW() - INTERVAL 5 MINUTE,NULL),
('NV000004','Yêu cầu xuất kho chờ phê duyệt','Có yêu cầu xuất kho mới cần bạn duyệt.','xuat-kho/yeu-cau','info',0,NOW() - INTERVAL 40 MINUTE,NULL),
('NV000009','Có phiếu giao hàng chờ tiếp nhận','Nhà cung cấp đã gửi phiếu giao hàng mới.','tiep-nhan','info',0,NOW() - INTERVAL 10 MINUTE,NULL),
('NV000009','Yêu cầu xuất kho đã được duyệt','Bạn có thể lập phiếu xuất kho.','xuat-kho/yeu-cau','success',1,NOW() - INTERVAL 1 DAY,NOW() - INTERVAL 20 HOUR),
('NV000018','Có lô hàng cần kiểm tra chất lượng','Vui lòng ghi nhận kết quả kiểm tra.','chat-luong','warning',0,NOW() - INTERVAL 15 MINUTE,NULL);

-- Mỗi nhân viên đang làm việc mà chưa có thông báo nào sẽ có một thông báo chào mừng (chạy lại file này không tạo trùng)
INSERT INTO `ThongBao` (`maNhanVien`,`tieuDe`,`noiDung`,`duongDan`,`loai`,`daDoc`,`thoiDiemTao`)
SELECT n.`maNhanVien`, 'Chào mừng đến với FeedWare', 'Thông báo về công việc của bạn sẽ hiện ở đây.', 'dashboard', 'success', 0, NOW()
FROM `NhanVien` n
WHERE n.`trangThai` = 'Đang làm việc'
  AND NOT EXISTS (SELECT 1 FROM `ThongBao` t WHERE t.`maNhanVien` = n.`maNhanVien`);
