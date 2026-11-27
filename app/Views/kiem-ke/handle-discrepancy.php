<?php
/**
 * View kiem-ke/handle-discrepancy — UC27: đề xuất → phê duyệt → thi hành phương án xử lý chênh lệch.
 * Biến từ KiemKeController::handleDiscrepancy(): $bb, $tienDo, $dong, $phuongAn, $phieu, $bienPhapThua, $bienPhapThieu.
 *
 * Thi hành phương án sẽ sinh PhieuDieuChinhTonKho + cập nhật TonKho + ghi LichSuBienDongTonKho (một giao dịch).
 */
$badgePA = [
    'Chờ phê duyệt' => 'badge badge-warn',
    'Đã phê duyệt'  => 'badge badge-info',
    'Đã từ chối'    => 'badge badge-danger',
    'Đã thi hành'   => 'badge badge-ok',
];
$paTheoCL = [];
foreach ($phuongAn as $pa) {
    if ($pa['maChenhLech'] !== null && !isset($paTheoCL[$pa['maChenhLech']])) {
        $paTheoCL[$pa['maChenhLech']] = $pa;   // danh sách đã sắp mới nhất trước
    }
}
$dongLech = array_values(array_filter($dong, function ($r) {
    return abs((float)$r['chenhLech']) > 0.000001;
}));
$choDuyet = 0;
$daThiHanh = 0;
foreach ($phuongAn as $pa) {
    if ($pa['trangThai'] === 'Chờ xử lý') {
        $choDuyet++;
    }
    if ($pa['trangThai'] === 'Hoàn tất') {
        $daThiHanh++;
    }
}
$moc = [
    'viTri'       => (int)$tienDo['viTriDaKiem'] === (int)$tienDo['soViTri'],
    'ketQua'      => (int)$tienDo['soDaGhi'] === (int)$tienDo['soDong'],
    'doiChieu'    => (int)$tienDo['soChuaDoiChieu'] === 0,
    'phuongAn'    => (int)$tienDo['soChuaDeXuat'] === 0,
    'khongTuChoi' => (int)$tienDo['soTuChoi'] === 0,
];
$coTheChot = !in_array(false, $moc, true) && $bb['trangThai'] === 'Đang kiểm kê';
?>
<div class="stat-grid">
    <div class="stat-card warn">
        <span class="stat-label">Dòng chênh lệch</span>
        <div class="stat-value"><?= count($dongLech) ?></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">Phương án chờ duyệt</span>
        <div class="stat-value"><?= $choDuyet ?></div>
    </div>
    <div class="stat-card ok">
        <span class="stat-label">Phương án đã thi hành</span>
        <div class="stat-value"><?= $daThiHanh ?></div>
    </div>
    <div class="stat-card <?= $coTheChot ? 'ok' : '' ?>">
        <span class="stat-label">Trạng thái biên bản</span>
        <div class="stat-value" style="font-size:16px"><?= e($bb['trangThai']) ?></div>
    </div>
</div>


