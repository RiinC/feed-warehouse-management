<?php
/** Màn hình tiếp nhận một chứng từ. Biến: $ct, $values, $errors, $viTriOptions, $nguoiNhan, $loaiLabel. */
$lineErr = $errors['line'] ?? [];
?>
<?php if (!empty($errors['_form'])): ?>
    <?php render_alert('error', $errors['_form']); ?>
<?php endif; ?>
<form method="post" action="<?= e(route('tiep-nhan/xac-nhan')) ?>" data-validate novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="chung_tu" value="<?= e($ct['soChungTu']) ?>">

    <div class="card">
        <div class="card-head"><b>Thông tin chứng từ</b><span class="badge <?= $ct['tinhTrang'] === 'Chờ tiếp nhận' ? 'badge-warn' : 'badge-info' ?>"><?= e($ct['tinhTrang']) ?></span></div>
        <dl class="detail-grid recv-meta">
            <dt>Số chứng từ</dt><dd><?= e($ct['soChungTu']) ?> <span class="muted">(<?= e($loaiLabel) ?>)</span></dd>
            <dt>Nhà cung cấp / Xưởng giao</dt><dd><?= e($ct['nguonGiao']) ?></dd>
            <dt>Ngày giao</dt><dd><?= e(fmt_date($ct['ngayGiao'])) ?></dd>
            <?php if (!empty($ct['nguoiGiao'])): ?><dt>Người giao</dt><dd><?= e($ct['nguoiGiao']) ?></dd><?php endif; ?>
            <?php if (!empty($ct['soBienSoXe'])): ?><dt>Biển số xe</dt><dd><?= e($ct['soBienSoXe']) ?></dd><?php endif; ?>
            <dt>Ngày nhận</dt><dd><?= e(date('d/m/Y')) ?></dd>
            <dt>Giờ nhận</dt><dd><?= e(date('H:i')) ?> <span class="muted">(ghi nhận theo giờ hệ thống khi xác nhận)</span></dd>
            <dt>Người tiếp nhận</dt><dd><?= e($nguoiNhan) ?></dd>
            <dt>Mã phiếu tiếp nhận</dt><dd class="muted">Tự động tạo khi xác nhận</dd>
        </dl>
    </div>

    <div class="card">
        <div class="card-head"><b>Hàng hóa tiếp nhận</b><span class="muted">Chọn các dòng thực tế đã nhận; dòng bỏ chọn sẽ còn chờ tiếp nhận</span></div>
        <div class="table-wrap">
            <table class="table recv-table">
                <thead><tr>
                    <th class="w-check"></th><th>Mã hàng</th><th>Tên hàng</th><th>Số lô NCC</th><th class="num">SL giao</th><th class="num">Còn phải nhận</th>
                    <th>Số lượng thực nhận</th><th>Ngày sản xuất</th><th>Hạn sử dụng</th>
                </tr></thead>
                <tbody>
                <?php foreach ($ct['dong'] as $id => $l):
                    $row = $values['lines'][$id] ?? ['chon' => '1', 'sl' => '', 'nsx' => '', 'hsd' => ''];
                    $bad = isset($lineErr[$id]);
                ?>
                    <tr class="<?= $bad ? 'row-invalid' : '' ?>">
                        <td><input type="checkbox" name="lines[<?= (int)$id ?>][chon]" value="1" <?= $row['chon'] ? 'checked' : '' ?> aria-label="Tiếp nhận dòng này"></td>
                        <td><?= e($l['maHangHoa']) ?></td>
                        <td><?= e($l['tenHangHoa']) ?></td>
                        <td><?= e($l['soLoNCC'] ?: '—') ?></td>
                        <td class="num"><?= e(fmt_number($l['soLuongGiao'], 3)) ?> <?= e($l['donViTinh']) ?></td>
                        <td class="num"><?= e(fmt_number($l['conLai'], 3)) ?> <?= e($l['donViTinh']) ?></td>
                        <td><input type="text" inputmode="decimal" name="lines[<?= (int)$id ?>][sl]" value="<?= e($row['sl']) ?>" aria-label="Số lượng thực nhận"></td>
                        <td><input type="date" name="lines[<?= (int)$id ?>][nsx]" value="<?= e($row['nsx']) ?>" aria-label="Ngày sản xuất"></td>
                        <td><input type="date" name="lines[<?= (int)$id ?>][hsd]" value="<?= e($row['hsd']) ?>" aria-label="Hạn sử dụng"></td>
                    </tr>
                    <?php if ($bad): ?>
                        <tr class="row-error"><td></td><td colspan="8"><span class="err-msg"><?= e($lineErr[$id]) ?></span></td></tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="hint recv-hint">Số lượng nhập theo đơn vị trên chứng từ; hệ thống tự quy đổi ra kg để tạo lô hàng. Nếu nhà cung cấp không ghi hạn sử dụng, hạn được tính từ hạn sử dụng tiêu chuẩn của hàng hóa.</p>
    </div>

    <div class="card">
        <div class="form-grid">
            <div class="field<?= isset($errors['vi_tri']) ? ' invalid' : '' ?>">
                <label for="tn_vi_tri">Vị trí lưu trữ tạm <span class="req">*</span></label>
                <select id="tn_vi_tri" name="vi_tri" required>
                    <option value="">-- Chọn vị trí tiếp nhận tạm --</option>
                    <?php foreach ($viTriOptions as $k => $label): ?>
                        <option value="<?= e($k) ?>" <?= $values['vi_tri'] === (string)$k ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['vi_tri'])): ?><span class="err-msg"><?= e($errors['vi_tri']) ?></span><?php endif; ?>
                <?php if (!$viTriOptions): ?><span class="err-msg">Hiện không có vị trí tiếp nhận tạm nào còn sử dụng được.</span><?php endif; ?>
            </div>
            <div class="field full<?= isset($errors['ghi_chu']) ? ' invalid' : '' ?>">
                <label for="tn_ghi_chu">Ghi chú</label>
                <textarea id="tn_ghi_chu" name="ghi_chu" rows="2" maxlength="255"><?= e($values['ghi_chu']) ?></textarea>
                <?php if (isset($errors['ghi_chu'])): ?><span class="err-msg"><?= e($errors['ghi_chu']) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="modal-actions flat">
            <a class="btn btn-ghost" href="<?= e(route('tiep-nhan')) ?>">Hủy</a>
            <button type="submit" class="btn btn-primary">Tiếp tục</button>
        </div>
    </div>
</form>
