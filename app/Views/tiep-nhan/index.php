<?php
/**
 * tiep-nhan/index — Tiếp nhận hàng vào kho (UC06), 3 bước ngay trong trang chính:
 *   list     danh sách chứng từ chờ tiếp nhận
 *   form     nhập thông tin tiếp nhận của một chứng từ
 *   confirm  xem lại và xác nhận
 * Biến từ TiepNhanController. CSS: assets/css/pages/receiving.css
 */
if ($step === 'list') {
    partial('tiep-nhan/_list', get_defined_vars());
} elseif ($step === 'confirm') {
    partial('tiep-nhan/_confirm', get_defined_vars());
} else {
    partial('tiep-nhan/_form', get_defined_vars());
}
