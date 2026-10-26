<?php
/** UC10 - Chi tiết phiếu nhập kho */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_module('UC10');

$page_title = 'Chi tiết phiếu nhập kho';
$active_menu = 'UC10';
$breadcrumb = [
    ['Tổng quan', 'index.php'],
    ['Nhập kho', 'index.php'],
    ['Chi tiết phiếu', null],
];

/** Chỉ kết nối đến QLK02, không làm thay đổi db() của QLK01. */
function uc10_detail_connection(): mysqli
{
    if (function_exists('db_uc10')) {
        return db_uc10();
    }
    static $connection = null;
    if ($connection === null) {
        $connection = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            defined('DB_NAME_UC10') ? DB_NAME_UC10 : 'qlk02'
        );
        $connection->set_charset('utf8mb4');
    }
    return $connection;
}

function uc10_detail_one(mysqli $connection, string $sql, string $types = '', array $params = []): ?array
{
    $statement = $connection->prepare($sql);
    if ($types !== '') {
        $statement->bind_param($types, ...$params);
    }
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    $statement->close();
    return $row ?: null;
}

function uc10_detail_escape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function uc10_detail_show($value): string
{
    if ($value === null || trim((string)$value) === '') {
        return '—';
    }
    return uc10_detail_escape($value);
}

function uc10_detail_date($value): string
{
    if (!$value) return '—';
    $timestamp = strtotime((string)$value);
    return $timestamp === false ? uc10_detail_escape($value) : date('d/m/Y H:i', $timestamp);
}

function uc10_detail_number($value): string
{
    return $value === null ? '—' : number_format((float)$value, 2, ',', '.');
}

$code = trim((string)($_GET['maPhieuNhapKho'] ?? $_GET['id'] ?? ''));
$receipt = null;
$employeeName = null;
$error = null;

