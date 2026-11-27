<?php
/**
 * View kiem-ke/index — UC24: Danh sách biên bản kiểm kê.
 * Biến từ KiemKeController::index():
 *   $f, $rows, $total, $page, $perPage, $kho, $loaiKho.
 */
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

/* 2. Bảng Master: Danh sách biên bản */
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
    background: #f8fafc;
    font-size: 12.5px;
    font-weight: 700;
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
}
.actions-slot-wrap .act:hover {
    background: #f1f5f9;
    color: #2563eb;
}
.actions-slot-wrap .act-danger {
    color: #ef4444;
}
.actions-slot-wrap .act-danger:hover {
    background: #fef2f2;
    color: #dc2626;
}

/* Badge trạng thái & loại kiểm kê dạng cũ chuẩn FeedWare */
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

/* 3. Modal khởi tạo nhanh kiểm kê kho (chuẩn CukCuk/MISA) */
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
    width: 450px;
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
}
.cukcuk-modal-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.cukcuk-modal-input {
    width: 100%;
    height: 40px;
    padding: 0 12px;
    font-size: 13.5px;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    box-sizing: border-box;
    outline: none;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.cukcuk-modal-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
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
    <form class="card filter-card" method="get" action="<?= e(route('kiem-ke')) ?>">
        <div class="filter-row">
            <div class="filter-group filter-search">
                <label>Tìm kiếm</label>
                <input type="text" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="<?= !empty($isWarehouseScoped) ? 'Mã biên bản / Người kiểm đếm...' : 'Mã biên bản / Tên kho...' ?>">
            </div>

            <?php if (!empty($isWarehouseScoped)): ?>
                <div class="filter-group filter-select">
                    <label>Khu vực</label>
                    <select name="maKhuVuc">
                        <option value="">Tất cả khu vực</option>
                        <?php foreach (($areas ?? []) as $kvId => $kvName): ?>
                            <option value="<?= e($kvId) ?>" <?= ($f['maKhuVuc'] ?? '') === $kvId ? 'selected' : '' ?>><?= e($kvName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="filter-group filter-select">
                    <label>Kho kiểm kê</label>
                    <select name="maKho">
                        <option value="">Tất cả kho</option>
                        <?php foreach ($kho as $kId => $kName): ?>
                            <option value="<?= e($kId) ?>" <?= ($f['maKho'] ?? '') === $kId ? 'selected' : '' ?>><?= e($kName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="filter-group filter-select">
                <label>Trạng thái</label>
                <select name="trangThai">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach (inventory_status_options() as $stCode => $stLabel): ?>
                        <option value="<?= e($stCode) ?>" <?= ($f['trangThai'] ?? '') === $stCode ? 'selected' : '' ?>><?= e($stLabel) ?></option>
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
                <a href="<?= e(route('kiem-ke')) ?>" class="btn btn-ghost btn-icon" title="Đặt lại bộ lọc">
                    <?= icon('repeat', 16) ?>
                </a>
            </div>
        </div>
    </form>

    <!-- 2. BẢNG MASTER: DANH SÁCH BIÊN BẢN KIỂM KÊ -->
    <div class="cukcuk-master-card">
        <div class="cukcuk-master-head">
            <h2 style="font-size: 14px; font-weight: 700; margin: 0; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                <?= icon('list', 16) ?> Danh sách biên bản kiểm kê
            </h2>
        </div>

        <div class="table-wrap" style="overflow-x: auto; margin: 0;">
            <table class="table" id="masterTable">
                <thead>
                    <tr>
                        <th style="width: 15%;">Mã biên bản</th>
                        <th style="width: 13%;">Loại kiểm kê</th>
                        <?php if (!empty($isWarehouseScoped)): ?>
                            <th style="width: 17%;">Khu vực</th>
                            <th style="width: 16%;">Người kiểm đếm</th>
                        <?php else: ?>
                            <th style="width: 16%;">Kho</th>
                            <th style="width: 14%;">Khu vực</th>
                        <?php endif; ?>
                        <th style="width: 15%;">Ngày lập</th>
                        <th style="width: 12%;">Trạng thái</th>
                        <th style="width: 12%; text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="empty-row" style="text-align: center; color: var(--muted); padding: 32px 0;">
                            Không tìm thấy biên bản kiểm kê nào phù hợp với điều kiện tìm kiếm.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r):
                        $ma = $r['maBienBanKiemKe'];
                        $daGhi = (int)($r['soDaGhi'] ?? 0);
                        $isPending = ($r['trangThai'] === 'Chờ xử lý');
                        $isReviewing = ($r['trangThai'] === 'Đang xử lý');
                        $isManager = can('kiem-ke', 'create') || can('kiem-ke', 'handle') || can('kiem-ke', 'reconcile');
                        $isStaff = can('kiem-ke', 'record') && (\Core\Auth::role() !== ROLE_MANAGER);

                        $canEdit = $isManager && $isPending && ($daGhi === 0);
                        $canCancel = $isManager && $isPending && ($daGhi === 0);
                        $canRecord = $isStaff && in_array($r['trangThai'], ['Chờ xử lý', 'Từ chối'], true);
                        $detailUrl = route('kiem-ke/detail', ['ma' => $ma]);
                        $editUrl = route('kiem-ke/edit', ['ma' => $ma]);
                        $recordUrl = route('kiem-ke/record-result', ['ma' => $ma]);
                        $loaiLabel = inventory_type_label($r['loaiKiemKe'] ?? '');
                        $kvName = !empty($r['tenKhuVuc']) ? $r['tenKhuVuc'] : 'Toàn kho';
                        $rawNgay = !empty($r['ngayBatDau']) ? $r['ngayBatDau'] : null;
                        $ngayFmt = $rawNgay ? date('d/m/Y H:i', strtotime($rawNgay)) : '—';
                    ?>
                        <tr onclick="window.location.href = '<?= e($detailUrl) ?>';">
                            <td>
                                <a href="<?= e($detailUrl) ?>" onclick="event.stopPropagation();" style="font-weight: 600; color: #2563eb; text-decoration: none;">
                                    <?= e($ma) ?>
                                </a>
                            </td>
                            <td><?= inventory_type_badge($r['loaiKiemKe'] ?? '') ?></td>
                            <?php if (!empty($isWarehouseScoped)): ?>
                                <td><?= e($kvName) ?></td>
                                <td><?= e($r['tenTruongDoan'] ?? '—') ?></td>
                            <?php else: ?>
                                <td><?= e($r['tenKho']) ?></td>
                                <td><?= e($kvName) ?></td>
                            <?php endif; ?>
                            <td><?= e($ngayFmt) ?></td>
                            <td><?= inventory_status_badge($r['trangThai']) ?></td>
                            <td style="text-align: center;" onclick="event.stopPropagation();">
                                <div class="actions-slot-wrap">
                                    <a class="act" title="Xem chi tiết" href="<?= e($detailUrl) ?>">
                                        <?= icon('eye', 16) ?>
                                    </a>
                                    <?php if ($isReviewing && $isManager): ?>
                                        <a class="act" title="Kiểm tra & xác nhận" href="<?= e($detailUrl) ?>" style="color: #16a34a;">
                                            <?= icon('check', 16) ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($canRecord): ?>
                                        <a class="act" title="Ghi nhận kiểm đếm" href="<?= e($recordUrl) ?>" style="color: #2563eb;">
                                            <?= icon('check-square', 16) ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($canEdit): ?>
                                        <a class="act" title="Sửa biên bản" href="<?= e($editUrl) ?>">
                                            <?= icon('edit', 16) ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($canCancel): ?>
                                        <button type="button" class="act act-danger" title="Hủy biên bản" onclick="confirmCancel('<?= e($ma) ?>')">
                                            <?= icon('trash', 16) ?>
                                        </button>
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
                Hiển thị <b><?= $from ?> - <?= $to ?></b> trong tổng số <b><?= $total ?></b> biên bản
            </div>
            <div class="pager-links" style="display: flex; gap: 4px; align-items: center;">
                <?php
                $pages = max(1, (int)ceil($total / $perPage));
                $pagerUrl = function(int $p) use ($f): string {
                    return route('kiem-ke', array_merge($f, ['page' => $p]));
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

<!-- 3. MODAL KHỞI TẠO KIỂM KÊ KHO -->
<div id="initKiemKeModal" class="cukcuk-modal-backdrop" style="display: none;" onclick="handleModalBackdropClick(event)">
    <div class="cukcuk-modal-box">
        <div class="cukcuk-modal-header">
            <h3 class="cukcuk-modal-title">Kiểm kê kho</h3>
            <button type="button" class="cukcuk-modal-close" onclick="closeInitModal()" title="Đóng">&times;</button>
        </div>
        <div class="cukcuk-modal-body">
            <!-- 1. Loại kiểm kê (Select chọn 3 cái) -->
            <div>
                <label for="modal_loaiKiemKe" class="cukcuk-modal-label">Loại kiểm kê <span style="color: #ef4444;">*</span></label>
                <select id="modal_loaiKiemKe" class="cukcuk-modal-input">
                    <?php foreach (inventory_type_options() as $loaiCode => $loaiName): ?>
                        <option value="<?= e($loaiCode) ?>" <?= $loaiCode === 'DinhKy' ? 'selected' : '' ?>>
                            <?= e($loaiName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 2. Kiểm kê kho (chỉ hiển thị khi có nhiều hơn 1 kho) -->
            <?php if (count($kho) === 1):
                $onlyKhoId = (string)array_key_first($kho);
            ?>
                <input type="hidden" id="modal_maKho" value="<?= e($onlyKhoId) ?>">
            <?php else: ?>
                <div>
                    <label for="modal_maKho" class="cukcuk-modal-label">Kiểm kê kho <span style="color: #ef4444;">*</span></label>
                    <select id="modal_maKho" class="cukcuk-modal-input">
                        <option value="">— Chọn kho kiểm kê —</option>
                        <?php foreach ($kho as $kId => $kName): ?>
                            <option value="<?= e($kId) ?>"><?= e($kName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- 3. Kiểm kê đến ngày -->
            <div>
                <label for="modal_thoiDiemKetThuc" class="cukcuk-modal-label">Kiểm kê đến ngày <span style="color: #ef4444;">*</span></label>
                <input type="date" id="modal_thoiDiemKetThuc" class="cukcuk-modal-input" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <div class="cukcuk-modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeInitModal()" style="height: 38px; padding: 0 20px; font-size: 13.5px; border: 1px solid #d0d5dd; border-radius: 6px;">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="confirmInitModal()" style="height: 38px; padding: 0 22px; font-size: 13.5px; font-weight: 600; border-radius: 6px; background: #2563eb;">Đồng ý</button>
        </div>
    </div>
</div>

<!-- 4. FORM ẨN XÁC NHẬN HỦY BIÊN BẢN -->
<form id="cancelBbForm" method="post" action="<?= e(route('kiem-ke/cancel')) ?>" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="ma" id="cancelMa" value="">
    <input type="hidden" name="lyDo" id="cancelLyDo" value="">
</form>

<script>
// Quản lý Modal Khởi tạo kiểm kê kho
function openInitModal() {
    var modal = document.getElementById('initKiemKeModal');
    if (modal) {
        modal.style.display = 'flex';
        var selKho = document.getElementById('modal_maKho');
        var selLoai = document.getElementById('modal_loaiKiemKe');
        if (selKho && selKho.tagName === 'SELECT') selKho.focus();
        else if (selLoai) selLoai.focus();
    }
}

function closeInitModal() {
    var modal = document.getElementById('initKiemKeModal');
    if (modal) modal.style.display = 'none';
}

function handleModalBackdropClick(e) {
    if (e.target && e.target.id === 'initKiemKeModal') {
        closeInitModal();
    }
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeInitModal();
    }
});

function confirmInitModal() {
    var loaiSel = document.getElementById('modal_loaiKiemKe');
    var loai = loaiSel ? loaiSel.value : 'DinhKy';
    var khoSel = document.getElementById('modal_maKho');
    var maKho = khoSel ? khoSel.value : '';
    var ngayKetThuc = document.getElementById('modal_thoiDiemKetThuc').value;

    if (!maKho) {
        fwModal.alert({
            title: 'Thiếu thông tin',
            message: 'Vui lòng chọn kho kiểm kê.',
            tone: 'warn',
            onConfirm: function () { if (khoSel) khoSel.focus(); }
        });
        return;
    }
    if (!ngayKetThuc) {
        fwModal.alert({
            title: 'Thiếu thông tin',
            message: 'Vui lòng chọn ngày kiểm kê đến ngày.',
            tone: 'warn'
        });
        return;
    }

    var baseUrl = <?= json_encode(route('kiem-ke/create')) ?>;
    var url = baseUrl + '?loaiKiemKe=' + encodeURIComponent(loai)
        + '&maKho=' + encodeURIComponent(maKho)
        + '&ngayKetThuc=' + encodeURIComponent(ngayKetThuc);
    window.location.href = url;
}

// Xác nhận Hủy biên bản
function confirmCancel(ma) {
    fwModal.prompt({
        title: 'Hủy biên bản kiểm kê',
        htmlMessage: 'Bạn có chắc chắn muốn hủy biên bản kiểm kê <strong>' + ma + '</strong>? Vui lòng nhập lý do hủy:',
        label: 'Lý do hủy biên bản',
        placeholder: 'Nhập lý do hủy...',
        required: true,
        requiredMsg: 'Vui lòng nhập lý do hủy biên bản.',
        tone: 'danger',
        icon: '!',
        okText: 'Xác nhận hủy',
        okColor: '#dc2626',
        onConfirm: function (lyDo) {
            document.getElementById('cancelMa').value = ma;
            document.getElementById('cancelLyDo').value = lyDo;
            document.getElementById('cancelBbForm').submit();
        }
    });
}
</script>
