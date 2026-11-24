<?php
/**
 * lo-hang/index — Tra cứu thông tin lô hàng (UC05). Chi tiết lô mở thành cửa sổ ngay trong trang.
 * Biến từ LoHangController::index(). CSS: assets/css/pages/products.css
 */
if ($error) {
    render_alert('error', $error);
}
if ($invalid) {
    render_alert('warning', $invalid);
}

render_search_form([
    'q'         => ['label' => 'Mã lô hoặc tên hàng', 'type' => 'text', 'placeholder' => 'Nhập mã lô, mã hàng hoặc tên hàng...'],
    'viTri'     => ['label' => 'Vị trí lưu trữ (kho / khu / vị trí)', 'type' => 'select', 'options' => $viTriOptions],
    'trangThai' => ['label' => 'Trạng thái lô hàng', 'type' => 'select', 'options' => $trangThaiOptions],
], $filters, route('lo-hang'));

$badge = ['Còn hạn' => 'badge-ok', 'Hết hạn' => 'badge-danger', 'Đã xuất hết' => '', 'Ngừng sử dụng' => 'badge-warn'];
?>
<div class="card">
    <div class="card-head"><b>Danh sách lô hàng</b><span class="muted"><?= fmt_number($total) ?> lô hàng</span></div>
    <?php
    render_data_table([
        'maLoHang'   => 'Mã lô hàng',
        'maHangHoa'  => 'Mã hàng',
        'tenHangHoa' => 'Tên hàng',
        'ngayNhap'   => ['label' => 'Ngày nhập', 'render' => fn($r) => e(fmt_date($r['ngayNhap']))],
        'ngayHetHan' => ['label' => 'HSD', 'render' => fn($r) => e(fmt_date($r['ngayHetHan']))],
        'viTri'      => ['label' => 'Vị trí', 'render' => fn($r) => e($r['viTri'] ?: '—')],
        'soLuongTon' => ['label' => 'SL tồn (kg)', 'class' => 'num', 'render' => fn($r) => e(fmt_number($r['soLuongTon'], 2))],
        'trangThaiHienThi' => ['label' => 'Trạng thái', 'render' => fn($r) => '<span class="badge ' . e($badge[$r['trangThaiHienThi']] ?? '') . '">' . e($r['trangThaiHienThi']) . '</span>'],
    ], $rows, [
        'empty'   => $error ? 'Không thể thực hiện tra cứu' : ($invalid ? 'Vui lòng nhập lại tiêu chí tìm kiếm.' : ($hasFilter ? 'Không tìm thấy lô hàng' : 'Chưa có lô hàng nào.')),
        'actions' => fn($r) => '<a class="btn btn-sm" href="' . e(route('lo-hang', ['mode' => 'view', 'id' => $r['maLoHang']] + $listQuery)) . '">Xem chi tiết</a>',
    ]);
    render_pagination($page, $total, $perPage, route('lo-hang'));
    ?>
</div>
<?php if ($detail): ?>
<div class="modal" id="loHangModal">
    <a class="modal-backdrop" href="<?= e($closeUrl) ?>" aria-label="Đóng"></a>
    <div class="modal-dialog modal-lg" role="dialog" aria-modal="true" aria-labelledby="loTitle">
        <h3 id="loTitle">Chi tiết lô hàng <?= e($detail['maLoHang']) ?></h3>
        <dl class="detail-grid">
            <dt>Mã lô hàng</dt><dd><?= e($detail['maLoHang']) ?></dd>
            <dt>Mã hàng</dt><dd><?= e($detail['maHangHoa']) ?></dd>
            <dt>Tên hàng</dt><dd><?= e($detail['tenHangHoa']) ?></dd>
            <dt>Nguồn / Nhà cung cấp</dt>
            <dd><?= $detail['tenNCC'] ? e($detail['tenNCC']) : ($detail['tenXuong'] ? 'Xưởng sản xuất: ' . e($detail['tenXuong']) : '—') ?></dd>
            <dt>Số lô nhà cung cấp</dt><dd><?= e($detail['soLoNhaCungCap'] ?: '—') ?></dd>
            <dt>Ngày sản xuất</dt><dd><?= e(fmt_date($detail['ngaySanXuat'])) ?></dd>
            <dt>Ngày nhập</dt><dd><?= e(fmt_datetime($detail['ngayNhap'])) ?></dd>
            <dt>Hạn sử dụng</dt><dd><?= e(fmt_date($detail['ngayHetHan'])) ?></dd>
            <dt>Số lượng ban đầu</dt><dd><?= e(fmt_number($detail['soLuongBanDau'], 2)) ?> kg</dd>
            <dt>Chất lượng (QC)</dt><dd><?= e($detail['trangThaiQC']) ?></dd>
            <dt>Trạng thái lô</dt><dd><span class="badge <?= e($badge[$detail['trangThaiHienThi']] ?? '') ?>"><?= e($detail['trangThaiHienThi']) ?></span> <span class="muted">(<?= e($detail['trangThaiVongDoi']) ?>)</span></dd>
        </dl>
        <h4 class="sub-title">Số lượng tồn tại từng vị trí</h4>
        <?php
        render_data_table([
            'tenKho'    => 'Kho',
            'tenKhuVuc' => 'Khu vực',
            'tenViTri'  => 'Vị trí',
            'soLuongTon' => ['label' => 'Tồn (kg)', 'class' => 'num', 'render' => fn($r) => e(fmt_number($r['soLuongTon'], 2))],
            'soLuongDaGiu' => ['label' => 'Đã giữ (kg)', 'class' => 'num', 'render' => fn($r) => e(fmt_number($r['soLuongDaGiu'], 2))],
        ], $detail['viTriChiTiet'], ['empty' => 'Lô hàng chưa được xếp vào vị trí lưu trữ nào.']);
        ?>
        <div class="modal-actions"><a class="btn btn-primary" href="<?= e($closeUrl) ?>">Đóng</a></div>
    </div>
</div>
<?php endif; ?>
