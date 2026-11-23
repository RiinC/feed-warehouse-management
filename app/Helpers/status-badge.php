<?php
/** Nhãn trạng thái dùng chung cho Module 3 (không truy vấn CSDL). */
function badge_html(string $label, string $class = ''): string
{
    return '<span class="badge ' . e($class) . '">' . e($label) . '</span>';
}

function vi_tri_badge(string $status): string
{
    $map = [
        'Đang để trống' => 'badge-ok',
        'Đang chứa hàng' => 'badge-info',
        'Đã lấp đầy' => 'badge-warn',
        'Đang bảo trì' => 'badge-warn',
        'Đã khóa' => 'badge-danger',
    ];
    return badge_html($status, $map[$status] ?? '');
}

function dieu_chuyen_badge(string $status): string
{
    $map = [
        'Chờ duyệt' => 'badge-warn',
        'Đã duyệt' => 'badge-info',
        'Đang thực hiện' => 'badge-info',
        'Chờ điều chuyển' => 'badge-warn',
        'Đã hoàn tất' => 'badge-ok',
        'Đã từ chối' => 'badge-danger',
        'Đã hủy' => '',
    ];
    return badge_html($status, $map[$status] ?? '');
}

function giao_dich_badge(string $type): string
{
    $map = [
        'Nhập kho' => 'badge-ok',
        'Điều chuyển đến' => 'badge-info',
        'Điều chuyển đi' => 'badge-warn',
        'Xuất kho' => 'badge-danger',
        'Điều chỉnh tăng' => 'badge-info',
        'Điều chỉnh giảm' => 'badge-warn',
        'Hao hụt' => 'badge-danger',
    ];
    return badge_html($type, $map[$type] ?? '');
}


function vi_tri_badge_row(array $r): string
{
    return vi_tri_badge((string)($r['trangThai'] ?? ''));
}

function dieu_chuyen_badge_row(array $r): string
{
    return dieu_chuyen_badge((string)($r['trangThai'] ?? ''));
}

