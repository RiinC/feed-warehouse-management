<?php
/**
 * View hang-bat-thuong/index — UC28: Danh sách hàng bất thường.
 * Thiết kế giao diện phẳng, tinh gọn chuẩn MISA CukCuk / FeedWare đồng bộ với Kiểm kê kho:
 * - 1. Bộ lọc ngang tinh gọn trên 1 dòng duy nhất
 * - 2. Bảng Master: Danh sách hàng bất thường kèm phân trang chuẩn
 * - 3. Modal Quét lô sắp / đã hết hạn gọn gàng, tiện lợi
 */

$badgeTT = [
    'Đang cách ly' => 'badge badge-warn',
    'Đang xử lý'   => 'badge badge-info',
    'Đã xử lý'     => 'badge badge-ok',
];
$badgeLoai = [
    'Rách vỡ' => 'badge badge-warn',
    'Hư hỏng' => 'badge badge-danger',
    'Hết hạn' => 'badge badge-danger',
];
?>

<style>
/* 1. Bộ lọc ngang tinh gọn trên 1 dòng duy nhất */
.filter-card {
    padding: 12px 18px !important;
    margin-bottom: 14px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: var(--radius);
}
.filter-row {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    flex-wrap: nowrap;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #344054;
    white-space: nowrap;
}
.filter-group input, .filter-group select {
    height: 36px;
    font-size: 13px;
    padding: 0 10px;
    border: 1px solid #d0d5dd;
    border-radius: 6px;
    background: #fff;
    box-sizing: border-box;
}
.filter-search {
    flex: 2 1 200px;
    min-width: 170px;
}
.filter-select {
    flex: 1.2 1 140px;
    min-width: 130px;
}
.filter-date {
    flex: 1 1 130px;
    min-width: 125px;
}
.filter-buttons {
    flex: 0 0 auto;
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    gap: 8px !important;
    margin-left: 4px;
    align-self: flex-end;
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
    box-sizing: border-box;
    cursor: pointer;
    transition: all .15s ease;
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
@media (max-width: 1100px) {
    .filter-row {
        flex-wrap: wrap;
    }
}

/* 2. Bảng Master: Danh sách hàng bất thường */
.cukcuk-master-card {
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: var(--radius);
}
.cukcuk-master-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid var(--line);
    background: #fafbfd;
}
#masterTable {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
    table-layout: fixed;
    min-width: 0;
}
#masterTable th {
    background: #fafbfd;
    font-size: 12.5px;
    font-weight: 600;
    color: #475467;
    padding: 12px 14px;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
}
#masterTable td {
    padding: 12px 14px;
    font-size: 13px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
    white-space: nowrap;
}
#masterTable tbody tr {
    cursor: pointer;
    transition: background .12s ease;
}
#masterTable tbody tr:hover {
    background: #f8fbff !important;
}

/* Cột hành động tối giản */
.actions-slot-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.actions-slot-wrap .act {
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    color: #64748b;
    background: transparent;
    border: none;
    padding: 0;
    cursor: pointer;
    flex-shrink: 0;
    transition: all .12s ease;
    text-decoration: none;
}
.actions-slot-wrap .act:hover {
    background: #f1f5f9;
    color: #2563eb;
}

/* Badge trạng thái chuẩn FeedWare */
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
    background: #f1f5f9;
    color: #475569;
}
.badge-info {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.badge-ok {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #dcfce7;
}
.badge-warn {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #ffedd5;
}
.badge-danger {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

/* Footer phân trang chuẩn */
.cukcuk-pagination-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 18px;
    background: #fafbfd;
    border-top: 1px solid var(--line);
    margin: 0;
    border-radius: 0 0 var(--radius) var(--radius);
}

/* Modal Popup chuẩn */
.cukcuk-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 16px;
    box-sizing: border-box;
}
.cukcuk-modal-box {
    background: #ffffff;
    width: 650px;
    max-width: 95vw;
    border-radius: 10px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: modalFadeIn 0.15s ease-out;
}
@keyframes modalFadeIn {
    from { opacity: 0; transform: scale(0.96); }
    to { opacity: 1; transform: scale(1); }
}
.cukcuk-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 22px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
}
.cukcuk-modal-title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}
.cukcuk-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    line-height: 1;
    color: #64748b;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all .12s ease;
}
.cukcuk-modal-close:hover {
    color: #0f172a;
    background: #f1f5f9;
}
.cukcuk-modal-body {
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: #ffffff;
    max-height: 70vh;
    overflow-y: auto;
}
.cukcuk-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 22px;
    background: #ffffff;
    border-top: 1px solid #f1f5f9;
}
</style>

