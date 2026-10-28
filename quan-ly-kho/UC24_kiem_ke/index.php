<?php
/**
 * UC24 - QUẢN LÝ KIỂM KÊ KHO: GIAO DIỆN MASTER-DETAIL (CHUẨN MISA CUKCUK)
 * Mô hình Master-Detail:
 *   - Nửa trên: Bảng Master danh sách biên bản kiểm kê, thanh Action Toolbar, bộ lọc ngang & compact status pills
 *   - Nửa dưới: Bảng Detail chi tiết lô hàng kiểm kê với Header 2 tầng (Grouped columns),
 *               hỗ trợ Inline Editing (UC25), đối chiếu (UC26), xử lý chênh lệch (UC27), bất thường (UC28)
 *
 * Tuân thủ schema QLK05, chuẩn chuỗi VARCHAR(15), prepared statements, transaction, CSRF và responsive.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/uc24_helper.php';
require_module('UC24');


/* ========================================================================== */
/* 1. XỬ LÝ AJAX VÀ POST REQUESTS                                             */
/* ========================================================================== */



// B. Xử lý các thao tác POST (Hủy biên bản, Lưu kiểm đếm nhanh, Đối chiếu)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $isAjax = !empty($_POST['is_ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    // 1. Hủy biên bản kiểm kê
    if ($action === 'cancel') {
        csrf_check();
        $id = trim((string)($_POST['id'] ?? ''));
        $bb = uc24_db_one("SELECT * FROM BienBanKiemKe WHERE maBienBanKiemKe = ?", 's', [$id]);
        if (!$bb) {
            $msg = 'Không tìm thấy biên bản kiểm kê cần hủy.';
            if ($isAjax) { echo json_encode(['success' => false, 'message' => $msg]); exit; }
            flash('error', $msg);
        } elseif ($bb['trangThai'] === 'Đã hủy') {
            $msg = 'Biên bản này đã bị hủy trước đó.';
            if ($isAjax) { echo json_encode(['success' => false, 'message' => $msg]); exit; }
            flash('info', $msg);
        } elseif ($bb['trangThai'] !== 'Chờ kiểm kê') {
            $msg = 'Chỉ có thể hủy biên bản đang ở trạng thái "Chờ kiểm kê".';
            if ($isAjax) { echo json_encode(['success' => false, 'message' => $msg]); exit; }
            flash('error', $msg);
        } else {
            uc24_db_exec("UPDATE BienBanKiemKe SET trangThai = 'Đã hủy' WHERE maBienBanKiemKe = ?", 's', [$id]);
            $msg = "Đã hủy biên bản kiểm kê {$id} thành công.";
            if ($isAjax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            flash('success', $msg);
        }
        redirect(uc_url('UC24'));
    }
}

/* ========================================================================== */
/* 2. BỘ LỌC VÀ TRUY VẤN DANH SÁCH MASTER                                      */
/* ========================================================================== */

$g = fn($k) => is_string($_GET[$k] ?? null) ? trim($_GET[$k]) : '';
$statusRows = uc24_db_all("SELECT DISTINCT trangThai FROM BienBanKiemKe WHERE trangThai IS NOT NULL AND trangThai <> '' ORDER BY trangThai");
$validStatuses = array_values(array_filter(array_map(static fn(array $row): string => trim((string)$row['trangThai']), $statusRows)));
$validTypes = ['NGUYEN_LIEU' => 'Nguyên liệu', 'THANH_PHAM' => 'Thành phẩm'];

$khoList = uc24_db_all("SELECT maKho, tenKho FROM Kho ORDER BY tenKho");
$khoIds = array_column($khoList, 'maKho');

$isDate = static function (string $value): bool {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) return false;
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
};

// Thiết lập kỳ thời gian
$period = $g('ky_thoi_gian');
$rawFrom = $g('tu_ngay');
$rawTo = $g('den_ngay');
$nowYear = date('Y');

if ($period === 'nam_nay' && empty($rawFrom) && empty($rawTo)) {
    $rawFrom = "{$nowYear}-01-01";
    $rawTo = "{$nowYear}-12-31";
} elseif ($period === 'thang_nay' && empty($rawFrom) && empty($rawTo)) {
    $rawFrom = date('Y-m-01');
    $rawTo = date('Y-m-t');
} elseif ($period === 'hom_nay' && empty($rawFrom) && empty($rawTo)) {
    $rawFrom = date('Y-m-d');
    $rawTo = date('Y-m-d');
}

