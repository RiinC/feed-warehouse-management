<?php
/** UC17 - Danh sách phiếu chờ duyệt. Biến: $rows,$total,$page,$perPage. */
?>
<div class="card">
    <h2>Phiếu chờ duyệt thuộc kho phụ trách</h2>
    <?php
    render_data_table([
        'maPhieuDieuChuyen' => ['label' => 'Mã phiếu', 'class' => 'nowrap'],
        'maLoHang' => ['label' => 'Mã lô', 'class' => 'nowrap'],
        'tenViTriNguon' => ['label' => 'Vị trí nguồn', 'class' => 'wrap'],
        'tenViTriDich' => ['label' => 'Vị trí đích', 'class' => 'wrap'],
        'soLuongDuKien' => ['label' => 'Số lượng (kg)', 'class' => 'num'],
        'nguoiLap' => ['label' => 'Người lập', 'class' => 'nowrap'],
        'ngayYeuCau' => ['label' => 'Ngày lập', 'class' => 'nowrap'],
    ], $rows, [
        'stt' => true,
        'empty' => 'Không có phiếu nào đang chờ duyệt.',
        'actions' => function ($r) {
            return '<a class="btn btn-sm btn-primary" href="' . e(route('dieu-chuyen/detail', ['id' => $r['maPhieuDieuChuyen']])) . '">Xem và xử lý</a>';
        },
    ]);
    render_pagination($page, $total, $perPage);
    ?>
</div>