<div class="cukcuk-layout">

    <!-- 1. BỘ LỌC NGANG TINH GỌN TRÊN 1 DÒNG DUY NHẤT -->
    <form class="card filter-card" method="get" action="<?= e(route('hang-bat-thuong')) ?>">
        <div class="filter-row">
            <div class="filter-group filter-search">
                <label>Tìm kiếm</label>
                <input type="text" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="Mã bản ghi / Lô / Hàng hóa...">
            </div>

            <?php if (count($kho) > 1): ?>
            <div class="filter-group filter-select">
                <label>Kho</label>
                <select name="maKho">
                    <option value="">Tất cả kho</option>
                    <?php foreach ($kho as $kId => $kName): ?>
                        <option value="<?= e($kId) ?>" <?= ($f['maKho'] ?? '') === $kId ? 'selected' : '' ?>><?= e($kName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="filter-group filter-select">
                <label>Loại bất thường</label>
                <select name="loaiBatThuong">
                    <option value="">Tất cả loại</option>
                    <?php foreach ($loai as $lKey => $lVal): ?>
                        <option value="<?= e($lKey) ?>" <?= ($f['loaiBatThuong'] ?? '') === $lKey ? 'selected' : '' ?>><?= e($lVal) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group filter-select">
                <label>Trạng thái</label>
                <select name="trangThai">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach ($trangThais as $stKey => $stVal): ?>
                        <option value="<?= e($stKey) ?>" <?= ($f['trangThai'] ?? '') === $stKey ? 'selected' : '' ?>><?= e($stVal) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group filter-date">
                <label>Từ ngày</label>
                <input type="date" name="tuNgay" value="<?= e($f['tuNgay'] ?? '') ?>">
            </div>

            <div class="filter-group filter-date">
                <label>Đến ngày</label>
                <input type="date" name="denNgay" value="<?= e($f['denNgay'] ?? '') ?>">
            </div>

            <div class="filter-buttons">
                <button type="submit" class="btn btn-primary btn-icon" title="Tìm kiếm">
                    <?= icon('search', 16) ?>
                </button>
                <a href="<?= e(route('hang-bat-thuong')) ?>" class="btn btn-ghost btn-icon" title="Đặt lại bộ lọc">
                    <?= icon('repeat', 16) ?>
                </a>
            </div>
        </div>
    </form>

    <!-- 2. BẢNG MASTER: DANH SÁCH HÀNG BẤT THƯỜNG -->
    <div class="cukcuk-master-card">
        <div class="cukcuk-master-head">
            <h2 style="font-size: 14px; font-weight: 700; margin: 0; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                <?= icon('alert-triangle', 16) ?> <?= !empty($isWarehouseRole) ? 'Hàng bất thường bạn đã ghi nhận' : 'Danh sách hàng bất thường' ?>
            </h2>
            <?php if (!empty($ungVien) && !empty($canScan)): ?>
                <span style="font-size: 12.5px; color: #d97706; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 9999px; padding: 2px 10px; font-weight: 500;">
                    <?= count($ungVien) ?> lô sắp / đã hết hạn cần quét
                </span>
            <?php endif; ?>
        </div>

        <div class="table-wrap" style="overflow-x: auto; margin: 0;">
            <table class="table" id="masterTable">
                <thead>
                    <tr>
                        <th style="width: 12%;">Mã bản ghi</th>
                        <th style="width: 20%;">Hàng hóa</th>
                        <th style="width: 13%;">Mã lô hàng</th>
                        <th style="width: 16%;">Khu vực</th>
                        <th style="width: 9%;">Loại</th>
                        <th style="width: 10%; text-align: right;">Số lượng</th>
                        <th style="width: 11%;">Trạng thái</th>
                        <th style="width: 9%; text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="8" class="empty-row" style="text-align: center; color: var(--muted); padding: 36px 0;">
                            <?= !empty($isWarehouseRole) ? 'Chưa có bản ghi hàng bất thường nào do bạn ghi nhận.' : 'Không tìm thấy bản ghi hàng bất thường nào phù hợp với điều kiện tìm kiếm.' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r):
                        $ma = $r['maHangBatThuong'];
                        $detailUrl = route('hang-bat-thuong/detail', ['ma' => $ma]);
                        $hsdFmt = !empty($r['ngayHetHan']) ? date('d/m/Y', strtotime($r['ngayHetHan'])) : '—';
                        $isDangXuLy = ($r['trangThai'] !== 'Đã xử lý');
                    ?>
                        <tr onclick="window.location.href = '<?= e($detailUrl) ?>';">
                            <td>
                                <a href="<?= e($detailUrl) ?>" onclick="event.stopPropagation();" style="font-weight: 600; color: #2563eb; text-decoration: none;">
                                    <?= e($ma) ?>
                                </a>
                            </td>
                            <td>
                                <div style="color: #0f172a; font-weight: 400; font-size: 13px;"><?= e($r['tenHangHoa']) ?></div>
                            </td>
                            <td>
                                <div style="color: #334155; font-size: 13px; font-weight: 400;"><?= e($r['maLoHang']) ?></div>
                            </td>
                            <td>
                                <div style="color: #0f172a; font-weight: 400; font-size: 13px;"><?= e(!empty($r['tenKhuVuc']) ? $r['tenKhuVuc'] : ($r['tenKho'] ?? '—')) ?></div>
                            </td>
                            <td>
                                <span class="<?= $badgeLoai[$r['loaiBatThuong']] ?? 'badge' ?>">
                                    <?= e($r['loaiBatThuong']) ?>
                                </span>
                            </td>
                            <td style="text-align: right; color: #0f172a; font-size: 13px;">
                                <?= number_format((float)$r['soLuong'], 0, ',', '.') ?> <span style="font-weight: 400; font-size: 12px; color: #64748b;"><?= e($r['donViTinhChuan']) ?></span>
                            </td>
                            <td>
                                <span class="<?= $badgeTT[$r['trangThai']] ?? 'badge' ?>">
                                    <?= e($r['trangThai']) ?>
                                </span>
                            </td>
                            <td style="text-align: center;" onclick="event.stopPropagation();">
                                <div class="actions-slot-wrap">
                                    <a class="act" title="Xem chi tiết" href="<?= e($detailUrl) ?>">
                                        <?= icon('eye', 16) ?>
                                    </a>
                                    <?php if ((!empty($canPropose) || $canXuLy || !empty($canExecute)) && $isDangXuLy): ?>
                                        <a class="act" title="Xử lý / Đề xuất phương án" href="<?= e($detailUrl) ?>" style="color: #2563eb;">
                                            <?= icon('check-square', 16) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Phân trang tối giản: hiển thị số lượng và nút < 1 2 > -->
        <div class="cukcuk-pagination-footer">
            <div style="font-size: 13px; color: #64748b;">
                <?php
                $from = $total > 0 ? ($page - 1) * $perPage + 1 : 0;
                $to = min($total, ($page - 1) * $perPage + count($rows));
                ?>
                Hiển thị <b><?= $from ?> - <?= $to ?></b> trong tổng số <b><?= $total ?></b> bản ghi
            </div>
            <div class="pager-links" style="display: flex; gap: 4px; align-items: center;">
                <?php
                $pages = max(1, (int)ceil($total / $perPage));
                $pagerUrl = function(int $p) use ($f): string {
                    return route('hang-bat-thuong', array_merge($f, ['page' => $p]));
                };
                ?>
                <?php if ($page > 1): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= e($pagerUrl($page - 1)) ?>" style="min-width: 28px; height: 28px; padding: 0 6px; font-size: 12px;" title="Trang trước">&lt;</a>
                <?php else: ?>
                    <span class="btn btn-ghost btn-sm" style="min-width: 28px; height: 28px; padding: 0 6px; font-size: 12px; opacity: 0.35; cursor: not-allowed;">&lt;</span>
                <?php endif; ?>

                <?php foreach (pagination_items($page, $pages) as $p): ?>
                    <?php if ($p === '...'): ?>
                        <span style="min-width: 22px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; color: #94a3b8; user-select: none;">...</span>
                    <?php elseif ($p === $page): ?>
                        <span class="btn btn-primary btn-sm" style="min-width: 28px; height: 28px; padding: 0 6px; font-size: 12px; font-weight: 600;"><?= $p ?></span>
                    <?php else: ?>
                        <a class="btn btn-ghost btn-sm" href="<?= e($pagerUrl($p)) ?>" style="min-width: 28px; height: 28px; padding: 0 6px; font-size: 12px;"><?= $p ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($page < $pages): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= e($pagerUrl($page + 1)) ?>" style="min-width: 28px; height: 28px; padding: 0 6px; font-size: 12px;" title="Trang sau">&gt;</a>
                <?php else: ?>
                    <span class="btn btn-ghost btn-sm" style="min-width: 28px; height: 28px; padding: 0 6px; font-size: 12px; opacity: 0.35; cursor: not-allowed;">&gt;</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- 3. MODAL QUÉT LÔ SẮP / ĐÃ HẾT HẠN -->
