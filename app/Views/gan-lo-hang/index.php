<?php
/** UC16 - Gán lô. Biến: $rows,$total,$page,$perPage,$history,$positions,$areas,$scope. */
$canAssign = can('gan-lo-hang', 'assign');
render_search_form(['q' => ['label' => 'Từ khóa', 'placeholder' => 'Mã lô hoặc tên hàng hóa']], $_GET);
?>
<div class="card">
    <h2>Lô đã nhập kho, chưa phân bổ đủ vị trí</h2>
    <p class="muted small">Chọn một lô để xem trước thông tin và gán vị trí. Chỉ hiển thị lô đã đạt QC, chưa hết hạn và còn số lượng chưa gán.</p>
    <div id="lotTable">
    <?php
    render_data_table([
        'chon' => ['label' => 'Chọn', 'render' => function ($r) use ($canAssign) {
            if (!$canAssign) { return ''; }
            return '<input type="radio" class="lot-radio" form="assignForm" name="maLoHang" required value="' . e($r['maLoHang']) . '" data-kho="' . e($r['maKho']) . '" data-kho-ten="' . e($r['tenKho']) . '" data-conlai="' . e($r['conLai']) . '" data-hang="' . e($r['tenHangHoa']) . '" data-hsd="' . e($r['ngayHetHan']) . '">';
        }],
        'maLoHang' => ['label' => 'Mã lô', 'class' => 'nowrap'],
        'tenHangHoa' => ['label' => 'Hàng hóa', 'class' => 'wrap'],
        'tenKho' => ['label' => 'Kho', 'class' => 'wrap'],
        'ngayHetHan' => ['label' => 'Hạn sử dụng', 'class' => 'nowrap'],
        'daNhap' => ['label' => 'Đã nhập (kg)', 'class' => 'num nowrap'],
        'daPhanBo' => ['label' => 'Đã gán (kg)', 'class' => 'num nowrap'],
        'conLai' => ['label' => 'Còn phải gán (kg)', 'class' => 'num nowrap'],
        'viTriHienTai' => ['label' => 'Vị trí đang chứa', 'class' => 'wrap'],
    ], $rows, ['stt' => true, 'stt_start' => max(0, ($page - 1) * $perPage), 'empty' => 'Không còn lô nào cần gán vị trí.', 'row_attrs' => fn(array $r): array => ['class' => 'selectable-row']]);
    render_pagination($page, $total, $perPage);
    ?>
    </div>
</div>

<?php if ($canAssign): ?>
<div class="card">
    <h2>Thông tin gán lô</h2>
    <div class="assign-lot-info">
        <div><label>Lô đã chọn</label><b id="selLot">—</b></div>
        <div><label>Hàng hóa</label><b id="selHang">—</b></div>
        <div><label>Kho (cố định theo lô)</label><b id="selKho">—</b></div>
        <div><label>Hạn sử dụng</label><b id="selHsd">—</b></div>
        <div><label>Còn phải gán</label><b id="selConLai">—</b></div>
    </div>
    <form id="assignForm" method="post" action="<?= e(route('gan-lo-hang/assign')) ?>" data-validate novalidate>
        <?= csrf_field() ?>
        <div class="filter-grid">
            <div class="field"><label>Kho</label><div class="info-item"><b id="assignKho">Chọn lô để xác định kho</b></div></div>
            <div class="field">
                <label for="assignArea">Khu vực đích <span class="req">*</span></label>
                <select id="assignArea" name="assignArea" required>
                    <option value="">-- Chọn khu vực --</option>
                    <?php foreach ($areas as $ma => $opt): ?>
                        <option value="<?= e($ma) ?>" data-kho="<?= e($opt['attrs']['data-kho']) ?>"><?= e($opt['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="assignPos">Vị trí đích <span class="req">*</span></label>
                <select id="assignPos" name="maViTri" required>
                    <option value="">-- Chọn vị trí --</option>
                    <?php foreach ($positions as $p): ?>
                        <option value="<?= e($p['maViTri']) ?>" data-khuvuc="<?= e($p['maKhuVuc']) ?>" data-kho="<?= e($p['maKho']) ?>" data-conlai="<?= e((float)$p['sucChuaToiDa'] - (float)$p['daDung']) ?>"><?= e($p['maViTri'] . ' — ' . $p['tenViTri']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label for="assignQty">Số lượng cần gán (kg) <span class="req">*</span></label><input id="assignQty" name="soLuong" type="text" inputmode="numeric" data-qty="positive" data-min="1" required></div>
            <div class="field"><label for="ghiChu">Ghi chú</label><input id="ghiChu" name="ghiChu" maxlength="255" placeholder="VD: Gán phần còn lại của lô"></div>
        </div>
        <p class="muted small" id="assignHint">Chọn lô rồi chọn khu vực, vị trí để xem sức chứa còn lại. Thao tác chỉ ghi nhận phân bổ vị trí, không thay đổi tổng tồn kho của lô.</p>
        <button type="submit" class="btn btn-primary" data-confirm="Xác nhận gán lô vào vị trí?">Gán lô</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h2>Lịch sử phân bổ gần đây</h2>
    <?php render_data_table(['maLoHang' => ['label' => 'Mã lô', 'class' => 'nowrap'], 'tenHangHoa' => ['label' => 'Hàng hóa', 'class' => 'wrap'], 'maViTri' => ['label' => 'Vị trí', 'class' => 'nowrap'], 'soLuongPhanBo' => ['label' => 'Số lượng (kg)', 'class' => 'num nowrap'], 'hoTen' => ['label' => 'Người thực hiện', 'class' => 'nowrap'], 'thoiDiemPhanBo' => ['label' => 'Thời điểm', 'class' => 'nowrap'], 'ghiChu' => ['label' => 'Ghi chú', 'class' => 'wrap']], $history, ['stt' => true, 'empty' => 'Chưa có lần phân bổ nào.']); ?>
</div>

