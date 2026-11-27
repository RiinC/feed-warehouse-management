<?php
/**
 * View ton-kho/index — UC29: Tra cứu tồn kho.
 * Biến từ TonKhoController::index():
 *   $f, $mode, $scope, $rows, $total, $page, $perPage,
 *   $kho, $khuVucTheoKho, $viTriList, $loaiHang, $mucHan.
 */
$badgeHSD = function (?string $muc): string {
    if ($muc === 'Đã hết hạn') {
        return 'badge badge-danger';
    }
    if ($muc === 'Còn ≤ 7 ngày' || $muc === 'Còn ≤ 30 ngày') {
        return 'badge badge-warn';
    }
    if ($muc === 'Còn ≤ 90 ngày') {
        return 'badge badge-info';
    }
    return 'badge badge-ok';
};

$baseFilterParams = array_filter([
    'q'           => $f['q'] ?? '',
    'maKho'       => $scope === null ? ($f['maKho'] ?? '') : '',
    'maKhuVuc'    => $f['maKhuVuc'] ?? '',
    'maViTri'     => $f['maViTri'] ?? '',
    'maHangHoa'   => $f['maHangHoa'] ?? '',
    'loaiHangHoa' => $f['loaiHangHoa'] ?? '',
    'hanSuDung'   => $f['hanSuDung'] ?? '',
    'conHang'     => $f['conHang'] ?? '',
], static fn($v) => $v !== '' && $v !== null);

// Bộ lọc nâng cao (Hạn sử dụng, Trạng thái tồn) mở sẵn khi đang có giá trị.
$moreFilterOpen = ($f['hanSuDung'] ?? '') !== '' || ($f['conHang'] ?? '') !== '';
?>

<style>
.filter-card {
    padding: 12px 16px !important;
    margin-bottom: 14px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: var(--radius);
}
.filter-row {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 125px;
    flex: 1 1 135px;
}
.filter-group.filter-search {
    flex: 1.8 1 210px;
    min-width: 185px;
}
.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #344054;
    white-space: nowrap;
}
.filter-group input,
.filter-group select {
    height: 36px;
    font-size: 13px;
    padding: 0 10px;
    border: 1px solid #d0d5dd;
    border-radius: 6px;
    background: #fff;
    box-sizing: border-box;
    width: 100%;
}
.filter-buttons {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    gap: 8px;
    height: 36px;
}
.filter-buttons .btn-icon {
    width: 36px;
    height: 36px;
    min-width: 36px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    cursor: pointer;
}
.filter-buttons .btn-ghost {
    background: #ffffff;
    border: 1px solid #d0d5dd;
    color: #475467;
}
.filter-buttons .btn-ghost:hover {
    background: #f8fafc;
    border-color: #98a2b3;
    color: #1d2939;
}
.filter-buttons .btn-ghost.active,
.filter-buttons .btn-ghost[aria-expanded="true"] {
    background: #eff6ff;
    border-color: #2563eb;
    color: #2563eb;
}

/* Bộ lọc nâng cao: ẩn mặc định, bấm nút icon cái lọc để mở */
.filter-more {
    display: none;
    gap: 10px;
    align-items: flex-end;
    flex-wrap: wrap;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #e2e8f0;
}
.filter-more.open { display: flex; }

.tk-master-card {
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: var(--radius);
}
.tk-master-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    padding: 12px 18px;
    border-bottom: 1px solid var(--line);
    background: #fafbfd;
}

#tkTable {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}
#tkTable th {
    background: #fafbfd;
    font-size: 12.5px;
    font-weight: 600;
    color: #475467;
    padding: 11px 14px;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
}
#tkTable td {
    padding: 11px 14px;
    font-size: 13px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
}
#tkTable tbody tr.clickable-row {
    cursor: pointer;
    transition: background .12s ease;
}
#tkTable tbody tr.clickable-row:hover {
    background: #f8fbff !important;
}
.tk-act {
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    color: #64748b;
    text-decoration: none;
    transition: all .12s ease;
}
.tk-act:hover {
    background: #f1f5f9;
    color: #2563eb;
}
.tk-pagination-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    padding: 10px 18px;
    background: #fafbfd;
    border-top: 1px solid var(--line);
}
</style>

