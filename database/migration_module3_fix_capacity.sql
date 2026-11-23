-- Module 3: đồng bộ sức chứa các vị trí seed đang vượt tồn thực tế.
-- Dữ liệu seed cũ xếp nhiều lô vào cùng một vị trí nên tổng TonKho > sucChuaToiDa.
-- Các giá trị dưới đây lấy tồn thực tế hiện tại + khoảng dự phòng 1.000 kg.
-- Chỉ dùng cho dữ liệu demo; với dữ liệu thật, nên điều chuyển bớt hàng hoặc hiệu chỉnh tồn
-- thay vì chỉ nâng sức chứa.

UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = 52000 WHERE `maViTri` = 'VT010101010001';
UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = 32000 WHERE `maViTri` = 'VT010102010002';
UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = 48000 WHERE `maViTri` = 'VT010201010003';
UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = 28000 WHERE `maViTri` = 'VT010202010004';
UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = 27000 WHERE `maViTri` = 'VT010201010045';
UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = 27000 WHERE `maViTri` = 'VT010202010046';
UPDATE `ViTriLuuTru` SET `sucChuaToiDa` = 6000  WHERE `maViTri` = 'VT010102010020';

-- Đồng bộ lại trạng thái theo sức chứa mới.
UPDATE `ViTriLuuTru`
SET `trangThai` = 'Đang chứa hàng'
WHERE `maViTri` IN (
    'VT010101010001', 'VT010102010002', 'VT010201010003', 'VT010202010004',
    'VT010201010045', 'VT010202010046', 'VT010102010020'
);
