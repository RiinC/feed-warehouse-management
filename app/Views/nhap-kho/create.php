<?php
/** UC10 — Lập phiếu nhập kho. Dữ liệu do NhapKhoController cung cấp. */
$e = static fn($v): string => htmlspecialchars(is_scalar($v) ? (string)$v : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$num = static fn($v): string => is_numeric($v) ? rtrim(rtrim(number_format((float)$v, 6, ',', '.'), '0'), ',') : '—';
$date = static fn($v): string => !empty($v) && strtotime((string)$v) !== false ? date('d/m/Y H:i', strtotime((string)$v)) : '—';
$base = '/feed-warehouse-management/public/nhap-kho';
$url = $base . '/create';
$loHang = $loHang ?? null;
$khoPhuTrach = $khoPhuTrach ?? [];
$danhSachViTri = $danhSachViTri ?? [];
$chungTuLienQuan = $chungTuLienQuan ?? [];
$loi = $loi ?? [];
$maViTri = $maViTri ?? '';
$soLuongNhap = $soLuongNhap ?? '';
$ghiChu = $ghiChu ?? '';
$duLieuXacNhan = $duLieuXacNhan ?? null;
$maXacNhan = $maXacNhan ?? '';
$choXacNhan = !empty($choXacNhan) && is_array($duLieuXacNhan) && $maXacNhan !== '';
if (!$loHang): ?>
    <div class="alert alert-warning" style="display:block">Không tìm thấy lô hàng đủ điều kiện nhập kho.</div>
    <a class="btn" href="<?= $e($base) ?>">Quay lại danh sách</a>
<?php return; endif;
$bb = $chungTuLienQuan['danhSachBienBanDat'] ?? [];
$yc = $chungTuLienQuan['danhSachYeuCauNhap'] ?? [];
$duChungTu = !empty($chungTuLienQuan['maPhieuTiepNhan']) && count($bb) === 1 && count($yc) === 1
    && (!empty($chungTuLienQuan['maPhieuGiao']) xor !empty($chungTuLienQuan['maXuong']));
$dvt = $loHang['donViTinh'] ?? '';
$heSo = $dvt === 'kg' ? 1 : (float)($loHang['heSoQuyDoi'] ?? 0);
$duocLuu = $duChungTu && !empty($danhSachViTri) && $heSo > 0 && can('nhap-kho', 'create');
$fields = [
    'Mã lô hàng' => $loHang['maLoHang'],
    'Tên lô hàng' => $loHang['tenLoHang'] ?? '',
    'Số lượng tiếp nhận' => $num($loHang['soLuongTiepNhan'] ?? null),
    'Đơn vị tính' => $dvt,
    'Thời gian tiếp nhận' => $date($loHang['thoiGianTiepNhan'] ?? null),
    'Trạng thái lô' => $loHang['trangThaiLo'] ?? 'Chờ nhập kho',
    'Trạng thái chất lượng' => $loHang['trangThaiChatLuong'] ?? ''
];
?>
<style>
    /* Chỉ áp dụng cho các ô thông tin cố định của UC10. */
    .uc10-fixed-field:disabled {
        background-color: #f5f7fa;
        color: #334155;
        -webkit-text-fill-color: #334155;
        opacity: 1;
        cursor: default;
        pointer-events: none;
    }
    .filter-grid.uc10-top-align {
        align-items: start;
    }
    .filter-grid.uc10-four-columns,
    .filter-grid.uc10-entry-row {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .filter-grid.uc10-entry-row {
        margin-top: 12px;
    }
    @media (max-width: 900px) {
        .filter-grid.uc10-four-columns, .filter-grid.uc10-entry-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .filter-grid.uc10-four-columns, .filter-grid.uc10-entry-row { grid-template-columns: 1fr; }
    }
</style>
<?php if ($loi): ?>
<div class="alert alert-warning" role="alert" style="display:block">
    <strong>Vui lòng kiểm tra lại thông tin.</strong>
    <?php foreach ($loi as $messages): foreach ((array)$messages as $message): ?>
        <div style="margin-top:6px"><?= $e($message) ?></div>
    <?php endforeach; endforeach; ?>
</div>
<?php endif; ?>
<div class="card">
    <div class="card-head"><strong>Thông tin lô hàng</strong>
        <?php if (!$choXacNhan): ?><a class="btn btn-sm" href="<?= $e($base) ?>">Quay lại danh sách</a><?php endif; ?>
    </div>
    <div class="table-wrap">
        <table class="table" style="width:100%; table-layout:fixed;">
            <colgroup><col style="width:35%;"><col style="width:65%;"></colgroup>
            <thead><tr><th scope="col">Thông tin</th><th scope="col">Giá trị</th></tr></thead>
            <tbody>
                <?php foreach ($fields as $label => $value): ?>
                    <tr><td><strong><?= $e($label) ?></strong></td><td style="overflow-wrap:anywhere;"><?= $e($value) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if (!$choXacNhan): ?>
<form method="POST" action="<?= $e($url) ?>" id="uc10-form">
    <?= csrf_field() ?>
    <input type="hidden" name="maLoHang" value="<?= $e($loHang['maLoHang']) ?>">
    <div class="card">
        <div class="card-head"><strong>Thông tin phiếu nhập kho</strong></div>
        <div class="filter-grid uc10-top-align uc10-four-columns">
            <div class="field"><label>Mã phiếu nhập kho</label><input type="text" class="uc10-fixed-field" disabled value="Tự phát sinh khi lưu"></div>
            <div class="field"><label>Ngày lập phiếu</label><input type="text" class="uc10-fixed-field" disabled value="Thời gian hệ thống khi lưu"></div>
            <div class="field"><label>Nhân viên lập phiếu</label><input type="text" class="uc10-fixed-field" disabled value="<?= $e(\Core\Auth::user()['hoTen'] ?? '—') ?>"></div>
            <div class="field"><label>Kho nhập</label><input type="text" class="uc10-fixed-field" disabled value="<?= $e($khoPhuTrach['tenKho'] ?? '—') ?>"><small class="muted">Kho được phân công, không thể thay đổi.</small></div>
        </div>
        <div class="filter-grid uc10-top-align uc10-entry-row">
            <div class="field"><label for="soLuongNhap">Số lượng thực nhập (<?= $e($dvt) ?>) *</label><input id="soLuongNhap" name="soLuongNhap" type="number" min="0" max="<?= $e(json_encode((float)$loHang['soLuongTiepNhan'])) ?>" step="any" required value="<?= $e($soLuongNhap) ?>"><small class="muted">Tối đa <?= $e($num($loHang['soLuongTiepNhan'])) ?> <?= $e($dvt) ?>.</small></div>
            <div class="field"><label for="maViTri">Vị trí lưu trữ *</label><select id="maViTri" name="maViTri" required>
                <option value="">-- Chọn vị trí lưu trữ --</option>
                <?php $found = false; foreach ($danhSachViTri as $vt): $selected = $maViTri === $vt['maViTri']; $found = $found || $selected; ?>
                <option value="<?= $e($vt['maViTri']) ?>" data-cap="<?= $e($vt['sucChuaConLai']) ?>" <?= $selected ? 'selected' : '' ?>><?= $e($vt['tenViTri'].' (còn '.$num($vt['sucChuaConLai']).' kg)') ?></option>
                <?php endforeach; if ($maViTri !== '' && !$found): ?><option selected disabled value="">Vị trí đã chọn không còn khả dụng</option><?php endif; ?>
            </select></div>
        </div>
        <div class="alert" style="display:block;margin-top:12px" id="uc10-capacity" role="status" aria-live="polite">
            <div>Khối lượng thực nhập: <strong id="uc10-kg">—</strong></div>
            <div>Sức chứa còn lại: <strong id="uc10-free">—</strong></div>
            <div id="uc10-cap-message" style="margin-top:6px"></div>
        </div>
        <div class="field"><label for="ghiChu">Ghi chú (bắt buộc nếu nhập thiếu)</label><textarea id="ghiChu" name="ghiChu" rows="3" maxlength="255" placeholder="Nhập ghi chú nếu có"><?= $e($ghiChu) ?></textarea></div>
    </div>
    <div class="card"><div class="card-head"><strong>Chứng từ liên quan</strong><span class="muted">Tự động truy xuất</span></div>
        <div class="filter-grid uc10-top-align">
            <?php $docs = ['Phiếu tiếp nhận'=>$chungTuLienQuan['maPhieuTiepNhan'] ?? '', 'Phiếu giao NCC'=>$chungTuLienQuan['maPhieuGiao'] ?? '', 'Mã xưởng sản xuất'=>$chungTuLienQuan['maXuong'] ?? '', 'Biên bản kiểm định'=>implode(', ',array_column($bb,'maBienBan')), 'Phiếu yêu cầu nhập kho'=>implode(', ',array_column($yc,'maYeuCauNhap'))]; foreach ($docs as $label=>$value): ?>
            <div class="field"><label><?= $e($label) ?></label><input type="text" class="uc10-fixed-field" disabled value="<?= $e($value ?: '—') ?>"></div>
            <?php endforeach; ?>
        </div>
        <?php if (!$duChungTu): ?><div class="alert alert-warning" style="display:block">Chứng từ còn thiếu hoặc không xác định được duy nhất. Không thể tiếp tục lưu phiếu.</div><?php endif; ?>
        <div class="filter-actions" style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px"><a class="btn" href="<?= $e($base) ?>">Quay lại danh sách</a><button class="btn btn-primary" type="submit" name="hanhDong" value="kiemTra" <?= !$duocLuu ? 'disabled' : '' ?>>Lưu phiếu nhập kho</button></div>
    </div>
</form>
<script>
(function(){
 const qty=document.getElementById('soLuongNhap'), pos=document.getElementById('maViTri'), note=document.getElementById('ghiChu');
 const factor=<?= json_encode($heSo, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?>, max=<?= json_encode((float)$loHang['soLuongTiepNhan'], JSON_PARTIAL_OUTPUT_ON_ERROR) ?>;
 const fmt=n=>n.toLocaleString('vi-VN',{maximumFractionDigits:6})+' kg';
 function update(){
  const n=qty.valueAsNumber, kg=n*factor, opt=pos.options[pos.selectedIndex], raw=opt?.dataset.cap, free=raw===undefined?NaN:Number(raw);
  document.getElementById('uc10-kg').textContent=Number.isFinite(kg)&&kg>0?fmt(kg):'—';
  document.getElementById('uc10-free').textContent=Number.isFinite(free)?fmt(free):'—';
  note.required=Number.isFinite(n)&&n>0&&n<max;
  const msg=document.getElementById('uc10-cap-message');
  let warning='';
  if(Number.isFinite(n)&&n<=0) warning='Số lượng nhập không hợp lệ';
  else if(n>max) warning='Số lượng nhập vượt quá số lượng được phép nhập';
  else if(Number.isFinite(free)&&kg>free) warning='Vị trí không đủ sức chứa. Vui lòng chọn vị trí khác trong kho.';
  msg.textContent=warning || (Number.isFinite(free)&&kg>0?'Vị trí đủ sức chứa theo dữ liệu đang hiển thị.':'');
  msg.style.color=warning?'#dc2626':'#15803d';
 }
 function limitQuantity(){
  const n=qty.valueAsNumber;
  if(Number.isFinite(n)&&n>max) qty.value=String(max);
  const current=qty.valueAsNumber;
  qty.setCustomValidity(Number.isFinite(current)&&current<=0?'Số lượng nhập không hợp lệ':'');
  update();
 }
 qty.addEventListener('input',limitQuantity);
 qty.addEventListener('change',limitQuantity);
 pos.addEventListener('change',update);
 limitQuantity();
})();
</script>
<?php else: $d = $duLieuXacNhan; ?>
<div class="card" id="uc10-confirm">
    <div class="card-head"><strong style="font-size:22px">Xác nhận lưu phiếu nhập kho</strong></div>
    <p>Vui lòng kiểm tra thông tin trước khi xác nhận. Mã phiếu và ngày lập được tạo khi lưu thành công.</p>
    <div class="filter-grid uc10-top-align">
    <?php $summary = [
        'Mã lô hàng'=>$d['maLoHang'] ?? '', 'Tên lô hàng'=>$d['tenLoHang'] ?? '',
        'Nhân viên lập phiếu'=>$d['hoTenNhanVien'] ?? '—',
        'Kho nhập'=>$d['tenKho'] ?? '—',
        'Vị trí lưu trữ'=>($d['maViTri'] ?? '').' - '.($d['tenViTri'] ?? ''),
        'Số lượng tiếp nhận'=>$num($d['soLuongTiepNhan'] ?? null).' '.($d['donViTinh'] ?? ''),
        'Số lượng thực nhập'=>$num($d['soLuongNhap'] ?? null).' '.($d['donViTinh'] ?? ''),
        'Khối lượng chuẩn'=>$num($d['soLuongChuanKg'] ?? null).' kg',
        'Sức chứa còn lại trước nhập'=>$num($d['sucChuaConLai'] ?? null).' kg',
        'Sức chứa dự kiến sau nhập'=>$num((float)($d['sucChuaConLai'] ?? 0)-(float)($d['soLuongChuanKg'] ?? 0)).' kg',
        'Phiếu tiếp nhận'=>$d['maPhieuTiepNhan'] ?? '', 'Phiếu giao NCC'=>$d['maPhieuGiao'] ?? '', 'Mã xưởng sản xuất'=>$d['maXuong'] ?? '',
        'Biên bản kiểm định'=>$d['maBienBan'] ?? '', 'Phiếu yêu cầu nhập kho'=>$d['maYeuCauNhap'] ?? ''
    ]; foreach ($summary as $label=>$value): ?>
        <div class="field"><label><?= $e($label) ?></label><input type="text" class="uc10-fixed-field" disabled value="<?= $e($value ?: '—') ?>"></div>
    <?php endforeach; ?>
    </div>
    <div class="field"><label>Ghi chú</label><textarea class="uc10-fixed-field" disabled rows="3"><?= $e($d['ghiChu'] ?? '') ?></textarea></div>
    <div class="alert alert-warning" style="display:block">Sau khi xác nhận, hệ thống kiểm tra lại điều kiện nhập kho, lưu phiếu, cập nhật tồn kho và ghi lịch sử biến động.</div>
    <form method="POST" action="<?= $e($url) ?>" id="uc10-confirm-form">
        <?= csrf_field() ?>
        <input type="hidden" name="maXacNhan" value="<?= $e($maXacNhan) ?>">
        <div class="filter-actions" style="display:flex;gap:8px;justify-content:flex-end">
            <button type="submit" name="hanhDong" value="quayLai" class="btn">Quay lại</button>
            <button type="submit" name="hanhDong" value="xacNhan" class="btn btn-primary">Xác nhận</button>
        </div>
    </form>
</div>
<script>document.getElementById('uc10-confirm').scrollIntoView({block:'start'});</script>
<?php endif; ?>
