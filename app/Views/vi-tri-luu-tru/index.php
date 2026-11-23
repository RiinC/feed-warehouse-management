<?php
$fields = [
    'q' => ['label' => 'Từ khóa', 'placeholder' => 'Mã hoặc tên vị trí'],
    'maKho' => ['label' => 'Kho', 'type' => 'select', 'options' => $khoOptions, 'attrs' => ['data-dependent-target' => 'f_maKhuVuc']],
    'maKhuVuc' => ['label' => 'Khu vực', 'type' => 'select', 'options' => $khuOptions],
    'trangThai' => ['label' => 'Trạng thái', 'type' => 'select', 'options' => array_combine($statuses, $statuses)],
    'tinhTrang' => ['label' => 'Tình trạng', 'type' => 'select', 'options' => ['dang-su-dung' => 'Đang sử dụng', 'ngung-su-dung' => 'Ngừng sử dụng']],
];
if ($scope !== null) { unset($fields['maKho']); }
render_search_form($fields, $_GET);
?>
<div class="card">
<?php
render_data_table([
    'maViTri' => ['label' => 'Mã vị trí', 'class' => 'w-code nowrap', 'render' => fn($r) => '<b>' . e($r['maViTri']) . '</b>'],
    'tenViTri' => ['label' => 'Tên vị trí', 'class' => 'wrap w-name'],
    'tenKho' => ['label' => 'Kho', 'class' => 'wrap'],
    'tenKhuVuc' => ['label' => 'Khu vực', 'class' => 'wrap'],
    'sucChuaToiDa' => ['label' => 'Sức chứa (kg)', 'class' => 'num nowrap'],
    'daDung' => ['label' => 'Đang lưu trữ (kg)', 'class' => 'num nowrap'],
    'conLai' => ['label' => 'Còn lại (kg)', 'class' => 'num nowrap'],
    'trangThai' => ['label' => 'Trạng thái', 'class' => 'w-status nowrap', 'render' => 'vi_tri_badge_row'],
], $rows, [
    'stt' => true,
    'empty' => 'Chưa có vị trí lưu trữ phù hợp.',
    'actions' => function ($r) {
        $html = '<a class="btn btn-sm" href="' . e(route('vi-tri-luu-tru/detail', ['id' => $r['maViTri']])) . '">Xem</a> ';
        if (can('vi-tri-luu-tru', 'edit')) {
            $html .= '<a class="btn btn-sm" href="' . e(route('vi-tri-luu-tru/edit', ['id' => $r['maViTri']])) . '">Sửa</a> ';
        }
        if (can('vi-tri-luu-tru', 'stop') && $r['trangThai'] !== 'Đã khóa') {
            $html .= '<a class="btn btn-sm btn-danger" href="' . e(route('vi-tri-luu-tru/stop-using', ['id' => $r['maViTri']])) . '">Ngừng sử dụng</a>';
        }
        return $html;
    },
]);
render_pagination($page, $total, $perPage);
?>
</div>