<div class="card">
    <div class="card-head">
        <h2>Chênh lệch cần xử lý</h2>
        <a class="link-btn" href="<?= e(route('kiem-ke/reconcile', ['ma' => $bb['maBienBanKiemKe']])) ?>">Quay lại đối chiếu</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Vị trí / Hàng hóa</th>
                <th>Chênh lệch</th>
                <th>Nguyên nhân</th>
                <th>Phương án hiện tại</th>
                <th>Đề xuất xử lý</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dongLech): ?>
                <tr><td class="empty-row" colspan="5">Không có dòng chênh lệch nào — biên bản khớp sổ sách.</td></tr>
            <?php endif; ?>
            <?php foreach ($dongLech as $r):
                $cl = $r['maChenhLech'];
                $pa = $cl !== null ? ($paTheoCL[$cl] ?? null) : null;
                $conHieuLuc = $pa && in_array($pa['trangThai'], ['Chờ xử lý', 'Đang xử lý', 'Hoàn tất'], true);
                $dsBienPhap = $r['loaiChenhLech'] === 'Thừa' ? $bienPhapThua : $bienPhapThieu;
                ?>
                <tr>
                    <td>
                        <b><?= e($r['maViTri']) ?></b> · <?= e($r['tenHangHoa']) ?>
                        <br><small class="muted"><?= e($r['maLoHang']) ?> · <?= e($r['tenViTri']) ?></small>
                    </td>
                    <td class="num <?= (float)$r['chenhLech'] > 0 ? 'diff-plus' : 'diff-minus' ?>">
                        <?= ((float)$r['chenhLech'] > 0 ? '+' : '') . fmt_number($r['chenhLech'], 2) ?>
                        <br><small class="muted"><?= e((string)$r['loaiChenhLech']) ?> · <?= e($r['donViTinhChuan']) ?></small>
                    </td>
                    <td><?= e((string)$r['nguyenNhan']) ?><?php if ($r['moTaChiTiet']): ?><br><small class="muted"><?= e($r['moTaChiTiet']) ?></small><?php endif; ?></td>
                    <td>
                        <?php if ($cl === null): ?>
                            <span class="badge badge-warn">Chưa đối chiếu</span>
                        <?php elseif ($pa === null): ?>
                            <span class="muted">Chưa đề xuất</span>
                        <?php else: ?>
                            <b><?= e($pa['maPhuongAn']) ?></b> · <?= e(resolution_method_label($pa['bienPhapXuLy'])) ?>
                            <br><?= proposal_status_badge($pa['trangThai']) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($cl === null): ?>
                            <a class="link-btn" href="<?= e(route('kiem-ke/reconcile', ['ma' => $bb['maBienBanKiemKe']])) ?>">Đối chiếu trước</a>
                        <?php elseif ($conHieuLuc): ?>
                            <span class="muted small">Đang xử lý</span>
                        <?php elseif (can('kiem-ke', 'handle')): ?>
                            <form class="inline-form" method="post" action="<?= e(route('kiem-ke/propose')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="ma" value="<?= e($bb['maBienBanKiemKe']) ?>">
                                <input type="hidden" name="maChenhLech" value="<?= e($cl) ?>">
                                <select name="bienPhapXuLy" required>
                                    <?php foreach ($dsBienPhap as $bpCode => $bpLabel): ?>
                                        <option value="<?= e($bpCode) ?>"><?= e($bpLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Đề xuất</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h2>Phương án xử lý</h2>
        <span class="note"><?= count($phuongAn) ?> phương án</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Mã phương án</th>
                <th>Đối tượng</th>
                <th>Biện pháp</th>
                <th>Đề xuất</th>
                <th>Phê duyệt</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$phuongAn): ?>
                <tr><td class="empty-row" colspan="7">Chưa có phương án nào được đề xuất.</td></tr>
            <?php endif; ?>
            <?php foreach ($phuongAn as $pa): ?>
                <tr>
                    <td><b><?= e($pa['maPhuongAn']) ?></b></td>
                    <td>
                        <?= e($pa['tenHangHoa']) ?>
                        <br><small class="muted"><?= e($pa['maLoHang'] . ' @ ' . $pa['maViTri']) ?> · <?= e((string)$pa['loaiChenhLech']) ?>
                            <?= fmt_number(abs((float)$pa['soLuongChenhLech']), 2) ?></small>
                    </td>
                    <td><?= e(resolution_method_label($pa['bienPhapXuLy'])) ?></td>
                    <td><?= e((string)$pa['tenNguoiDeXuat']) ?><br><small class="muted"><?= e(fmt_datetime($pa['ngayDeXuat'])) ?></small></td>
                    <td>
                        <?= e((string)$pa['tenNguoiPheDuyet']) ?>
                        <?php if ($pa['ngayPheDuyet']): ?><br><small class="muted"><?= e(fmt_datetime($pa['ngayPheDuyet'])) ?></small><?php endif; ?>
                        <?php if ($pa['yKienPheDuyet']): ?><br><small class="muted"><?= e($pa['yKienPheDuyet']) ?></small><?php endif; ?>
                    </td>
                    <td><?= proposal_status_badge($pa['trangThai']) ?></td>
                    <td>
                        <?php if ($pa['trangThai'] === 'Chờ xử lý' && can('kiem-ke', 'handle')): ?>
                            <form class="inline-form" method="post" action="<?= e(route('kiem-ke/approve')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="ma" value="<?= e($bb['maBienBanKiemKe']) ?>">
                                <input type="hidden" name="maPhuongAn" value="<?= e($pa['maPhuongAn']) ?>">
                                <input type="text" name="yKien" maxlength="255" placeholder="Ý kiến (không bắt buộc)">
                                <button class="btn btn-sm btn-primary" type="submit">Duyệt</button>
                            </form>
                            <form class="inline-form" method="post" action="<?= e(route('kiem-ke/reject')) ?>" style="margin-top:6px">
                                <?= csrf_field() ?>
                                <input type="hidden" name="ma" value="<?= e($bb['maBienBanKiemKe']) ?>">
                                <input type="hidden" name="maPhuongAn" value="<?= e($pa['maPhuongAn']) ?>">
                                <input type="text" name="lyDo" maxlength="255" placeholder="Lý do từ chối" required>
                                <button class="btn btn-sm btn-danger" type="submit" data-confirm="Từ chối phương án này?">Từ chối</button>
                            </form>
                        <?php elseif ($pa['trangThai'] === 'Đang xử lý' && can('kiem-ke', 'handle')): ?>
                            <form class="inline-form" method="post" action="<?= e(route('kiem-ke/execute')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="ma" value="<?= e($bb['maBienBanKiemKe']) ?>">
                                <input type="hidden" name="maPhuongAn" value="<?= e($pa['maPhuongAn']) ?>">
                                <button class="btn btn-sm btn-primary" type="submit" data-confirm="Thi hành phương án và cập nhật tồn kho?">Thi hành</button>
                            </form>
                        <?php else: ?>
                            <span class="muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<?php if ($phieu): ?>
    <div class="card">
        <div class="card-head">
            <h2>Phiếu điều chỉnh đã phát sinh</h2>
            <a class="link-btn" href="<?= e(route('ton-kho')) ?>">Xem tồn kho sau điều chỉnh</a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Mã phiếu</th>
                    <th>Hàng hóa / Lô / Vị trí</th>
                    <th>Loại</th>
                    <th class="num">Số lượng</th>
                    <th>Thi hành</th>
                    <th>Ghi chú</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($phieu as $pd): ?>
                    <tr>
                        <td><b><?= e($pd['maPhieuDieuChinh']) ?></b></td>
                        <td><?= e($pd['tenHangHoa']) ?><br><small class="muted"><?= e($pd['maLoHang'] . ' @ ' . $pd['maViTri']) ?> · <?= e($pd['tenViTri']) ?></small></td>
                        <td><span class="badge <?= ($pd['loaiDieuChinh'] === 'Tang' || $pd['loaiDieuChinh'] === 'Tăng') ? 'badge-ok' : 'badge-danger' ?>"><?= e(adjustment_type_label($pd['loaiDieuChinh'])) ?></span></td>
                        <td class="num"><?= fmt_number($pd['soLuongDieuChinh'], 2) ?></td>
                        <td><?= e($pd['tenNhanVienThiHanh']) ?><br><small class="muted"><?= e(fmt_datetime($pd['ngayThiHanh'])) ?></small></td>
                        <td><?= e((string)$pd['ghiChu']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <h2>Chốt biên bản</h2>
        <span class="note">Biên bản chỉ chốt được khi hoàn tất các mục dưới đây.</span>
    </div>
    <ul class="checklist">
        <li class="<?= $moc['viTri'] ? 'done' : 'todo' ?>">Vị trí đã kiểm tra: <?= (int)$tienDo['viTriDaKiem'] ?>/<?= (int)$tienDo['soViTri'] ?></li>
        <li class="<?= $moc['ketQua'] ? 'done' : 'todo' ?>">Dòng sổ sách đã ghi kết quả: <?= (int)$tienDo['soDaGhi'] ?>/<?= (int)$tienDo['soDong'] ?></li>
        <li class="<?= $moc['doiChieu'] ? 'done' : 'todo' ?>">Chênh lệch chưa đối chiếu: <?= (int)$tienDo['soChuaDoiChieu'] ?></li>
        <li class="<?= $moc['phuongAn'] ? 'done' : 'todo' ?>">Chênh lệch chưa có phương án: <?= (int)$tienDo['soChuaDeXuat'] ?></li>
        <li class="<?= $moc['khongTuChoi'] ? 'done' : 'todo' ?>">Phương án bị từ chối cần đề xuất lại: <?= (int)$tienDo['soTuChoi'] ?></li>
    </ul>

    <?php if ($bb['trangThai'] === 'Đang kiểm kê' && can('kiem-ke', 'handle')): ?>
        <form class="toolbar" method="post" action="<?= e(route('kiem-ke/close')) ?>" style="margin-top:14px">
            <?= csrf_field() ?>
            <input type="hidden" name="ma" value="<?= e($bb['maBienBanKiemKe']) ?>">
            <div class="field" style="flex:1;margin:0">
                <label for="f_ghiChuClose">Ghi chú khi chốt (không bắt buộc)</label>
                <input id="f_ghiChuClose" type="text" name="ghiChu" maxlength="255" placeholder="VD: Đã xử lý xong toàn bộ chênh lệch">
            </div>
            <button class="btn btn-primary" type="submit" <?= $coTheChot ? '' : 'disabled' ?>
                    data-confirm="Chốt biên bản? Sau khi chốt sẽ không ghi nhận/sửa kết quả được nữa.">Chốt biên bản</button>
        </form>
        <form class="toolbar" method="post" action="<?= e(route('kiem-ke/cancel')) ?>" style="margin-top:4px">
            <?= csrf_field() ?>
            <input type="hidden" name="ma" value="<?= e($bb['maBienBanKiemKe']) ?>">
            <input type="text" name="lyDo" maxlength="200" placeholder="Lý do hủy biên bản (nếu lập nhầm)">
            <button class="btn btn-danger" type="submit" data-confirm="Hủy biên bản này? Chỉ hủy được khi chưa ghi kết quả.">Hủy biên bản</button>
        </form>
    <?php else: ?>
        <p class="note">Biên bản đang ở trạng thái "<?= e($bb['trangThai']) ?>".</p>
    <?php endif; ?>
</div>

