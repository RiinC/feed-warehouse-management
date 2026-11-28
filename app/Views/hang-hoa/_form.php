<?php
/** Cửa sổ thêm mới (UC01) / chỉnh sửa (UC02) hàng hóa. Biến: $modal [mode, item, values, errors], $closeUrl, $loaiOptions, $donViOptions, $statusOptions. */
use App\Models\HangHoa;

$isEdit = $modal['mode'] === 'edit';
$v = $modal['values'];
$err = $modal['errors'];
$item = $modal['item'];
$field = function (string $name, string $label, string $inner, bool $required = false, string $hint = '') use ($err): void {
    echo '<div class="field' . (isset($err[$name]) ? ' invalid' : '') . '"><label for="hh_' . e($name) . '">' . e($label) . ($required ? ' <span class="req">*</span>' : '') . '</label>' . $inner;
    if ($hint !== '') {
        echo '<small class="hint">' . e($hint) . '</small>';
    }
    if (isset($err[$name])) {
        echo '<span class="err-msg">' . e($err[$name]) . '</span>';
    }
    echo '</div>';
};
?>
<div class="modal" id="hangHoaModal">
    <a class="modal-backdrop" href="<?= e($closeUrl) ?>" aria-label="Đóng"></a>
    <div class="modal-dialog modal-lg" role="dialog" aria-modal="true" aria-labelledby="hhTitle">
        <h3 id="hhTitle"><?= $isEdit ? 'Chỉnh sửa thông tin hàng hóa' : 'Thêm thông tin hàng hóa' ?></h3>
        <?php if (!empty($err['_form'])): ?>
            <div class="alert alert-error" role="alert"><span><?= e($err['_form']) ?></span></div>
        <?php endif; ?>
        <form method="post" action="<?= e(route($isEdit ? 'hang-hoa/update' : 'hang-hoa/store')) ?>" data-validate novalidate>
            <?= csrf_field() ?>
            <?php if ($isEdit): ?><input type="hidden" name="maHangHoa" value="<?= e($item['maHangHoa']) ?>"><?php endif; ?>
            <div class="form-grid">
                <?php
                $field('maHangHoa', 'Mã hàng hóa', '<input type="text" id="hh_maHangHoa" value="' . e($isEdit ? $item['maHangHoa'] : 'Tự động tạo khi lưu') . '" disabled>');
                $field('tenHangHoa', 'Tên hàng', '<input type="text" id="hh_tenHangHoa" name="tenHangHoa" maxlength="120" placeholder="Nhập tên hàng, VD: Ngô hạt vàng" value="' . e($v['tenHangHoa']) . '" required>', true);

                $o = '<select id="hh_loaiHangHoa" name="loaiHangHoa" required><option value="">-- Chọn nhóm hàng --</option>';
                foreach ($loaiOptions as $k => $label) {
                    $o .= '<option value="' . e($k) . '"' . ($v['loaiHangHoa'] === (string)$k ? ' selected' : '') . '>' . e($label) . '</option>';
                }
                $field('loaiHangHoa', 'Nhóm hàng', $o . '</select>', true, $isEdit ? 'Mã hàng giữ nguyên khi đổi nhóm hàng.' : 'Nhóm hàng quyết định mã hàng được cấp.');

                $o = '<select id="hh_donViTinhChuan" name="donViTinhChuan" required>';
                foreach ($donViOptions as $d) {
                    $o .= '<option value="' . e($d) . '"' . ($v['donViTinhChuan'] === $d ? ' selected' : '') . '>' . e($d) . '</option>';
                }
                $field('donViTinhChuan', 'Đơn vị tính', $o . '</select>', true);

                $field('heSoQuyDoi', 'Quy cách đóng gói (kg / đơn vị)', '<input type="text" inputmode="decimal" id="hh_heSoQuyDoi" name="heSoQuyDoi" placeholder="VD: 25" value="' . e($v['heSoQuyDoi']) . '" required>', true, 'VD: bao 25 kg → 25; tính theo kg → 1.');
                $field('hanSuDungTieuChuanNgay', 'Hạn sử dụng tiêu chuẩn (ngày)', '<input type="text" inputmode="numeric" id="hh_hanSuDungTieuChuanNgay" name="hanSuDungTieuChuanNgay" placeholder="VD: 180" value="' . e($v['hanSuDungTieuChuanNgay']) . '">', false, 'Dùng để tính hạn sử dụng của lô khi nhà cung cấp không ghi.');
                $field('dieuKienBaoQuan', 'Điều kiện bảo quản', '<input type="text" id="hh_dieuKienBaoQuan" name="dieuKienBaoQuan" maxlength="150" placeholder="VD: Nơi khô ráo, thoáng mát" value="' . e($v['dieuKienBaoQuan']) . '">');
                if ($isEdit) {   // UC02 bước 4: hiển thị thời gian nhập/xuất (chỉ xem)
                    $field('lanNhapCuoi', 'Thời gian nhập (gần nhất)', '<input type="text" id="hh_lanNhapCuoi" value="' . e($item['lanNhapCuoi'] ? fmt_datetime($item['lanNhapCuoi']) : 'Chưa phát sinh') . '" disabled>');
                    $field('lanXuatCuoi', 'Thời gian xuất (gần nhất)', '<input type="text" id="hh_lanXuatCuoi" value="' . e($item['lanXuatCuoi'] ? fmt_datetime($item['lanXuatCuoi']) : 'Chưa phát sinh') . '" disabled>');
                }

                $o = '<select id="hh_trangThai" name="trangThai"' . ($isEdit ? '' : ' disabled') . '>';
                foreach ($statusOptions as $k => $label) {
                    $sel = ($isEdit ? $v['trangThai'] : HangHoa::TT_DANG_DUNG) === (string)$k;
                    $o .= '<option value="' . e($k) . '"' . ($sel ? ' selected' : '') . '>' . e($label) . '</option>';
                }
                $field('trangThai', 'Trạng thái sử dụng', $o . '</select>', false, $isEdit ? 'Muốn ngừng sử dụng hãy dùng nút "Ngừng sử dụng" ở danh sách.' : 'Hàng hóa mới mặc định "Đang sử dụng".');
                ?>
                <div class="full">
                    <?php $field('moTa', 'Mô tả', '<textarea id="hh_moTa" name="moTa" rows="3" maxlength="255" placeholder="Nhập mô tả hàng hóa..."' . ($isEdit ? '' : ' required') . '>' . e($v['moTa']) . '</textarea>', !$isEdit); ?>
                </div>
            </div>
            <div class="modal-actions">
                <a class="btn btn-ghost" href="<?= e($closeUrl) ?>">Hủy</a>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>
