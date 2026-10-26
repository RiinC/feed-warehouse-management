<?php
/** UC10 - Danh sách phiếu nhập kho (QLK02, tên cột Domain Model). */
require_once __DIR__ . '/../../includes/auth.php';
require_module('UC10');

$page_title = 'Lập phiếu nhập kho';
$active_menu = 'UC10';
$breadcrumb = [['Tổng quan', 'index.php'], ['Nhập kho', null]];
$toolbar_actions = '<a class="btn btn-primary" href="form.php">'
                 . icon('plus', 16)
                 . ' Tạo phiếu nhập</a>';

$soPhieu = trim((string)($_GET['so_phieu'] ?? ''));
$tuNgay = trim((string)($_GET['tu_ngay'] ?? ''));
$denNgay = trim((string)($_GET['den_ngay'] ?? ''));
$maKho = trim((string)($_GET['ma_kho'] ?? ''));
$trangThai = trim((string)($_GET['trang_thai'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$allowedStatuses = ['Đã lập', 'Đã nhập kho', 'Đã hủy'];
if (!in_array($trangThai, $allowedStatuses, true)) $trangThai = '';
foreach (['tuNgay', 'denNgay'] as $key) {
    if ($$key !== '') {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $$key);
        if (!$date || $date->format('Y-m-d') !== $$key) $$key = '';
    }
}
$khoList = [];
$rows = [];
$total = 0;
$totalPages = 1;
$offset = 0;
$dbError = null;
try {
    $khoList = db_all('SELECT maKho, tenKho FROM Kho ORDER BY tenKho', '', [], DB_UC10);
    $where = [];
    $types = '';
    $params = [];
    if ($soPhieu !== '') { $where[] = 'pn.maPhieuNhapKho LIKE ?'; $types .= 's'; $params[] = '%' . $soPhieu . '%'; }
    if ($tuNgay !== '') { $where[] = 'pn.ngayLapPhieu >= ?'; $types .= 's'; $params[] = $tuNgay . ' 00:00:00'; }
    if ($denNgay !== '') { $where[] = 'pn.ngayLapPhieu <= ?'; $types .= 's'; $params[] = $denNgay . ' 23:59:59'; }
    if ($maKho !== '') { $where[] = 'pn.maKho = ?'; $types .= 's'; $params[] = $maKho; }
    if ($trangThai !== '') { $where[] = 'pn.trangThai = ?'; $types .= 's'; $params[] = $trangThai; }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $count = db_one('SELECT COUNT(*) AS total FROM PhieuNhapKho pn' . $whereSql, $types, $params, DB_UC10);
    $total = (int)($count['total'] ?? 0);
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    // Nhân viên thuộc QLK00, khóa chính maNV; các bảng còn lại thuộc QLK02.
    $sql = 'SELECT pn.maPhieuNhapKho, pn.ngayLapPhieu, pn.soLuongThucNhap,
                   pn.donViTinh, pn.trangThai, pn.maNhanVien, pn.maLoHang,
                   hh.tenHangHoa, k.tenKho, nv.hoTen AS nguoiLap
            FROM PhieuNhapKho pn
            LEFT JOIN LoHang lh ON lh.maLoHang = pn.maLoHang
            LEFT JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa
            LEFT JOIN Kho k ON k.maKho = pn.maKho
            LEFT JOIN QLK00.nhanvien nv
    ON nv.maNV COLLATE utf8mb4_unicode_ci
     = pn.maNhanVien COLLATE utf8mb4_unicode_ci'
            . $whereSql . ' ORDER BY pn.ngayLapPhieu DESC, pn.maPhieuNhapKho DESC LIMIT ? OFFSET ?';
    $rows = db_all($sql, $types . 'ii', array_merge($params, [$perPage, $offset]), DB_UC10);
} catch (Throwable $ex) {
    error_log('UC10 index: ' . $ex->getMessage());
    $dbError = 'Không tải được danh sách phiếu nhập kho. Hãy kiểm tra cấu trúc QLK02, QLK00.nhanvien và kết nối trong config.php.';
}
function uc10_index_page_url(int $number): string {
    $query = $_GET;
    $query['page'] = $number;
    return url('pages/UC10_nhap_kho/index.php') . '?' . http_build_query($query);
}
include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<style>
.uc10-filter-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px}
.uc10-filter-grid label{display:block;margin-bottom:8px;font-weight:600;color:#213653}
.uc10-filter-grid input,.uc10-filter-grid select{box-sizing:border-box;width:100%;height:44px;border:1px solid #d4ddeb;border-radius:9px;background:#fff;padding:0 12px;color:#213653;font:inherit}
.uc10-actions{display:flex;gap:12px;align-items:center;margin-top:18px}
.uc10-table-wrap{width:100%;overflow-x:auto}
.uc10-table{width:100%;border-collapse:collapse;white-space:nowrap;font-size:14px}
.uc10-table th{background:#f4f7fb;color:#263c5c;text-align:left;font-weight:600;padding:15px 12px}
.uc10-table td{padding:15px 12px;border-bottom:1px solid #e4eaf2}
.uc10-table tbody tr:nth-child(even){background:#f8faff}
.uc10-table a{color:#1667d9;text-decoration:none;font-weight:600}
.uc10-table a:hover{text-decoration:underline}
.uc10-status{display:inline-block;padding:6px 12px;border-radius:999px;font-size:12px;font-weight:600}
.uc10-status-done{background:#dff5e8;color:#167044}
.uc10-status-draft{background:#fff0c6;color:#936200}
.uc10-status-cancel{background:#ffe4e4;color:#ad3434}
.uc10-card{padding:24px;margin-bottom:22px}
.uc10-card-header{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px}
.uc10-card-header h2{font-size:18px;margin:0;color:#0e3064}
.uc10-muted{color:#687994;font-size:14px}
.uc10-error{padding:16px;border:1px solid #e9a8a8;border-radius:9px;background:#fff1f1;color:#9c2424;margin-bottom:20px}
.uc10-pagination{display:flex;justify-content:center;gap:8px;margin-top:22px;flex-wrap:wrap}
@media(max-width:1250px){.uc10-filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:760px){.uc10-filter-grid{grid-template-columns:1fr}.uc10-card{padding:16px}.uc10-card-header{align-items:flex-start;flex-direction:column}}
</style>
<?php if ($dbError !== null): ?><div class="uc10-error"><?= e($dbError) ?></div><?php endif; ?>
<div class="card uc10-card">
  <form method="get" action="">
    <div class="uc10-filter-grid">
      <div><label for="uc10-so-phieu">Số phiếu</label><input id="uc10-so-phieu" type="text" name="so_phieu" placeholder="VD: PN2026-0001" value="<?= e($soPhieu) ?>"></div>
      <div><label for="uc10-tu-ngay">Từ ngày</label><input id="uc10-tu-ngay" type="date" name="tu_ngay" value="<?= e($tuNgay) ?>"></div>
      <div><label for="uc10-den-ngay">Đến ngày</label><input id="uc10-den-ngay" type="date" name="den_ngay" value="<?= e($denNgay) ?>"></div>
      <div><label for="uc10-kho">Kho nhập</label><select id="uc10-kho" name="ma_kho"><option value="">Tất cả</option>
        <?php foreach ($khoList as $kho): ?><option value="<?= e($kho['maKho']) ?>" <?= $maKho === $kho['maKho'] ? 'selected' : '' ?>><?= e($kho['tenKho']) ?></option><?php endforeach; ?>
      </select></div>
      <div><label for="uc10-trang-thai">Trạng thái</label><select id="uc10-trang-thai" name="trang_thai"><option value="">Tất cả</option>
        <?php foreach ($allowedStatuses as $status): ?><option value="<?= e($status) ?>" <?= $trangThai === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
      </select></div>
    </div>
    <div class="uc10-actions"><button type="submit" class="btn btn-primary"><?= icon('search', 16) ?> Tìm kiếm</button><a class="btn btn-ghost" href="">Đặt lại</a></div>
  </form>
</div>
<div class="card uc10-card">
  <div class="uc10-card-header"><h2>Danh sách phiếu nhập kho</h2><span class="uc10-muted">Tổng: <?= number_format($total, 0, ',', '.') ?> phiếu</span></div>
  <div class="uc10-table-wrap"><table class="uc10-table"><thead><tr>
    <th>STT</th><th>Số phiếu</th><th>Ngày lập</th><th>Mã lô</th><th>Tên hàng hóa</th><th>Kho nhập</th><th>Người lập</th><th>Số lượng thực nhập</th><th>Trạng thái</th><th>Thao tác</th>
  </tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="10" style="text-align:center;padding:42px 12px;color:#718096"><?= $dbError !== null ? 'Không thể tải danh sách.' : 'Không tìm thấy phiếu nhập kho nào.' ?></td></tr>
  <?php else: foreach ($rows as $i => $row):
    $statusClass = $row['trangThai'] === 'Đã nhập kho' ? 'uc10-status-done' : ($row['trangThai'] === 'Đã hủy' ? 'uc10-status-cancel' : 'uc10-status-draft');
    $detailUrl = 'detail.php?' . http_build_query([
    'maPhieuNhapKho' => $row['maPhieuNhapKho']
]);
  ?>
    <tr>
      <td><?= $offset + $i + 1 ?></td>
      <td><a href="<?= e($detailUrl) ?>"><?= e($row['maPhieuNhapKho']) ?></a></td>
      <td><?= e(date('d/m/Y', strtotime($row['ngayLapPhieu']))) ?></td>
      <td><?= e($row['maLoHang']) ?></td>
      <td><?= e($row['tenHangHoa'] ?? '—') ?></td>
      <td><?= e($row['tenKho'] ?? '—') ?></td>
      <td><?= e($row['nguoiLap'] ?? $row['maNhanVien']) ?></td>
      <td><?= e(number_format((float)$row['soLuongThucNhap'], 2, ',', '.')) ?> <?= e($row['donViTinh']) ?></td>
      <td><span class="uc10-status <?= e($statusClass) ?>"><?= e($row['trangThai']) ?></span></td>
      <td><a href="<?= e($detailUrl) ?>" title="Xem chi tiết"><?= icon('eye', 18) ?></a></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody></table></div>
  <?php if ($totalPages > 1): ?><div class="uc10-pagination">
    <?php if ($page > 1): ?><a class="btn btn-ghost" href="<?= e(uc10_index_page_url($page - 1)) ?>">Trước</a><?php endif; ?>
    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?><a class="btn <?= $p === $page ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e(uc10_index_page_url($p)) ?>"><?= $p ?></a><?php endfor; ?>
    <?php if ($page < $totalPages): ?><a class="btn btn-ghost" href="<?= e(uc10_index_page_url($page + 1)) ?>">Sau</a><?php endif; ?>
  </div><?php endif; ?>
</div>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