$kw = trim((string)($g('q') !== '' ? $g('q') : $g('ma_bb')));
$perPage = in_array((int)$g('limit'), [5, 10, 20, 50], true) ? (int)$g('limit') : 5;

$f = [
    'q' => $kw,
    'ky_thoi_gian' => $period,
    'loai_kiem_ke' => isset($validTypes[$g('loai_kiem_ke')]) ? $g('loai_kiem_ke') : '',
    'ma_kho' => in_array($g('ma_kho'), $khoIds, true) ? $g('ma_kho') : '',
    'trang_thai' => in_array($g('trang_thai'), $validStatuses, true) ? $g('trang_thai') : '',
    'tu_ngay' => $rawFrom !== '' && $isDate($rawFrom) ? $rawFrom : '',
    'den_ngay' => $rawTo !== '' && $isDate($rawTo) ? $rawTo : '',
    'limit' => $perPage,
];

$where = [];
$types = '';
$params = [];

if ($f['q'] !== '') {
    $where[] = '(b.maBienBanKiemKe LIKE ? OR k.tenKho LIKE ?)';
    $types .= 'ss';
    $params[] = '%' . $f['q'] . '%';
    $params[] = '%' . $f['q'] . '%';
}
if ($f['loai_kiem_ke'] !== '') {
    $where[] = 'b.loaiKiemKe = ?';
    $types .= 's';
    $params[] = $f['loai_kiem_ke'];
}
if ($f['ma_kho'] !== '') {
    $where[] = 'b.maKho = ?';
    $types .= 's';
    $params[] = $f['ma_kho'];
}
if ($f['trang_thai'] !== '') {
    $where[] = 'b.trangThai = ?';
    $types .= 's';
    $params[] = $f['trang_thai'];
}
if ($f['tu_ngay'] !== '') {
    $where[] = 'b.ngayLap >= ?';
    $types .= 's';
    $params[] = $f['tu_ngay'] . ' 00:00:00';
}
if ($f['den_ngay'] !== '') {
    $where[] = 'b.ngayLap <= ?';
    $types .= 's';
    $params[] = $f['den_ngay'] . ' 23:59:59';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* Phân trang danh sách Master */
$countSql = "SELECT COUNT(*) AS c FROM BienBanKiemKe b JOIN Kho k ON k.maKho = b.maKho $whereSql";
$countRow = uc24_db_one($countSql, $types, $params);
$total = (int)($countRow['c'] ?? 0);
$pages = max(1, (int)ceil($total / $perPage));
$page = min(max(1, (int)$g('p')), $pages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT b.maBienBanKiemKe, b.ngayLap, b.loaiKiemKe, b.maKho, b.maKhuVuc, b.trangThai,
               k.tenKho, kv.tenKhuVuc
        FROM BienBanKiemKe b
        JOIN Kho k ON k.maKho = b.maKho
        LEFT JOIN KhuVuc kv ON kv.maKhuVuc = b.maKhuVuc AND kv.maKho = b.maKho
        $whereSql
        ORDER BY (CASE WHEN b.trangThai = 'Đã hủy' THEN 1 ELSE 0 END) ASC, b.ngayLap DESC, b.maBienBanKiemKe DESC
        LIMIT ? OFFSET ?";
$masterRows = uc24_db_all($sql, $types . 'ii', array_merge($params, [$perPage, $offset]));

function master_pager_url(array $f, int $p): string
{
    $q = array_filter($f, fn($v) => $v !== '' && $v !== null);
    $q['p'] = $p;
    return '?' . http_build_query($q);
}

// Lấy danh sách kho phục vụ modal khởi tạo nhanh
$allWhRaw = uc24_get_warehouses_by_type('NGUYEN_LIEU');
$allWhProd = uc24_get_warehouses_by_type('THANH_PHAM');
$defaultStartDate = date('Y-m-d');
$defaultEndDate = date('Y-m-d', strtotime('+1 day'));

$page_title = 'Kiểm kê kho';
$active_menu = 'UC24';
$breadcrumb = [['Tổng quan', 'index.php'], ['Kiểm kê', null]];
$toolbar_actions = '<button type="button" class="btn btn-primary" onclick="openInitModal()">' . icon('plus', 16) . ' Lập biên bản</button>';

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>

<style>
/* Tối ưu lề và bố cục trang Kiểm kê (UC24) - Chuẩn responsive 15.6 inch 100% */
.cukcuk-layout {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.filter-card {
    margin: 0 !important;
    padding: 12px 18px !important;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
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
.cukcuk-master-card {
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}
.cukcuk-master-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
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
#masterTable th:nth-child(1), #masterTable td:nth-child(1) { width: 15%; font-weight: 600; }
#masterTable th:nth-child(2), #masterTable td:nth-child(2) { width: 14%; }
#masterTable th:nth-child(3), #masterTable td:nth-child(3) { width: 14%; }
#masterTable th:nth-child(4), #masterTable td:nth-child(4) { width: 13%; }
#masterTable th:nth-child(5), #masterTable td:nth-child(5) { width: 15%; }
#masterTable th:nth-child(6), #masterTable td:nth-child(6) { width: 17%; }
#masterTable th:nth-child(7) {
    width: 120px;
    min-width: 120px;
    max-width: 120px;
    text-align: center;
    position: sticky;
    right: 0;
    background: #f8fafc;
    z-index: 3;
    box-shadow: -3px 0 6px -2px rgba(0, 0, 0, 0.06);
}
#masterTable td:nth-child(7) {
    width: 120px;
    min-width: 120px;
    max-width: 120px;
    text-align: center;
    position: sticky;
    right: 0;
    background: #ffffff;
    z-index: 2;
    box-shadow: -3px 0 6px -2px rgba(0, 0, 0, 0.06);
    overflow: visible;
}
#masterTable tbody tr:hover td:nth-child(7) {
    background: #f8fbff;
}
.actions-slot-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    width: 104px;
}
.actions-slot-wrap .act {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    color: #475467;
    background: transparent;
    border: none;
    padding: 0;
    cursor: pointer;
    flex-shrink: 0;
    transition: all .12s ease;
}
.actions-slot-wrap .act:hover {
    background: #f1f5f9;
    color: var(--primary);
}
.actions-slot-wrap .act-danger {
    color: #ef4444;
}
.actions-slot-wrap .act-danger:hover {
    background: #fef2f2;
    color: var(--danger);
}
.actions-slot-wrap .act-placeholder {
    width: 30px;
    height: 30px;
    display: inline-block;
    visibility: hidden;
    flex-shrink: 0;
}
#masterTable th {
    background: #f8fafc;
    font-size: 12.5px;
    font-weight: 700;
    color: #475467;
    padding: 11px 14px;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
}
#masterTable td {
    padding: 11px 14px;
    font-size: 13px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
    white-space: nowrap;
}
#masterTable td:nth-child(1),
#masterTable td:nth-child(2),
#masterTable td:nth-child(3),
#masterTable td:nth-child(4),
#masterTable td:nth-child(5) {
    overflow: hidden;
    text-overflow: ellipsis;
}
#masterTable .actions { overflow: visible; }
#masterTable .actions a, #masterTable .actions button { flex: 0 0 auto; }
#masterTable tbody tr {
    cursor: pointer;
    transition: background .12s ease;
}
#masterTable tbody tr:hover {
    background: #f8fbff;
}
#masterTable tbody tr:last-child td {
    border-bottom: none;
}
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
@media (max-width: 900px) {
    #masterTable th, #masterTable td { padding: 8px 10px; font-size: 12px; }
}
</style>