if ($code === '') {
    $error = 'Bạn chưa chọn phiếu nhập kho cần xem.';
} elseif (strlen($code) > 100) {
    $error = 'Mã phiếu nhập kho không hợp lệ.';
} else {
    try {
        $conn = uc10_detail_connection();
        $receipt = uc10_detail_one($conn, '
            SELECT
                pn.maPhieuNhapKho,
                pn.ngayLapPhieu,
                pn.thoiGianLap,
                pn.soLuongThucNhap,
                pn.donViTinh,
                pn.trangThai,
                pn.ghiChu,
                pn.maNhanVien,
                pn.maKho,
                k.tenKho,
                pn.maLoHang,
                lh.soLo,
                lh.soLuongTiepNhan,
                lh.ngaySanXuat,
                lh.hanSuDung,
                lh.tinhTrang,
                lh.maHangHoa,
                hh.tenHangHoa,
                hh.loaiHangHoa,
                (
                    SELECT bb.ketQua
                    FROM BienBanKiemDinhChatLuong bb
                    WHERE bb.maLoHang = pn.maLoHang
                      AND bb.trangThai = \'Đã xác nhận\'
                    ORDER BY bb.thoiDiemKiemDinh DESC
                    LIMIT 1
                ) AS ketQuaKiemDinh
            FROM PhieuNhapKho pn
            LEFT JOIN LoHang lh ON lh.maLoHang = pn.maLoHang
            LEFT JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa
            LEFT JOIN Kho k ON k.maKho = pn.maKho
            WHERE pn.maPhieuNhapKho = ?
            LIMIT 1
        ', 's', [$code]);

        if (!$receipt) {
            $error = 'Không tìm thấy phiếu nhập kho có mã ' . $code . '.';
        } elseif (!empty($receipt['maNhanVien'])) {
            // QLK01 là DB nhân viên theo config.php người dùng cung cấp.
            try {
                $staff = uc10_detail_one(
                    db(),
                    'SELECT hoTen FROM nhanvien WHERE maNV = ? LIMIT 1',
                    's',
                    [(string)$receipt['maNhanVien']]
                );
                $employeeName = $staff['hoTen'] ?? null;
            } catch (Throwable $employeeError) {
                error_log('UC10 employee detail lookup: ' . $employeeError->getMessage());
            }
        }
    } catch (Throwable $ex) {
        error_log('UC10 detail: ' . $ex->getMessage());
        $error = 'Không tải được chi tiết phiếu nhập kho: ' . $ex->getMessage();
    }
}

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<style>
.uc10-detail-wrap{max-width:1100px;margin:0 auto;padding:16px}
.uc10-detail-card{background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:24px;margin-bottom:18px;box-shadow:0 2px 8px rgba(16,40,80,.04)}
.uc10-detail-title{margin:0 0 18px;font-size:19px;color:#183b69}
.uc10-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 30px}
.uc10-detail-item{padding:12px 0;border-bottom:1px solid #edf0f5;display:flex;gap:12px;justify-content:space-between;align-items:flex-start}
.uc10-detail-label{color:#66768d;flex:0 0 45%}
.uc10-detail-value{text-align:right;overflow-wrap:anywhere;white-space:pre-wrap;font-weight:600;color:#26364b}
.uc10-detail-actions{display:flex;justify-content:flex-end;gap:12px;margin-top:24px;margin-bottom:16px}
.uc10-detail-back{display:inline-block;border:1px solid #cdd7e5;border-radius:8px;padding:10px 16px;text-decoration:none;color:#24446e;background:#fff}
.uc10-detail-error{padding:16px;border:1px solid #f1b4b4;background:#fff2f2;color:#a12727;border-radius:9px}
.uc10-detail-note{white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.6;color:#34445c}
@media(max-width:750px){.uc10-detail-grid{grid-template-columns:1fr}.uc10-detail-card{padding:16px}.uc10-detail-item{gap:8px}}
</style>
<div class="uc10-detail-wrap">
    <?php if ($error !== null): ?>
        <div class="uc10-detail-error"><?= uc10_detail_escape($error) ?></div>
    <?php elseif ($receipt): ?>
        <section class="uc10-detail-card">
            <h2 class="uc10-detail-title">Thông tin phiếu nhập kho</h2>
            <div class="uc10-detail-grid">
                <?php
                $info = [
                    'Số phiếu nhập' => $receipt['maPhieuNhapKho'],
                    'Ngày lập phiếu' => uc10_detail_date($receipt['ngayLapPhieu']),
                    'Thời gian lập' => uc10_detail_date($receipt['thoiGianLap']),
                    'Trạng thái phiếu' => $receipt['trangThai'],
                    'Mã nhân viên' => $receipt['maNhanVien'],
                    'Người lập' => $employeeName ?: $receipt['maNhanVien'],
                    'Mã kho' => $receipt['maKho'],
                    'Kho nhập' => ($receipt['tenKho'] ?: $receipt['maKho']),  
                ];
                foreach ($info as $label => $value): ?>
                    <div class="uc10-detail-item">
                        <span class="uc10-detail-label"><?= uc10_detail_escape($label) ?></span>
                        <span class="uc10-detail-value"><?= uc10_detail_show($value) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="uc10-detail-card">
            <h2 class="uc10-detail-title">Chi tiết lô hàng</h2>
            <div class="uc10-detail-grid">
                <?php
                // UC10 lưu chứng từ và ghi chú chung trong PhieuNhapKho.ghiChu.
                // Quy ước khi tạo phiếu: 'Chứng từ liên quan: ...' rồi đến ghi chú ở dòng sau.
                $rawNote = trim((string)($receipt['ghiChu'] ?? ''));
                $document = '';
                $note = $rawNote;
                if (preg_match('/^Chứng từ liên quan:\s*([^\r\n]*)(?:\r?\n([\s\S]*))?$/u', $rawNote, $matches)) {
                    $document = trim($matches[1]);
                    $note = trim($matches[2] ?? '');
                }
                $lotInfo = [
                    'Mã lô hàng' => $receipt['maLoHang'],
                    'Số lô sản xuất' => $receipt['soLo'],
                    'Mã hàng hóa' => $receipt['maHangHoa'],
                    'Tên hàng hóa' => $receipt['tenHangHoa'],
                    'Loại hàng hóa' => $receipt['loaiHangHoa'],
                    'Ngày sản xuất' => $receipt['ngaySanXuat'],
                    'Tình trạng lô' => $receipt['tinhTrang'],
                    'Hạn sử dụng' => $receipt['hanSuDung'],
                    'Kết quả kiểm định' => $receipt['ketQuaKiemDinh'],
                    'Đơn vị tính' => $receipt['donViTinh'],
                    'Số lượng tiếp nhận' => uc10_detail_number($receipt['soLuongTiepNhan']),
                    'Số lượng thực nhập' => uc10_detail_number($receipt['soLuongThucNhap']),
                    
                    'Chứng từ liên quan' => $document,
                    'Ghi chú' => $note,
                ];
                foreach ($lotInfo as $label => $value): ?>
                    <div class="uc10-detail-item">
                        <span class="uc10-detail-label"><?= uc10_detail_escape($label) ?></span>
                        <span class="uc10-detail-value"><?= uc10_detail_show($value) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    <?php endif; ?>
    <div class="uc10-detail-actions">
        <a class="uc10-detail-back" href="index.php">← Quay lại danh sách</a>
    </div>
</div>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