<?php if (!empty($canScan)):
    $ungVienJs = [];
    foreach (($ungVien365 ?? []) as $u) {
        $ungVienJs[] = [
            'maLoHang'       => (string)$u['maLoHang'],
            'tenHangHoa'     => (string)$u['tenHangHoa'],
            'tenKhuVuc'      => (string)(!empty($u['tenKhuVuc']) ? $u['tenKhuVuc'] : ($u['tenKho'] ?? '—')),
            'tenViTri'       => (string)$u['tenViTri'],
            'ngayHetHan'     => (string)$u['ngayHetHan'],
            'soNgayConLai'   => (int)$u['soNgayConLai'],
            'soLuongTon'     => (float)$u['soLuongTon'],
            'donViTinhChuan' => (string)($u['donViTinhChuan'] ?? 'kg'),
        ];
    }
?>
<div id="scanModal" class="cukcuk-modal-backdrop" style="display: none;" onclick="handleScanModalBackdrop(event)">
    <div class="cukcuk-modal-box">
        <div class="cukcuk-modal-header">
            <h3 class="cukcuk-modal-title">Quét lô sắp / đã hết hạn</h3>
            <button type="button" class="cukcuk-modal-close" onclick="closeScanModal()" title="Đóng">&times;</button>
        </div>
        <form id="scanExpiredForm" method="post" action="<?= e(route('hang-bat-thuong/detect')) ?>" onsubmit="return handleScanFormSubmit(event);">
            <?= csrf_field() ?>
            <div class="cukcuk-modal-body">
                <div>
                    <label for="modal_soNgay" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                        Kiểm tra lô hết hạn trong (số ngày tới) <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="text" inputmode="numeric" data-int="0:365" id="modal_soNgay" name="soNgay" required
                               value="<?= (int)config('expiry_warning_days', 30) ?>"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();searchExpiredCandidates();}"
                               style="flex: 1; height: 38px; padding: 0 12px; font-size: 13.5px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                        <button type="button" class="btn btn-ghost" onclick="searchExpiredCandidates()"
                                style="height: 38px; padding: 0 16px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; font-weight: 600; background: #f8fafc; color: #0f172a; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; cursor: pointer;">
                            <?= icon('search', 15) ?> Tìm kiếm
                        </button>
                    </div>
                </div>

                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 600; color: #334155;">Danh sách lô chưa ghi nhận</span>
                        <b id="scanCountLabel" style="font-size: 13px; color: #2563eb; display: none;">0 lô</b>
                    </div>
                    <div id="scanCandidatesContainer">
                        <div style="padding: 22px 16px; text-align: center; color: #64748b; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 13px;">
                            Nhập số ngày và nhấn <b>Tìm kiếm</b> để kiểm tra danh sách lô.
                        </div>
                    </div>
                </div>
            </div>
            <div class="cukcuk-modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeScanModal()" style="height: 38px; padding: 0 18px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 13.5px;">Hủy</button>
                <button type="submit" id="btnSubmitScan" class="btn btn-primary" style="height: 38px; padding: 0 20px; border-radius: 6px; font-size: 13.5px; font-weight: 600; background: #2563eb; color: #ffffff; cursor: pointer;">
                    Quét và ghi nhận
                </button>
            </div>
        </form>
    </div>
