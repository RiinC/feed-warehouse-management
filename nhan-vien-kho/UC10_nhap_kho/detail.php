<?php

/** UC10 - Chi tiết phiếu nhập kho, giao diện mẫu 3. */
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

function uc10_detail_connection(): mysqli
{
    if (function_exists('db_uc10')) return db_uc10();
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
    if ($types !== '') $statement->bind_param($types, ...$params);
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
    return $value === null || trim((string)$value) === ''
        ? '—' : uc10_detail_escape($value);
}

function uc10_detail_date($value, bool $withTime = false): string
{
    if (!$value) return '—';
    $timestamp = strtotime((string)$value);
    return $timestamp === false
        ? uc10_detail_escape($value)
        : date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $timestamp);
}

function uc10_detail_number($value, string $unit = ''): string
{
    if ($value === null) return '—';
    $decimals = in_array(mb_strtolower(trim($unit), 'UTF-8'), ['thùng', 'bao'], true) ? 0 : 2;
    return number_format((float)$value, $decimals, ',', '.');
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
        $receipt = uc10_detail_one($conn, "
            SELECT pn.maPhieuNhapKho, pn.ngayLapPhieu, pn.thoiGianLap,
                   pn.soLuongThucNhap, pn.donViTinh, pn.trangThai, pn.ghiChu,
                   pn.maNhanVien, pn.maKho, k.tenKho, pn.maLoHang,
                   lh.soLo, lh.soLuongTiepNhan, lh.ngaySanXuat,
                   lh.hanSuDung, lh.tinhTrang, lh.maHangHoa,
                   hh.tenHangHoa, hh.loaiHangHoa,
                   (SELECT bb.ketQua
                      FROM BienBanKiemDinhChatLuong bb
                     WHERE bb.maLoHang = pn.maLoHang
                       AND bb.trangThai = 'Đã xác nhận'
                     ORDER BY bb.thoiDiemKiemDinh DESC
                     LIMIT 1) AS ketQuaKiemDinh
            FROM PhieuNhapKho pn
            LEFT JOIN LoHang lh ON lh.maLoHang = pn.maLoHang
            LEFT JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa
            LEFT JOIN Kho k ON k.maKho = pn.maKho
            WHERE pn.maPhieuNhapKho = ?
            LIMIT 1
        ", 's', [$code]);

        if (!$receipt) {
            $error = 'Không tìm thấy phiếu nhập kho có mã ' . $code . '.';
        } elseif (!empty($receipt['maNhanVien'])) {
            try {
                // Giữ cách tra nhân viên từ QLK01 như file detail.php gốc.
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

$document = '';
$note = '';
if ($receipt) {
    $rawNote = trim((string)($receipt['ghiChu'] ?? ''));
    $note = $rawNote;
    // form.php lưu: "Chứng từ liên quan: ...\nGhi chú"
    if (preg_match('/^Chứng từ liên quan:\s*([^\r\n]*)(?:\r?\n([\s\S]*))?$/u', $rawNote, $matches)) {
        $document = trim($matches[1]);
        $note = trim($matches[2] ?? '');
    }
}

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<style>
    .uc10-detail-wrap {
        max-width: 1050px;
        margin: 0 auto;
        padding: 16px 0 28px
    }

    .uc10-detail-card {
        background: #fff;
        border: 1px solid #e2eaf4;
        border-radius: 11px;
        margin-bottom: 20px;
        overflow: hidden;
        box-shadow: 0 3px 12px rgba(21, 52, 95, .04)
    }

    .uc10-detail-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
        padding: 17px 22px;
        font-size: 18px;
        font-weight: 700
    }

    .uc10-detail-title-blue {
        background: #e4efff;
        color: #10458d
    }

    .uc10-detail-title-green {
        background: #e5f8ed;
        color: #17683d
    }

    .uc10-detail-title-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 9px;
        font-size: 19px;
        color: #fff;
        background: #2682ef;
        flex-shrink: 0
    }

    .uc10-detail-title-green .uc10-detail-title-icon {
        background: #25b966
    }

    .uc10-detail-content {
        padding: 8px 22px 14px
    }

    .uc10-detail-item {
        display: grid;
        grid-template-columns: minmax(180px, 38%) minmax(0, 1fr);
        gap: 16px;
        align-items: center;
        padding: 10px 4px;
        margin: 0;
        border-bottom: 1px solid #edf1f6;
        min-height: 0
    }

    .uc10-detail-item:last-child {
        border-bottom: 0
    }

    /* Phần thông tin phiếu gọn hơn, đồng đều với chi tiết lô hàng */
    .uc10-detail-card:first-of-type .uc10-detail-item {
        padding-top: 9px;
        padding-bottom: 9px
    }

    .uc10-detail-label {
        display: block;
        color: #5b6e89;
        line-height: 1.5;
        margin: 0;
        padding: 0
    }

    .uc10-detail-value {
        display: block;
        color: #173963;
        font-weight: 600;
        overflow-wrap: anywhere;
        white-space: normal;
        text-align: left;
        line-height: 1.5;
        margin: 0;
        padding: 0
    }

    .uc10-detail-status {
        display: inline-block;
        padding: 4px 11px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700
    }

    .uc10-detail-status-draft {
        background: #fff0c3;
        color: #9a6700
    }

    .uc10-detail-status-done {
        background: #d9f7e5;
        color: #146b3c
    }

    .uc10-detail-status-cancel {
        background: #ffe2e2;
        color: #a82d2d
    }

    .uc10-detail-actions {
        display: flex;
        justify-content: flex-end;
        margin: 24px 0 8px
    }

    .uc10-detail-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #b9d4f6;
        border-radius: 7px;
        padding: 10px 16px;
        text-decoration: none;
        color: #1455a0;
        background: #fff;
        font-weight: 600
    }

    .uc10-detail-back:hover {
        background: #eef6ff
    }

    .uc10-detail-error {
        padding: 16px;
        border: 1px solid #f1b4b4;
        background: #fff2f2;
        color: #a12727;
        border-radius: 9px
    }

    @media(max-width:650px) {
        .uc10-detail-wrap {
            padding: 8px 0 22px
        }

        .uc10-detail-title {
            padding: 14px;
            font-size: 16px
        }

        .uc10-detail-content {
            padding: 6px 12px 12px
        }

        .uc10-detail-item {
            grid-template-columns: minmax(110px, 42%) minmax(0, 1fr);
            gap: 10px;
            font-size: 13px
        }
    }
</style>

<div class="uc10-detail-wrap">
    <?php if ($error !== null): ?>
        <div class="uc10-detail-error"><?= uc10_detail_escape($error) ?></div>
    <?php elseif ($receipt): ?>
        <section class="uc10-detail-card">
            <h2 class="uc10-detail-title uc10-detail-title-blue">
                <span class="uc10-detail-title-icon" aria-hidden="true">▤</span>
                Thông tin phiếu nhập kho
            </h2>
            <div class="uc10-detail-content">
                <?php
                $info = [
                    'Số phiếu nhập' => $receipt['maPhieuNhapKho'],
                    'Ngày lập phiếu' => uc10_detail_date($receipt['ngayLapPhieu']),
                    'Thời gian lập' => uc10_detail_date($receipt['thoiGianLap'], true),
                    'Trạng thái phiếu' => $receipt['trangThai'],
                    'Người lập' => $employeeName ?: $receipt['maNhanVien'],
                    'Mã kho' => $receipt['maKho'],
                    'Kho nhập' => $receipt['tenKho'] ?: $receipt['maKho'],
                ];
                foreach ($info as $label => $value):
                ?>
                    <div class="uc10-detail-item">
                        <span class="uc10-detail-label"><?= uc10_detail_escape($label) ?></span>
                        <span class="uc10-detail-value"><?php if ($label === 'Trạng thái phiếu'):
                                                            $statusClass = $value === 'Đã nhập kho' ? 'uc10-detail-status-done'
                                                                : ($value === 'Đã hủy' ? 'uc10-detail-status-cancel' : 'uc10-detail-status-draft');
                                                        ?><span class="uc10-detail-status <?= $statusClass ?>"><?= uc10_detail_show($value) ?></span><?php else: ?><?= uc10_detail_show($value) ?><?php endif; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="uc10-detail-card">
            <h2 class="uc10-detail-title uc10-detail-title-green">
                <span class="uc10-detail-title-icon" aria-hidden="true">⬡</span>
                Chi tiết lô hàng
            </h2>
            <div class="uc10-detail-content">
                <?php
                $lotInfo = [
                    'Mã lô hàng' => $receipt['maLoHang'],
                    'Số lô sản xuất' => $receipt['soLo'],
                    'Mã hàng hóa' => $receipt['maHangHoa'],
                    'Tên hàng hóa' => $receipt['tenHangHoa'],
                    'Loại hàng hóa' => $receipt['loaiHangHoa'],
                    'Ngày sản xuất' => uc10_detail_date($receipt['ngaySanXuat']),
                    'Hạn sử dụng' => uc10_detail_date($receipt['hanSuDung']),
                    'Tình trạng lô' => $receipt['tinhTrang'],
                    'Kết quả kiểm định' => $receipt['ketQuaKiemDinh'],
                    'Đơn vị tính' => $receipt['donViTinh'],
                    'Số lượng tiếp nhận' => uc10_detail_number($receipt['soLuongTiepNhan'], (string)$receipt['donViTinh']),
                    'Số lượng thực nhập' => uc10_detail_number($receipt['soLuongThucNhap'], (string)$receipt['donViTinh']),
                    'Chứng từ liên quan' => $document,
                    'Ghi chú' => $note,
                ];
                foreach ($lotInfo as $label => $value):
                ?>
                    <div class="uc10-detail-item">
                        <span class="uc10-detail-label"><?= uc10_detail_escape($label) ?></span>
                        <span class="uc10-detail-value" style="<?= $label === 'Ghi chú' ? 'white-space:pre-wrap' : '' ?>"><?= uc10_detail_show($value) ?></span>
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
