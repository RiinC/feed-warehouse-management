
<?php
/**
 * UC10 - Danh sách phiếu nhập kho
 * Database: QLK02
 * Nhân viên: QLK00.nhanvien
 */

require_once __DIR__ . '/../../includes/auth.php';
require_module('UC10');

$page_title = 'Lập phiếu nhập kho';
$active_menu = 'UC10';

$breadcrumb = [
    ['Tổng quan', 'index.php'],
    ['Nhập kho', null]
];

$toolbar_actions = '<a class="btn btn-primary" href="form.php">'
    . icon('plus', 16)
    . ' Tạo phiếu nhập</a>';

/* Hủy phiếu: chỉ nhận POST và chỉ cho phép hủy phiếu Đã lập. */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['uc10_cancel_token'])) {
    $_SESSION['uc10_cancel_token'] = bin2hex(random_bytes(32));
}

$cancelMessage = null;
$cancelError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_receipt') {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    $receiptCode = trim((string)($_POST['maPhieuNhapKho'] ?? ''));

    if (!hash_equals($_SESSION['uc10_cancel_token'], $postedToken)) {
        $cancelError = 'Phiên xác nhận không hợp lệ. Vui lòng tải lại trang.';
    } elseif ($receiptCode === '' || strlen($receiptCode) > 100) {
        $cancelError = 'Mã phiếu nhập kho không hợp lệ.';
    } else {
        try {
            // Kết nối riêng QLK02 để không ảnh hưởng kết nối QLK01.
            if (function_exists('db_uc10')) {
                $cancelConn = db_uc10();
            } else {
                $cancelConn = new mysqli(
                    DB_HOST, DB_USER, DB_PASS,
                    defined('DB_NAME_UC10') ? DB_NAME_UC10 : 'qlk02'
                );
                $cancelConn->set_charset('utf8mb4');
            }
            $stmt = $cancelConn->prepare(
                "UPDATE PhieuNhapKho SET trangThai = 'Đã hủy' " .
                "WHERE maPhieuNhapKho = ? AND trangThai = 'Đã lập'"
            );
            $stmt->bind_param('s', $receiptCode);
            $stmt->execute();
            $changed = $stmt->affected_rows;
            $stmt->close();

            if ($changed === 1) {
                $_SESSION['uc10_cancel_success'] = 'Đã hủy phiếu ' . $receiptCode . ' thành công.';
                $returnQuery = $_GET;
                header('Location: index.php' . ($returnQuery ? '?' . http_build_query($returnQuery) : ''));
                exit;
            }
            $cancelError = 'Không thể hủy phiếu. Phiếu không tồn tại hoặc không còn ở trạng thái Đã lập.';
        } catch (Throwable $ex) {
            error_log('UC10 cancel: ' . $ex->getMessage());
            $cancelError = 'Có lỗi khi hủy phiếu nhập kho. Vui lòng thử lại.';
        }
    }
}

if (isset($_SESSION['uc10_cancel_success'])) {
    $cancelMessage = (string)$_SESSION['uc10_cancel_success'];
    unset($_SESSION['uc10_cancel_success']);
}

/* ==============================
   1. NHẬN DỮ LIỆU LỌC
============================== */

$soPhieu = trim((string)($_GET['so_phieu'] ?? ''));
$tuNgay = trim((string)($_GET['tu_ngay'] ?? ''));
$denNgay = trim((string)($_GET['den_ngay'] ?? ''));
$maKho = trim((string)($_GET['ma_kho'] ?? ''));
$trangThai = trim((string)($_GET['trang_thai'] ?? ''));

$allowedStatuses = [
    'Đã lập',
    'Đã nhập kho',
    'Đã hủy'
];

if (!in_array($trangThai, $allowedStatuses, true)) {
    $trangThai = '';
}

/* Kiểm tra ngày hợp lệ */
foreach (['tuNgay', 'denNgay'] as $key) {
    if ($$key !== '') {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $$key
        );

        if (
            !$date ||
            $date->format('Y-m-d') !== $$key
        ) {
            $$key = '';
        }
    }
}

/* ==============================
   2. PHÂN TRANG
============================== */

$page = max(1, (int)($_GET['page'] ?? 1));

$allowedPerPages = [5, 10, 20, 50];

