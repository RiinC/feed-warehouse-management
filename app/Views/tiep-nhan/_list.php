<?php
/** Danh sách chứng từ chờ tiếp nhận. Biến: $q, $rows, $total, $page, $perPage, $error, $invalid, $canReceive. */
if ($error) {
    render_alert('error', $error);
}
if ($invalid) {
    render_alert('warning', $invalid);
}
render_search_form([
    'q' => ['label' => 'Số chứng từ', 'type' => 'text', 'placeholder' => 'VD: PG2611010001'],
], ['q' => $q], route('tiep-nhan'));
?>
<div class="card">
    <div class="card-head"><b>Chứng từ chờ tiếp nhận</b><span class="muted"><?= fmt_number($total) ?> chứng từ</span></div>
    <?php
    render_data_table([
        'soChungTu' => 'Số chứng từ',
        'loai'      => ['label' => 'Loại chứng từ', 'render' => fn($r) => e($r['loai'] === 'ncc' ? 'Phiếu giao hàng NCC' : 'Yêu cầu nhập thành phẩm')],
        'nguonGiao' => 'Nhà cung cấp / Xưởng giao',
        'ngayGiao'  => ['label' => 'Ngày giao', 'render' => fn($r) => e(fmt_date($r['ngayGiao']))],
        'tinhTrang' => ['label' => 'Tình trạng', 'render' => fn($r) => '<span class="badge ' . ($r['tinhTrang'] === 'Chờ tiếp nhận' ? 'badge-warn' : 'badge-info') . '">' . e($r['tinhTrang']) . '</span>'],
    ], $rows, [
        'empty'   => $invalid ? 'Vui lòng nhập lại số chứng từ.' : ($q !== '' ? 'Không tìm thấy chứng từ chờ tiếp nhận phù hợp.' : 'Hiện không có chứng từ nào chờ tiếp nhận.'),
        'actions' => $canReceive
            ? fn($r) => '<a class="btn btn-sm btn-primary" href="' . e(route('tiep-nhan', ['chung_tu' => $r['soChungTu']])) . '">Tiếp nhận</a>'
            : null,
    ]);
    render_pagination($page, $total, $perPage, route('tiep-nhan'));
    ?>
</div>
