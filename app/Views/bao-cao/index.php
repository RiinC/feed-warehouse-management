<?php
/** UC30 - Xem báo cáo tồn kho. View chỉ hiển thị dữ liệu từ Controller. */
$e = static fn($v): string => htmlspecialchars(is_scalar($v) ? (string)$v : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$num = static fn($v): string => is_numeric($v) ? rtrim(rtrim(number_format((float)$v, 6, ',', '.'), '0'), ',') : '—';
$date = static function ($v): string {
    $t = !empty($v) ? strtotime((string)$v) : false;
    return $t === false ? '—' : date('d/m/Y H:i:s', $t);
};
$url = '/feed-warehouse-management/public/bao-cao';
$urlTrucTiep = $url . '?tab=direct';
$urlLichSu = $url . '/history';
$maXacNhanGui = (string)($maXacNhanGui ?? '');
$coTheGui = (bool)($coTheGui ?? false);
$danhSachKho = $danhSachKho ?? [];
$laQuanLyKho = (bool)($laQuanLyKho ?? false);
$khoPhuTrach = $khoPhuTrach ?? null;
$maKho = $maKho ?? '';
$tuNgay = $tuNgay ?? '';
$denNgay = $denNgay ?? '';
$daYeuCau = (bool)($daYeuCau ?? false);
$baoCao = $baoCao ?? null;
$danhSach = $danhSach ?? [];
$tongCong = $tongCong ?? null;
$canhBao = $canhBao ?? [];
$thongBao = $thongBao ?? '';
$loi = (bool)($loi ?? false);
$tongSoHangHoa = max(0, (int)($tongSoHangHoa ?? 0));
$tongSoTrang = max(1, (int)($tongSoTrang ?? 1));
$trang = max(1, min($tongSoTrang, (int)($trang ?? 1)));
$soLuongMoiTrang = max(1, (int)($soLuongMoiTrang ?? 10));
$offset = ($trang - 1) * $soLuongMoiTrang;
$loc = ['tab'=>'direct', 'xemBaoCao'=>'1', 'maKho'=>$maKho, 'tuNgay'=>$tuNgay, 'denNgay'=>$denNgay];
$taoUrl = static fn(int $p): string => $url . '?' . http_build_query(array_merge($loc, ['page'=>$p]));
$cotSo = ['tonDauKy', 'nhapTrongKy', 'xuatTrongKy', 'tonCuoiKy'];
?>
<style>
.uc30-page .uc30-filter, .uc30-page .uc30-info {
    display:grid; gap:16px; align-items:start;
    grid-template-columns:repeat(4,minmax(0,1fr));
}
.uc30-page .uc30-filter { grid-template-columns:minmax(0,1.4fr) repeat(2,minmax(0,1fr)) auto; align-items:end; }
.uc30-page .field { min-width:0; margin-bottom:0; }
.uc30-page .uc30-filter input, .uc30-page .uc30-filter select, .uc30-page .uc30-filter .btn,
.uc30-page .uc30-info input { height:40px; min-height:40px; box-sizing:border-box; margin:0; }
.uc30-page .uc30-filter input, .uc30-page .uc30-filter select, .uc30-page .uc30-info input { width:100%; }
.uc30-page .uc30-filter .btn { display:inline-flex; align-items:center; padding:0 16px; white-space:nowrap; }
.uc30-page .uc30-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.uc30-page .uc30-fixed:disabled { background:#f5f7fa; color:#334155; -webkit-text-fill-color:#334155; opacity:1; pointer-events:none; cursor:default; }
.uc30-page .uc30-number { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
.uc30-page .uc30-totals td { font-weight:600; border-top:2px solid #dbe2ea; }
.uc30-page .uc30-warning { margin-bottom:16px; }
.uc30-page .uc30-warning ul { margin:8px 0 0; padding-left:22px; }
.uc30-page .uc30-info { margin-bottom:16px; }
.uc30-page .uc30-footer { margin-top:16px; flex-wrap:wrap; gap:8px; }
@media(max-width:1050px) { .uc30-page .uc30-filter,.uc30-page .uc30-info { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:640px) { .uc30-page .uc30-filter,.uc30-page .uc30-info { grid-template-columns:1fr; } }
</style>
<div class="uc30-page">
<div class="card">
    <nav class="uc30-actions" aria-label="Chức năng báo cáo tồn kho">
        <?php if (!$laQuanLyKho): ?>
            <a class="btn" href="<?= $e($urlLichSu) ?>">Báo cáo đã nhận</a>
        <?php endif; ?>
        <a class="btn btn-primary" href="<?= $e($urlTrucTiep) ?>" aria-current="page"><?= $laQuanLyKho ? 'Xem báo cáo tồn kho' : 'Xem báo cáo trực tiếp' ?></a>
        <?php if ($laQuanLyKho): ?>
            <a class="btn" href="<?= $e($urlLichSu) ?>">Báo cáo đã gửi</a>
        <?php endif; ?>
    </nav>
</div>
<div class="card filter-card">
    <div class="card-head"><strong>Thông tin tra cứu</strong></div>
    <form method="GET" action="<?= $e($url) ?>">
        <input type="hidden" name="tab" value="direct">
        <input type="hidden" name="xemBaoCao" value="1">
        <div class="filter-grid uc30-filter">
            <div class="field">
                <label for="uc30-maKho">Mã kho<?= $laQuanLyKho ? '' : ' *' ?></label>
                <?php if ($laQuanLyKho): ?>
                    <?php $maCoDinh = (string)($khoPhuTrach['maKho'] ?? ''); ?>
                    <input id="uc30-maKho" class="uc30-fixed" type="text" disabled value="<?= $e($maCoDinh !== '' ? $maCoDinh . ' - ' . ($khoPhuTrach['tenKho'] ?? '') : 'Không xác định được kho phụ trách') ?>">
                    <input type="hidden" name="maKho" value="<?= $e($maCoDinh) ?>">
                <?php else: ?>
                    <select id="uc30-maKho" name="maKho" required>
                        <option value="">Chọn kho</option>
                        <?php foreach ($danhSachKho as $k): ?>
                            <option value="<?= $e($k['maKho']) ?>" <?= $maKho === $k['maKho'] ? 'selected' : '' ?>><?= $e($k['maKho'] . ' - ' . $k['tenKho']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="field">
                <label for="uc30-tuNgay">Thời gian bắt đầu</label>
                <input type="date" id="uc30-tuNgay" name="tuNgay" value="<?= $e($tuNgay) ?>">
            </div>
            <div class="field">
                <label for="uc30-denNgay">Thời gian kết thúc</label>
                <input type="date" id="uc30-denNgay" name="denNgay" value="<?= $e($denNgay) ?>">
            </div>
            <div class="filter-actions uc30-actions">
                <button type="submit" class="btn btn-primary" <?= !$danhSachKho ? 'disabled' : '' ?>>Xem báo cáo</button>
                <a class="btn" href="<?= $e($urlTrucTiep) ?>">Đặt lại</a>
            </div>
        </div>
    </form>
</div>
<?php if ($thongBao !== ''): ?>
    <div class="alert <?= $loi ? 'alert-danger' : 'alert-warning' ?>" role="alert"><?= $e($thongBao) ?></div>
<?php endif; ?>
<?php if ($daYeuCau && !$loi && is_array($baoCao)): ?>
    <?php if ($canhBao): ?>
        <div class="alert alert-warning uc30-warning" role="alert">
            <strong>Dữ liệu tồn kho cần đối chiếu.</strong>
            <div>Dấu — biểu thị số liệu chưa xác định được, không phải 0 kg.</div>
            <details>
                <summary>Xem chi tiết <?= count($canhBao) ?> cảnh báo</summary>
                <ul>
                    <?php foreach ($canhBao as $cb): ?>
                        <li><?= $e(($cb['maLoHang'] ?? '') . ' / ' . ($cb['maViTri'] ?? '') . ': ' . ($cb['noiDung'] ?? '')) ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        </div>
    <?php endif; ?>
    <div class="card">
        <div class="card-head"><strong>Báo cáo tồn kho</strong><span class="muted">Tổng số: <?= $tongSoHangHoa ?> hàng hóa</span></div>
        <?php $fields = [
            'Mã kho' => $baoCao['kho']['maKho'] ?? $maKho,
            'Tên kho' => $baoCao['kho']['tenKho'] ?? '—',
            'Bắt đầu kỳ báo cáo' => !empty($baoCao['thoiGianBatDau']) ? $date($baoCao['thoiGianBatDau']) : 'Toàn bộ lịch sử',
            'Kết thúc kỳ báo cáo' => $date($baoCao['thoiGianKetThuc'] ?? null),
        ]; ?>
        <div class="filter-grid uc30-info">
            <?php foreach ($fields as $label => $value): ?>
                <div class="field">
                    <label><?= $e($label) ?></label>
                    <input type="text" class="uc30-fixed" disabled aria-label="<?= $e($label) ?>" value="<?= $e($value) ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th class="w-stt">STT</th><th>Tên hàng</th><th>Đơn vị tính</th><th class="uc30-number">Tồn đầu kỳ</th><th class="uc30-number">Nhập trong kỳ</th><th class="uc30-number">Xuất trong kỳ</th><th class="uc30-number">Tồn cuối kỳ</th></tr></thead>
                <tbody>
                <?php if (!$danhSach): ?>
                    <tr><td colspan="7" class="empty-row">Không tìm thấy dữ liệu tồn kho phù hợp.</td></tr>
                <?php else: foreach ($danhSach as $i => $dong): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><?= $e($dong['tenHangHoa'] ?? '—') ?><?php if (!empty($dong['coCanhBao'])): ?> <span title="Số liệu cần đối chiếu" aria-label="Số liệu cần đối chiếu">⚠</span><?php endif; ?></td>
                        <td>kg</td>
                        <?php foreach ($cotSo as $cot): ?><td class="uc30-number"><?= $e($num($dong[$cot] ?? null)) ?></td><?php endforeach; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
                <?php if ($danhSach && is_array($tongCong)): ?>
                    <tfoot><tr class="uc30-totals"><td colspan="2">Tổng cộng (toàn bộ báo cáo)</td><td>kg</td>
                        <?php foreach ($cotSo as $cot): ?><td class="uc30-number"><?= $e($num($tongCong[$cot] ?? null)) ?></td><?php endforeach; ?>
                    </tr></tfoot>
                <?php endif; ?>
            </table>
        </div>
        <div class="card-head uc30-footer">
            <span class="muted">Hiển thị <?= $danhSach ? $offset + 1 : 0 ?> - <?= $danhSach ? $offset + count($danhSach) : 0 ?> / <?= $tongSoHangHoa ?> hàng hóa</span>
            <?php if ($tongSoTrang > 1): ?>
                <nav class="pagination" aria-label="Phân trang">
                    <?php if ($trang > 1): ?><a href="<?= $e($taoUrl($trang - 1)) ?>" aria-label="Trang trước">&laquo;</a>
                    <?php else: ?><button class="btn btn-sm" type="button" disabled aria-label="Trang trước">&laquo;</button><?php endif; ?>
                    <?php $pages = array_unique(array_merge([1], range(max(1,$trang-2), min($tongSoTrang,$trang+2)), [$tongSoTrang])); $last = 0; ?>
                    <?php foreach ($pages as $p): ?>
                        <?php if ($p > $last + 1): ?><span class="muted">…</span><?php endif; ?>
                        <?php if ($p === $trang): ?><span class="current" aria-current="page"><?= $p ?></span>
                        <?php else: ?><a href="<?= $e($taoUrl($p)) ?>"><?= $p ?></a><?php endif; $last = $p; ?>
                    <?php endforeach; ?>
                    <?php if ($trang < $tongSoTrang): ?><a href="<?= $e($taoUrl($trang + 1)) ?>" aria-label="Trang sau">&raquo;</a>
                    <?php else: ?><button class="btn btn-sm" type="button" disabled aria-label="Trang sau">&raquo;</button><?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($baoCao['danhSach'])): ?>
        <?php
        // Dữ liệu toàn kỳ, không lấy danh sách đã phân trang.
        $chartRows = [];
        foreach ($baoCao['danhSach'] as $dong) {
            $item = ['ten' => (string)($dong['tenHangHoa'] ?? '—')];
            foreach ($cotSo as $cot) {
                $v = $dong[$cot] ?? null;
                $item[$cot] = is_numeric($v) && is_finite((float)$v) ? (float)$v : null;
            }
            $chartRows[] = $item;
        }
        $chartJson = json_encode($chartRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        ?>
        <style>
        .uc30-page .uc30-charts { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; margin-top:16px; }
        .uc30-page .uc30-charts .card { min-width:0; margin:0; }
        .uc30-page .uc30-chart-scroll { overflow-x:auto; }
        .uc30-page .uc30-chart-bar { position:relative; height:340px; }
        .uc30-page .uc30-chart-pie { position:relative; height:360px; }
        .uc30-page .uc30-chart-wide { grid-column:1/-1; }
        .uc30-page .uc30-chart-note { margin-top:10px; font-size:13px; }
        @media(max-width:900px) { .uc30-page .uc30-charts { grid-template-columns:1fr; } }
        </style>
        <div class="uc30-charts" id="uc30-charts">
            <div class="card">
                <div class="card-head"><strong>Tồn đầu kỳ – Tồn cuối kỳ</strong></div>
                <div class="uc30-chart-scroll"><div class="uc30-chart-bar"><canvas id="uc30-stock-chart" role="img" aria-label="So sánh tồn đầu kỳ và tồn cuối kỳ theo hàng hóa"></canvas></div></div>
            </div>
            <div class="card">
                <div class="card-head"><strong>Nhập – Xuất trong kỳ</strong></div>
                <div class="uc30-chart-scroll"><div class="uc30-chart-bar"><canvas id="uc30-flow-chart" role="img" aria-label="So sánh nhập xuất trong kỳ theo hàng hóa"></canvas></div></div>
            </div>
            <div class="card uc30-chart-wide">
                <div class="card-head"><strong>Tỷ trọng tồn kho cuối kỳ</strong></div>
                <div class="uc30-chart-pie"><canvas id="uc30-share-chart" role="img" aria-label="Tỷ trọng khối lượng tồn cuối kỳ"></canvas></div>
                <div id="uc30-share-note" class="muted uc30-chart-note"></div>
            </div>
        </div>
        <p class="muted uc30-chart-note">Biểu đồ sử dụng toàn bộ hàng hóa trong kỳ báo cáo, không phụ thuộc trang đang xem. Số liệu chưa xác định không được thay bằng 0.</p>
        <p id="uc30-chart-error" class="alert alert-warning" role="alert" hidden></p>
        <script type="application/json" id="uc30-chart-data"><?= $chartJson ?></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>
        <script>
        (function () {
            const error = document.getElementById('uc30-chart-error');
            if (typeof Chart === 'undefined') {
                error.textContent = 'Không tải được thư viện biểu đồ. Vui lòng kiểm tra kết nối Internet. Bảng báo cáo vẫn sử dụng bình thường.';
                error.hidden = false;
                document.getElementById('uc30-charts').hidden = true;
                return;
            }
            const rows = JSON.parse(document.getElementById('uc30-chart-data').textContent);
            const labels = rows.map(r => r.ten);
            const fmt = new Intl.NumberFormat('vi-VN', {maximumFractionDigits: 6});
            const colors = ['#2563eb','#16a34a','#f59e0b','#9333ea','#0891b2','#dc2626','#64748b','#db2777','#65a30d','#4f46e5'];
            function bar(id, sets) {
                const canvas = document.getElementById(id);
                canvas.parentElement.style.minWidth = Math.max(320, rows.length * 78) + 'px';
                new Chart(canvas, {
                    type: 'bar',
                    data: {labels, datasets: sets.map(([label,key,color]) => ({label, data: rows.map(r => r[key]), backgroundColor: color, borderRadius: 3, maxBarThickness: 32}))},
                    options: {responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false},
                        scales:{y:{beginAtZero:true,title:{display:true,text:'kg'},ticks:{callback:v=>fmt.format(v)}},x:{ticks:{autoSkip:false,maxRotation:60,minRotation:0}}},
                        plugins:{legend:{position:'bottom'},tooltip:{callbacks:{label:c=>c.dataset.label+': '+(c.raw===null?'Chưa xác định':fmt.format(c.raw)+' kg')}}}}
                });
            }
            bar('uc30-stock-chart', [['Tồn đầu kỳ','tonDauKy','#64748b'],['Tồn cuối kỳ','tonCuoiKy','#2563eb']]);
            bar('uc30-flow-chart', [['Nhập trong kỳ','nhapTrongKy','#16a34a'],['Xuất trong kỳ','xuatTrongKy','#dc2626']]);
            const note = document.getElementById('uc30-share-note');
            const canvas = document.getElementById('uc30-share-chart');
            const invalid = rows.some(r=>r.tonCuoiKy===null || r.tonCuoiKy<0);
            const shares = rows.filter(r=>r.tonCuoiKy!==null && r.tonCuoiKy>0);
            const total = shares.reduce((s,r)=>s+r.tonCuoiKy,0);
            if (invalid || total<=0) {
                canvas.parentElement.style.display='none';
                note.textContent = invalid ? 'Không thể tính tỷ trọng toàn kho vì có tồn cuối kỳ chưa xác định hoặc âm. Vui lòng đối chiếu dữ liệu.' : 'Không có tồn cuối kỳ dương để tính tỷ trọng.';
                return;
            }
            new Chart(canvas,{type:'doughnut',data:{labels:shares.map(r=>r.ten),datasets:[{data:shares.map(r=>r.tonCuoiKy),backgroundColor:shares.map((r,i)=>colors[i%colors.length]),borderWidth:1}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'right'},tooltip:{callbacks:{label:c=>c.label+': '+fmt.format(c.raw)+' kg ('+fmt.format(c.raw/total*100)+'%)'}}}}});
            note.textContent='Tổng tồn cuối kỳ: '+fmt.format(total)+' kg. Các mặt hàng tồn 0 kg không xuất hiện trên biểu đồ tỷ trọng.';
        })();
        </script>
    <?php endif; ?>
    <?php if ($laQuanLyKho && !empty($baoCao['danhSach'])): ?>
        <?php
        $homNay = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
        $kyTuongLai = ($tuNgay !== '' && $tuNgay > $homNay) || ($denNgay !== '' && $denNgay > $homNay);
        $choPhepGui = $coTheGui && $maXacNhanGui !== '' && !$kyTuongLai;
        ?>
        <div class="card" style="margin-top:16px">
            <div class="uc30-actions" style="justify-content:flex-end">
                <?php if (!$choPhepGui): ?>
                    <span class="muted"><?= $kyTuongLai ? 'Không gửi báo cáo có kỳ trong tương lai.' : 'Chưa thể gửi báo cáo. Vui lòng kiểm tra cảnh báo hoặc xem lại báo cáo.' ?></span>
                <?php endif; ?>
                <button type="button" class="btn btn-primary" id="uc30-open-send" <?= !$choPhepGui ? 'disabled' : '' ?>>Gửi báo cáo</button>
            </div>
        </div>
        <?php if ($choPhepGui): ?>
            <dialog id="uc30-send-dialog" aria-labelledby="uc30-send-title" style="width:min(640px, calc(100% - 32px));max-width:calc(100% - 32px);max-height:90vh;box-sizing:border-box;padding:0;border:0;border-radius:16px;overflow-x:hidden;overflow-y:auto;margin:auto;box-shadow:0 16px 48px rgba(15,23,42,.22)">
                <div class="card" style="margin:0;border:0;border-radius:16px;box-shadow:none;min-width:0;box-sizing:border-box">
                <div class="card-head"><strong id="uc30-send-title">Xác nhận gửi báo cáo</strong></div>
                <form method="POST" action="<?= $e($url . '/send') ?>" id="uc30-send-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="maXacNhanGui" value="<?= $e($maXacNhanGui) ?>">
                    <div style="width:100%;min-width:0">
                        <table class="table" style="width:100%;min-width:0;max-width:100%;table-layout:fixed;white-space:normal"><tbody>
                            <?php $thongTinGui = [
                                'Kho báo cáo' => $baoCao['kho']['tenKho'] ?? '—',
                                'Bắt đầu kỳ báo cáo' => !empty($baoCao['thoiGianBatDau']) ? $date($baoCao['thoiGianBatDau']) : 'Toàn bộ lịch sử',
                                'Kết thúc kỳ báo cáo' => $date($baoCao['thoiGianKetThuc'] ?? null),
                                'Người nhận' => 'Ban giám đốc',
                                'Số mặt hàng' => count($baoCao['danhSach'])
                            ]; ?>
                            <?php foreach ($thongTinGui as $label => $value): ?>
                                <tr><th scope="row" style="width:35%;white-space:normal;overflow-wrap:anywhere"><?= $e($label) ?></th><td style="white-space:normal;overflow-wrap:anywhere"><?= $e($value) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody></table>
                    </div>
                    <div class="field" style="margin-top:16px">
                        <label for="uc30-ghiChu">Ghi chú (không bắt buộc)</label>
                        <textarea id="uc30-ghiChu" name="ghiChu" rows="3" maxlength="500" placeholder="Nhập nội dung cần lưu ý..." style="width:100%;box-sizing:border-box"></textarea>
                    </div>
                    <p class="muted">Toàn bộ số liệu đang xem sẽ được lưu khi gửi. Báo cáo đã gửi không thể chỉnh sửa. Phiên gửi có hiệu lực 30 phút.</p>
                    <div class="uc30-actions" style="justify-content:flex-end;margin-top:16px">
                        <button type="button" class="btn" id="uc30-cancel-send">Hủy</button>
                        <button type="submit" class="btn btn-primary" id="uc30-confirm-send">Xác nhận gửi</button>
                    </div>
                </form>
                </div>
            </dialog>
            <script>
            (function () {
                const dialog = document.getElementById('uc30-send-dialog');
                const form = document.getElementById('uc30-send-form');
                const confirm = document.getElementById('uc30-confirm-send');
                const cancel = document.getElementById('uc30-cancel-send');
                document.getElementById('uc30-open-send').addEventListener('click', function () {
                    if (!dialog.open) dialog.showModal();
                });
                cancel.addEventListener('click', function () { dialog.close(); });
                form.addEventListener('submit', function (event) {
                    if (confirm.disabled) { event.preventDefault(); return; }
                    confirm.disabled = true;
                    cancel.disabled = true;
                    confirm.textContent = 'Đang gửi...';
                });
                window.addEventListener('pageshow', function (event) {
                    if (event.persisted) {
                        confirm.disabled = false;
                        cancel.disabled = false;
                        confirm.textContent = 'Xác nhận gửi';
                        if (dialog.open) dialog.close();
                    }
                });
            })();
            </script>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
</div>
