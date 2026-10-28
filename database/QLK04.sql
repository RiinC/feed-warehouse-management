

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `chitietdexuatfefo` (
  `maChiTietDeXuat` bigint(20) UNSIGNED NOT NULL,
  `maDeXuatFEFO` varchar(20) NOT NULL,
  `maHangHoa` varchar(20) NOT NULL,
  `soLuongDeXuat` decimal(15,3) NOT NULL,
  `ghiChu` varchar(500) DEFAULT NULL
) ;

--
-- Đang đổ dữ liệu cho bảng `chitietdexuatfefo`
--

INSERT INTO `chitietdexuatfefo` (`maChiTietDeXuat`, `maDeXuatFEFO`, `maHangHoa`, `soLuongDeXuat`, `ghiChu`) VALUES
(1, 'FEFO04-001', 'HH04-001', 100.000, 'Bắp hạt vàng từ LO04-001'),
(2, 'FEFO04-001', 'HH04-002', 10.000, 'Cám gạo từ LO04-001'),
(3, 'FEFO04-001', 'HH04-004', 80.000, 'Ngô nghiền từ LO04-001'),
(4, 'FEFO04-002', 'HH04-001', 100.000, 'Bắp hạt vàng từ LO04-002'),
(5, 'FEFO04-002', 'HH04-002', 10.000, 'Cám gạo từ LO04-002'),
(6, 'FEFO04-003', 'HH04-003', 15.000, 'Cám đậm đặc từ LO04-003'),
(7, 'FEFO04-003', 'HH04-005', 12.000, 'Cám viên từ LO04-003'),
(8, 'FEFO04-003', 'HH04-008', 5.000, 'Premix từ LO04-003'),
(9, 'FEFO04-004', 'HH04-003', 15.000, 'Cám đậm đặc kiểm thử UC23'),
(10, 'FEFO04-004', 'HH04-008', 3.000, 'Premix khoáng vitamin cùng lô LO04-003'),
(11, 'FEFO04-005', 'HH04-006', 8.000, 'Khô dầu đậu nành từ LO04-004'),
(12, 'FEFO04-005', 'HH04-011', 10.000, 'Bột đá canxi từ LO04-004'),
(13, 'FEFO04-006', 'HH04-001', 120.000, 'Bắp hạt vàng từ LO04-007'),
(14, 'FEFO04-006', 'HH04-004', 60.000, 'Ngô nghiền từ LO04-007'),
(15, 'FEFO04-007', 'HH04-005', 12.000, 'Cám viên từ LO04-006'),
(16, 'FEFO04-007', 'HH04-008', 5.000, 'Premix từ LO04-006'),
(17, 'FEFO04-008', 'HH04-004', 60.000, 'Ngô nghiền từ LO04-005'),
(18, 'FEFO04-008', 'HH04-001', 120.000, 'Bắp hạt vàng từ LO04-005');

-- --------------------------------------------------------




CREATE TABLE `chitietlohang` (
  `maChiTietLo` bigint(20) UNSIGNED NOT NULL,
  `maLoHang` varchar(20) NOT NULL,
  `maHangHoa` varchar(20) NOT NULL,
  `ngayHetHan` datetime NOT NULL,
  `soLuongTon` decimal(15,3) NOT NULL DEFAULT 0.000,
  `soLuongDaGiu` decimal(15,3) NOT NULL DEFAULT 0.000,
  `donGiaNhap` decimal(15,2) NOT NULL DEFAULT 0.00
) ;



