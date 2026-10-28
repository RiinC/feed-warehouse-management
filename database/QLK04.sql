
INSERT INTO ChiTietYeuCauXuatKho (maYeuCau, maHangHoa, soLuongYeuCau, soLuongDaXuat, ghiChu) VALUES
('YC04-001', 'HH04-001', 100, 0, 'Bắp hạt vàng'),
('YC04-001', 'HH04-002', 10, 0, 'Cám gạo'),
('YC04-001', 'HH04-004', 80, 0, 'Ngô nghiền'),
('YC04-002', 'HH04-003', 15, 0, 'Cám đậm đặc'),
('YC04-002', 'HH04-005', 12, 0, 'Cám viên cho gà'),
('YC04-002', 'HH04-008', 5, 0, 'Premix khoáng vitamin'),
('YC04-003', 'HH04-006', 8, 0, 'Khô dầu đậu nành'),
('YC04-003', 'HH04-007', 4, 0, 'Bột cá'),
('YC04-003', 'HH04-011', 10, 0, 'Bột đá canxi'),
('YC04-004', 'HH04-003', 15, 0, 'Cám đậm đặc kiểm thử UC23'),
('YC04-004', 'HH04-008', 3, 0, 'Premix khoáng vitamin đi cùng lô kiểm thử'),
('YC04-005', 'HH04-001', 120, 0, 'Bắp hạt vàng'),
('YC04-005', 'HH04-004', 60, 0, 'Ngô nghiền');

-- Moi de xuat FEFO gan mot lo; moi de xuat co nhieu dong hang hoa.
-- Cac dong chi tiet deu la hang hoa co trong lo duoc chon va trong yeu cau.
INSERT INTO DeXuatFEFO
(maDeXuatFEFO, maYeuCau, maLoHang, ngayTao, maNguoiDuyet, trangThai, ghiChu) VALUES
('FEFO04-001', 'YC04-001', 'LO04-001', '2026-10-26 09:10:00', 'NVK04-004', 'Cho lap phieu', 'Lô có hạn dùng sớm cho bắp, cám gạo, ngô nghiền'),
('FEFO04-002', 'YC04-001', 'LO04-002', '2026-10-26 09:12:00', 'NVK04-004', 'Cho lap phieu', 'Phương án thay thế cho yêu cầu 001'),
('FEFO04-003', 'YC04-002', 'LO04-003', '2026-10-26 10:10:00', 'NVK04-004', 'Cho lap phieu', 'Cám đậm đặc và cám viên theo FEFO'),
('FEFO04-004', 'YC04-004', 'LO04-003', '2026-10-27 14:35:00', 'NVK04-001', 'Cho lap phieu', 'Đề xuất FEFO cho cám đậm đặc kiểm thử UC23'),
('FEFO04-005', 'YC04-003', 'LO04-004', '2026-10-27 09:10:00', 'NVK04-001', 'Cho lap phieu', 'Khô dầu đậu nành và bột đá canxi'),
('FEFO04-006', 'YC04-005', 'LO04-007', '2026-10-28 08:35:00', 'NVK04-004', 'Cho lap phieu', 'Ưu tiên hạn dùng sớm cho bắp và ngô nghiền'),
('FEFO04-007', 'YC04-002', 'LO04-006', '2026-10-26 10:15:00', 'NVK04-004', 'Cho lap phieu', 'Phương án khác cho cám viên và premix'),
('FEFO04-008', 'YC04-005', 'LO04-005', '2026-10-28 08:40:00', 'NVK04-004', 'Cho lap phieu', 'Phương án khác cho yêu cầu 005');

INSERT INTO ChiTietDeXuatFEFO (maDeXuatFEFO, maHangHoa, soLuongDeXuat, ghiChu) VALUES
('FEFO04-001', 'HH04-001', 100, 'Bắp hạt vàng từ LO04-001'),
('FEFO04-001', 'HH04-002', 10, 'Cám gạo từ LO04-001'),
('FEFO04-001', 'HH04-004', 80, 'Ngô nghiền từ LO04-001'),

('FEFO04-002', 'HH04-001', 100, 'Bắp hạt vàng từ LO04-002'),
('FEFO04-002', 'HH04-002', 10, 'Cám gạo từ LO04-002'),
('FEFO04-003', 'HH04-003', 15, 'Cám đậm đặc từ LO04-003'),
('FEFO04-003', 'HH04-005', 12, 'Cám viên từ LO04-003'),
('FEFO04-003', 'HH04-008', 5, 'Premix từ LO04-003'),

('FEFO04-004', 'HH04-003', 15, 'Cám đậm đặc kiểm thử UC23'),
('FEFO04-004', 'HH04-008', 3, 'Premix khoáng vitamin cùng lô LO04-003'),

('FEFO04-005', 'HH04-006', 8, 'Khô dầu đậu nành từ LO04-004'),
('FEFO04-005', 'HH04-011', 10, 'Bột đá canxi từ LO04-004'),

('FEFO04-006', 'HH04-001', 120, 'Bắp hạt vàng từ LO04-007'),
('FEFO04-006', 'HH04-004', 60, 'Ngô nghiền từ LO04-007'),

('FEFO04-007', 'HH04-005', 12, 'Cám viên từ LO04-006'),
('FEFO04-007', 'HH04-008', 5, 'Premix từ LO04-006'),

('FEFO04-008', 'HH04-004', 60, 'Ngô nghiền từ LO04-005'),
('FEFO04-008', 'HH04-001', 120, 'Bắp hạt vàng từ LO04-005');

-- ------------------------------------------------------------
-- 9. VIEW TIEN ICH: TON KHA DUNG VA THONG TIN FEFO
-- Ton kha dung = ton - da giu. HSD sap xep tang dan (FEFO).
-- ------------------------------------------------------------
CREATE VIEW vw_TonKhoFEFO AS
SELECT
    lh.maLoHang,
    lh.kho,
    lh.diaDiem,
    hh.maHangHoa,
    hh.tenHangHoa,
    hh.donViTinh,
    ctl.ngayHetHan,
    ctl.soLuongTon,
    ctl.soLuongDaGiu,
    (ctl.soLuongTon - ctl.soLuongDaGiu) AS tonKhaDung,
    ctl.donGiaNhap
FROM ChiTietLoHang ctl
JOIN LoHang lh ON lh.maLoHang = ctl.maLoHang
JOIN HangHoa hh ON hh.maHangHoa = ctl.maHangHoa
WHERE ctl.soLuongTon > ctl.soLuongDaGiu
  AND ctl.ngayHetHan > NOW();