<div class="cukcuk-layout">

    <!-- Bộ lọc ngang tinh gọn trên 1 dòng duy nhất -->
    <form class="card filter-card" method="get" action="<?= e(url(uc_url('UC24'))) ?>">
        <div class="filter-row">
            <div class="filter-group filter-search">
                <label>Tìm kiếm</label>
                <input type="text" name="q" value="<?= e($f['q']) ?>" placeholder="Mã biên bản / Tên kho...">
            </div>

            <div class="filter-group filter-select">
                <label>Kho kiểm kê</label>
                <select name="ma_kho">
                    <option value="">Tất cả kho</option>
                    <?php foreach ($khoList as $k): ?>
                        <option value="<?= e($k['maKho']) ?>" <?= $f['ma_kho'] === $k['maKho'] ? 'selected' : '' ?>><?= e($k['tenKho']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group filter-select">
                <label>Trạng thái</label>
                <select name="trang_thai">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach ($validStatuses as $st): ?>
                        <option value="<?= e($st) ?>" <?= $f['trang_thai'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group filter-date">
                <label>Từ ngày</label>
                <input type="date" name="tu_ngay" value="<?= e($f['tu_ngay']) ?>">
            </div>

            <div class="filter-group filter-date">
                <label>Đến ngày</label>
                <input type="date" name="den_ngay" value="<?= e($f['den_ngay']) ?>">
            </div>

            <div class="filter-buttons" style="display: flex; flex-direction: row; align-items: center; gap: 8px; align-self: flex-end; height: 36px;">
                <button type="submit" class="btn btn-primary btn-icon" title="Tìm kiếm">
                    <?= icon('search', 16) ?>
                </button>
                <a href="<?= e(url(uc_url('UC24'))) ?>" class="btn btn-ghost btn-icon" title="Đặt lại bộ lọc">
                    <?= icon('repeat', 16) ?>
                </a>
            </div>
        </div>
    </form>

    <!-- BẢNG MASTER: DANH SÁCH BIÊN BẢN KIỂM KÊ -->
    <div class="cukcuk-master-card">
        <div class="cukcuk-master-head">
            <h2 style="font-size: 14px; font-weight: 700; margin: 0; color: var(--navy); display: flex; align-items: center; gap: 8px;">
                <?= icon('list', 16) ?> Danh sách biên bản kiểm kê
            </h2>
        </div>

        <div class="table-wrap" style="overflow-x: hidden; margin: 0;">
            <table class="table" id="masterTable">
                <thead>
                    <tr>
                        <th>Mã biên bản</th>
                        <th>Loại kiểm kê</th>
                        <th>Kho</th>
                        <th>Khu vực</th>
                        <th>Ngày lập</th>
                        <th>Trạng thái</th>
                        <th style="text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($masterRows)): ?>
                    <tr>
                        <td colspan="7" class="empty-row" style="text-align: center; color: var(--muted); padding: 28px 0;">
                            Không có biên bản kiểm kê nào phù hợp với điều kiện tìm kiếm.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($masterRows as $row):
                        $detailUrl = url(uc_url('UC24', 'form.php') . '?mode=detail&id=' . urlencode($row['maBienBanKiemKe']));
                        $countUrl = url(uc_url('UC24', 'form.php') . '?mode=count&id=' . urlencode($row['maBienBanKiemKe']));
                        $loaiLabel = uc24_type_label($row['loaiKiemKe'] ?? 'NGUYEN_LIEU');
                        $kvName = !empty($row['tenKhuVuc']) ? $row['tenKhuVuc'] : 'Toàn kho';
                        $rawNgay = !empty($row['ngayLap']) ? $row['ngayLap'] : null;
                        $ngayFmt = $rawNgay ? date('d/m/Y H:i', strtotime($rawNgay)) : '-';
                        $canEdit = ($row['trangThai'] !== 'Đã đối chiếu' && $row['trangThai'] !== 'Đã hủy');
                        $canCancel = ($row['trangThai'] === 'Chờ kiểm kê');
                    ?>
                        <tr class="master-row" onclick="window.location.href = '<?= e($detailUrl) ?>';">
                            <td style="font-weight: 600;">
                                <a class="link" href="<?= e($detailUrl) ?>" onclick="event.stopPropagation();">
                                     <?= e($row['maBienBanKiemKe']) ?>
                                </a>
                            </td>
                            <td>
                                <?= e($loaiLabel) ?>
                            </td>
                            <td title="<?= e($row['tenKho']) ?>">
                                <?= e($row['tenKho']) ?>
                            </td>
                            <td>
                                <?= e($kvName) ?>
                            </td>
                            <td>
                                <?= e($ngayFmt) ?>
                            </td>
                            <td>
                                <?= uc24_status_dot($row['trangThai']) ?>
                            </td>
                            <td style="text-align: center;" onclick="event.stopPropagation();">
                                <div class="actions-slot-wrap">
                                    <a class="act" title="Xem chi tiết" href="<?= e($detailUrl) ?>">
                                        <?= icon('eye', 16) ?>
                                    </a>
                                    <?php if ($canEdit): ?>
                                        <a class="act" title="Ghi nhận kiểm đếm" href="<?= e($countUrl) ?>" onclick="alert('Chức năng Ghi nhận kết quả kiểm đếm thuộc quy trình kiểm đếm kho (UC25), không nằm trong phạm vi demo UC24 hiện tại.'); return false;">
                                            <?= icon('edit', 16) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="act-placeholder"></span>
                                    <?php endif; ?>
                                    <?php if ($canCancel): ?>
                                        <button type="button" class="act act-danger" title="Hủy biên bản" onclick="performCancel('<?= e($row['maBienBanKiemKe']) ?>');">
                                            <?= icon('trash', 16) ?>
                                        </button>
                                    <?php else: ?>
                                        <span class="act-placeholder"></span>
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
            <div style="font-size: 12.5px; color: #64748b;">
                Hiển thị <b><?= min($total, $offset + 1) ?> - <?= min($total, $offset + count($masterRows)) ?></b> trong tổng số <b><?= $total ?></b> biên bản
            </div>
            <div class="pager-links" style="display: flex; gap: 4px; align-items: center;">
                <?php if ($page > 1): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= e(master_pager_url($f, $page - 1)) ?>" style="min-width: 28px; height: 28px; padding: 0 6px; font-size: 12px;" title="Trang trước">&lt;</a>
                <?php else: ?>
                    <span class="btn btn-ghost btn-sm" style="min-width: 28px; height: 28px; padding: 0 6px; font-size: 12px; opacity: 0.35; cursor: not-allowed;">&lt;</span>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $pages; $p++): ?>
                    <?php if ($p === $page): ?>
                        <span class="btn btn-primary btn-sm" style="min-width: 28px; height: 28px; padding: 0 6px; font-size: 12px; font-weight: 600;"><?= $p ?></span>
                    <?php else: ?>
                        <a class="btn btn-ghost btn-sm" href="<?= e(master_pager_url($f, $p)) ?>" style="min-width: 28px; height: 28px; padding: 0 6px; font-size: 12px;"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= e(master_pager_url($f, $page + 1)) ?>" style="min-width: 28px; height: 28px; padding: 0 6px; font-size: 12px;" title="Trang sau">&gt;</a>
                <?php else: ?>
                    <span class="btn btn-ghost btn-sm" style="min-width: 28px; height: 28px; padding: 0 6px; font-size: 12px; opacity: 0.35; cursor: not-allowed;">&gt;</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>



<!-- Form POST ẩn để thực hiện các thao tác Cancel / Reconcile -->
<form id="hiddenActionForm" method="post" action="<?= e(url(uc_url('UC24'))) ?>" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" id="hiddenAction" value="">
    <input type="hidden" name="id" id="hiddenId" value="">
</form>

<script>
// Hủy biên bản kiểm kê (Chỉ áp dụng khi trạng thái là Chờ kiểm kê)
function performCancel(id) {
    if (!confirm('Bạn có chắc chắn muốn hủy biên bản kiểm kê ' + id + '? Thao tác này không thể hoàn tác.')) {
        return;
    }
    var f = document.getElementById('hiddenActionForm');
    document.getElementById('hiddenAction').value = 'cancel';
    document.getElementById('hiddenId').value = id;
    f.submit();
}

// BƯỚC 1: POPUP (MODAL) KHỞI TẠO NHANH KIỂM KÊ KHO
var WAREHOUSES_BY_TYPE = {
    'NGUYEN_LIEU': <?= json_encode($allWhRaw, JSON_UNESCAPED_UNICODE) ?>,
    'THANH_PHAM': <?= json_encode($allWhProd, JSON_UNESCAPED_UNICODE) ?>
};

function onModalTypeChange(type) {
    var optNL = document.getElementById('optNguyenLieu');
    var optTP = document.getElementById('optThanhPham');
    if (optNL && optTP) {
        if (type === 'THANH_PHAM') {
            optNL.classList.remove('active');
            optTP.classList.add('active');
        } else {
            optTP.classList.remove('active');
            optNL.classList.add('active');
        }
    }
    renderModalWarehouses(type);
}

function renderModalWarehouses(type) {
    var selKho = document.getElementById('modal_maKho');
    if (!selKho) return;
    selKho.innerHTML = '';
    var list = WAREHOUSES_BY_TYPE[type] || [];
    list.forEach(function (wh) {
        var opt = document.createElement('option');
        opt.value = wh.maKho;
        opt.textContent = wh.tenKho;
        selKho.appendChild(opt);
    });
}

function openInitModal() {
    var modal = document.getElementById('initKiemKeModal');
    if (modal) {
        modal.style.display = 'flex';
        var typeRadio = document.querySelector('input[name="modal_loaiKiemKe"]:checked');
        var curType = typeRadio ? typeRadio.value : 'NGUYEN_LIEU';
        renderModalWarehouses(curType);
    }
}

function closeInitModal() {
    var modal = document.getElementById('initKiemKeModal');
    if (modal) modal.style.display = 'none';
}

function confirmInitModal() {
    var typeRadio = document.querySelector('input[name="modal_loaiKiemKe"]:checked');
    var type = typeRadio ? typeRadio.value : 'NGUYEN_LIEU';
    var selKho = document.getElementById('modal_maKho');
    var maKho = selKho ? selKho.value : '';
    var end = document.getElementById('modal_thoiDiemKetThuc').value;

    if (!maKho) {
        alert('Vui lòng chọn kho kiểm kê.');
        return;
    }
    if (!end) {
        alert('Vui lòng chọn ngày kiểm kê đến ngày.');
        return;
    }

    var baseUrl = <?= json_encode(url(uc_url('UC24', 'form.php'))) ?>;
    var url = baseUrl + '?mode=create'
        + '&type=' + encodeURIComponent(type)
        + '&kho=' + encodeURIComponent(maKho)
        + '&end=' + encodeURIComponent(end);
    window.location.href = url;
}
</script>

<!-- MODAL KHỞI TẠO NHANH KIỂM KÊ KHO -->
<style>
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
}
.cukcuk-modal-box {
    background: #ffffff;
    width: 480px;
    max-width: 92vw;
    border-radius: 8px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.12), 0 10px 10px -5px rgba(0,0,0,0.04);
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
    padding: 14px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
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
    padding: 2px 6px;
    border-radius: 4px;
}
.cukcuk-modal-close:hover {
    color: #0f172a;
    background: #e2e8f0;
}
.cukcuk-modal-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.cukcuk-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 12px 20px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}
.modal-segmented-radio {
    display: flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 6px;
    gap: 4px;
}
.seg-radio-option {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 12px;
    font-size: 13.5px;
    font-weight: 600;
    color: #475467;
    border-radius: 5px;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
}
.seg-radio-option input {
    margin: 0;
    accent-color: #2563eb;
    cursor: pointer;
}
.seg-radio-option.active {
    background: #ffffff;
    color: #1d4ed8;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
    height: 38px;
    padding: 0 12px;
    font-size: 13.5px;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    box-sizing: border-box;
    outline: none;
}
.cukcuk-modal-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
</style>