INSERT INTO `chitietlohang` (`maChiTietLo`, `maLoHang`, `maHangHoa`, `ngayHetHan`, `soLuongTon`, `soLuongDaGiu`, `donGiaNhap`) VALUES
(1, 'LO04-001', 'HH04-001', '2027-03-01 08:30:00', 700.000, 0.000, 11000.00),
(2, 'LO04-001', 'HH04-002', '2027-02-15 12:00:00', 110.000, 0.000, 160000.00),
(3, 'LO04-001', 'HH04-004', '2027-03-05 17:00:00', 420.000, 0.000, 12000.00),
(4, 'LO04-001', 'HH04-006', '2027-04-10 09:00:00', 45.000, 0.000, 385000.00),
(5, 'LO04-002', 'HH04-001', '2027-06-01 08:30:00', 1200.000, 0.000, 11200.00),
(6, 'LO04-002', 'HH04-002', '2027-05-20 12:00:00', 180.000, 0.000, 162000.00),
(7, 'LO04-002', 'HH04-009', '2027-06-15 16:00:00', 90.000, 0.000, 148000.00),
(8, 'LO04-003', 'HH04-003', '2027-04-15 09:00:00', 60.000, 0.000, 300000.00),
(9, 'LO04-003', 'HH04-005', '2027-05-01 10:30:00', 75.000, 0.000, 278000.00),
(10, 'LO04-003', 'HH04-007', '2027-03-20 15:00:00', 25.000, 0.000, 650000.00),
(11, 'LO04-003', 'HH04-008', '2027-07-01 08:00:00', 40.000, 0.000, 69000.00),
(12, 'LO04-004', 'HH04-003', '2027-05-15 09:00:00', 90.000, 0.000, 302000.00),
(13, 'LO04-004', 'HH04-006', '2027-06-10 10:00:00', 47.000, 0.000, 390000.00),
(14, 'LO04-004', 'HH04-011', '2027-08-01 08:00:00', 70.000, 0.000, 88000.00),
(15, 'LO04-005', 'HH04-004', '2027-02-28 18:00:00', 650.000, 0.000, 12200.00),
(16, 'LO04-005', 'HH04-001', '2027-03-10 08:30:00', 700.000, 0.000, 10900.00),
(17, 'LO04-005', 'HH04-010', '2027-04-20 12:00:00', 300.000, 0.000, 24500.00),
(18, 'LO04-006', 'HH04-005', '2027-04-25 10:00:00', 100.000, 0.000, 280000.00),
(19, 'LO04-006', 'HH04-012', '2027-05-05 09:00:00', 85.000, 0.000, 260000.00),
(20, 'LO04-006', 'HH04-008', '2027-06-30 08:00:00', 35.000, 0.000, 69500.00),
(21, 'LO04-007', 'HH04-001', '2027-02-20 08:30:00', 450.000, 0.000, 10800.00),
(22, 'LO04-007', 'HH04-004', '2027-02-25 18:00:00', 240.000, 0.000, 11900.00),
(23, 'LO04-007', 'HH04-009', '2027-04-01 16:00:00', 70.000, 0.000, 149000.00),
(24, 'LO04-008', 'HH04-002', '2027-03-15 12:00:00', 160.000, 0.000, 163000.00),
(25, 'LO04-008', 'HH04-003', '2027-04-05 09:00:00', 50.000, 0.000, 301000.00),
(26, 'LO04-008', 'HH04-010', '2027-06-01 12:00:00', 240.000, 0.000, 24800.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietphieuxuatkho`
--

CREATE TABLE `chitietphieuxuatkho` (
  `maChiTietPhieu` bigint(20) UNSIGNED NOT NULL,
  `maPhieuXuat` varchar(30) NOT NULL,
  `maHangHoa` varchar(20) NOT NULL,
  `maLoHang` varchar(20) NOT NULL,
  `soLuongXuat` decimal(15,3) NOT NULL,
  `donGia` decimal(15,2) NOT NULL DEFAULT 0.00,
  `thanhTien` decimal(18,2) NOT NULL DEFAULT 0.00,
  `ghiChu` varchar(500) DEFAULT NULL
) ;

--
-- Đang đổ dữ liệu cho bảng `chitietphieuxuatkho`
--

INSERT INTO `chitietphieuxuatkho` (`maChiTietPhieu`, `maPhieuXuat`, `maHangHoa`, `maLoHang`, `soLuongXuat`, `donGia`, `thanhTien`, `ghiChu`) VALUES
(1, 'PX202610281128526502', 'HH04-002', 'LO04-001', 10.000, 160000.00, 1600000.00, 'Theo đề xuất FEFO FEFO04-001'),
(2, 'PX202610281128526502', 'HH04-001', 'LO04-001', 100.000, 11000.00, 1100000.00, 'Theo đề xuất FEFO FEFO04-001'),
(3, 'PX202610281128526502', 'HH04-004', 'LO04-001', 80.000, 12000.00, 960000.00, 'Theo đề xuất FEFO FEFO04-001'),
(4, 'PX202610281134095667', 'HH04-006', 'LO04-004', 8.000, 390000.00, 3120000.00, 'Theo đề xuất FEFO FEFO04-005'),
(5, 'PX202610281134095667', 'HH04-011', 'LO04-004', 10.000, 88000.00, 880000.00, 'Theo đề xuất FEFO FEFO04-005'),
(6, 'PX202610281137533741', 'HH04-004', 'LO04-007', 60.000, 11900.00, 714000.00, 'Theo đề xuất FEFO FEFO04-006');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietyeucauxuatkho`
--

CREATE TABLE `chitietyeucauxuatkho` (
  `maChiTietYeuCau` bigint(20) UNSIGNED NOT NULL,
  `maYeuCau` varchar(20) NOT NULL,
  `maHangHoa` varchar(20) NOT NULL,
  `soLuongYeuCau` decimal(15,3) NOT NULL,
  `soLuongDaXuat` decimal(15,3) NOT NULL DEFAULT 0.000,
  `ghiChu` varchar(500) DEFAULT NULL
) ;

--
-- Đang đổ dữ liệu cho bảng `chitietyeucauxuatkho`
--

INSERT INTO `chitietyeucauxuatkho` (`maChiTietYeuCau`, `maYeuCau`, `maHangHoa`, `soLuongYeuCau`, `soLuongDaXuat`, `ghiChu`) VALUES
(1, 'YC04-001', 'HH04-001', 100.000, 0.000, 'Bắp hạt vàng'),
(2, 'YC04-001', 'HH04-002', 10.000, 0.000, 'Cám gạo'),
(3, 'YC04-001', 'HH04-004', 80.000, 0.000, 'Ngô nghiền'),
(4, 'YC04-002', 'HH04-003', 15.000, 0.000, 'Cám đậm đặc'),
(5, 'YC04-002', 'HH04-005', 12.000, 0.000, 'Cám viên cho gà'),
(6, 'YC04-002', 'HH04-008', 5.000, 0.000, 'Premix khoáng vitamin'),
(7, 'YC04-003', 'HH04-006', 8.000, 0.000, 'Khô dầu đậu nành'),
(8, 'YC04-003', 'HH04-007', 4.000, 0.000, 'Bột cá'),
(9, 'YC04-003', 'HH04-011', 10.000, 0.000, 'Bột đá canxi'),
(10, 'YC04-004', 'HH04-003', 15.000, 0.000, 'Cám đậm đặc kiểm thử UC23'),
(11, 'YC04-004', 'HH04-008', 3.000, 0.000, 'Premix khoáng vitamin đi cùng lô kiểm thử'),
(12, 'YC04-005', 'HH04-001', 120.000, 0.000, 'Bắp hạt vàng'),
(13, 'YC04-005', 'HH04-004', 60.000, 0.000, 'Ngô nghiền');

-- --------------------------------------------------------


--

CREATE TABLE `dexuatfefo` (
  `maDeXuatFEFO` varchar(20) NOT NULL,
  `maYeuCau` varchar(20) NOT NULL,
  `maLoHang` varchar(20) NOT NULL,
  `ngayTao` datetime NOT NULL DEFAULT current_timestamp(),
  `maNguoiDuyet` varchar(15) DEFAULT NULL,
  `trangThai` varchar(30) NOT NULL DEFAULT 'Cho lap phieu',
  `ghiChu` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `dexuatfefo`
--

INSERT INTO `dexuatfefo` (`maDeXuatFEFO`, `maYeuCau`, `maLoHang`, `ngayTao`, `maNguoiDuyet`, `trangThai`, `ghiChu`) VALUES
('FEFO04-001', 'YC04-001', 'LO04-001', '2026-10-26 09:10:00', 'NVK04-004', 'Cho lap phieu', 'Lô có hạn dùng sớm cho bắp, cám gạo, ngô nghiền'),
('FEFO04-002', 'YC04-001', 'LO04-002', '2026-10-26 09:12:00', 'NVK04-004', 'Cho lap phieu', 'Phương án thay thế cho yêu cầu 001'),
('FEFO04-003', 'YC04-002', 'LO04-003', '2026-10-26 10:10:00', 'NVK04-004', 'Cho lap phieu', 'Cám đậm đặc và cám viên theo FEFO'),
('FEFO04-004', 'YC04-004', 'LO04-003', '2026-10-27 14:35:00', 'NVK04-001', 'Cho lap phieu', 'Đề xuất FEFO cho cám đậm đặc kiểm thử UC23'),
('FEFO04-005', 'YC04-003', 'LO04-004', '2026-10-27 09:10:00', 'NVK04-001', 'Cho lap phieu', 'Khô dầu đậu nành và bột đá canxi'),
('FEFO04-006', 'YC04-005', 'LO04-007', '2026-10-28 08:35:00', 'NVK04-004', 'Cho lap phieu', 'Ưu tiên hạn dùng sớm cho bắp và ngô nghiền'),
('FEFO04-007', 'YC04-002', 'LO04-006', '2026-10-26 10:15:00', 'NVK04-004', 'Cho lap phieu', 'Phương án khác cho cám viên và premix'),
('FEFO04-008', 'YC04-005', 'LO04-005', '2026-10-28 08:40:00', 'NVK04-004', 'Cho lap phieu', 'Phương án khác cho yêu cầu 005');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hanghoa`
--

CREATE TABLE `hanghoa` (
  `maHangHoa` varchar(20) NOT NULL,
  `tenHangHoa` varchar(150) NOT NULL,
  `donViTinh` varchar(30) NOT NULL,
  `donGia` decimal(15,2) NOT NULL DEFAULT 0.00,
  `trangThai` varchar(30) NOT NULL DEFAULT 'Dang kinh doanh',
  `moTa` varchar(500) DEFAULT NULL
) ;

--
-- Đang đổ dữ liệu cho bảng `hanghoa`
--

INSERT INTO `hanghoa` (`maHangHoa`, `tenHangHoa`, `donViTinh`, `donGia`, `trangThai`, `moTa`) VALUES
('HH04-001', 'Bắp hạt vàng', 'Kg', 12500.00, 'Đang kinh doanh', 'Bắp hạt dùng phối trộn thức ăn chăn nuôi'),
('HH04-002', 'Cám gạo nguyên chất', 'Bao', 185000.00, 'Đang kinh doanh', 'Cám gạo đóng bao 25 kg'),
('HH04-003', 'Cám đậm đặc cho heo', 'Bao', 320000.00, 'Đang kinh doanh', 'Cám đậm đặc dùng phối trộn'),
('HH04-004', 'Ngô nghiền', 'Kg', 13800.00, 'Đang kinh doanh', 'Ngô nghiền dùng phối trộn thức ăn'),
('HH04-005', 'Cám viên cho gà', 'Bao', 295000.00, 'Đang kinh doanh', 'Thức ăn viên cho gà'),
('HH04-006', 'Khô dầu đậu nành', 'Bao', 410000.00, 'Đang kinh doanh', 'Nguyên liệu giàu đạm'),
('HH04-007', 'Bột cá 60% đạm', 'Bao', 680000.00, 'Đang kinh doanh', 'Nguyên liệu bổ sung đạm'),
('HH04-008', 'Premix khoáng vitamin', 'Kg', 72000.00, 'Đang kinh doanh', 'Hỗn hợp khoáng và vitamin'),
('HH04-009', 'Cám mì', 'Bao', 165000.00, 'Đang kinh doanh', 'Sản phẩm phụ từ chế biến lúa mì'),
('HH04-010', 'Đậu tương ép mảnh', 'Kg', 26500.00, 'Đang kinh doanh', 'Nguyên liệu đậu tương'),
('HH04-011', 'Bột đá canxi', 'Bao', 95000.00, 'Đang kinh doanh', 'Bổ sung canxi trong thức ăn'),
('HH04-012', 'Thức ăn hỗn hợp cho vịt', 'Bao', 275000.00, 'Đang kinh doanh', 'Thức ăn hỗn hợp hoàn chỉnh');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lohang`
--

CREATE TABLE `lohang` (
  `maLoHang` varchar(20) NOT NULL,
  `kho` varchar(100) NOT NULL,
  `diaDiem` varchar(200) NOT NULL,
  `ngayNhap` datetime NOT NULL,
  `trangThai` varchar(30) NOT NULL DEFAULT 'Dang luu kho',
  `ghiChu` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `lohang`
--

INSERT INTO `lohang` (`maLoHang`, `kho`, `diaDiem`, `ngayNhap`, `trangThai`, `ghiChu`) VALUES
('LO04-001', 'Kho QLK04', 'Kệ A1', '2026-09-01 08:15:00', 'Đang lưu kho', 'Lô nhập đầu tháng 9'),
('LO04-002', 'Kho QLK04', 'Kệ A2', '2026-10-01 09:30:00', 'Đang lưu kho', 'Lô nhập tháng 10'),
('LO04-003', 'Kho QLK04', 'Kệ B1', '2026-09-15 10:00:00', 'Đang lưu kho', 'Lô cám và nguyên liệu đạm'),
('LO04-004', 'Kho QLK04', 'Kệ B2', '2026-10-05 13:45:00', 'Đang lưu kho', 'Lô hàng hỗn hợp'),
('LO04-005', 'Kho QLK04', 'Kệ C1', '2026-10-10 08:00:00', 'Đang lưu kho', 'Lô nguyên liệu mới'),
('LO04-006', 'Kho QLK04', 'Kệ C2', '2026-10-15 14:20:00', 'Đang lưu kho', 'Lô cám viên và thức ăn vịt'),
('LO04-007', 'Kho QLK04', 'Kệ D1', '2026-10-20 07:50:00', 'Đang lưu kho', 'Lô bắp và ngô nghiền'),
('LO04-008', 'Kho QLK04', 'Kệ D2', '2026-10-25 11:10:00', 'Đang lưu kho', 'Lô nhập bổ sung');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhanvienkho`
--

CREATE TABLE `nhanvienkho` (
  `maNhanVien` varchar(15) NOT NULL,
  `hoTen` varchar(120) NOT NULL,
  `tenDangNhap` varchar(60) DEFAULT NULL,
  `vaiTro` varchar(50) NOT NULL DEFAULT 'Nhan vien kho',
  `trangThai` varchar(30) NOT NULL DEFAULT 'Hoat dong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nhanvienkho`
--

INSERT INTO `nhanvienkho` (`maNhanVien`, `hoTen`, `tenDangNhap`, `vaiTro`, `trangThai`) VALUES
('NVK04-001', 'Nguyễn Văn An', 'nguyenvanan', 'Quản lý kho', 'Hoạt động'),
('NVK04-002', 'Lê Văn Cường', 'levancuong', 'Nhân viên kho', 'Hoạt động'),
('NVK04-003', 'Trần Thị Kho', 'tranthikho', 'Nhân viên kho', 'Hoạt động'),
('NVK04-004', 'Phạm Minh Tâm', 'phamminhtam', 'Người phê duyệt', 'Hoạt động');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieuxuatkho`
--

CREATE TABLE `phieuxuatkho` (
  `maPhieuXuat` varchar(30) NOT NULL,
  `maYeuCau` varchar(20) NOT NULL,
  `maDeXuatFEFO` varchar(20) NOT NULL,
  `ngayLap` datetime NOT NULL DEFAULT current_timestamp(),
  `maNhanVien` varchar(15) DEFAULT NULL,
  `khoXuat` varchar(100) DEFAULT NULL,
  `diaDiemXuat` varchar(200) DEFAULT NULL,
  `maNguoiPheDuyet` varchar(15) DEFAULT NULL,
  `thoiGianPheDuyet` datetime DEFAULT NULL,
  `yKienPheDuyet` varchar(500) DEFAULT NULL,
  `tongSoLuong` decimal(15,3) NOT NULL DEFAULT 0.000,
  `tongThanhTien` decimal(18,2) NOT NULL DEFAULT 0.00,
  `trangThai` varchar(30) NOT NULL DEFAULT 'Da xuat',
  `ghiChu` varchar(500) DEFAULT NULL
) ;

--
-- Đang đổ dữ liệu cho bảng `phieuxuatkho`
--

INSERT INTO `phieuxuatkho` (`maPhieuXuat`, `maYeuCau`, `maDeXuatFEFO`, `ngayLap`, `maNhanVien`, `khoXuat`, `diaDiemXuat`, `maNguoiPheDuyet`, `thoiGianPheDuyet`, `yKienPheDuyet`, `tongSoLuong`, `tongThanhTien`, `trangThai`, `ghiChu`) VALUES
('PX202610281128526502', 'YC04-001', 'FEFO04-001', '2026-10-28 11:28:00', 'NVK04-002', 'Kho QLK04', 'Khu xuất A', NULL, NULL, NULL, 0.000, 0.00, 'Da xuat', ''),
('PX202610281134095667', 'YC04-003', 'FEFO04-005', '2026-10-28 11:33:00', 'NVK04-002', 'Kho QLK04', 'Khu xuất B', NULL, NULL, NULL, 0.000, 0.00, 'Da xuat', ''),
('PX202610281137533741', 'YC04-005', 'FEFO04-006', '2026-10-28 11:37:00', 'NVK04-001', 'Kho QLK04', 'Khu xuất D', NULL, NULL, NULL, 0.000, 0.00, 'Da xuat', '');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `vw_tonkhofefo`
-- (See below for the actual view)
--
CREATE TABLE `vw_tonkhofefo` (
`maLoHang` varchar(20)
,`kho` varchar(100)
,`diaDiem` varchar(200)
,`maHangHoa` varchar(20)
,`tenHangHoa` varchar(150)
,`donViTinh` varchar(30)
,`ngayHetHan` datetime
,`soLuongTon` decimal(15,3)
,`soLuongDaGiu` decimal(15,3)
,`tonKhaDung` decimal(16,3)
,`donGiaNhap` decimal(15,2)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `yeucauxuatkho`
--

CREATE TABLE `yeucauxuatkho` (
  `maYeuCau` varchar(20) NOT NULL,
  `ngayYeuCau` datetime NOT NULL DEFAULT current_timestamp(),
  `maNguoiYeuCau` varchar(15) DEFAULT NULL,
  `mucDichXuat` varchar(300) NOT NULL,
  `khoXuat` varchar(100) NOT NULL,
  `diaDiemXuat` varchar(200) DEFAULT NULL,
  `trangThai` varchar(30) NOT NULL DEFAULT 'Da duyet',
  `maNguoiDuyet` varchar(15) DEFAULT NULL,
  `thoiGianDuyet` datetime DEFAULT NULL,
  `ghiChu` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `yeucauxuatkho`
--

INSERT INTO `yeucauxuatkho` (`maYeuCau`, `ngayYeuCau`, `maNguoiYeuCau`, `mucDichXuat`, `khoXuat`, `diaDiemXuat`, `trangThai`, `maNguoiDuyet`, `thoiGianDuyet`, `ghiChu`) VALUES
('YC04-001', '2026-10-26 08:00:00', 'NVK04-002', 'Xuất nguyên liệu phối trộn thức ăn', 'Kho QLK04', 'Khu xuất A', 'Da xuat', 'NVK04-004', '2026-10-26 09:00:00', 'Yêu cầu nhiều mặt hàng'),
('YC04-002', '2026-10-26 09:15:00', 'NVK04-003', 'Cấp cám cho khu chăn nuôi', 'Kho QLK04', 'Khu xuất B', 'Đã duyệt', 'NVK04-004', '2026-10-26 10:00:00', 'Ưu tiên FEFO'),
('YC04-003', '2026-10-27 08:30:00', 'NVK04-002', 'Xuất nguyên liệu cho dây chuyền sản xuất', 'Kho QLK04', 'Khu xuất A', 'Da xuat mot phan', 'NVK04-001', '2026-10-27 09:00:00', 'Có nhiều dòng hàng'),
('YC04-004', '2026-10-27 14:00:00', 'NVK04-003', 'Xuất cám đậm đặc kiểm thử UC23', 'Kho QLK04', 'Kệ B1', 'Đã duyệt', 'NVK04-001', '2026-10-27 14:30:00', 'Yêu cầu dùng cho kiểm thử UC23'),
('YC04-005', '2026-10-28 08:00:00', 'NVK04-002', 'Xuất bắp và ngô nghiền', 'Kho QLK04', 'Khu xuất D', 'Da xuat mot phan', 'NVK04-004', '2026-10-28 08:30:00', 'Yêu cầu nhiều sản phẩm');

-- --------------------------------------------------------

--
-- Cấu trúc cho view `vw_tonkhofefo`
--
DROP TABLE IF EXISTS `vw_tonkhofefo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_tonkhofefo`  AS SELECT `lh`.`maLoHang` AS `maLoHang`, `lh`.`kho` AS `kho`, `lh`.`diaDiem` AS `diaDiem`, `hh`.`maHangHoa` AS `maHangHoa`, `hh`.`tenHangHoa` AS `tenHangHoa`, `hh`.`donViTinh` AS `donViTinh`, `ctl`.`ngayHetHan` AS `ngayHetHan`, `ctl`.`soLuongTon` AS `soLuongTon`, `ctl`.`soLuongDaGiu` AS `soLuongDaGiu`, `ctl`.`soLuongTon`- `ctl`.`soLuongDaGiu` AS `tonKhaDung`, `ctl`.`donGiaNhap` AS `donGiaNhap` FROM ((`chitietlohang` `ctl` join `lohang` `lh` on(`lh`.`maLoHang` = `ctl`.`maLoHang`)) join `hanghoa` `hh` on(`hh`.`maHangHoa` = `ctl`.`maHangHoa`)) WHERE `ctl`.`soLuongTon` > `ctl`.`soLuongDaGiu` AND `ctl`.`ngayHetHan` > current_timestamp() ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chitietdexuatfefo`
--
ALTER TABLE `chitietdexuatfefo`
  ADD PRIMARY KEY (`maChiTietDeXuat`),
  ADD UNIQUE KEY `uq_ctfefo_dexuat_hh` (`maDeXuatFEFO`,`maHangHoa`),
  ADD KEY `fk_ctfefo_hh` (`maHangHoa`);

--
-- Chỉ mục cho bảng `chitietlohang`
--
ALTER TABLE `chitietlohang`
  ADD PRIMARY KEY (`maChiTietLo`),
  ADD UNIQUE KEY `uq_chitietlo_lo_hang` (`maLoHang`,`maHangHoa`),
  ADD KEY `idx_ctlo_hsd` (`ngayHetHan`),
  ADD KEY `idx_ctlo_hanghoa` (`maHangHoa`);

--
-- Chỉ mục cho bảng `chitietphieuxuatkho`
--
ALTER TABLE `chitietphieuxuatkho`
  ADD PRIMARY KEY (`maChiTietPhieu`),
  ADD KEY `idx_ctpx_phieu` (`maPhieuXuat`),
  ADD KEY `idx_ctpx_lo_hh` (`maLoHang`,`maHangHoa`),
  ADD KEY `fk_ctpx_hh` (`maHangHoa`);

--
-- Chỉ mục cho bảng `chitietyeucauxuatkho`
--
ALTER TABLE `chitietyeucauxuatkho`
  ADD PRIMARY KEY (`maChiTietYeuCau`),
  ADD UNIQUE KEY `uq_ctyc_yc_hh` (`maYeuCau`,`maHangHoa`),
  ADD KEY `fk_ctyc_hh` (`maHangHoa`);

--
-- Chỉ mục cho bảng `dexuatfefo`
--
ALTER TABLE `dexuatfefo`
  ADD PRIMARY KEY (`maDeXuatFEFO`),
  ADD KEY `idx_fefo_yc` (`maYeuCau`),
  ADD KEY `idx_fefo_lo` (`maLoHang`),
  ADD KEY `fk_fefo_nv` (`maNguoiDuyet`);

--
-- Chỉ mục cho bảng `hanghoa`
--
ALTER TABLE `hanghoa`
  ADD PRIMARY KEY (`maHangHoa`);

--
-- Chỉ mục cho bảng `lohang`
--
ALTER TABLE `lohang`
  ADD PRIMARY KEY (`maLoHang`),
  ADD KEY `idx_lohang_ngaynhap` (`ngayNhap`),
  ADD KEY `idx_lohang_kho` (`kho`,`diaDiem`);

--
-- Chỉ mục cho bảng `nhanvienkho`
--
ALTER TABLE `nhanvienkho`
  ADD PRIMARY KEY (`maNhanVien`),
  ADD UNIQUE KEY `uq_nhanvien_tendangnhap` (`tenDangNhap`);

--
-- Chỉ mục cho bảng `phieuxuatkho`
--
ALTER TABLE `phieuxuatkho`
  ADD PRIMARY KEY (`maPhieuXuat`),
  ADD KEY `idx_pxk_ngaylap` (`ngayLap`),
  ADD KEY `idx_pxk_trangthai` (`trangThai`),
  ADD KEY `fk_pxk_yc` (`maYeuCau`),
  ADD KEY `fk_pxk_fefo` (`maDeXuatFEFO`),
  ADD KEY `fk_pxk_nv` (`maNhanVien`),
  ADD KEY `fk_pxk_nvduyet` (`maNguoiPheDuyet`);

--
-- Chỉ mục cho bảng `yeucauxuatkho`
--
ALTER TABLE `yeucauxuatkho`
  ADD PRIMARY KEY (`maYeuCau`),
  ADD KEY `idx_ycxk_trangthai` (`trangThai`),
  ADD KEY `fk_ycxk_nv` (`maNguoiYeuCau`),
  ADD KEY `fk_ycxk_nvduyet` (`maNguoiDuyet`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `chitietdexuatfefo`
--
ALTER TABLE `chitietdexuatfefo`
  MODIFY `maChiTietDeXuat` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitietlohang`
--
ALTER TABLE `chitietlohang`
  MODIFY `maChiTietLo` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitietphieuxuatkho`
--
ALTER TABLE `chitietphieuxuatkho`
  MODIFY `maChiTietPhieu` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitietyeucauxuatkho`
--
ALTER TABLE `chitietyeucauxuatkho`
  MODIFY `maChiTietYeuCau` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chitietdexuatfefo`
--
ALTER TABLE `chitietdexuatfefo`
  ADD CONSTRAINT `fk_ctfefo_dexuat` FOREIGN KEY (`maDeXuatFEFO`) REFERENCES `dexuatfefo` (`maDeXuatFEFO`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctfefo_hh` FOREIGN KEY (`maHangHoa`) REFERENCES `hanghoa` (`maHangHoa`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chitietlohang`
--
ALTER TABLE `chitietlohang`
  ADD CONSTRAINT `fk_ctlo_hh` FOREIGN KEY (`maHangHoa`) REFERENCES `hanghoa` (`maHangHoa`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctlo_lo` FOREIGN KEY (`maLoHang`) REFERENCES `lohang` (`maLoHang`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chitietphieuxuatkho`
--
ALTER TABLE `chitietphieuxuatkho`
  ADD CONSTRAINT `fk_ctpx_hh` FOREIGN KEY (`maHangHoa`) REFERENCES `hanghoa` (`maHangHoa`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctpx_lo` FOREIGN KEY (`maLoHang`) REFERENCES `lohang` (`maLoHang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctpx_phieu` FOREIGN KEY (`maPhieuXuat`) REFERENCES `phieuxuatkho` (`maPhieuXuat`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chitietyeucauxuatkho`
--
ALTER TABLE `chitietyeucauxuatkho`
  ADD CONSTRAINT `fk_ctyc_hh` FOREIGN KEY (`maHangHoa`) REFERENCES `hanghoa` (`maHangHoa`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctyc_yc` FOREIGN KEY (`maYeuCau`) REFERENCES `yeucauxuatkho` (`maYeuCau`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `dexuatfefo`
--
ALTER TABLE `dexuatfefo`
  ADD CONSTRAINT `fk_fefo_lo` FOREIGN KEY (`maLoHang`) REFERENCES `lohang` (`maLoHang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fefo_nv` FOREIGN KEY (`maNguoiDuyet`) REFERENCES `nhanvienkho` (`maNhanVien`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fefo_yc` FOREIGN KEY (`maYeuCau`) REFERENCES `yeucauxuatkho` (`maYeuCau`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `phieuxuatkho`
--
ALTER TABLE `phieuxuatkho`
  ADD CONSTRAINT `fk_pxk_fefo` FOREIGN KEY (`maDeXuatFEFO`) REFERENCES `dexuatfefo` (`maDeXuatFEFO`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pxk_nv` FOREIGN KEY (`maNhanVien`) REFERENCES `nhanvienkho` (`maNhanVien`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pxk_nvduyet` FOREIGN KEY (`maNguoiPheDuyet`) REFERENCES `nhanvienkho` (`maNhanVien`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pxk_yc` FOREIGN KEY (`maYeuCau`) REFERENCES `yeucauxuatkho` (`maYeuCau`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `yeucauxuatkho`
--
ALTER TABLE `yeucauxuatkho`
  ADD CONSTRAINT `fk_ycxk_nv` FOREIGN KEY (`maNguoiYeuCau`) REFERENCES `nhanvienkho` (`maNhanVien`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ycxk_nvduyet` FOREIGN KEY (`maNguoiDuyet`) REFERENCES `nhanvienkho` (`maNhanVien`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;


