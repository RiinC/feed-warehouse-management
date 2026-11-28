<?php
/** Cửa sổ ngừng sử dụng hàng hóa (UC03). Biến: $modal [item, values, errors], $closeUrl. */
use App\Models\HangHoa;

$item = $modal['item'];
$v = $modal['values'];
$err = $modal['errors'];
?>
<div class="modal" id="hangHoaModal">
    <a class="modal-backdrop" href="<?= e($closeUrl) ?>" aria-label="Đóng"></a>
    <div class="modal-dialog modal-lg" role="dialog" aria-modal="true" aria-labelledby="hhTitle">
        <h3 id="hhTitle">Ngừng sử dụng hàng hóa</h3>
        <?php if (!empty($err['_form'])): ?>
            <div class="alert alert-error" role="alert"><span><?= e($err['_form']) ?></span></div>
        <?php endif; ?>
        <dl class="detail-grid">
            <dt>Mã hàng</dt><dd><?= e($item['maHangHoa']) ?></dd>
            <dt>Tên hàng</dt><dd><?= e($item['tenHangHoa']) ?></dd>
            <dt>Nhóm hàng</dt><dd><?= e($item['loaiHangHoa']) ?></dd>
            <dt>Trạng thái hiện tại</dt><dd><span class="badge badge-ok"><?= e(HangHoa::statusLabel($item['trangThai'])) ?></span></dd>
        </dl>
        <form method="post" action="<?= e(route('hang-hoa/stop')) ?>" data-validate novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="maHangHoa" value="<?= e($item['maHangHoa']) ?>">
            <div class="form-grid">
                <div class="field">
                    <label for="hh_ngayNgung">Ngày ngừng sử dụng</label>
                    <input type="text" id="hh_ngayNgung" value="<?= e(date('d/m/Y H:i')) ?>" disabled>
                    <small class="hint">Hệ thống tự ghi nhận thời điểm xác nhận, không thể thay đổi.</small>
                </div>
                <div class="field full<?= isset($err['lyDo']) ? ' invalid' : '' ?>">
                    <label for="hh_lyDo">Lý do ngừng sử dụng <span class="req">*</span></label>
                    <textarea id="hh_lyDo" name="lyDo" rows="3" maxlength="150" placeholder="Nhập lý do ngừng sử dụng hàng hóa..." required><?= e($v['lyDo']) ?></textarea>
                    <?php if (isset($err['lyDo'])): ?><span class="err-msg"><?= e($err['lyDo']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="modal-actions">
                <a class="btn btn-ghost" href="<?= e($closeUrl) ?>">Hủy</a>
                <button type="submit" class="btn btn-danger" data-confirm="Bạn có chắc chắn muốn ngừng sử dụng hàng hóa &quot;<?= e($item['tenHangHoa']) ?>&quot;?">Ngừng sử dụng</button>
            </div>
        </form>
    </div>
</div>