$perPage = (int)($_GET['per_page'] ?? 5);

if (!in_array($perPage, $allowedPerPages, true)) {
    $perPage = 5;
}

$total = 0;
$totalPages = 1;
$offset = 0;

$rows = [];
$khoList = [];
$dbError = null;

/* ==============================
   3. LẤY DỮ LIỆU
============================== */

try {

    $khoList = db_all(
        'SELECT maKho, tenKho
         FROM Kho
         ORDER BY tenKho',
        '',
        [],
        DB_UC10
    );

    $where = [];
    $types = '';
    $params = [];

    if ($soPhieu !== '') {
        $where[] = 'pn.maPhieuNhapKho LIKE ?';
        $types .= 's';
        $params[] = '%' . $soPhieu . '%';
    }

    if ($tuNgay !== '') {
        $where[] = 'pn.ngayLapPhieu >= ?';
        $types .= 's';
        $params[] = $tuNgay . ' 00:00:00';
    }

    if ($denNgay !== '') {
        $where[] = 'pn.ngayLapPhieu <= ?';
        $types .= 's';
        $params[] = $denNgay . ' 23:59:59';
    }

    if ($maKho !== '') {
        $where[] = 'pn.maKho = ?';
        $types .= 's';
        $params[] = $maKho;
    }

    if ($trangThai !== '') {
        $where[] = 'pn.trangThai = ?';
        $types .= 's';
        $params[] = $trangThai;
    }

    $whereSql = $where
        ? ' WHERE ' . implode(' AND ', $where)
        : '';

    /* Đếm tổng số phiếu */
    $count = db_one(
        'SELECT COUNT(*) AS total
         FROM PhieuNhapKho pn' . $whereSql,
        $types,
        $params,
        DB_UC10
    );

    $total = (int)($count['total'] ?? 0);

    $totalPages = max(
        1,
        (int)ceil($total / $perPage)
    );

    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    /* Danh sách phiếu */
    $sql = "
        SELECT
            pn.maPhieuNhapKho,
            pn.ngayLapPhieu,
            pn.soLuongThucNhap,
            pn.donViTinh,
            pn.trangThai,
            pn.maNhanVien,
            pn.maLoHang,
            hh.tenHangHoa,
            k.tenKho,
            nv.hoTen AS nguoiLap

        FROM PhieuNhapKho pn

        LEFT JOIN LoHang lh
            ON lh.maLoHang = pn.maLoHang

        LEFT JOIN HangHoa hh
            ON hh.maHangHoa = lh.maHangHoa

        LEFT JOIN Kho k
            ON k.maKho = pn.maKho

        LEFT JOIN QLK00.nhanvien nv
            ON CONVERT(nv.maNV USING utf8mb4)
               COLLATE utf8mb4_unicode_ci
             = CONVERT(pn.maNhanVien USING utf8mb4)
               COLLATE utf8mb4_unicode_ci

        $whereSql

        ORDER BY
            pn.ngayLapPhieu DESC,
            pn.maPhieuNhapKho DESC

        LIMIT ? OFFSET ?
    ";

    $rows = db_all(
        $sql,
        $types . 'ii',
        array_merge($params, [$perPage, $offset]),
        DB_UC10
    );

} catch (Throwable $ex) {

    error_log('UC10 index: ' . $ex->getMessage());

    $dbError = 'Không tải được danh sách phiếu nhập kho. '
        . 'Hãy kiểm tra kết nối và cấu trúc database.';
}

/* ==============================
   4. LINK PHÂN TRANG
============================== */

function uc10_index_page_url(int $number): string
{
    $query = $_GET;
    $query['page'] = $number;

    return 'index.php?' . http_build_query($query);
}

/* ==============================
   5. GIAO DIỆN
============================== */

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';

?>

<!-- Flatpickr: lịch hiển thị dd/mm/yyyy -->
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
>

<style>
.uc10-filter-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 16px;
}

.uc10-filter-grid label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #213653;
}

.uc10-filter-grid input,
.uc10-filter-grid select {
    box-sizing: border-box;
    width: 100%;
    height: 44px;
    border: 1px solid #d4ddeb;
    border-radius: 9px;
    background: #fff;
    padding: 0 12px;
    color: #213653;
    font: inherit;
}

.uc10-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 18px;
}

