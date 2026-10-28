<?php
require_once __DIR__ . '/../../includes/auth.php';
require_module('UC23');

$page_title = 'Danh sách phiếu xuất kho';
$active_menu = 'UC23';
$breadcrumb = [['Tổng quan', 'index.php'], ['Xuất kho', null]];
$toolbar_actions = '<a class="btn btn-primary" href="' . e(url(uc_url('UC23', 'form.php'))) . '">' . icon('plus', 16) . ' Lập phiếu xuất</a>';

$db = db(DB_UC23);

/*
 * XEM CHI TIẾT PHIẾU:
 * Tích hợp trực tiếp trong index.php để dự án chỉ cần index.php và form.php.
 * URL mẫu: index.php?view=detail&maPhieuXuat=PX...
 */
if (($_GET['view'] ?? '') === 'detail') {
    $maPhieuXuat = trim($_GET['maPhieuXuat'] ?? '');
    if ($maPhieuXuat === '') {
        redirect(uc_url('UC23', 'index.php'));
    }

    $sqlHeader = "
        SELECT p.*, nv.hoTen AS tenNguoiLap
        FROM PhieuXuatKho p
        LEFT JOIN NhanVienKho nv ON nv.maNhanVien = p.maNhanVien
        WHERE p.maPhieuXuat = ?
        LIMIT 1
    ";
    $detailStmt = $db->prepare($sqlHeader);
    if (!$detailStmt) {
        die('Lỗi truy vấn thông tin phiếu: ' . e($db->error));
    }
    $detailStmt->bind_param('s', $maPhieuXuat);
    $detailStmt->execute();
    $phieu = $detailStmt->get_result()->fetch_assoc();
    $detailStmt->close();

    $page_title = 'Chi tiết phiếu xuất kho';
    $breadcrumb = [
        ['Tổng quan', 'index.php'],
        ['Xuất kho', uc_url('UC23', 'index.php')],
        ['Chi tiết phiếu', null]
    ];

    include ROOT_PATH . '/includes/head.php';
    include ROOT_PATH . '/includes/sidebar.php';
    include ROOT_PATH . '/includes/header.php';
    $toolbar_actions = '<a class="btn btn-light" href="' . e(url(uc_url('UC23', 'index.php'))) . '">Quay lại danh sách</a>';
    include ROOT_PATH . '/includes/toolbar.php';

    if (!$phieu) {
        http_response_code(404);
        ?>
        <div class="card">
            <h2>Không tìm thấy phiếu xuất kho</h2>
            <p>Mã phiếu không tồn tại hoặc đã bị xóa.</p>
            <a class="btn btn-primary" href="<?= e(url(uc_url('UC23', 'index.php'))) ?>">Quay lại danh sách</a>
        </div>
        <?php
        include ROOT_PATH . '/includes/footer.php';
        include ROOT_PATH . '/includes/scripts.php';
        exit;
    }

    /*
     * Truy vấn chi tiết theo mô hình một lô có nhiều hàng hóa.
     * Nếu database hiện tại lưu hạn dùng trực tiếp ở LoHang, dùng COALESCE
     * để ưu tiên hạn dùng trong ChiTietLoHang nếu có.
     */
    $sqlLines = "
        SELECT
            ct.maHangHoa,
            hh.tenHangHoa,
            hh.donViTinh,
            ct.maLoHang,
            ctl.ngayHetHan AS ngayHetHan,
            ct.soLuongXuat,
            ct.donGia,
            ct.thanhTien,
            ct.ghiChu
        FROM ChiTietPhieuXuatKho ct
        LEFT JOIN HangHoa hh ON hh.maHangHoa = ct.maHangHoa
        LEFT JOIN ChiTietLoHang ctl
            ON ctl.maLoHang = ct.maLoHang
           AND ctl.maHangHoa = ct.maHangHoa
        LEFT JOIN LoHang lh ON lh.maLoHang = ct.maLoHang
        WHERE ct.maPhieuXuat = ?
        ORDER BY ct.maLoHang, hh.tenHangHoa
    ";
    $detailStmt = $db->prepare($sqlLines);
    if (!$detailStmt) {
        die('Lỗi truy vấn hàng hóa chi tiết. Kiểm tra bảng ChiTietLoHang, LoHang và các cột liên quan trong QLK04: ' . e($db->error));
    }
    $detailStmt->bind_param('s', $maPhieuXuat);
    $detailStmt->execute();
    $details = $detailStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $detailStmt->close();

    $statusLabelsDetail = [
        'Cho xuat' => 'Chờ xuất',
        'Dang xuat' => 'Đang xuất',
        'Da xuat' => 'Đã xuất',
        'Da huy' => 'Đã hủy'
    ];
    ?>
    <style>
    .uc23-detail{max-width:1450px;margin:0 auto;color:#263b4d;font:13px "Segoe UI",Arial,sans-serif}
    .uc23-detail *{box-sizing:border-box}
    .uc23-detail .panel{margin-bottom:18px;padding:20px;border:1px solid #e0eaf2;border-radius:12px;background:#fff;box-shadow:0 4px 14px rgba(31,75,100,.05)}
    .uc23-detail h2,.uc23-detail h3{margin:0 0 16px;color:#20516b}
    .uc23-detail .info-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    .uc23-detail .info-item{padding:12px;border-radius:8px;background:#f5f9fc;overflow-wrap:anywhere}
    .uc23-detail .label{display:block;margin-bottom:6px;color:#7b8ea1;font-size:12px}
    .uc23-detail .value{color:#29475b;font-weight:700}
    .uc23-detail .table-wrap{overflow-x:auto;border:1px solid #e0eaf2;border-radius:9px}
    .uc23-detail table{width:100%;min-width:850px;border-collapse:collapse}
    .uc23-detail th,.uc23-detail td{padding:12px;border-bottom:1px solid #eaf0f5;text-align:left}
    .uc23-detail th{background:#edf6fb;color:#31586f;font-size:12px}
    .uc23-detail tr:last-child td{border-bottom:0}
    .uc23-detail .note{white-space:pre-wrap;overflow-wrap:anywhere}
    .uc23-detail .btn-back{display:inline-block;padding:9px 14px;border-radius:8px;background:#247ba0;color:#fff;text-decoration:none;font-weight:700}
    .uc23-detail .empty{padding:25px;text-align:center;color:#7b8d9c}
    @media(max-width:750px){.uc23-detail .info-grid{grid-template-columns:1fr 1fr}.uc23-detail .panel{padding:13px}}
    @media(max-width:480px){.uc23-detail .info-grid{grid-template-columns:1fr}}
    </style>
    <div class="uc23-detail">
        <div style="margin-bottom:18px">
            <a class="btn-back" href="<?= e(url(uc_url('UC23', 'index.php'))) ?>">← Quay lại danh sách phiếu</a>
        </div>
        <section class="panel">
            <h2>Chi tiết phiếu xuất kho</h2>
            <div class="info-grid">
                <div class="info-item"><span class="label">Mã phiếu xuất</span><span class="value"><?= e($phieu['maPhieuXuat'] ?? '—') ?></span></div>
                <div class="info-item"><span class="label">Ngày lập</span><span class="value"><?= !empty($phieu['ngayLap']) ? e(date('d/m/Y H:i', strtotime($phieu['ngayLap']))) : '—' ?></span></div>
                <div class="info-item"><span class="label">Trạng thái</span><span class="value"><?= e($statusLabelsDetail[$phieu['trangThai'] ?? ''] ?? ($phieu['trangThai'] ?? '—')) ?></span></div>
                <div class="info-item"><span class="label">Mã yêu cầu</span><span class="value"><?= e($phieu['maYeuCau'] ?? '—') ?></span></div>
                <div class="info-item"><span class="label">Người lập</span><span class="value"><?= e($phieu['tenNguoiLap'] ?? '—') ?></span></div>
                <div class="info-item"><span class="label">Kho xuất</span><span class="value"><?= e($phieu['khoXuat'] ?? '—') ?></span></div>
                <div class="info-item"><span class="label">Địa điểm xuất</span><span class="value"><?= e($phieu['diaDiemXuat'] ?? '—') ?></span></div>
                <div class="info-item"><span class="label">Người phê duyệt</span><span class="value"><?= e($phieu['maNguoiPheDuyet'] ?? '—') ?></span></div>
                <div class="info-item"><span class="label">Thời gian phê duyệt</span><span class="value"><?= !empty($phieu['thoiGianPheDuyet']) ? e(date('d/m/Y H:i', strtotime($phieu['thoiGianPheDuyet']))) : '—' ?></span></div>
            </div>
            <p><strong>Ghi chú phiếu:</strong> <span class="note"><?= e($phieu['ghiChu'] ?? 'Không có ghi chú') ?></span></p>
            <?php if (!empty($phieu['yKienPheDuyet'])): ?>
                <p><strong>Ý kiến phê duyệt:</strong> <span class="note"><?= e($phieu['yKienPheDuyet']) ?></span></p>
            <?php endif; ?>
        </section>
        <section class="panel">
            <h3>Danh sách hàng hóa đã xuất</h3>
            <div class="table-wrap"><table>
                <thead><tr><th>STT</th><th>Mã hàng</th><th>Tên hàng hóa</th><th>ĐVT</th><th>Mã lô</th><th>Hạn sử dụng</th><th>Số lượng xuất</th><th>Đơn giá</th><th>Thành tiền</th><th>Ghi chú</th></tr></thead>
                <tbody>
                <?php if (!$details): ?>
                    <tr><td colspan="10" class="empty">Phiếu này chưa có dòng hàng hóa chi tiết.</td></tr>
                <?php else: foreach ($details as $i => $d): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($d['maHangHoa'] ?? '—') ?></td>
                        <td><?= e($d['tenHangHoa'] ?? 'Không tìm thấy hàng hóa') ?></td>
                        <td><?= e($d['donViTinh'] ?? '—') ?></td>
                        <td><?= e($d['maLoHang'] ?? '—') ?></td>
                        <td><?= !empty($d['ngayHetHan']) ? e(date('d/m/Y H:i', strtotime($d['ngayHetHan']))) : '—' ?></td>
                        <td><?= number_format((float)($d['soLuongXuat'] ?? 0), 3, ',', '.') ?></td>
                        <td><?= number_format((float)($d['donGia'] ?? 0), 0, ',', '.') ?> đ</td>
                        <td><?= number_format((float)($d['thanhTien'] ?? 0), 0, ',', '.') ?> đ</td>
                        <td><?= e($d['ghiChu'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table></div>
        </section>
    </div>
    <?php
    include ROOT_PATH . '/includes/footer.php';
    include ROOT_PATH . '/includes/scripts.php';
    exit;
}

$message = '';
$created = trim($_GET['created'] ?? '');
if ($created !== '') {
    $message = 'Lập phiếu xuất kho thành công. Mã phiếu: ' . $created;
}
$keyword = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');

$statusLabels = [
    'Cho xuat' => 'Chờ xuất',
    'Dang xuat' => 'Đang xuất',
    'Da xuat' => 'Đã xuất',
    'Da huy' => 'Đã hủy',
];
$statusClasses = [
    'Cho xuat' => 'pending',
    'Dang xuat' => 'processing',
    'Da xuat' => 'completed',
    'Da huy' => 'cancelled',
];

$sql = "
    SELECT
        p.maPhieuXuat,
        p.ngayLap,
        p.trangThai,
        p.ghiChu,
        p.maYeuCau,
        p.khoXuat,
        p.diaDiemXuat,
        nv.hoTen AS tenNguoiLap,
        COALESCE((
            SELECT SUM(ct.soLuongXuat)
            FROM ChiTietPhieuXuatKho ct
            WHERE ct.maPhieuXuat = p.maPhieuXuat
        ), 0) AS soLuongThucXuat,
        (
            SELECT COUNT(*)
            FROM ChiTietPhieuXuatKho ct
            WHERE ct.maPhieuXuat = p.maPhieuXuat
        ) AS soDongHang
    FROM PhieuXuatKho p
    LEFT JOIN NhanVienKho nv ON nv.maNhanVien = p.maNhanVien
    WHERE 1 = 1
";
$types = '';
$params = [];
if ($keyword !== '') {
    $sql .= ' AND (p.maPhieuXuat LIKE ? OR p.maYeuCau LIKE ? OR nv.hoTen LIKE ?)';
    $like = '%' . $keyword . '%';
    $types .= 'sss';
    array_push($params, $like, $like, $like);
}
if (isset($statusLabels[$status])) {
    $sql .= ' AND p.trangThai = ?';
    $types .= 's';
    $params[] = $status;
}
$sql .= ' ORDER BY p.ngayLap DESC, p.maPhieuXuat DESC';
$stmt = $db->prepare($sql);
if (!$stmt) {
    die('Lỗi truy vấn danh sách phiếu xuất kho: ' . e($db->error));
}
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
if (!$stmt->execute()) {
    die('Không thể tải danh sách phiếu xuất kho: ' . e($stmt->error));
}
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statusCounts = ['Cho xuat' => 0, 'Dang xuat' => 0, 'Da xuat' => 0, 'Da huy' => 0];
$totalQuantity = 0;
foreach ($rows as $row) {
    $totalQuantity += (float)$row['soLuongThucXuat'];
    if (isset($statusCounts[$row['trangThai']])) {
        $statusCounts[$row['trangThai']]++;
    }
}

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<style>
.uc23-page{max-width:1600px;margin:0 auto;color:#263b4d;font-family:"Segoe UI",Arial,sans-serif;font-size:13px}
.uc23-page,.uc23-page *{box-sizing:border-box}
.uc23-page .uc23-description{margin:0 0 20px;color:#7c8fa1;font-size:12px}
.uc23-page .uc23-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:20px}
.uc23-page .uc23-stat{position:relative;min-width:0;padding:18px;overflow:hidden;border:1px solid #e0eaf2;border-radius:12px;background:#fff;box-shadow:0 4px 14px rgba(31,75,100,.05)}
.uc23-page .uc23-stat:after{position:absolute;right:-20px;bottom:-28px;width:85px;height:85px;border-radius:50%;background:#eaf5fb;content:""}
.uc23-page .uc23-stat-label,.uc23-page .uc23-stat-value,.uc23-page .uc23-stat-sub{position:relative;z-index:1}
.uc23-page .uc23-stat-label{margin-bottom:10px;color:#7b8ea1;font-size:12px;font-weight:600}
.uc23-page .uc23-stat-value{color:#20516b;font-size:25px;font-weight:800;overflow-wrap:anywhere}
.uc23-page .uc23-stat-sub{margin-top:6px;color:#93a2b0;font-size:11px}
.uc23-page .uc23-card{min-width:0;padding:20px;border:1px solid #e0eaf2;border-radius:13px;background:#fff;box-shadow:0 5px 18px rgba(31,75,100,.05)}
.uc23-page .uc23-card-heading{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:17px}
.uc23-page .uc23-card-heading h3{margin:0;color:#20516b;font-size:16px;font-weight:750}
.uc23-page .uc23-card-heading span{color:#8495a5;font-size:11px}
.uc23-page .uc23-filter{display:grid;grid-template-columns:minmax(220px,1.5fr) minmax(180px,.9fr) auto;align-items:end;gap:13px;margin-bottom:20px;padding:15px;border:1px solid #e4edf4;border-radius:10px;background:#f7fafc}
.uc23-page .filter-field{display:flex;flex-direction:column;gap:7px;min-width:0}
.uc23-page .filter-field label{color:#43596c;font-size:12px;font-weight:700}
.uc23-page .filter-field input,.uc23-page .filter-field select{width:100%;min-width:0;height:41px;padding:0 12px;border:1px solid #d7e3ed;border-radius:8px;background:#fff;color:#263b4d;font-family:inherit;font-size:12px}
.uc23-page .filter-actions{display:flex;gap:8px;flex-wrap:wrap}
.uc23-page .uc23-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:36px;padding:8px 12px;border:1px solid #247ba0;border-radius:8px;background:#247ba0;color:#fff;font-family:inherit;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;cursor:pointer}
.uc23-page .uc23-btn:hover{border-color:#185d7a;background:#185d7a;color:#fff}
.uc23-page .uc23-btn-light{border-color:#d7e3ed;background:#fff;color:#506779}
.uc23-page .uc23-btn-light:hover{border-color:#b8dceb;background:#eaf5fb;color:#185d7a}
.uc23-page .uc23-table-wrap{width:100%;overflow-x:auto;border:1px solid #e0eaf2;border-radius:10px}
.uc23-page .uc23-table{width:100%;min-width:1050px;margin:0;border-collapse:separate;border-spacing:0;background:#fff;font-size:12px}
.uc23-page .uc23-table th,.uc23-page .uc23-table td{padding:13px 12px;border-bottom:1px solid #eaf0f5;text-align:left;vertical-align:middle}
.uc23-page .uc23-table thead th{background:#edf6fb;color:#31586f;font-size:11px;font-weight:750;white-space:nowrap}
.uc23-page .uc23-table tbody tr:nth-child(even){background:#fbfdff}
.uc23-page .uc23-table tbody tr:hover{background:#f0f8fc}
.uc23-page .uc23-table tbody tr:last-child td{border-bottom:none}
.uc23-page .uc23-code{color:#176a91;font-weight:750;white-space:nowrap}
.uc23-page .uc23-status{display:inline-flex;align-items:center;justify-content:center;padding:5px 10px;border:1px solid transparent;border-radius:999px;font-size:11px;font-weight:750;white-space:nowrap}
.uc23-page .status-pending{border-color:#f4df9d;background:#fff7df;color:#8a6714}
.uc23-page .status-processing{border-color:#b8d9fa;background:#eaf4ff;color:#1f5b99}
.uc23-page .status-completed{border-color:#bce4ca;background:#eaf8ef;color:#237443}
.uc23-page .status-cancelled{border-color:#f0c6ca;background:#fff0f1;color:#a3313d}
.uc23-page .uc23-empty{padding:38px 20px!important;color:#7d8e9d;text-align:center!important;line-height:1.8}
.uc23-page .uc23-empty strong{display:block;margin-bottom:5px;color:#38556b;font-size:13px}
.uc23-page .uc23-alert{margin-bottom:17px;padding:13px 15px;border:1px solid #bce4ca;border-radius:9px;background:#effaf2;color:#246b3a;font-size:12px}
.uc23-page .uc23-summary{display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:16px;padding-top:15px;border-top:1px solid #eaf0f5;color:#7b8d9c;font-size:12px}
.uc23-page .uc23-summary strong{color:#174a66;font-size:13px}
@media(max-width:1050px){.uc23-page .uc23-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.uc23-page .uc23-filter{grid-template-columns:1fr 1fr}.uc23-page .filter-actions{grid-column:1/-1}}
@media(max-width:600px){.uc23-page .uc23-stats{grid-template-columns:1fr 1fr;gap:9px}.uc23-page .uc23-stat{padding:13px}.uc23-page .uc23-stat-value{font-size:21px}.uc23-page .uc23-card{padding:12px}.uc23-page .uc23-filter{grid-template-columns:1fr;padding:12px}.uc23-page .filter-actions{grid-column:auto}.uc23-page .filter-actions .uc23-btn{flex:1}}
</style>

<div class="uc23-page">
    <p class="uc23-description">Quản lý, theo dõi và tra cứu các phiếu xuất kho.</p>
    <?php if ($message !== ''): ?>
        <div class="uc23-alert" role="alert">✓ <?= e($message) ?></div>
    <?php endif; ?>

    <div class="uc23-stats">
        <div class="uc23-stat"><div class="uc23-stat-label">Tổng số phiếu</div><div class="uc23-stat-value"><?= count($rows) ?></div><div class="uc23-stat-sub">Theo điều kiện lọc hiện tại</div></div>
        <div class="uc23-stat"><div class="uc23-stat-label">Chờ xuất</div><div class="uc23-stat-value"><?= $statusCounts['Cho xuat'] ?></div><div class="uc23-stat-sub">Phiếu đang chờ xử lý</div></div>
        <div class="uc23-stat"><div class="uc23-stat-label">Đã xuất</div><div class="uc23-stat-value"><?= $statusCounts['Da xuat'] ?></div><div class="uc23-stat-sub">Phiếu đã hoàn tất xuất kho</div></div>
    </div>

    <section class="uc23-card">
        <div class="uc23-card-heading"><h3>Danh sách phiếu xuất</h3><span><?= count($rows) ?> phiếu phù hợp</span></div>
        <form method="get" class="uc23-filter">
            <div class="filter-field"><label for="q">Tìm kiếm phiếu xuất</label><input type="search" name="q" id="q" value="<?= e($keyword) ?>" placeholder="Mã phiếu, mã yêu cầu, người lập..."></div>
            <div class="filter-field"><label for="status">Trạng thái phiếu</label><select name="status" id="status"><option value="">Tất cả trạng thái</option><?php foreach ($statusLabels as $value => $label): ?><option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
            <div class="filter-actions"><button class="uc23-btn" type="submit"><?= icon('search', 15) ?> Tìm kiếm</button><a class="uc23-btn uc23-btn-light" href="<?= e(url(uc_url('UC23', 'index.php'))) ?>">Làm mới</a></div>
        </form>

        <div class="uc23-table-wrap"><table class="uc23-table">
            <thead><tr><th>STT</th><th>Mã phiếu xuất</th><th>Ngày lập</th><th>Mã yêu cầu</th><th>Người lập</th><th>Kho xuất</th><th>Địa điểm xuất</th><th>Tổng SL xuất</th><th>Trạng thái</th><th>Chi tiết</th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="10" class="uc23-empty"><strong>Chưa có phiếu xuất kho phù hợp</strong>Hãy thay đổi điều kiện tìm kiếm hoặc lập phiếu mới.</td></tr>
            <?php else: foreach ($rows as $i => $row): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><span class="uc23-code"><?= e($row['maPhieuXuat']) ?></span></td>
                    <td><?= !empty($row['ngayLap']) ? e(date('d/m/Y H:i', strtotime($row['ngayLap']))) : '—' ?></td>
                    <td><?= e($row['maYeuCau'] ?? '—') ?></td>
                    <td><?= e($row['tenNguoiLap'] ?? '—') ?></td>
                    <td><?= e($row['khoXuat'] ?? '—') ?></td>
                    <td><?= e($row['diaDiemXuat'] ?? '—') ?></td>
                    <td><?= number_format((float)$row['soLuongThucXuat'], 3, ',', '.') ?></td>
                    <td><span class="uc23-status status-<?= e($statusClasses[$row['trangThai']] ?? 'pending') ?>"><?= e($statusLabels[$row['trangThai']] ?? $row['trangThai']) ?></span></td>
                    <td><a class="uc23-btn" href="<?= e(url(uc_url('UC23', 'index.php'))) ?>?view=detail&amp;maPhieuXuat=<?= rawurlencode($row['maPhieuXuat']) ?>"><?= icon('eye', 15) ?> Xem chi tiết</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table></div>
        <div class="uc23-summary"><span>Tổng số phiếu: <strong><?= count($rows) ?></strong></span><span>Tổng số lượng xuất: <strong><?= number_format($totalQuantity, 3, ',', '.') ?></strong></span></div>
    </section>
</div>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
?>
