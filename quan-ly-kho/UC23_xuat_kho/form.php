<?php
require_once __DIR__ . '/../../includes/auth.php';
require_module('UC23');

$db = db(DB_UC23);
$db->set_charset('utf8mb4');

$page_title = 'Lập phiếu xuất kho';
$active_menu = 'UC23';
$breadcrumb = [
    ['Tổng quan', 'index.php'],
    ['Xuất kho', 'index.php'],
    ['Lập phiếu xuất', null]
];
$toolbar_actions = '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Người lập phiếu cố định, không kiểm tra đăng nhập / NhanVienKho.
const UC23_NGUOI_LAP = 'Trần Thị Kho';

function uc23e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function uc23money($value): string
{
    return number_format((float)$value, 0, ',', '.') . ' đ';
}

/** Số lượng hiển thị dạng số nguyên, không dấu chấm/phẩy thập phân. */
function uc23int($value): string
{
    return (string)(int)round((float)$value);
}

/**
 * Tạo mã phiếu xuất chưa tồn tại trong PhieuXuatKho.
 */
function taoMaPhieuXuat(mysqli $db): string
{
    do {
        $maPhieuXuat = 'PX' . date('YmdHis') . random_int(1000, 9999);

        $stmt = $db->prepare(
            "SELECT 1 FROM PhieuXuatKho WHERE maPhieuXuat = ? LIMIT 1"
        );
        $stmt->bind_param('s', $maPhieuXuat);
        $stmt->execute();
        $stmt->store_result();

        $daTonTai = $stmt->num_rows > 0;
        $stmt->close();
    } while ($daTonTai);

    return $maPhieuXuat;
}

/**
 * Đọc danh sách cột thực tế của bảng.
 */
function uc23Columns(mysqli $db, string $table): array
{
    $allowed = [
        'PhieuXuatKho',
        'ChiTietPhieuXuatKho',
        'YeuCauXuatKho',
        'DeXuatFEFO',
        'ChiTietDeXuatFEFO',
        'ChiTietLoHang',
        'HangHoa',
        'NhanVienKho',
        'ChiTietYeuCauXuatKho'
    ];

    if (!in_array($table, $allowed, true)) {
        throw new RuntimeException('Tên bảng không hợp lệ.');
    }

    $result = $db->query("SHOW COLUMNS FROM `$table`");
    $columns = [];

    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }

    return $columns;
}

/**
 * INSERT theo những cột có thật trong database.
 */
function uc23Insert(mysqli $db, string $table, array $data): void
{
    $columns = uc23Columns($db, $table);
    $data = array_intersect_key($data, $columns);

    if (!$data) {
        throw new RuntimeException('Không có cột phù hợp để ghi vào bảng ' . $table);
    }

    $names = array_keys($data);
    $quoted = array_map(
        static fn($name) => '`' . $name . '`',
        $names
    );

    $sql = 'INSERT INTO `' . $table . '` (' .
        implode(',', $quoted) . ') VALUES (' .
        implode(',', array_fill(0, count($names), '?')) . ')';

    $stmt = $db->prepare($sql);
    $values = array_values($data);
    $types = '';

    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }

    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $stmt->close();
}

$error = '';
$success = '';

$selectedProposal = trim((string)(
    $_GET['maDeXuatFEFO'] ??
    $_POST['maDeXuatFEFO'] ??
    ''
));

$khoXuat = trim((string)($_POST['khoXuat'] ?? ''));
$diaDiemXuat = trim((string)($_POST['diaDiemXuat'] ?? ''));
$ghiChu = trim((string)($_POST['ghiChu'] ?? ''));

$warehouseOptions = [];
$locationOptions = [];

$qtyInput = $_POST['qty'] ?? [];
$lineNotes = $_POST['ghiChuDong'] ?? [];

$requests = [];
$proposal = null;
$lines = [];
$previewCode = 'PX' . date('YmdHis') . random_int(10, 99);