.uc10-card {
    padding: 24px;
    margin-bottom: 22px;
}

.uc10-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
}

.uc10-card-header h2 {
    font-size: 18px;
    margin: 0;
    color: #0e3064;
}

.uc10-muted {
    color: #687994;
    font-size: 14px;
}

.uc10-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.uc10-table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
    font-size: 14px;
}

.uc10-table th {
    background: #f4f7fb;
    color: #263c5c;
    text-align: left;
    font-weight: 600;
    padding: 15px 12px;
}

.uc10-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #e4eaf2;
}

.uc10-table tbody tr:nth-child(even) {
    background: #f8faff;
}

.uc10-table a {
    color: #1667d9;
    text-decoration: none;
    font-weight: 600;
}

.uc10-table a:hover {
    text-decoration: underline;
}

/* Trạng thái */
.uc10-status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}

.uc10-status-done {
    background: #dff5e8;
    color: #167044;
}

.uc10-status-draft {
    background: #fff0c6;
    color: #936200;
}

.uc10-status-cancel {
    background: #ffe4e4;
    color: #ad3434;
}

/* Phân trang */
.uc10-pagination-bar {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 16px;
    margin-top: 22px;
    padding-top: 16px;
    border-top: 1px solid #e4eaf2;
}

.uc10-pagination-total {
    font-size: 13px;
    color: #64748b;
}

.uc10-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.uc10-page-btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    border: 1px solid #dce5f2;
    border-radius: 5px;
    background: #fff;
    color: #37608c;
    text-decoration: none;
    font-size: 13px;
    box-sizing: border-box;
}

.uc10-page-btn:hover {
    background: #edf4ff;
}

.uc10-page-btn.active {
    background: #1670e8;
    color: #fff;
    border-color: #1670e8;
    font-weight: 600;
}

.uc10-page-btn.disabled {
    opacity: 0.4;
    pointer-events: none;
}

