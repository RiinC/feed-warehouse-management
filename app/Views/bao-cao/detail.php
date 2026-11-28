<?php
/** UC30 - Chi tiết báo cáo đã gửi. Chỉ đọc bản chụp JSON từ Controller. */
$e = static fn($v): string => htmlspecialchars(is_scalar($v) ? (string)$v : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$num = static fn($v): string => is_numeric($v) && is_finite((float)$v) ? rtrim(rtrim(number_format((float)$v, 6, ',', '.'), '0'), ',') : '—';
$date = static function ($v): string {
    if (!is_string($v) || $v === '') return '—';
    try {
        return (new DateTimeImmutable($v, new DateTimeZone('Asia/Ho_Chi_Minh')))->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('d/m/Y H:i:s');
    } catch (Exception $e) { return '—'; }
};
$url = '/feed-warehouse-management/public/bao-cao';
$urlLichSu = $url . '/history';
$urlTrucTiep = $url . '?tab=direct';
$chiTiet = $chiTiet ?? [];
$baoCao = $baoCao ?? [];
$danhSach = $danhSach ?? [];
$tongCong = $tongCong ?? [];
$laQuanLyKho = (bool)($laQuanLyKho ?? false);
$maBaoCao = (string)($chiTiet['maBaoCao'] ?? '');
$canDanhDau = !$laQuanLyKho && !empty($canDanhDau) && ($chiTiet['trangThai'] ?? '') === 'Chưa xem' && preg_match('/\ABC[0-9]{6}\z/', $maBaoCao) === 1;
$daThuDanhDau = ($_GET['readAttempt'] ?? '') === '1';
$urlChiTiet = $url . '/detail?' . http_build_query(['maBaoCao'=>$maBaoCao, 'readAttempt'=>'1']);
$cotSo = ['tonDauKy','nhapTrongKy','xuatTrongKy','tonCuoiKy'];
$fields = [
    'Mã báo cáo' => $maBaoCao,
    'Tên báo cáo' => $chiTiet['tenBaoCao'] ?? '—',
    'Mã kho' => $chiTiet['maKho'] ?? '—',
    'Tên kho' => $chiTiet['tenKho'] ?? '—',
    'Người gửi' => $chiTiet['tenNhanVienGui'] ?? '—',
    'Bắt đầu kỳ báo cáo' => !empty($baoCao['thoiGianBatDau']) ? $date($baoCao['thoiGianBatDau']) : 'Toàn bộ lịch sử',
    'Kết thúc kỳ báo cáo' => $date($baoCao['thoiGianKetThuc'] ?? null),
    'Thời gian gửi' => $date($chiTiet['thoiGianGui'] ?? null),
    'Người nhận' => 'Ban giám đốc',
    'Số mặt hàng' => count($danhSach),
    'Trạng thái' => $chiTiet['trangThai'] ?? '—',
    'Ghi chú' => trim((string)($chiTiet['ghiChu'] ?? '')) !== '' ? $chiTiet['ghiChu'] : '—',
];
?>
<div class="uc30-page">
<div class="card">
    <nav class="filter-actions" aria-label="Chức năng báo cáo tồn kho">
        <?php if ($laQuanLyKho): ?>
            <a class="btn" href="<?= $e($urlTrucTiep) ?>">Xem báo cáo tồn kho</a>
            <a class="btn btn-primary" href="<?= $e($urlLichSu) ?>">Báo cáo đã gửi</a>
        <?php else: ?>
            <a class="btn btn-primary" href="<?= $e($urlLichSu) ?>">Báo cáo đã nhận</a>
            <a class="btn" href="<?= $e($urlTrucTiep) ?>">Xem báo cáo trực tiếp</a>
        <?php endif; ?>
    </nav>
</div>
<div class="card">
    <div class="card-head"><strong>Thông tin báo cáo</strong></div>
    <div class="table-wrap">
        <table class="table"><tbody>
        <?php foreach ($fields as $label => $value): ?>
            <tr><th scope="row" style="width:35%"><?= $e($label) ?></th><td style="white-space:pre-wrap;overflow-wrap:anywhere"><?= $e($value) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
    <p class="muted">Số liệu được lưu tại thời điểm gửi báo cáo, không tính lại từ tồn kho hiện tại.</p>
    <?php if ($canDanhDau): ?>
        <form id="uc30-read-form" method="POST" action="<?= $e($url . '/read') ?>" data-reload="<?= $e($urlChiTiet) ?>" data-auto="<?= $daThuDanhDau ? '0' : '1' ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="maBaoCao" value="<?= $e($maBaoCao) ?>">
            <p id="uc30-read-note" class="muted" role="status"><?= $daThuDanhDau ? 'Chưa ghi nhận được trạng thái đã xem. Bạn có thể thử lại.' : 'Hệ thống sẽ tự động ghi nhận trạng thái đã xem.' ?></p>
            <button type="submit" id="uc30-read-button" class="btn btn-sm">Đánh dấu đã xem</button>
        </form>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-head"><strong>Chi tiết báo cáo tồn kho</strong><span class="muted">Tổng số: <?= count($danhSach) ?> hàng hóa</span></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th class="w-stt">STT</th><th>Tên hàng</th><th>Đơn vị tính</th><th style="text-align:right">Tồn đầu kỳ</th><th style="text-align:right">Nhập trong kỳ</th><th style="text-align:right">Xuất trong kỳ</th><th style="text-align:right">Tồn cuối kỳ</th></tr></thead>
        <tbody>
        <?php if (!$danhSach): ?>
            <tr><td colspan="7" class="empty-row">Không có dữ liệu báo cáo.</td></tr>
        <?php else: foreach (array_values($danhSach) as $i => $dong): ?>
            <tr>
                <td><?= $i + 1 ?></td><td><?= $e($dong['tenHangHoa'] ?? '—') ?><?php if (!empty($dong['coCanhBao'])): ?> <span title="Số liệu cần đối chiếu">⚠</span><?php endif; ?></td><td>kg</td>
                <?php foreach ($cotSo as $cot): ?><td style="text-align:right;white-space:nowrap"><?= $e($num($dong[$cot] ?? null)) ?></td><?php endforeach; ?>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
        <?php if ($danhSach && is_array($tongCong)): ?>
            <tfoot><tr><th colspan="2">Tổng cộng (toàn bộ báo cáo)</th><th>kg</th>
                <?php foreach ($cotSo as $cot): ?><th style="text-align:right;white-space:nowrap"><?= $e($num($tongCong[$cot] ?? null)) ?></th><?php endforeach; ?>
            </tr></tfoot>
        <?php endif; ?>
    </table></div>
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
        <p class="muted uc30-chart-note">Biểu đồ sử dụng toàn bộ số liệu trong báo cáo đã lưu. Số liệu chưa xác định không được thay bằng 0.</p>
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
<div class="filter-actions" style="display:flex;justify-content:flex-end;margin-top:20px;margin-bottom:20px">
    <a class="btn" href="<?= $e($urlLichSu) ?>">Quay lại danh sách</a>
</div>
<?php if ($canDanhDau): ?>
<script>
(function () {
    const form = document.getElementById('uc30-read-form');
    const button = document.getElementById('uc30-read-button');
    const note = document.getElementById('uc30-read-note');
    let pending = false;
    async function markRead() {
        if (pending) return;
        pending = true;
        button.disabled = true;
        note.textContent = 'Đang ghi nhận trạng thái đã xem...';
        try {
            // Controller hiện tại dùng POST-redirect-GET về history.
            // Không theo redirect đó: mở lại detail để kiểm tra trạng thái thực từ JSON.
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form),
                credentials: 'same-origin', redirect: 'manual', cache: 'no-store'
            });
            if (response.type !== 'opaqueredirect' && !(response.status >= 300 && response.status < 400)) {
                throw new Error('Không nhận được phản hồi chuyển hướng hợp lệ.');
            }
            window.location.replace(form.dataset.reload);
        } catch (error) {
            pending = false;
            button.disabled = false;
            note.textContent = 'Không thể xác nhận trạng thái đã xem. Vui lòng thử lại.';
        }
    }
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        markRead();
    });
    if (form.dataset.auto === '1') markRead();
})();
</script>
<?php endif; ?>
</div>
