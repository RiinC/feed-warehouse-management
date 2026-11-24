<?php
/** Xác nhận tiếp nhận. Biến: $ct, $values, $chon (dòng đã chuẩn hóa), $failure, $viTriNhan, $nguoiNhan, $loaiLabel. */
use App\Models\TiepNhan;
?>
<?php if ($failure): ?>
    <?php render_alert('error', $failure); ?>
<?php endif; ?>
<div class="card">
    <div class="card-head"><b>Xác nhận tiếp nhận hàng</b><span class="muted">Kiểm tra lại thông tin trước khi ghi nhận</span></div>
    <dl class="detail-grid recv-meta">
        <dt>Số chứng từ</dt><dd><?= e($ct['soChungTu']) ?> <span class="muted">(<?= e($loaiLabel) ?>)</span></dd>
        <dt>Nhà cung cấp / Xưởng giao</dt><dd><?= e($ct['nguonGiao']) ?></dd>
        <dt>Ngày nhận</dt><dd><?= e(date('d/m/Y')) ?></dd>
        <dt>Giờ nhận</dt><dd><?= e(date('H:i')) ?> <span class="muted">(theo giờ hệ thống khi xác nhận)</span></dd>
        <dt>Người tiếp nhận</dt><dd><?= e($nguoiNhan) ?></dd>
        <dt>Vị trí lưu trữ tạm</dt><dd><?= e($viTriNhan) ?></dd>
        <dt>Ghi chú</dt><dd><?= e($values['ghi_chu'] !== '' ? $values['ghi_chu'] : '—') ?></dd>
    </dl>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Mã hàng</th><th>Tên hàng</th><th class="num">Thực nhận</th><th class="num">Quy đổi (kg)</th><th>Ngày sản xuất</th><th>Hạn sử dụng</th><th>Sau khi tiếp nhận</th></tr></thead>
            <tbody>
            <?php $tong = 0.0; foreach ($chon as $id => $in): $l = $ct['dong'][$id]; $kg = round($in['sl'] * $l['heSo'], 3); $tong += $kg; ?>
                <tr>
                    <td><?= e($l['maHangHoa']) ?></td>
                    <td><?= e($l['tenHangHoa']) ?></td>
                    <td class="num"><?= e(fmt_number($in['sl'], 3)) ?> <?= e($l['donViTinh']) ?></td>
                    <td class="num"><?= e(fmt_number($kg, 3)) ?></td>
                    <td><?= e(fmt_date($in['nsx'])) ?></td>
                    <td><?= e(fmt_date($in['hsd'])) ?></td>
                    <td><?= $l['conLai'] - $in['sl'] > 0.0005 ? '<span class="badge badge-warn">Còn thiếu ' . e(fmt_number($l['conLai'] - $in['sl'], 3)) . ' ' . e($l['donViTinh']) . '</span>' : '<span class="badge badge-ok">Đủ</span>' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><td colspan="3" class="num"><b>Tổng quy đổi</b></td><td class="num"><b><?= e(fmt_number($tong, 3)) ?></b></td><td colspan="3"></td></tr></tfoot>
        </table>
    </div>

    <form method="post" action="<?= e(route('tiep-nhan/luu')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="chung_tu" value="<?= e($ct['soChungTu']) ?>">
        <input type="hidden" name="vi_tri" value="<?= e($values['vi_tri']) ?>">
        <input type="hidden" name="ghi_chu" value="<?= e($values['ghi_chu']) ?>">
        <?php foreach ($chon as $id => $in): ?>
            <input type="hidden" name="lines[<?= (int)$id ?>][chon]" value="1">
            <input type="hidden" name="lines[<?= (int)$id ?>][sl]" value="<?= e((string)$in['sl']) ?>">
            <input type="hidden" name="lines[<?= (int)$id ?>][nsx]" value="<?= e($in['nsx']) ?>">
            <input type="hidden" name="lines[<?= (int)$id ?>][hsd]" value="<?= e($in['hsd']) ?>">
        <?php endforeach; ?>
        <div class="modal-actions flat">
            <a class="btn btn-ghost" href="<?= e(route('tiep-nhan')) ?>">Hủy</a>
            <button type="submit" class="btn btn-primary"><?= $failure ? 'Thử lại' : 'Xác nhận tiếp nhận' ?></button>
        </div>
    </form>
</div>