.uc10-pagination-size {
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

.uc10-pagination-size select {
    height: 32px;
    padding: 0 8px;
    border: 1px solid #dce5f2;
    border-radius: 5px;
    background: #fff;
    color: #37608c;
    font-size: 13px;
    cursor: pointer;
}

.uc10-row-actions { display:flex; align-items:center; gap:12px; }
.uc10-cancel-form { display:inline; margin:0; }
.uc10-cancel-btn { border:0; background:transparent; color:#c0392b; cursor:pointer; padding:3px 5px; font:inherit; font-weight:600; }
.uc10-cancel-btn:hover { color:#922b21; text-decoration:underline; }
.uc10-success { padding:14px 16px; margin-bottom:18px; border:1px solid #a7dec0; border-radius:9px; background:#e9f8f0; color:#176b3f; }

.uc10-error {
    padding: 16px;
    border: 1px solid #e9a8a8;
    border-radius: 9px;
    background: #fff1f1;
    color: #9c2424;
    margin-bottom: 20px;
}

/* Flatpickr */
.flatpickr-input[readonly] {
    cursor: pointer;
}

@media (max-width: 1250px) {
    .uc10-filter-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .uc10-filter-grid {
        grid-template-columns: 1fr;
    }

    .uc10-card {
        padding: 16px;
    }

    .uc10-card-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .uc10-pagination-bar {
        grid-template-columns: 1fr;
        justify-items: center;
    }
}
</style>

<?php if ($dbError !== null): ?>
    <div class="uc10-error">
        <?= e($dbError) ?>
    </div>
<?php endif; ?>

<?php if ($cancelMessage !== null): ?>
    <div class="uc10-success"><?= e($cancelMessage) ?></div>
<?php endif; ?>
<?php if ($cancelError !== null): ?>
    <div class="uc10-error"><?= e($cancelError) ?></div>
<?php endif; ?>

<!-- ==============================
     BỘ LỌC
============================== -->

<div class="card uc10-card">

    <form method="get" action="index.php">

        <input
            type="hidden"
            name="per_page"
            value="<?= $perPage ?>"
        >

        <div class="uc10-filter-grid">

            <div>
                <label for="uc10-so-phieu">Số phiếu</label>
                <input
                    id="uc10-so-phieu"
                    type="text"
                    name="so_phieu"
                    placeholder="VD: PN2026-0001"
                    value="<?= e($soPhieu) ?>"
                >
            </div>

            <!-- Từ ngày -->
            <div>
                <label for="uc10-tu-ngay">Từ ngày</label>
                <input
                    id="uc10-tu-ngay"
                    type="text"
                    name="tu_ngay"
                    placeholder="dd/mm/yyyy"
                    value="<?= e($tuNgay) ?>"
                    autocomplete="off"
                >
            </div>

            <!-- Đến ngày -->
            <div>
                <label for="uc10-den-ngay">Đến ngày</label>
                <input
                    id="uc10-den-ngay"
                    type="text"
                    name="den_ngay"
                    placeholder="dd/mm/yyyy"
                    value="<?= e($denNgay) ?>"
                    autocomplete="off"
                >
            </div>

            <div>
                <label for="uc10-kho">Kho nhập</label>
                <select id="uc10-kho" name="ma_kho">
                    <option value="">Tất cả</option>

                    <?php foreach ($khoList as $kho): ?>
                        <option
                            value="<?= e($kho['maKho']) ?>"
                            <?= $maKho === $kho['maKho'] ? 'selected' : '' ?>
                        >
                            <?= e($kho['tenKho']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div>
                <label for="uc10-trang-thai">Trạng thái</label>
                <select id="uc10-trang-thai" name="trang_thai">
                    <option value="">Tất cả</option>

                    <?php foreach ($allowedStatuses as $status): ?>
                        <option
                            value="<?= e($status) ?>"
                            <?= $trangThai === $status ? 'selected' : '' ?>
                        >
                            <?= e($status) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

        </div>

        <div class="uc10-actions">

            <button type="submit" class="btn btn-primary">
                <?= icon('search', 16) ?>
                Tìm kiếm
            </button>

            <a class="btn btn-ghost" href="index.php">
                Đặt lại
            </a>

        </div>

    </form>

</div>

<!-- ==============================
     DANH SÁCH PHIẾU
============================== -->

<div class="card uc10-card">

    <div class="uc10-card-header">
        <h2>Danh sách phiếu nhập kho</h2>

        <span class="uc10-muted">
            Tổng:
            <?= number_format($total, 0, ',', '.') ?>
            phiếu
        </span>
    </div>

    <div class="uc10-table-wrap">

        <table class="uc10-table">

            <thead>
                <tr>
                    <th>STT</th>
                    <th>Số phiếu</th>
                    <th>Ngày lập</th>
                    <th>Mã lô</th>
                    <th>Tên hàng hóa</th>
                    <th>Kho nhập</th>
                    <th>Người lập</th>
                    <th>Số lượng thực nhập</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!$rows): ?>

                    <tr>
                        <td colspan="10"
                            style="text-align:center;padding:42px 12px;color:#718096">
                            <?= $dbError !== null
                                ? 'Không thể tải danh sách.'
                                : 'Không tìm thấy phiếu nhập kho nào.' ?>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($rows as $i => $row): ?>

                        <?php
                        if ($row['trangThai'] === 'Đã nhập kho') {
                            $statusClass = 'uc10-status-done';
                        } elseif ($row['trangThai'] === 'Đã hủy') {
                            $statusClass = 'uc10-status-cancel';
                        } else {
                            $statusClass = 'uc10-status-draft';
                        }

                        $detailUrl = 'detail.php?' . http_build_query([
                            'maPhieuNhapKho' => $row['maPhieuNhapKho']
                        ]);
                        ?>

                        <tr>

                            <td><?= $offset + $i + 1 ?></td>

                            <td>
                                <a href="<?= e($detailUrl) ?>">
                                    <?= e($row['maPhieuNhapKho']) ?>
                                </a>
                            </td>

                            <td>
                                <?= e(date(
                                    'd/m/Y',
                                    strtotime($row['ngayLapPhieu'])
                                )) ?>
                            </td>

                            <td><?= e($row['maLoHang']) ?></td>

                            <td>
                                <?= e($row['tenHangHoa'] ?? '—') ?>
                            </td>

                            <td>
                                <?= e($row['tenKho'] ?? '—') ?>
                            </td>

                            <td>
                                <?= e(
                                    $row['nguoiLap']
                                    ?? $row['maNhanVien']
                                ) ?>
                            </td>

                            <td>
    <?php
    $donViTinh = trim($row['donViTinh']);
    $soLuong = (float)$row['soLuongThucNhap'];

    $soThapPhan = in_array(
        mb_strtolower($donViTinh),
        ['thùng', 'bao'],
        true
    ) ? 0 : 2;
    ?>

    <?= e(number_format($soLuong, $soThapPhan, ',', '.')) ?>
    <?= e($donViTinh) ?>
</td>

                            <td>
                                <span class="uc10-status <?= e($statusClass) ?>">
                                    <?= e($row['trangThai']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="uc10-row-actions">
                                    <a href="<?= e($detailUrl) ?>" title="Xem chi tiết">
                                        <?= icon('eye', 18) ?>
                                    </a>
                                    <?php if ($row['trangThai'] === 'Đã lập'): ?>
                                        <form method="post"
                                              action="<?= e('index.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>"
                                              class="uc10-cancel-form"
                                              onsubmit="return confirm('Bạn có chắc chắn muốn hủy phiếu <?= e($row['maPhieuNhapKho']) ?>?');">
                                            <input type="hidden" name="action" value="cancel_receipt">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['uc10_cancel_token']) ?>">
                                            <input type="hidden" name="maPhieuNhapKho" value="<?= e($row['maPhieuNhapKho']) ?>">
                                            <button type="submit" class="uc10-cancel-btn" title="Hủy phiếu">Hủy</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <!-- ==============================
         PHÂN TRANG
    ============================== -->

    <div class="uc10-pagination-bar">

        <!-- Tổng số -->
        <div class="uc10-pagination-total">
            Tổng số:
            <?= number_format($total, 0, ',', '.') ?>
            bản ghi
        </div>

        <!-- Chuyển trang -->
        <div class="uc10-pagination">

            <?php if ($page > 1): ?>

                <a
                    class="uc10-page-btn"
                    href="<?= e(uc10_index_page_url($page - 1)) ?>"
                    title="Trang trước"
                >
                    &lsaquo;
                </a>

            <?php else: ?>

                <span class="uc10-page-btn disabled">
                    &lsaquo;
                </span>

            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);

            for ($p = $startPage; $p <= $endPage; $p++):
            ?>

                <a
                    class="uc10-page-btn <?= $p === $page ? 'active' : '' ?>"
                    href="<?= e(uc10_index_page_url($p)) ?>"
                    <?= $p === $page ? 'aria-current="page"' : '' ?>
                >
                    <?= $p ?>
                </a>

            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>

                <a
                    class="uc10-page-btn"
                    href="<?= e(uc10_index_page_url($page + 1)) ?>"
                    title="Trang sau"
                >
                    &rsaquo;
                </a>

            <?php else: ?>

                <span class="uc10-page-btn disabled">
                    &rsaquo;
                </span>

            <?php endif; ?>

        </div>

        <!-- Số phiếu/trang -->
        <div class="uc10-pagination-size">

            <form method="get" action="index.php">

                <?php foreach ($_GET as $key => $value): ?>

                    <?php if (
                        !in_array($key, ['per_page', 'page'], true)
                        && is_scalar($value)
                    ): ?>

                        <input
                            type="hidden"
                            name="<?= e((string)$key) ?>"
                            value="<?= e((string)$value) ?>"
                        >

                    <?php endif; ?>

                <?php endforeach; ?>

                <select
                    name="per_page"
                    aria-label="Số phiếu mỗi trang"
                    onchange="this.form.submit()"
                >

                    <?php foreach ($allowedPerPages as $size): ?>

                        <option
                            value="<?= $size ?>"
                            <?= $perPage === $size ? 'selected' : '' ?>
                        >
                            <?= $size ?> / trang
                        </option>

                    <?php endforeach; ?>

                </select>

            </form>

        </div>

    </div>

</div>

<!-- ==============================
     THƯ VIỆN VÀ KHỞI TẠO LỊCH
============================== -->

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    if (typeof flatpickr === 'undefined') {
        console.error('Không tải được thư viện Flatpickr.');
        return;
    }

    const dateOptions = {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        allowInput: false
    };

    flatpickr('#uc10-tu-ngay', dateOptions);
    flatpickr('#uc10-den-ngay', dateOptions);

});
</script>

<?php

include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';

?>