<!-- 1. BỘ LỌC TRA CỨU -->
<form class="card filter-card" method="get" action="<?= e(route('ton-kho')) ?>">
    <?php if (!config('url_rewrite', true) && isset($_GET['r'])): ?>
        <input type="hidden" name="r" value="<?= e((string)$_GET['r']) ?>">
    <?php endif; ?>
    <input type="hidden" name="mode" value="<?= e($mode) ?>">
    <?php if (!empty($f['maHangHoa'])): ?>
        <input type="hidden" name="maHangHoa" value="<?= e($f['maHangHoa']) ?>">
    <?php endif; ?>

    <div class="filter-row">
        <div class="filter-group filter-search">
            <label for="f_q">Tìm kiếm</label>
            <input id="f_q" type="text" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="Mã hàng, tên hàng, mã lô, vị trí...">
        </div>

        <?php if ($scope === null): ?>
            <div class="filter-group">
                <label for="f_maKho">Kho</label>
                <select id="f_maKho" name="maKho">
                    <option value="">Tất cả kho</option>
                    <?php foreach ($kho as $kId => $kName): ?>
                        <option value="<?= e($kId) ?>" <?= ($f['maKho'] ?? '') === $kId ? 'selected' : '' ?>><?= e($kName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="filter-group">
            <label for="f_maKhuVuc">Khu vực</label>
            <select id="f_maKhuVuc" name="maKhuVuc">
                <option value="">Tất cả khu vực</option>
                <?php foreach ($khuVucTheoKho as $kId => $kvList): ?>
                    <?php if ($scope !== null && $kId !== $scope) { continue; } ?>
                    <?php foreach ($kvList as $kvId => $kvName): ?>
                        <option value="<?= e($kvId) ?>" data-kho="<?= e($kId) ?>" <?= ($f['maKhuVuc'] ?? '') === $kvId ? 'selected' : '' ?>>
                            <?= e($kvName) ?><?= $scope === null && isset($kho[$kId]) ? ' (' . e($kho[$kId]) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($mode !== 'product'): ?>
            <div class="filter-group">
                <label for="f_maViTri">Vị trí</label>
                <select id="f_maViTri" name="maViTri">
                    <option value="">Tất cả vị trí</option>
                    <?php foreach ($viTriList as $vt): ?>
                        <option value="<?= e($vt['maViTri']) ?>"
                                data-kho="<?= e($vt['maKho']) ?>"
                                data-khuvuc="<?= e($vt['maKhuVuc']) ?>"
                                <?= ($f['maViTri'] ?? '') === $vt['maViTri'] ? 'selected' : '' ?>>
                            <?= e($vt['tenViTri']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if ($mode !== 'history'): ?>
            <div class="filter-group">
                <label for="f_loaiHangHoa">Loại hàng</label>
                <select id="f_loaiHangHoa" name="loaiHangHoa">
                    <option value="">Tất cả loại</option>
                    <?php foreach ($loaiHang as $lhCode => $lhLabel): ?>
                        <option value="<?= e($lhCode) ?>" <?= ($f['loaiHangHoa'] ?? '') === $lhCode ? 'selected' : '' ?>><?= e($lhLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="filter-buttons">
            <?php if ($mode !== 'history'): ?>
                <button type="button" id="tkFilterToggle"
                        class="btn btn-ghost btn-icon<?= $moreFilterOpen ? ' active' : '' ?>"
                        title="Bộ lọc nâng cao: hạn sử dụng, trạng thái tồn"
                        aria-controls="tkMoreFilters" aria-expanded="<?= $moreFilterOpen ? 'true' : 'false' ?>">
                    <?= icon('filter', 16) ?>
                </button>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary btn-icon" title="Tìm kiếm">
                <?= icon('search', 16) ?>
            </button>
            <a href="<?= e(route('ton-kho', ['mode' => $mode])) ?>" class="btn btn-ghost btn-icon" title="Đặt lại bộ lọc">
                <?= icon('repeat', 16) ?>
            </a>
        </div>
    </div>

    <?php if ($mode !== 'history'): ?>
        <div class="filter-more<?= $moreFilterOpen ? ' open' : '' ?>" id="tkMoreFilters">
            <div class="filter-group">
                <label for="f_hanSuDung">Hạn sử dụng</label>
                <select id="f_hanSuDung" name="hanSuDung">
                    <option value="">Tất cả hạn</option>
                    <?php foreach ($mucHan as $hKey => $hLabel): ?>
                        <option value="<?= e($hKey) ?>" <?= ($f['hanSuDung'] ?? '') === $hKey ? 'selected' : '' ?>><?= e($hLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="f_conHang">Trạng thái tồn</label>
                <select id="f_conHang" name="conHang">
                    <option value="">Tất cả</option>
                    <option value="1" <?= ($f['conHang'] ?? '') === '1' ? 'selected' : '' ?>>Còn tồn (&gt; 0)</option>
                    <option value="kha-dung" <?= ($f['conHang'] ?? '') === 'kha-dung' ? 'selected' : '' ?>>Còn khả dụng (&gt; 0)</option>
                    <option value="da-giu" <?= ($f['conHang'] ?? '') === 'da-giu' ? 'selected' : '' ?>>Đang giữ cho đơn</option>
                    <option value="het-ton" <?= ($f['conHang'] ?? '') === 'het-ton' ? 'selected' : '' ?>>Đã hết tồn (= 0)</option>
                </select>
            </div>
        </div>
    <?php endif; ?>
</form>

<!-- 2. BẢNG KẾT QUẢ TRA CỨU TỒN KHO -->
<div class="tk-master-card">
    <div class="tk-master-head">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <h2 style="font-size: 14px; font-weight: 700; margin: 0; color: #1e293b;">Danh sách tồn kho</h2>
            <?php if (!empty($f['maHangHoa'])): ?>
                <span class="badge badge-info">
                    Mặt hàng: <?= e($f['maHangHoa']) ?>
                    <a href="<?= e(route('ton-kho', array_diff_key($baseFilterParams, ['maHangHoa' => 1]) + ['mode' => $mode])) ?>" style="margin-left: 6px; color: inherit; text-decoration: none;" title="Bỏ lọc mặt hàng">&times;</a>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap" style="overflow-x: auto; margin: 0;">
        <?php if ($mode === 'product'): ?>
            <table class="table" id="tkTable">
                <thead>
                    <tr>
                        <th style="width: 48px;">STT</th>
                        <th>Hàng hóa</th>
                        <th>Loại</th>
                        <?php if ($scope === null): ?><th>Kho</th><?php endif; ?>
                        <th class="num">Số lô</th>
                        <th class="num">Số vị trí</th>
                        <th class="num">Tổng tồn</th>
                        <th class="num">Đang giữ</th>
                        <th class="num">Khả dụng</th>
                        <th>HSD gần nhất</th>
                        <th style="text-align: center; width: 96px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="<?= $scope === null ? 11 : 10 ?>" class="empty-row" style="text-align: center; color: var(--muted); padding: 36px 0;">
                            Không có mặt hàng nào khớp bộ lọc.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $idx => $r):
                        $lotFilterUrl = route('ton-kho', [
                            'mode'      => 'lot',
                            'maKho'     => $scope === null ? $r['maKho'] : '',
                            'maHangHoa' => $r['maHangHoa'],
                        ]);
                    ?>
                        <tr class="clickable-row" onclick="window.location='<?= e($lotFilterUrl) ?>'">
                            <td><?= ($page - 1) * $perPage + $idx + 1 ?></td>
                            <td><?= e($r['tenHangHoa']) ?></td>
                            <td><?= e(product_type_label($r['loaiHangHoa'])) ?></td>
                            <?php if ($scope === null): ?>
                                <td><?= e($r['tenKho']) ?></td>
                            <?php endif; ?>
                            <td class="num"><?= fmt_number($r['soLo']) ?></td>
                            <td class="num"><?= fmt_number($r['soViTri']) ?></td>
                            <td class="num"><?= fmt_number($r['soLuongTon'], 2) ?> <?= e($r['donViTinhChuan']) ?></td>
                            <td class="num"><?= fmt_number($r['soLuongDaGiu'], 2) ?></td>
                            <td class="num">
                                <?= (float)$r['soLuongKhaDung'] > 0
                                    ? '<span style="color:#15803d;">' . fmt_number($r['soLuongKhaDung'], 2) . '</span>'
                                    : '<span class="diff-minus">0</span>' ?>
                            </td>
                            <td class="nowrap"><?= !empty($r['ngayHetHanGanNhat']) ? e(fmt_date($r['ngayHetHanGanNhat'])) : '—' ?></td>
                            <td style="text-align: center;" onclick="event.stopPropagation()">
                                <a class="btn btn-sm" href="<?= e($lotFilterUrl) ?>">Xem các lô</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($mode === 'history'): ?>
            <table class="table" id="tkTable">
                <thead>
                    <tr>
                        <th style="width: 48px;">STT</th>
                        <th>Thời gian</th>
                        <th>Loại giao dịch</th>
                        <th>Hàng hóa</th>
                        <th>Mã lô</th>
                        <th>Vị trí</th>
                        <th class="num">Biến động</th>
                        <th class="num">Tồn trước &rarr; Sau</th>
                        <th>Chứng từ</th>
                        <th>Người thực hiện</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="10" class="empty-row" style="text-align: center; color: var(--muted); padding: 36px 0;">
                            Chưa có biến động tồn kho nào phù hợp bộ lọc.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $idx => $r):
                        $delta = (float)$r['soLuongBienDong'];
                        $deltaSign = $delta > 0 ? '+' : '';
                        $deltaColor = $delta > 0 ? '#15803d' : ($delta < 0 ? '#b91c1c' : '#475469');
                    ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td class="nowrap"><?= e(fmt_datetime($r['thoiGianPhatSinh'])) ?></td>
                            <td><?= stock_tx_badge($r['loaiGiaoDich']) ?></td>
                            <td><?= e($r['tenHangHoa']) ?></td>
                            <td class="nowrap"><?= e($r['maLoHang']) ?></td>
                            <td><?= e($r['tenViTri']) ?><?= $scope === null ? ' · ' . e($r['tenKho']) : '' ?></td>
                            <td class="num nowrap" style="font-weight: 700; color: <?= $deltaColor ?>;">
                                <?= $deltaSign . fmt_number($delta, 2) ?> <?= e($r['donViTinhChuan']) ?>
                            </td>
                            <td class="num nowrap">
                                <?= fmt_number($r['soLuongTonTruoc'], 2) ?> &rarr; <?= fmt_number($r['soLuongTonSau'], 2) ?>
                            </td>
                            <td class="nowrap"><?= e($r['maChungTuLienQuan']) ?></td>
                            <td class="nowrap"><?= e($r['hoTen']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

        <?php else: ?>
            <?php
            $sortUrl = function (string $key) use ($baseFilterParams, $f): string {
                $nextDir = (($f['sort'] ?? '') === $key && ($f['dir'] ?? '') === 'asc') ? 'desc' : 'asc';
                return route('ton-kho', $baseFilterParams + ['mode' => 'lot', 'sort' => $key, 'dir' => $nextDir]);
            };
            $sortIcon = function (string $key) use ($f): string {
                if (($f['sort'] ?? '') !== $key) return ' ↕';
                return ($f['dir'] ?? '') === 'desc' ? ' ▼' : ' ▲';
            };
            ?>
            <table class="table" id="tkTable">
                <thead>
                    <tr>
                        <th style="width: 140px;">Mã lô</th>
                        <th><a class="sort-link" href="<?= e($sortUrl('hang')) ?>">Hàng hóa<?= $sortIcon('hang') ?></a></th>
                        <th><a class="sort-link" href="<?= e($sortUrl('hsd')) ?>">Hạn sử dụng<?= $sortIcon('hsd') ?></a></th>
                        <th>Vị trí lưu trữ</th>
                        <th class="num"><a class="sort-link" href="<?= e($sortUrl('ton')) ?>">Tồn<?= $sortIcon('ton') ?></a></th>
                        <th class="num"><a class="sort-link" href="<?= e($sortUrl('kd')) ?>">Khả dụng<?= $sortIcon('kd') ?></a></th>
                        <th style="text-align: center; width: 72px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="empty-row" style="text-align: center; color: var(--muted); padding: 36px 0;">
                            Không có dòng tồn kho nào khớp bộ lọc.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r):
                        $detailUrl = route('ton-kho/detail', ['id' => $r['maTonKho']]);
                        $qcNotOk = !empty($r['trangThaiQC']) && !in_array($r['trangThaiQC'], ['Đạt', 'Đã đạt chuẩn'], true);
                    ?>
                        <tr class="clickable-row" onclick="window.location='<?= e($detailUrl) ?>'">
                            <td class="nowrap">
                                <a href="<?= e($detailUrl) ?>" onclick="event.stopPropagation();" style="font-weight: 600; color: #2563eb; text-decoration: none;"><?= e($r['maLoHang']) ?></a>
                            </td>
                            <td><?= e($r['tenHangHoa']) ?></td>
                            <td class="nowrap">
                                <span class="<?= $badgeHSD($r['mucHanSuDung']) ?>"><?= e($r['mucHanSuDung']) ?></span>
                                <?php if ($qcNotOk): ?>
                                    <span class="badge badge-danger" style="margin-left: 4px;">QC: <?= e($r['trangThaiQC']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($r['tenViTri']) ?><?= $scope === null ? ' · ' . e($r['tenKho']) : '' ?></td>
                            <td class="num nowrap"><?= fmt_number($r['soLuongTon'], 2) ?> <?= e($r['donViTinhChuan']) ?></td>
                            <td class="num nowrap">
                                <?= (float)$r['soLuongKhaDung'] > 0
                                    ? '<span style="color:#15803d;">' . fmt_number($r['soLuongKhaDung'], 2) . '</span>'
                                    : '<span class="diff-minus">0</span>' ?>
                            </td>
                            <td style="text-align: center;" onclick="event.stopPropagation()">
                                <a class="tk-act" href="<?= e($detailUrl) ?>" title="Xem chi tiết"><?= icon('eye', 16) ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="tk-pagination-footer">
        <div style="font-size: 13px; color: #64748b;">
            <?php
            $from = $total > 0 ? ($page - 1) * $perPage + 1 : 0;
            $to = $mode === 'history' ? $total : min($total, ($page - 1) * $perPage + count($rows));
            ?>
            Hiển thị <b><?= $from ?> - <?= $to ?></b> trong tổng số <b><?= $total ?></b> bản ghi
        </div>
        <?php if ($mode !== 'history'): ?>
            <?php render_pagination($page, $total, $perPage); ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    // Nút icon "cái lọc": mở/đóng 2 bộ lọc nâng cao (hạn sử dụng, trạng thái tồn).
    var filterToggle = document.getElementById('tkFilterToggle');
    var moreFilters  = document.getElementById('tkMoreFilters');
    if (filterToggle && moreFilters) {
        var setOpen = function (open) {
            moreFilters.classList.toggle('open', open);
            filterToggle.classList.toggle('active', open);
            filterToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        setOpen(moreFilters.classList.contains('open'));
        filterToggle.addEventListener('click', function () {
            setOpen(!moreFilters.classList.contains('open'));
        });
    }

    var selKho = document.getElementById('f_maKho');
    var selKv  = document.getElementById('f_maKhuVuc');
    var selVt  = document.getElementById('f_maViTri');
    if (!selKv) return;

    function filterAreas() {
        var khoVal = selKho ? selKho.value : '';
        var needReset = false;
        Array.prototype.forEach.call(selKv.options, function (opt, i) {
            if (i === 0) return;
            var optKho = opt.getAttribute('data-kho') || '';
            var hide = khoVal !== '' && optKho !== khoVal;
            opt.hidden = hide;
            opt.disabled = hide;
            if (hide && opt.selected) needReset = true;
        });
        if (needReset) selKv.value = '';
        filterPositions();
    }

    function filterPositions() {
        if (!selVt) return;
        var khoVal = selKho ? selKho.value : '';
        var kvVal  = selKv.value;
        var needReset = false;
        Array.prototype.forEach.call(selVt.options, function (opt, i) {
            if (i === 0) return;
            var optKho = opt.getAttribute('data-kho') || '';
            var optKv  = opt.getAttribute('data-khuvuc') || '';
            var hide = (khoVal !== '' && optKho !== khoVal) || (kvVal !== '' && optKv !== kvVal);
            opt.hidden = hide;
            opt.disabled = hide;
            if (hide && opt.selected) needReset = true;
        });
        if (needReset) selVt.value = '';
    }

    if (selKho) selKho.addEventListener('change', filterAreas);
    selKv.addEventListener('change', filterPositions);
    filterAreas();
})();
</script>