// Người lập phiếu: mặc định là "Trần Thị Kho".
$tenNguoiLapPhieu = trim((string)($_POST['nguoiLapPhieu'] ?? ''));
if ($tenNguoiLapPhieu === '') {
    $tenNguoiLapPhieu = UC23_NGUOI_LAP;
}

try {
    $requests = $db->query("
        SELECT
            d.maDeXuatFEFO,
            d.maYeuCau,
            d.maLoHang,
            d.trangThai AS trangThaiFEFO,
            d.maNguoiDuyet,
            d.ngayTao,
            y.trangThai AS trangThaiYeuCau,
            y.mucDichXuat,
            y.khoXuat,
            y.diaDiemXuat
        FROM DeXuatFEFO d
        INNER JOIN YeuCauXuatKho y
            ON y.maYeuCau = d.maYeuCau
        WHERE y.trangThai IN (
            'Da duyet', 'Đã duyệt',
            'Da xuat mot phan', 'Đã xuất một phần'
        )
          AND d.trangThai IN (
            'Da duyet', 'Đã duyệt',
            'Cho lap phieu', 'Chờ lập phiếu'
        )
          AND EXISTS (
              SELECT 1
              FROM ChiTietDeXuatFEFO c
              WHERE c.maDeXuatFEFO = d.maDeXuatFEFO
          )
        ORDER BY d.ngayTao DESC, d.maDeXuatFEFO DESC
    ")->fetch_all(MYSQLI_ASSOC);

    foreach ($requests as $r) {
        $warehouse = trim((string)($r['khoXuat'] ?? ''));
        $location = trim((string)($r['diaDiemXuat'] ?? ''));

        if ($warehouse !== '') {
            $warehouseOptions[$warehouse] = $warehouse;
        }

        if ($location !== '') {
            $locationOptions[$location] = $location;
        }
    }

    if ($khoXuat !== '') {
        $warehouseOptions[$khoXuat] = $khoXuat;
    }

    if ($diaDiemXuat !== '') {
        $locationOptions[$diaDiemXuat] = $diaDiemXuat;
    }

    if (!$warehouseOptions) {
        $warehouseOptions['Kho QLK04'] = 'Kho QLK04';
    }

    if (!$locationOptions) {
        $locationOptions['Ke A1'] = 'Kệ A1';
        $locationOptions['Ke B1'] = 'Kệ B1';
    }

    if ($selectedProposal !== '') {
        $stmt = $db->prepare("
            SELECT
                d.maDeXuatFEFO,
                d.maYeuCau,
                d.maLoHang,
                d.trangThai AS trangThaiFEFO,
                d.maNguoiDuyet,
                d.ngayTao,
                y.mucDichXuat,
                y.khoXuat,
                y.diaDiemXuat,
                y.trangThai AS trangThaiYeuCau
            FROM DeXuatFEFO d
            INNER JOIN YeuCauXuatKho y
                ON y.maYeuCau = d.maYeuCau
            WHERE d.maDeXuatFEFO = ?
              AND y.trangThai IN (
                  'Da duyet', 'Đã duyệt',
                  'Da xuat mot phan', 'Đã xuất một phần'
              )
              AND d.trangThai IN (
                  'Da duyet', 'Đã duyệt',
                  'Cho lap phieu', 'Chờ lập phiếu'
              )
            LIMIT 1
        ");

        $stmt->bind_param('s', $selectedProposal);
        $stmt->execute();
        $proposal = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($proposal) {
            $stmt = $db->prepare("
                SELECT
                    c.maChiTietDeXuat,
                    c.maHangHoa,
                    c.soLuongDeXuat,
                    c.ghiChu AS ghiChuFEFO,

                    h.tenHangHoa,
                    h.donViTinh,

                    d.maDeXuatFEFO,
                    d.maYeuCau,
                    d.maLoHang,

                    cl.maChiTietLo,
                    cl.ngayHetHan,
                    cl.soLuongTon,
                    cl.soLuongDaGiu,
                    cl.donGiaNhap,

                    GREATEST(
                        0,
                        cl.soLuongTon - cl.soLuongDaGiu
                    ) AS tonKhaDung,

                    COALESCE((
                        SELECT SUM(ct.soLuongXuat)
                        FROM ChiTietPhieuXuatKho ct
                        INNER JOIN PhieuXuatKho p
                            ON p.maPhieuXuat = ct.maPhieuXuat
                        WHERE p.maYeuCau = d.maYeuCau
                          AND p.trangThai NOT IN ('Da huy', 'Đã hủy')
                          AND ct.maLoHang = d.maLoHang
                          AND ct.maHangHoa = c.maHangHoa
                    ), 0) AS daXuatTheoLo,

                    COALESCE((
                        SELECT SUM(cy.soLuongYeuCau)
                        FROM ChiTietYeuCauXuatKho cy
                        WHERE cy.maYeuCau = d.maYeuCau
                          AND cy.maHangHoa = c.maHangHoa
                    ), 0) AS soLuongYeuCau,

                    COALESCE((
                        SELECT SUM(ct2.soLuongXuat)
                        FROM ChiTietPhieuXuatKho ct2
                        INNER JOIN PhieuXuatKho p2
                            ON p2.maPhieuXuat = ct2.maPhieuXuat
                        WHERE p2.maYeuCau = d.maYeuCau
                          AND p2.trangThai NOT IN ('Da huy', 'Đã hủy')
                          AND ct2.maHangHoa = c.maHangHoa
                    ), 0) AS daXuatTheoHang

                FROM ChiTietDeXuatFEFO c

                INNER JOIN DeXuatFEFO d
                    ON d.maDeXuatFEFO = c.maDeXuatFEFO

                INNER JOIN HangHoa h
                    ON h.maHangHoa = c.maHangHoa

                INNER JOIN ChiTietLoHang cl
                    ON cl.maLoHang = d.maLoHang
                   AND cl.maHangHoa = c.maHangHoa

                WHERE d.maDeXuatFEFO = ?

                ORDER BY
                    cl.ngayHetHan ASC,
                    c.maChiTietDeXuat ASC
            ");

            $stmt->bind_param('s', $selectedProposal);
            $stmt->execute();
            $lines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
} catch (Throwable $e) {
    $error = 'Không tải được dữ liệu: ' . $e->getMessage();
}

/*
 * Lưu phiếu xuất kho.
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['save_uc23'])
    && $error === ''
) {
    if (!$proposal || !$lines) {
        $error = 'Vui lòng chọn đề xuất FEFO hợp lệ có chi tiết hàng hóa.';
    } else {
        $dateTime = new DateTime('now');

        $checked = [];
        $sumByProduct = [];

        foreach ($lines as $line) {
            $id = (string)$line['maChiTietDeXuat'];
            $raw = trim((string)($qtyInput[$id] ?? ''));

            // Số lượng phải là số nguyên dương (không có dấu chấm/phẩy).
            if (!preg_match('/^\d+$/', $raw) || (int)$raw <= 0) {
                $error = 'Số lượng thực xuất phải là số nguyên lớn hơn 0 (không có dấu chấm).';
                break;
            }

            $qty = (int)$raw;

            $fefoRemaining = max(
                0,
                (int)round((float)$line['soLuongDeXuat'])
                - (int)round((float)$line['daXuatTheoLo'])
            );

            $tonKhaDung = (int)floor((float)$line['tonKhaDung']);

            if ($qty > $fefoRemaining || $qty > $tonKhaDung) {
                $error =
                    'Số lượng xuất vượt lượng FEFO còn lại hoặc tồn khả dụng: '
                    . $line['maHangHoa'] . ' / lô '
                    . $line['maLoHang'] . '.';
                break;
            }

            $sumByProduct[$line['maHangHoa']] =
                ($sumByProduct[$line['maHangHoa']] ?? 0) + $qty;

            $checked[] = [
                'line' => $line,
                'qty' => $qty,
                'note' => trim((string)($lineNotes[$id] ?? ''))
            ];
        }

        if ($error === '' && !$checked) {
            $error = 'Vui lòng nhập số lượng thực xuất lớn hơn 0.';
        }

        if ($error === '') {
            foreach ($lines as $line) {
                $productId = $line['maHangHoa'];

                if (!isset($sumByProduct[$productId])) {
                    continue;
                }

                $remaining = max(
                    0,
                    (int)round((float)$line['soLuongYeuCau'])
                    - (int)round((float)$line['daXuatTheoHang'])
                );

                if ($sumByProduct[$productId] > $remaining) {
                    $error =
                        'Tổng số lượng xuất vượt số lượng yêu cầu còn lại của hàng hóa '
                        . $productId . '.';
                    break;
                }
            }
        }

        if ($error === '') {
            try {
                $db->begin_transaction();

                $stmt = $db->prepare("
                    SELECT d.maDeXuatFEFO, d.maYeuCau, d.maLoHang
                    FROM DeXuatFEFO d
                    INNER JOIN YeuCauXuatKho y
                        ON y.maYeuCau = d.maYeuCau
                    WHERE d.maDeXuatFEFO = ?
                      AND y.trangThai IN (
                          'Da duyet', 'Đã duyệt',
                          'Da xuat mot phan', 'Đã xuất một phần'
                      )
                      AND d.trangThai IN (
                          'Da duyet', 'Đã duyệt',
                          'Cho lap phieu', 'Chờ lập phiếu'
                      )
                    FOR UPDATE
                ");

                $stmt->bind_param('s', $selectedProposal);
                $stmt->execute();
                $locked = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$locked) {
                    throw new RuntimeException(
                        'Đề xuất FEFO không còn ở trạng thái hợp lệ.'
                    );
                }

                $maPhieuXuat = taoMaPhieuXuat($db);

                $warehouse = $khoXuat !== ''
                    ? $khoXuat
                    : (string)($proposal['khoXuat'] ?? '');

                $location = $diaDiemXuat !== ''
                    ? $diaDiemXuat
                    : (string)($proposal['diaDiemXuat'] ?? '');

                $totalQty = (int)array_sum($sumByProduct);

                // Chỉ các cột có thật trong bảng mới được ghi (uc23Insert tự lọc).
                $header = [
                    'maPhieuXuat' => $maPhieuXuat,
                    'maYeuCau' => $proposal['maYeuCau'],
                    'maDeXuatFEFO' => $proposal['maDeXuatFEFO'],
                    'maNhanVien' => null,
                    'tenNguoiLap' => $tenNguoiLapPhieu,
                    'nguoiLap' => $tenNguoiLapPhieu,
                    'nguoiLapPhieu' => $tenNguoiLapPhieu,
                    'ngayLap' => $dateTime->format('Y-m-d H:i:s'),
                    'khoXuat' => $warehouse,
                    'diaDiemXuat' => $location,
                    'trangThai' => 'Da xuat',
                    'ghiChu' => $ghiChu,
                    'soLuongThucXuat' => $totalQty
                ];

                uc23Insert($db, 'PhieuXuatKho', $header);

                foreach ($checked as $item) {
                    $line = $item['line'];
                    $qty = (int)$item['qty'];
                    $price = (float)$line['donGiaNhap'];
                    $amount = $qty * $price;
                    $maChiTietLo = (int)$line['maChiTietLo'];

                    $stmt = $db->prepare("
                        SELECT soLuongTon, soLuongDaGiu
                        FROM ChiTietLoHang
                        WHERE maChiTietLo = ?
                          AND maLoHang = ?
                          AND maHangHoa = ?
                        FOR UPDATE
                    ");

                    $stmt->bind_param(
                        'iss',
                        $maChiTietLo,
                        $line['maLoHang'],
                        $line['maHangHoa']
                    );

                    $stmt->execute();
                    $stock = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (
                        !$stock
                        || (
                            (float)$stock['soLuongTon']
                            - (float)$stock['soLuongDaGiu']
                        ) < $qty
                    ) {
                        throw new RuntimeException(
                            'Tồn khả dụng không đủ cho hàng '
                            . $line['maHangHoa']
                            . ' tại lô ' . $line['maLoHang'] . '.'
                        );
                    }

                    $note = $item['note'] !== ''
                        ? $item['note']
                        : 'Theo đề xuất FEFO ' . $proposal['maDeXuatFEFO'];

                    uc23Insert(
                        $db,
                        'ChiTietPhieuXuatKho',
                        [
                            'maPhieuXuat' => $maPhieuXuat,
                            'maHangHoa' => $line['maHangHoa'],
                            'maLoHang' => $line['maLoHang'],
                            'soLuongXuat' => $qty,
                            'donGia' => $price,
                            'thanhTien' => $amount,
                            'ghiChu' => $note
                        ]
                    );

                    $stmt = $db->prepare("
                        UPDATE ChiTietLoHang
                        SET soLuongTon = soLuongTon - ?
                        WHERE maChiTietLo = ?
                          AND maLoHang = ?
                          AND maHangHoa = ?
                          AND (soLuongTon - soLuongDaGiu) >= ?
                    ");

                    $stmt->bind_param(
                        'iissi',
                        $qty,
                        $maChiTietLo,
                        $line['maLoHang'],
                        $line['maHangHoa'],
                        $qty
                    );

                    $stmt->execute();

                    if ($stmt->affected_rows !== 1) {
                        $stmt->close();
                        throw new RuntimeException(
                            'Không thể cập nhật tồn kho của hàng '
                            . $line['maHangHoa'] . '.'
                        );
                    }

                    $stmt->close();
                }

                $stmt = $db->prepare("
                    SELECT
                        cy.maHangHoa,
                        SUM(cy.soLuongYeuCau) AS soLuongYeuCau,
                        COALESCE((
                            SELECT SUM(ct.soLuongXuat)
                            FROM ChiTietPhieuXuatKho ct
                            INNER JOIN PhieuXuatKho p
                                ON p.maPhieuXuat = ct.maPhieuXuat
                            WHERE p.maYeuCau = cy.maYeuCau
                              AND p.trangThai NOT IN ('Da huy', 'Đã hủy')
                              AND ct.maHangHoa = cy.maHangHoa
                        ), 0) AS daXuat
                    FROM ChiTietYeuCauXuatKho cy
                    WHERE cy.maYeuCau = ?
                    GROUP BY cy.maHangHoa, cy.maYeuCau
                ");

                $stmt->bind_param('s', $proposal['maYeuCau']);
                $stmt->execute();
                $result = $stmt->get_result();

                $allDone = true;

                while ($row = $result->fetch_assoc()) {
                    if ((float)$row['daXuat'] < (float)$row['soLuongYeuCau']) {
                        $allDone = false;
                    }
                }

                $stmt->close();

                $requestStatus = $allDone ? 'Da xuat' : 'Da xuat mot phan';

                $stmt = $db->prepare("
                    UPDATE YeuCauXuatKho
                    SET trangThai = ?
                    WHERE maYeuCau = ?
                ");

                $stmt->bind_param('ss', $requestStatus, $proposal['maYeuCau']);
                $stmt->execute();
                $stmt->close();

                $db->commit();

                header(
                    'Location: ' .
                    url(uc_url('UC23', 'index.php')) .
                    '?created=' . urlencode($maPhieuXuat)
                );
                exit;

            } catch (Throwable $e) {
                try {
                    $db->rollback();
                } catch (Throwable $ignored) {
                }

                $error = 'Không thể lập phiếu xuất kho: ' . $e->getMessage();
            }
        }
    }
}

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>

<div class="uc23-page">
    <div class="uc23-heading">
        <p>
            Chọn yêu cầu đã duyệt và đề xuất FEFO.
            Mỗi đề xuất thuộc một lô hàng và có thể có nhiều hàng hóa.
        </p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="uc23-alert uc23-error">
            <?= uc23e($error) ?>
        </div>
    <?php endif; ?>

    <form method="get" class="uc23-picker">
        <div class="uc23-field">
            <label for="maDeXuatFEFO">Yêu cầu xuất kho / Đề xuất FEFO *</label>
            <select name="maDeXuatFEFO" id="maDeXuatFEFO" required>
                <option value="">-- Chọn yêu cầu xuất kho --</option>

                <?php foreach ($requests as $r): ?>
                    <option
                        value="<?= uc23e($r['maDeXuatFEFO']) ?>"
                        <?= $selectedProposal === $r['maDeXuatFEFO'] ? 'selected' : '' ?>
                    >
                        <?= uc23e(
                            $r['maYeuCau']
                            . ' | FEFO: '
                            . $r['maDeXuatFEFO']
                            . ' | Lô: '
                            . $r['maLoHang']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="btn btn-primary" type="submit">Xem chi tiết</button>
    </form>

    <?php if (!$requests): ?>
        <div class="uc23-alert uc23-warning">
            Chưa có yêu cầu đã duyệt kèm đề xuất FEFO
            ở trạng thái cho phép lập phiếu.
        </div>
    <?php endif; ?>

    <?php if ($selectedProposal !== '' && !$proposal && $error === ''): ?>
        <div class="uc23-alert uc23-warning">
            Không tìm thấy đề xuất hợp lệ. Hãy kiểm tra mã yêu cầu
            và trạng thái duyệt trong QLK04.
        </div>
    <?php endif; ?>

    <?php if ($proposal && $lines): ?>
        <form method="post" id="uc23Form">
            <input
                type="hidden"
                name="maDeXuatFEFO"
                value="<?= uc23e($proposal['maDeXuatFEFO']) ?>"
            >

            <section class="uc23-card">
                <h3>Thông tin phiếu xuất kho</h3>

                <div class="uc23-grid">
                    <div class="uc23-field">
                        <label>Mã phiếu xuất</label>
                        <input value="<?= uc23e($previewCode) ?>" readonly>
                    </div>

                    <div class="uc23-field">
                        <label>Ngày lập</label>
                        <input type="text" value="<?= uc23e(date('d/m/Y H:i')) ?>" readonly>
                    </div>

                    <div class="uc23-field">
                        <label>Mã yêu cầu</label>
                        <input value="<?= uc23e($proposal['maYeuCau']) ?>" readonly>
                    </div>

                    <div class="uc23-field">
                        <label>Mã đề xuất FEFO</label>
                        <input value="<?= uc23e($proposal['maDeXuatFEFO']) ?>" readonly>
                    </div>

                    <div class="uc23-field">
                        <label>Mã lô hàng</label>
                        <input value="<?= uc23e($proposal['maLoHang']) ?>" readonly>
                    </div>

                    <div class="uc23-field">
                        <label for="nguoiLapPhieu">Người lập phiếu</label>
                        <input type="text" name="nguoiLapPhieu" id="nguoiLapPhieu" value="<?= uc23e($tenNguoiLapPhieu) ?>">
                    </div>

                    <div class="uc23-field">
                        <label for="khoXuat">Kho xuất *</label>
                        <select name="khoXuat" id="khoXuat" required>
                            <option value="">-- Chọn kho xuất --</option>
                            <?php foreach ($warehouseOptions as $value => $label): ?>
                                <option
                                    value="<?= uc23e($value) ?>"
                                    <?= (
                                        $khoXuat !== ''
                                            ? $khoXuat
                                            : (string)($proposal['khoXuat'] ?? '')
                                    ) === (string)$value ? 'selected' : '' ?>
                                >
                                    <?= uc23e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="uc23-field">
                        <label for="diaDiemXuat">Địa điểm xuất *</label>
                        <select name="diaDiemXuat" id="diaDiemXuat" required>
                            <option value="">-- Chọn địa điểm xuất --</option>
                            <?php foreach ($locationOptions as $value => $label): ?>
                                <option
                                    value="<?= uc23e($value) ?>"
                                    <?= (
                                        $diaDiemXuat !== ''
                                            ? $diaDiemXuat
                                            : (string)($proposal['diaDiemXuat'] ?? '')
                                    ) === (string)$value ? 'selected' : '' ?>
                                >
                                    <?= uc23e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="uc23-field uc23-wide">
                        <label>Ghi chú phiếu xuất</label>
                        <textarea name="ghiChu" rows="2"><?= uc23e($ghiChu) ?></textarea>
                    </div>
                </div>
            </section>

            <section class="uc23-card">
                <h3>Danh sách hàng hóa xuất theo FEFO</h3>
                <p class="uc23-help">
                    Tất cả hàng hóa bên dưới thuộc cùng một lô.
                    Hạn sử dụng được lấy từ ChiTietLoHang.
                    Số lượng là số nguyên, không nhập vượt số lượng FEFO còn lại hoặc tồn khả dụng.
                </p>

                <div class="uc23-table-wrap">
                    <table class="uc23-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã hàng</th>
                                <th>Tên hàng hóa</th>
                                <th>ĐVT</th>
                                <th>Mã lô</th>
                                <th>Hạn sử dụng</th>
                                <th>SL yêu cầu</th>
                                <th>SL tồn khả dụng</th>
                                <th>SL FEFO còn lại</th>
                                <th>SL thực xuất</th>
                                <th>Đơn giá</th>
                                <th>Thành tiền</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($lines as $i => $line):
                            $remainingFefo = max(
                                0,
                                (int)round((float)$line['soLuongDeXuat'])
                                - (int)round((float)$line['daXuatTheoLo'])
                            );

                            $maxQty = (int)min(
                                $remainingFefo,
                                (int)floor((float)$line['tonKhaDung'])
                            );

                            $qtyRaw = $qtyInput[$line['maChiTietDeXuat']] ?? $maxQty;
                            $qty = (int)$qtyRaw;

                            $price = (float)$line['donGiaNhap'];
                            $rowTotal = $qty * $price;
                        ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= uc23e($line['maHangHoa']) ?></td>
                                <td><?= uc23e($line['tenHangHoa']) ?></td>
                                <td><?= uc23e($line['donViTinh']) ?></td>
                                <td><?= uc23e($line['maLoHang']) ?></td>
                                <td>
                                    <?= uc23e(
                                        date('d/m/Y H:i', strtotime($line['ngayHetHan']))
                                    ) ?>
                                </td>
                                <td><?= uc23e(uc23int($line['soLuongYeuCau'])) ?></td>
                                <td><?= uc23e(uc23int($line['tonKhaDung'])) ?></td>
                                <td><?= uc23e($remainingFefo) ?></td>

                                <td>
                                    <input
                                        class="uc23-qty"
                                        type="number"
                                        name="qty[<?= (int)$line['maChiTietDeXuat'] ?>]"
                                        min="1"
max="<?= uc23e($maxQty) ?>"
step="1"
oninvalid="this.setCustomValidity('Vui lòng nhập số lượng từ 1  trở lên và không vượt quá số lượng tồn kho.')"
oninput="this.setCustomValidity('')"
                                        inputmode="numeric"
                                        value="<?= (int)$qty ?>"
                                        data-price="<?= uc23e($price) ?>"
                                        data-total="total-<?= (int)$line['maChiTietDeXuat'] ?>"
                                        required
                                    >
                                </td>

                                <td><?= uc23money($price) ?></td>

                                <td
                                    class="uc23-row-total"
                                    id="total-<?= (int)$line['maChiTietDeXuat'] ?>"
                                >
                                    <?= uc23money($rowTotal) ?>
                                </td>

                                <td>
                                    <textarea
                                        name="ghiChuDong[<?= (int)$line['maChiTietDeXuat'] ?>]"
                                        rows="2"
                                        placeholder="Nhập ghi chú"
                                    ><?= uc23e($lineNotes[$line['maChiTietDeXuat']] ?? '') ?></textarea>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="uc23-actions">
                <button
                    type="submit"
                    name="save_uc23"
                    value="1"
                    class="btn btn-primary"
                >
                    Tạo phiếu xuất
                </button>

                <a
                    class="btn btn-secondary"
                    href="<?= uc23e(url(uc_url('UC23', 'index.php'))) ?>"
                >
                    Hủy
                </a>
            </div>
        </form>

    <?php elseif ($proposal && !$lines): ?>
        <div class="uc23-alert uc23-warning">
            Đề xuất này chưa có dòng hàng hóa phù hợp với lô đã chọn.
            Kiểm tra ChiTietDeXuatFEFO và ChiTietLoHang trong QLK04.
        </div>
    <?php endif; ?>
</div>

<style>
.uc23-page{display:flex;flex-direction:column;gap:18px}
.uc23-heading h2{margin:0 0 8px;color:#17365d}
.uc23-heading p,.uc23-help{color:#64748b}
.uc23-picker{display:grid;grid-template-columns:minmax(260px,1fr) auto;gap:12px;align-items:end;background:#fff;padding:18px;border:1px solid #e2e8f0;border-radius:12px}
.uc23-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-top:16px}
.uc23-card h3{margin:0 0 16px;color:#17365d}
.uc23-grid{display:grid;grid-template-columns:repeat(2,minmax(220px,1fr));gap:16px}
.uc23-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.uc23-field label{font-weight:600;color:#334155}
.uc23-field input,.uc23-field select,.uc23-field textarea,.uc23-table input{box-sizing:border-box;width:100%;min-width:90px;padding:9px 10px;border:1px solid #cbd5e1;border-radius:7px;background:#fff;color:#0f172a}
.uc23-field input[readonly]{background:#f8fafc}
.uc23-wide{grid-column:span 2}
.uc23-table-wrap{overflow:auto;margin-top:14px}
.uc23-table{width:100%;min-width:1500px;border-collapse:collapse}
.uc23-table th,.uc23-table td{padding:9px;border:1px solid #e2e8f0;text-align:left;vertical-align:middle}
.uc23-table th{background:#eff6ff;white-space:nowrap}
.uc23-table td{background:#fff}
.uc23-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}
.uc23-alert{padding:12px 14px;border-radius:8px;margin:10px 0}
.uc23-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;overflow-wrap:anywhere}
.uc23-warning{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
@media(max-width:760px){.uc23-picker,.uc23-grid{grid-template-columns:1fr}.uc23-wide{grid-column:auto}.uc23-card{padding:14px}}
</style>

<script>
(function () {
    const form = document.getElementById('uc23Form');
    if (!form) return;

    const inputs = [...form.querySelectorAll('.uc23-qty')];

    function updateTotals() {
        inputs.forEach(input => {
            const qty = Number(input.value || 0);
            const price = Number(input.dataset.price || 0);
            const target = document.getElementById(input.dataset.total);

            if (target) {
                target.textContent = new Intl.NumberFormat('vi-VN', {
                    maximumFractionDigits: 0
                }).format(qty * price) + ' đ';
            }
        });
    }

    inputs.forEach(input => {
        input.addEventListener('input', updateTotals);

        // Chặn nhập dấu chấm, dấu phẩy, e, +, - (chỉ cho số nguyên).
        input.addEventListener('keydown', function (e) {
            if (['.', ',', 'e', 'E', '+', '-'].includes(e.key)) {
                e.preventDefault();
            }
        });
    });

    form.addEventListener('submit', function (event) {
        for (const input of inputs) {
            const raw = input.value.trim();
            const qty = Number(raw);
            const max = Number(input.max);

            if (!/^\d+$/.test(raw) || qty <= 0) {
                event.preventDefault();
                alert('Số lượng thực xuất phải là số nguyên lớn hơn 0 (không có dấu chấm).');
                input.focus();
                return;
            }

            if (qty > max) {
                event.preventDefault();
                alert('Số lượng thực xuất không được vượt quá giới hạn FEFO còn lại và tồn khả dụng (' + max + ').');
                input.focus();
                return;
            }
        }
    });

    updateTotals();
})();
</script>

<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
?>