<div id="initKiemKeModal" class="cukcuk-modal-backdrop" style="display: none;">
    <div class="cukcuk-modal-box">
        <div class="cukcuk-modal-header">
            <h3 class="cukcuk-modal-title">Kiểm kê kho</h3>
            <button type="button" class="cukcuk-modal-close" onclick="closeInitModal()" title="Đóng">&times;</button>
        </div>
        <div class="cukcuk-modal-body">
            <!-- 1. Loại kiểm kê * -->
            <div>
                <label class="cukcuk-modal-label">Loại kiểm kê <span style="color: #ef4444;">*</span></label>
                <div class="modal-segmented-radio">
                    <label class="seg-radio-option active" id="optNguyenLieu">
                        <input type="radio" name="modal_loaiKiemKe" value="NGUYEN_LIEU" checked onchange="onModalTypeChange('NGUYEN_LIEU')">
                        <span>Nguyên liệu</span>
                    </label>
                    <label class="seg-radio-option" id="optThanhPham">
                        <input type="radio" name="modal_loaiKiemKe" value="THANH_PHAM" onchange="onModalTypeChange('THANH_PHAM')">
                        <span>Thành phẩm</span>
                    </label>
                </div>
            </div>

            <!-- 2. Kiểm kê kho * -->
            <div>
                <label for="modal_maKho" class="cukcuk-modal-label">Kiểm kê kho <span style="color: #ef4444;">*</span></label>
                <select id="modal_maKho" class="cukcuk-modal-input">
                    <!-- Populated dynamically via JS -->
                </select>
            </div>

            <!-- 3. Kiểm kê đến ngày * (chỉ cần chọn kiểm kê đến ngày) -->
            <div>
                <label for="modal_thoiDiemKetThuc" class="cukcuk-modal-label">Kiểm kê đến ngày <span style="color: #ef4444;">*</span></label>
                <input type="date" id="modal_thoiDiemKetThuc" class="cukcuk-modal-input" value="<?= $defaultEndDate ?>">
            </div>
        </div>
        <div class="cukcuk-modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeInitModal()" style="height: 36px; padding: 0 16px; font-size: 13.5px;">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="confirmInitModal()" style="height: 36px; padding: 0 18px; font-size: 13.5px; font-weight: 600;">Đồng ý</button>
        </div>
    </div>
</div>

<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