</div>

<script>
var scanExpiredCandidates365 = <?= json_encode($ungVienJs, JSON_UNESCAPED_UNICODE) ?>;
var scanHasSearched = false;
var scanLastSearchedDays = null;
var scanMatchedRows = [];

function escScanHtml(s) {
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function fmtScanDate(ymd) {
    if (!ymd) return '—';
    var p = ymd.slice(0, 10).split('-');
    return p.length === 3 ? (p[2] + '/' + p[1] + '/' + p[0]) : ymd;
}

function parseScanDaysInput() {
    var inp = document.getElementById('modal_soNgay');
    var raw = inp ? String(inp.value || '').trim() : '';
    if (!/^\d+$/.test(raw)) return null;
    var days = parseInt(raw, 10);
    if (days < 0 || days > 365) return null;
    return days;
}

function getScanMatches(days) {
    return scanExpiredCandidates365.filter(function (r) {
        return Number(r.soNgayConLai) <= days;
    });
}

function searchExpiredCandidates() {
    var inp = document.getElementById('modal_soNgay');
    var days = parseScanDaysInput();
    if (days === null) {
        fwModal.alert({
            title: 'Số ngày không hợp lệ',
            message: 'Vui lòng nhập số nguyên từ 0 đến 365.',
            tone: 'warn',
            onConfirm: function () { if (inp) inp.focus(); }
        });
        return;
    }

    scanHasSearched = true;
    scanLastSearchedDays = days;
    scanMatchedRows = getScanMatches(days);
    var countEl = document.getElementById('scanCountLabel');
    var container = document.getElementById('scanCandidatesContainer');
    if (countEl) {
        countEl.style.display = 'inline';
        countEl.textContent = scanMatchedRows.length + ' lô';
    }

    if (scanMatchedRows.length === 0) {
        container.innerHTML = '<div style="padding: 20px; text-align: center; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px;">'
            + 'Không có lô nào sắp hoặc đã hết hạn trong <b>' + days + ' ngày</b> tới cần ghi nhận.'
            + '</div>';
        return;
    }

    var rowsHtml = '';
    scanMatchedRows.forEach(function (u) {
        var d = Number(u.soNgayConLai);
        var hsdColor = d < 0 ? '#b91c1c' : (d <= 7 ? '#c2410c' : '#475467');
        var hanTxt = d < 0 ? ('Quá hạn ' + Math.abs(d) + ' ngày') : ('Còn ' + d + ' ngày');
        rowsHtml += '<tr style="border-bottom: 1px solid #f1f5f9;">'
            + '<td style="padding: 8px 10px;"><b style="color: #0f172a;">' + escScanHtml(u.tenHangHoa) + '</b><br><small style="color: #64748b;">' + escScanHtml(u.maLoHang) + '</small></td>'
            + '<td style="padding: 8px 10px;"><div style="color: #0f172a; font-weight: 500;">' + escScanHtml(u.tenKhuVuc) + '</div><small style="color: #64748b;">' + escScanHtml(u.tenViTri) + '</small></td>'
            + '<td style="padding: 8px 10px; text-align: center;"><div style="color: ' + hsdColor + '; font-weight: 600;">' + fmtScanDate(u.ngayHetHan) + '</div><small style="color: ' + hsdColor + ';">' + hanTxt + '</small></td>'
            + '<td style="padding: 8px 10px; text-align: right; font-weight: 600;">' + Number(u.soLuongTon || 0).toLocaleString('vi-VN', { maximumFractionDigits: 2 }) + ' <small style="font-weight: 400; color: #64748b;">' + escScanHtml(u.donViTinhChuan) + '</small></td>'
            + '</tr>';
    });

    container.innerHTML = '<div style="max-height: 240px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">'
        + '<table class="table" style="margin: 0; font-size: 12px; table-layout: fixed; width: 100%;">'
        + '<thead><tr style="background: #f8fafc;">'
        + '<th style="width: 34%; padding: 8px 10px; text-align: left;">Hàng hóa / Lô</th>'
        + '<th style="width: 24%; padding: 8px 10px; text-align: left;">Khu vực / Vị trí</th>'
        + '<th style="width: 24%; padding: 8px 10px; text-align: center;">HSD</th>'
        + '<th style="width: 18%; padding: 8px 10px; text-align: right;">Tồn</th>'
        + '</tr></thead>'
        + '<tbody>' + rowsHtml + '</tbody>'
        + '</table></div>';
}

function handleScanFormSubmit(e) {
    if (e) e.preventDefault();
    var inp = document.getElementById('modal_soNgay');
    var days = parseScanDaysInput();
    if (days === null) {
        fwModal.alert({
            title: 'Số ngày không hợp lệ',
            message: 'Vui lòng nhập số nguyên từ 0 đến 365.',
            tone: 'warn',
            onConfirm: function () { if (inp) inp.focus(); }
        });
        return false;
    }

    if (!scanHasSearched || scanLastSearchedDays !== days) {
        searchExpiredCandidates();
        return false;
    }

    var matches = getScanMatches(days);
    scanMatchedRows = matches;
    if (matches.length === 0) {
        fwModal.alert({
            title: 'Không có lô cần ghi nhận',
            message: 'Hiện không có lô hàng nào sắp hoặc đã hết hạn trong ' + days + ' ngày tới.',
            tone: 'info'
        });
        return false;
    }

    fwModal.confirm({
        title: 'Xác nhận quét và ghi nhận',
        htmlMessage: 'Tìm thấy <strong>' + matches.length + ' lô hàng</strong> sắp hoặc đã hết hạn trong <strong>' + days + ' ngày</strong> tới.<br>Bạn có chắc chắn muốn ghi nhận vào danh sách hàng bất thường không?',
        tone: 'warn',
        okText: 'Xác nhận ghi nhận',
        cancelText: 'Hủy bỏ',
        onConfirm: function () {
            var form = document.getElementById('scanExpiredForm');
            var btn = document.getElementById('btnSubmitScan');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Đang ghi nhận...';
            }
            if (form) form.submit();
        }
    });
    return false;
}

function openScanModal() {
    scanHasSearched = false;
    scanLastSearchedDays = null;
    scanMatchedRows = [];
    var countEl = document.getElementById('scanCountLabel');
    var container = document.getElementById('scanCandidatesContainer');
    if (countEl) countEl.style.display = 'none';
    if (container) {
        container.innerHTML = '<div style="padding: 22px 16px; text-align: center; color: #64748b; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 13px;">'
            + 'Nhập số ngày và nhấn <b>Tìm kiếm</b> để kiểm tra danh sách lô.'
            + '</div>';
    }
    var modal = document.getElementById('scanModal');
    if (modal) modal.style.display = 'flex';
}
function closeScanModal() {
    var modal = document.getElementById('scanModal');
    if (modal) modal.style.display = 'none';
}
function handleScanModalBackdrop(e) {
    if (e.target && e.target.id === 'scanModal') {
        closeScanModal();
    }
}
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeScanModal();
    }
});
</script>
<?php endif; ?>
