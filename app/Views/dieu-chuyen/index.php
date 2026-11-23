<?php
render_search_form([
    'q' => ['label' => 'Từ khóa', 'placeholder' => 'Mã phiếu, mã lô, người lập'],
    'trangThai' => ['label' => 'Trạng thái', 'type' => 'select', 'options' => array_combine($statuses, $statuses)],
    'maLoHang' => ['label' => 'Mã lô', 'placeholder' => 'VD: LO2611010001'],
    'maViTriNguon' => ['label' => 'Vị trí nguồn', 'placeholder' => 'Mã vị trí nguồn'],
    'maViTriDich' => ['label' => 'Vị trí đích', 'placeholder' => 'Mã vị trí đích'],
    'tuNgay' => ['label' => 'Từ ngày', 'type' => 'date'],
    'denNgay' => ['label' => 'Đến ngày', 'type' => 'date'],
    'nguoiLap' => ['label' => 'Người lập', 'placeholder' => 'Tên hoặc mã nhân viên'],
], $_GET);
?>
<div class="card">
<?php render_data_table([
    'maPhieuDieuChuyen' => ['label' => 'Mã phiếu', 'class' => 'nowrap'],
    'maLoHang' => ['label' => 'Mã lô', 'class' => 'nowrap'],
    'tenViTriNguon' => ['label' => 'Vị trí nguồn', 'class' => 'wrap'],
    'tenViTriDich' => ['label' => 'Vị trí đích', 'class' => 'wrap'],
    'soLuongDuKien' => ['label' => 'Dự kiến (kg)', 'class' => 'num nowrap'],
    'soLuongThucChuyen' => ['label' => 'Đã chuyển (kg)', 'class' => 'num nowrap', 'render' => fn($r) => e(fmt_number((float)($r['soLuongThucChuyen'] ?? 0), 2))],
    'conLai' => ['label' => 'Còn lại (kg)', 'class' => 'num nowrap', 'render' => fn($r) => e(fmt_number(max(0, (float)$r['conLai']), 2))],
    'nguoiLap' => ['label' => 'Người lập', 'class' => 'nowrap'],
    'ngayYeuCau' => ['label' => 'Ngày lập', 'class' => 'nowrap'],
    'trangThai' => ['label' => 'Trạng thái', 'class' => 'w-status nowrap', 'render' => 'dieu_chuyen_badge_row'],
], $rows, ['stt' => true, 'empty' => 'Chưa có phiếu điều chuyển phù hợp.', 'actions' => function ($r) { return '<a class="btn btn-sm" href="' . e(route('dieu-chuyen/detail', ['id' => $r['maPhieuDieuChuyen']])) . '">Chi tiết</a>'; }]); ?>
<?php render_pagination($page, $total, $perPage); ?>
</div>

