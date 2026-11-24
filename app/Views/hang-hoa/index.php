<?php
/**
 * hang-hoa/index — Quản lý hàng hóa: tra cứu (UC04) + thao tác thêm/sửa/ngừng sử dụng (UC01-03) ngay trong trang.
 * Biến từ HangHoaController::renderIndex(): $filters, $rows, $total, $page, $perPage, $error, $hasFilter, $modal,
 * $closeUrl, $listQuery, $loaiOptions, $donViOptions, $statusOptions. CSS: assets/css/pages/products.css
 */
use App\Models\HangHoa;

if ($error) {
    render_alert('error', $error);
}

render_search_form([
    'q'         => ['label' => 'Mã hàng hoặc tên hàng', 'type' => 'text', 'placeholder' => 'Nhập mã hàng hoặc tên hàng...'],
    'loai'      => ['label' => 'Nhóm hàng', 'type' => 'select', 'options' => $loaiOptions],
    'trangThai' => ['label' => 'Trạng thái', 'type' => 'select', 'options' => $statusOptions],
], $filters, route('hang-hoa'));

$canEdit = can('hang-hoa', 'edit');
$canStop = can('hang-hoa', 'stop');
?>
<div class="card">
    <div class="card-head"><b>Danh sách hàng hóa</b><span class="muted"><?= fmt_number($total) ?> hàng hóa</span></div>
    <?php
    render_data_table([
        'maHangHoa'   => 'Mã hàng',
        'tenHangHoa'  => 'Tên hàng',
        'loaiHangHoa' => 'Nhóm hàng',
        'donViTinhChuan' => 'Đơn vị tính',
        'soLuongTon'  => ['label' => 'Số lượng tồn (kg)', 'class' => 'num', 'render' => fn($r) => e(fmt_number($r['soLuongTon'], 2))],
        'trangThai'   => ['label' => 'Trạng thái', 'render' => fn($r) => '<span class="badge ' . ($r['trangThai'] === HangHoa::TT_NGUNG ? 'badge-danger' : 'badge-ok') . '">' . e(HangHoa::statusLabel($r['trangThai'])) . '</span>'],
    ], $rows, [
        'empty'   => $error ? 'Không thể thực hiện tra cứu' : ($hasFilter ? 'Không tìm thấy hàng hóa phù hợp' : 'Chưa có hàng hóa nào.'),
        'actions' => function ($r) use ($canEdit, $canStop, $listQuery) {
            $id = ['id' => $r['maHangHoa']] + $listQuery;
            $h = '<a class="btn btn-sm" href="' . e(route('hang-hoa', ['mode' => 'view'] + $id)) . '">Xem</a> ';
            if ($canEdit) {
                $h .= '<a class="btn btn-sm" href="' . e(route('hang-hoa', ['mode' => 'edit'] + $id)) . '">Sửa</a> ';
            }
            if ($canStop && $r['trangThai'] === HangHoa::TT_DANG_DUNG) {
                $h .= '<a class="btn btn-sm btn-danger" href="' . e(route('hang-hoa', ['mode' => 'stop'] + $id)) . '">Ngừng sử dụng</a>';
            }
            return trim($h);
        },
    ]);
    render_pagination($page, $total, $perPage, route('hang-hoa'));
    ?>
</div>
<?php
if ($modal) {
    $m = ['modal' => $modal, 'closeUrl' => $closeUrl, 'loaiOptions' => $loaiOptions, 'donViOptions' => $donViOptions, 'statusOptions' => $statusOptions, 'listQuery' => $listQuery];
    if ($modal['mode'] === 'create' || $modal['mode'] === 'edit') {
        partial('hang-hoa/_form', $m);
    } elseif ($modal['mode'] === 'stop') {
        partial('hang-hoa/_stop', $m);
    } else {
        partial('hang-hoa/_detail', $m);
    }
}
