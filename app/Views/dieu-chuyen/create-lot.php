<?php
partial('components/transfer-steps', ['current' => 1]);
render_search_form(['q' => ['label' => 'Tìm lô', 'placeholder' => 'Mã lô hoặc tên hàng hóa']], $_GET);
$selected = (string)($draft['maLoHang'] ?? '') . (isset($draft['maKho']) && $draft['maKho'] !== '' ? '|' . $draft['maKho'] : '' );
?>
<form method="post" action="<?= e(route('dieu-chuyen/create/lot')) ?>" class="card" data-validate novalidate>
    <?= csrf_field() ?>
    <h2>Chọn lô hàng cần điều chuyển</h2>
    <p class="muted small">Mỗi dòng là một lô tại một kho. Nhấp vào dòng hoặc radio để chọn; nhấp vào tiêu đề cột để sắp xếp tăng/giảm.</p>
    <?php
    render_data_table([
        'chon' => ['label' => 'Chọn', 'render' => function ($r) use ($selected) {
            $val = $r['maLoHang'] . '|' . $r['maKho'];
            return '<input type="radio" name="lot" value="' . e($val) . '" required' . ($selected === $val ? ' checked' : '') . '>';
        }],
        'maLoHang' => ['label' => 'Mã lô', 'class' => 'nowrap', 'sortable' => true],
        'tenHangHoa' => ['label' => 'Hàng hóa', 'class' => 'wrap', 'sortable' => true],
        'tenKho' => ['label' => 'Kho', 'class' => 'wrap'],
        'ngayHetHan' => ['label' => 'Hạn sử dụng', 'class' => 'nowrap', 'sortable' => true],
        'khaDung' => ['label' => 'Khả dụng (kg)', 'class' => 'num nowrap', 'sortable' => true],
        'soViTri' => ['label' => 'Số vị trí', 'class' => 'num nowrap', 'sortable' => true],
    ], $lots, [
        'stt' => true,
        'stt_start' => max(0, ($page - 1) * $perPage),
        'empty' => 'Không còn lô nào có tồn khả dụng.',
        'sort_key' => $sort,
        'sort_dir' => $dir,
        'row_attrs' => fn(array $r): array => ['class' => 'selectable-row'],
    ]);
    render_pagination($page, $total, $perPage);
    ?>
    <div class="page-actions">
        <button type="submit" class="btn btn-primary">Tiếp tục</button>
        <a class="btn" href="<?= e(route('dieu-chuyen')) ?>">Hủy</a>
    </div>
</form>

