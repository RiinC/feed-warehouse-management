<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/inventory_units.php';
require_module('UC17');

$db = db(DB_UC17);
$listUrl = uc_url('UC17');
$steps = [
    1 => 'Chọn lô hàng',
    2 => 'Chọn vị trí nguồn',
    3 => 'Chọn vị trí đích',
    4 => 'Nhập thông tin',
    5 => 'Xác nhận',
];
$reasons = [
    'Điều chuyển vị trí lưu kho',
    'Cân bằng tồn kho giữa các kho',
    'Phục vụ kế hoạch sản xuất',
    'Khác',
];
$errors = [];

if (isset($_GET['cancel'])) {
    unset($_SESSION['uc17_transfer']);
    redirect($listUrl);
}
if (isset($_GET['new'])) {
    unset($_SESSION['uc17_transfer']);
    redirect(uc_url('UC17', 'form.php'));
}
if (!isset($_SESSION['uc17_transfer']) || !is_array($_SESSION['uc17_transfer'])) {
    $_SESSION['uc17_transfer'] = ['step' => 1];
}
$wizard = &$_SESSION['uc17_transfer'];
$currentStep = max(1, min(5, (int)($wizard['step'] ?? 1)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $postedStep = (int)($_POST['current_step'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    if ($postedStep !== $currentStep) {
        $errors[] = 'Bước hiện tại không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } elseif ($action === 'back') {
        $wizard['step'] = max(1, $currentStep - 1);
        redirect(uc_url('UC17', 'form.php'));
    } elseif ($action === 'cancel') {
        unset($_SESSION['uc17_transfer']);
        redirect($listUrl);
    } elseif ($action === 'next') {
        if ($currentStep === 1) {
            $lotCode = trim((string)($_POST['maLoHang'] ?? ''));
            $lot = db_one(
                "SELECT l.maLoHang
                 FROM LoHang l JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
                 WHERE l.maLoHang = ? AND l.trangThai = 'Đang sử dụng'
                   AND l.hanSuDung >= NOW() AND h.trangThai = 'Đang sử dụng'
                   AND EXISTS (
                       SELECT 1 FROM TonKho t
                       JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
                       JOIN KhuVuc a ON a.maKhuVuc = v.maKhuVuc
                       JOIN Kho k ON k.maKho = a.maKho
                       WHERE t.maLoHang = l.maLoHang
                         AND t.soLuongTon > t.soLuongDaGiu
                         AND t.trangThai = 'Đã nhập kho'
                         AND v.trangThai = 'Đang hoạt động'
                         AND a.trangThai = 'Đang hoạt động'
                         AND k.trangThai = 'Đang hoạt động'
                   )",
                's',
                [$lotCode],
                DB_UC17
            );
            if (!$lot) {
                $errors[] = 'Vui lòng chọn lô hàng còn tồn khả dụng và còn hạn sử dụng.';
            } else {
                $wizard['maLoHang'] = $lot['maLoHang'];
                unset($wizard['maTonKho'], $wizard['maViTriDich']);
                $wizard['step'] = 2;
            }
        } elseif ($currentStep === 2) {
            $stockCode = trim((string)($_POST['maTonKho'] ?? ''));
            $source = db_one(
                "SELECT t.maTonKho, t.maLoHang, t.maViTri, t.soLuongTon, t.soLuongDaGiu,
                        v.tenViTri, a.tenKhuVuc, k.maKho, k.tenKho
                 FROM TonKho t
                 JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
                 JOIN KhuVuc a ON a.maKhuVuc = v.maKhuVuc
                 JOIN Kho k ON k.maKho = a.maKho
                 WHERE t.maTonKho = ? AND t.maLoHang = ?
                   AND t.trangThai = 'Đã nhập kho'
                   AND v.trangThai = 'Đang hoạt động'
                   AND a.trangThai = 'Đang hoạt động'
                   AND k.trangThai = 'Đang hoạt động'
                   AND t.soLuongTon > t.soLuongDaGiu",
                'ss',
                [$stockCode, $wizard['maLoHang'] ?? ''],
                DB_UC17
            );
            if (!$source) {
                $errors[] = 'Vị trí nguồn không còn hàng khả dụng. Vui lòng chọn lại.';
            } else {
                $wizard['maTonKho'] = $source['maTonKho'];
                $wizard['maViTriNguon'] = $source['maViTri'];
                $wizard['step'] = 3;
            }
        } elseif ($currentStep === 3) {
            $destinationCode = trim((string)($_POST['maViTriDich'] ?? ''));
            $destination = db_one(
                "SELECT v.maViTri, v.donViSucChua, v.sucChuaToiDa
                 FROM ViTriLuuTru v
                 JOIN KhuVuc a ON a.maKhuVuc = v.maKhuVuc
                 JOIN Kho k ON k.maKho = a.maKho
                 WHERE v.maViTri = ? AND v.maViTri <> ?
                   AND v.trangThai = 'Đang hoạt động'
                   AND a.trangThai = 'Đang hoạt động'
                   AND k.trangThai = 'Đang hoạt động'",
                'ss',
                [$destinationCode, $wizard['maViTriNguon'] ?? ''],
                DB_UC17
            );
            $item = db_one(
                "SELECT h.kgMoiBao, h.baoMoiPallet, h.khoiLuongRieng
                 FROM LoHang l JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
                 WHERE l.maLoHang = ?",
                's',
                [$wizard['maLoHang'] ?? ''],
                DB_UC17
            );
            $capacityUsage = $destination
                ? inventory_location_capacity_usage($destination['maViTri'], $destination['donViSucChua'], DB_UC17)
                : null;
            try {
                if ($destination && $item) {
                    inventory_kg_per_unit($destination['donViSucChua'], $item);
                }
            } catch (DomainException $ex) {
                $errors[] = $ex->getMessage();
            }
            if (!$errors && (!$destination || $capacityUsage === null || $capacityUsage >= (float)$destination['sucChuaToiDa'])) {
                $errors[] = 'Vui lòng chọn vị trí đích đang hoạt động, còn sức chứa và khác vị trí nguồn.';
            }
            if (!$errors) {
                $destination['daSuDung'] = $capacityUsage;
                $wizard['maViTriDich'] = $destination['maViTri'];
                $wizard['step'] = 4;
            }
        } elseif ($currentStep === 4) {
            $planned = filter_var($_POST['soLuongDuKien'] ?? null, FILTER_VALIDATE_FLOAT);
            $actual = filter_var($_POST['soLuongThucTe'] ?? null, FILTER_VALIDATE_FLOAT);
            $unit = trim((string)($_POST['donViTinh'] ?? ''));
            $reason = trim((string)($_POST['lyDoDieuChuyen'] ?? ''));
            $note = trim((string)($_POST['ghiChu'] ?? ''));
            $source = db_one(
                "SELECT soLuongTon - soLuongDaGiu AS khaDung
                 FROM TonKho WHERE maTonKho = ? AND maLoHang = ?",
                'ss',
                [$wizard['maTonKho'] ?? '', $wizard['maLoHang'] ?? ''],
                DB_UC17
            );
            $availableKg = (float)($source['khaDung'] ?? 0);
            $item = db_one(
                "SELECT h.kgMoiBao, h.baoMoiPallet, h.khoiLuongRieng
                 FROM LoHang l JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
                 WHERE l.maLoHang = ?",
                's',
                [$wizard['maLoHang'] ?? ''],
                DB_UC17
            );
            $factor = null;
            $plannedKg = 0.0;
            $actualKg = 0.0;
            try {
                if (!in_array($unit, inventory_unit_options(), true)) {
                    throw new DomainException('Vui lòng chọn đơn vị tính hợp lệ.');
                }
                $factor = inventory_kg_per_unit($unit, $item ?? []);
                if ($planned !== false && $planned > 0) {
                    $plannedKg = inventory_quantity_to_kg((float)$planned, $unit, $item ?? []);
                }
                if ($actual !== false && $actual > 0) {
                    $actualKg = inventory_quantity_to_kg((float)$actual, $unit, $item ?? []);
                }
            } catch (DomainException $ex) {
                $errors[] = $ex->getMessage();
            }
            if ($planned === false || $planned <= 0 || $plannedKg > $availableKg) {
                $errors[] = 'Số lượng dự kiến sau quy đổi phải lớn hơn 0 và không vượt tồn khả dụng nguồn (' . fmt_qty($availableKg) . ' Kg).';
            }
            if ($actual === false || $actual <= 0 || ($planned !== false && $actual > $planned)) {
                $errors[] = 'Số thực nhận ước tính phải lớn hơn 0 và không vượt quá số lượng dự kiến.';
            }
            if (!in_array($reason, $reasons, true)) {
                $errors[] = 'Vui lòng chọn lý do điều chuyển hợp lệ.';
            }
            if (mb_strlen($note) > 500) {
                $errors[] = 'Ghi chú không được vượt quá 500 ký tự.';
            }
            if (!$errors) {
                $wizard['soLuongDuKien'] = (float)$planned;
                $wizard['soLuongThucTe'] = (float)$actual;
                $wizard['donViTinh'] = $unit;
                $wizard['heSoQuyDoiKg'] = $factor;
                $wizard['soLuongDuKienKg'] = $plannedKg;
                $wizard['soLuongThucTeKg'] = $actualKg;
                $wizard['lyDoDieuChuyen'] = $reason;
                $wizard['ghiChu'] = $note;
                $wizard['step'] = 5;
            }
        }

        if (!$errors && $currentStep < 5) {
            redirect(uc_url('UC17', 'form.php'));
        }
    } elseif ($action === 'complete' && $currentStep === 5) {
        $sourceLocation = (string)($wizard['maViTriNguon'] ?? '');
        $destinationLocation = (string)($wizard['maViTriDich'] ?? '');
        $stockCode = (string)($wizard['maTonKho'] ?? '');
        $lotCode = (string)($wizard['maLoHang'] ?? '');
        $planned = (float)($wizard['soLuongDuKien'] ?? 0);
        $actual = (float)($wizard['soLuongThucTe'] ?? 0);
        $plannedKg = (float)($wizard['soLuongDuKienKg'] ?? 0);
        $actualKg = (float)($wizard['soLuongThucTeKg'] ?? 0);
        $unit = (string)($wizard['donViTinh'] ?? '');
        $conversionFactor = (float)($wizard['heSoQuyDoiKg'] ?? 0);
        $employeeCode = (string)(current_user()['id'] ?? '');

        if (
            !$sourceLocation || !$destinationLocation || !$stockCode || !$lotCode
            || $planned <= 0 || $actual <= 0 || $plannedKg <= 0 || $actualKg <= 0
            || $conversionFactor <= 0 || !in_array($unit, inventory_unit_options(), true)
        ) {
            $errors[] = 'Thông tin điều chuyển chưa đầy đủ. Vui lòng bắt đầu lại.';
        } else {
            $db->begin_transaction();
            try {
                $lockedLocations = db_all(
                    "SELECT v.maViTri, v.sucChuaToiDa, v.donViSucChua, v.trangThai AS trangThaiViTri,
                            a.trangThai AS trangThaiKhuVuc, k.trangThai AS trangThaiKho
                     FROM ViTriLuuTru v
                     JOIN KhuVuc a ON a.maKhuVuc = v.maKhuVuc
                     JOIN Kho k ON k.maKho = a.maKho
                     WHERE v.maViTri IN (?, ?)
                     ORDER BY v.maViTri
                     FOR UPDATE",
                    'ss',
                    [$sourceLocation, $destinationLocation],
                    DB_UC17
                );
                if (count($lockedLocations) !== 2) {
                    throw new DomainException('Không tìm thấy đầy đủ vị trí nguồn và vị trí đích.');
                }
                foreach ($lockedLocations as $location) {
                    if (
                        $location['trangThaiViTri'] !== 'Đang hoạt động'
                        || $location['trangThaiKhuVuc'] !== 'Đang hoạt động'
                        || $location['trangThaiKho'] !== 'Đang hoạt động'
                    ) {
                        throw new DomainException('Kho, khu vực hoặc vị trí đã ngừng hoạt động. Vui lòng tạo phiếu mới.');
                    }
                }
                $lockedLot = db_one(
                    "SELECT l.trangThai AS trangThaiLo, l.hanSuDung, h.trangThai AS trangThaiHang,
                            h.donViTinh, h.kgMoiBao, h.baoMoiPallet, h.khoiLuongRieng
                     FROM LoHang l JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
                     WHERE l.maLoHang = ? FOR UPDATE",
                    's',
                    [$lotCode],
                    DB_UC17
                );
                if (
                    !$lockedLot || $lockedLot['trangThaiLo'] !== 'Đang sử dụng'
                    || $lockedLot['trangThaiHang'] !== 'Đang sử dụng'
                    || strtotime($lockedLot['hanSuDung']) < time()
                ) {
                    throw new DomainException('Lô hàng đã ngừng sử dụng hoặc hết hạn. Vui lòng tạo phiếu mới.');
                }
                $currentFactor = inventory_kg_per_unit($unit, $lockedLot);
                if (abs($currentFactor - $conversionFactor) > 0.000001) {
                    throw new DomainException('Hệ số quy đổi của mặt hàng vừa thay đổi. Vui lòng quay lại kiểm tra số lượng trước khi gửi duyệt.');
                }
                $lockedStock = db_one(
                    'SELECT soLuongTon, soLuongDaGiu FROM TonKho WHERE maTonKho = ? AND maLoHang = ? AND maViTri = ? FOR UPDATE',
                    'sss',
                    [$stockCode, $lotCode, $sourceLocation],
                    DB_UC17
                );
                if (
                    !$lockedStock || ((float)$lockedStock['soLuongTon'] - (float)$lockedStock['soLuongDaGiu']) < $plannedKg
                ) {
                    throw new DomainException('Tồn khả dụng đã thay đổi hoặc không đủ số lượng. Vui lòng tạo phiếu mới.');
                }
                $destinationMeta = null;
                foreach ($lockedLocations as $location) {
                    if ($location['maViTri'] === $destinationLocation) {
                        $destinationMeta = $location;
                        break;
                    }
                }
                if (!$destinationMeta) {
                    throw new DomainException('Vị trí đích không còn hoạt động.');
                }
                $occupied = inventory_location_capacity_usage($destinationLocation, $destinationMeta['donViSucChua'], DB_UC17);
                if ($occupied === null) {
                    throw new DomainException('Không thể tính sức chứa vị trí m³ vì một mặt hàng đang lưu chưa có khối lượng riêng.');
                }
                $incomingCapacity = inventory_kg_to_capacity($actualKg, $destinationMeta['donViSucChua'], $lockedLot);
                if (!$destinationMeta || $occupied + $incomingCapacity > (float)$destinationMeta['sucChuaToiDa']) {
                    throw new DomainException('Vị trí đích không đủ sức chứa cho số lượng đã quy đổi.');
                }

                $temporaryCode = 'TMP' . strtoupper(bin2hex(random_bytes(8)));
                db_exec(
                    "INSERT INTO PhieuDieuChuyen
                        (maPhieuDieuChuyen, soLuongDuKien, soLuongThucTe, donViTinh, heSoQuyDoiKg,
                         soLuongDuKienKg, soLuongThucTeKg, lyDoDieuChuyen, thoiDiemLap, thoiDiemHoanTat,
                         trangThai, ghiChu, maLoHang, maViTriNguon, maViTriDich, maNV)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NULL, 'Chờ phê duyệt', ?, ?, ?, ?, ?)
                     ",
                    'sddsdddssssss',
                    [
                        $temporaryCode,
                        $planned,
                        $actual,
                        $unit,
                        $conversionFactor,
                        $plannedKg,
                        $actualKg,
                        $wizard['lyDoDieuChuyen'],
                        $wizard['ghiChu'] ?? '',
                        $lotCode,
                        $sourceLocation,
                        $destinationLocation,
                        $employeeCode,
                    ],
                    DB_UC17
                );
                $newDocumentId = $db->insert_id;
                $documentCode = 'DC' . str_pad((string)($newDocumentId + 125), 6, '0', STR_PAD_LEFT);
                db_exec(
                    'UPDATE PhieuDieuChuyen SET maPhieuDieuChuyen = ? WHERE maPhieuDieuChuyen = ?',
                    'ss',
                    [$documentCode, $temporaryCode],
                    DB_UC17
                );
                $db->commit();
                unset($_SESSION['uc17_transfer']);
                flash('success', 'Phiếu đã được lập và đang chờ phê duyệt. Mã phiếu: ' . $documentCode . '.');
                redirect($listUrl . '?id=' . rawurlencode($documentCode));
            } catch (Throwable $ex) {
                $db->rollback();
                error_log('UC17 transfer failed: ' . $ex->getMessage());
                $errors[] = $ex instanceof DomainException ? $ex->getMessage() : 'Không thể lập phiếu điều chuyển do lỗi cơ sở dữ liệu. Vui lòng thử lại.';
            }
        }
    } else {
        $errors[] = 'Thao tác không hợp lệ. Vui lòng tải lại trang.';
    }
}

$currentStep = max(1, min(5, (int)($wizard['step'] ?? 1)));
$page_title = $currentStep === 5 ? 'Xác nhận điều chuyển' : 'Tạo phiếu điều chuyển';
$active_menu = 'UC17';
$breadcrumb = [['Tổng quan', 'index.php'], ['Điều chuyển nội bộ', $listUrl], [$page_title, null]];

$lots = [];
$lotCategories = [];
$lotWarehouses = [];
if ($currentStep === 1) {
    $itemSearch = trim((string)($_GET['q'] ?? ''));
    $categoryFilter = trim((string)($_GET['category'] ?? ''));
    $warehouseFilter = trim((string)($_GET['warehouse'] ?? ''));
    $expiryFrom = trim((string)($_GET['expiry_from'] ?? ''));
    $expiryTo = trim((string)($_GET['expiry_to'] ?? ''));
    $lotParams = [];
    $lotTypes = '';
    $itemFilters = [];
    if ($itemSearch !== '') {
        $itemFilters[] = '(h.maHangHoa LIKE ? OR h.tenHangHoa LIKE ? OR l.maLoHang LIKE ? OR l.soLo LIKE ?)';
        $like = '%' . $itemSearch . '%';
        $lotTypes = 'ssss';
        $lotParams = [$like, $like, $like, $like];
    }
    if ($categoryFilter !== '') {
        $itemFilters[] = 'h.loaiHangHoa = ?';
        $lotTypes .= 's';
        $lotParams[] = $categoryFilter;
    }
    if ($warehouseFilter !== '') {
        $itemFilters[] = 'k.maKho = ?';
        $lotTypes .= 's';
        $lotParams[] = $warehouseFilter;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryFrom)) {
        $itemFilters[] = 'l.hanSuDung >= ?';
        $lotTypes .= 's';
        $lotParams[] = $expiryFrom;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryTo)) {
        $itemFilters[] = 'l.hanSuDung < DATE_ADD(?, INTERVAL 1 DAY)';
        $lotTypes .= 's';
        $lotParams[] = $expiryTo;
    }
    $itemFilter = $itemFilters ? ' AND ' . implode(' AND ', $itemFilters) : '';
    $lotCategories = db_all(
        "SELECT DISTINCT loaiHangHoa FROM HangHoa
         WHERE trangThai = 'Đang sử dụng' ORDER BY loaiHangHoa",
        '',
        [],
        DB_UC17
    );
    $lotWarehouses = db_all(
        "SELECT DISTINCT k.maKho, k.tenKho
         FROM Kho k
         JOIN KhuVuc a ON a.maKho = k.maKho
         JOIN ViTriLuuTru v ON v.maKhuVuc = a.maKhuVuc
         JOIN TonKho t ON t.maViTri = v.maViTri
         WHERE k.trangThai = 'Đang hoạt động' AND a.trangThai = 'Đang hoạt động'
           AND v.trangThai = 'Đang hoạt động'
         ORDER BY k.tenKho",
        '',
        [],
        DB_UC17
    );
    $lots = db_all(
        "SELECT l.maLoHang, l.soLo, l.hanSuDung, h.maHangHoa, h.tenHangHoa, h.donViTinh,
                h.kgMoiBao, h.baoMoiPallet, h.khoiLuongRieng,
                COALESCE(SUM(GREATEST(t.soLuongTon - t.soLuongDaGiu, 0)), 0) AS khaDung
         FROM LoHang l
         JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
         JOIN TonKho t ON t.maLoHang = l.maLoHang
         JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
         JOIN KhuVuc a ON a.maKhuVuc = v.maKhuVuc
         JOIN Kho k ON k.maKho = a.maKho
         WHERE l.trangThai = 'Đang sử dụng' AND l.hanSuDung >= NOW()
           AND h.trangThai = 'Đang sử dụng' AND t.trangThai = 'Đã nhập kho'
           AND v.trangThai = 'Đang hoạt động' AND a.trangThai = 'Đang hoạt động'
           AND k.trangThai = 'Đang hoạt động' $itemFilter
         GROUP BY l.maLoHang, l.soLo, l.hanSuDung, h.maHangHoa, h.tenHangHoa, h.donViTinh,
                  h.kgMoiBao, h.baoMoiPallet, h.khoiLuongRieng
         HAVING khaDung > 0
         ORDER BY h.tenHangHoa, l.hanSuDung",
        $lotTypes,
        $lotParams,
        DB_UC17
    );
}

$sources = [];
$selectedLot = null;
if (!empty($wizard['maLoHang'])) {
    $selectedLot = db_one(
        "SELECT l.maLoHang, l.soLo, l.hanSuDung, h.maHangHoa, h.tenHangHoa, h.donViTinh,
                h.kgMoiBao, h.baoMoiPallet, h.khoiLuongRieng
         FROM LoHang l JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
         WHERE l.maLoHang = ?",
        's',
        [$wizard['maLoHang']],
        DB_UC17
    );
}
if ($currentStep === 2 && $selectedLot) {
    $sources = db_all(
        "SELECT t.maTonKho, t.maViTri, t.soLuongTon, t.soLuongDaGiu,
                v.tenViTri, v.sucChuaToiDa, a.tenKhuVuc, k.tenKho, k.maKho
         FROM TonKho t
         JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
         JOIN KhuVuc a ON a.maKhuVuc = v.maKhuVuc
         JOIN Kho k ON k.maKho = a.maKho
         WHERE t.maLoHang = ? AND t.trangThai = 'Đã nhập kho'
           AND t.soLuongTon > t.soLuongDaGiu
           AND v.trangThai = 'Đang hoạt động'
           AND a.trangThai = 'Đang hoạt động'
           AND k.trangThai = 'Đang hoạt động'
         ORDER BY k.tenKho, a.tenKhuVuc, v.tenViTri",
        's',
        [$wizard['maLoHang']],
        DB_UC17
    );
}
$source = null;
if (!empty($wizard['maTonKho'])) {
    $source = db_one(
        "SELECT t.maTonKho, t.maViTri, t.soLuongTon, t.soLuongDaGiu,
                v.tenViTri, v.sucChuaToiDa, a.tenKhuVuc, k.maKho, k.tenKho
         FROM TonKho t
         JOIN ViTriLuuTru v ON v.maViTri = t.maViTri
         JOIN KhuVuc a ON a.maKhuVuc = v.maKhuVuc
         JOIN Kho k ON k.maKho = a.maKho
         WHERE t.maTonKho = ?",
        's',
        [$wizard['maTonKho']],
        DB_UC17
    );
}
$destinations = [];
if ($currentStep === 3 && $source) {
    $destinations = db_all(
        "SELECT v.maViTri, v.tenViTri, v.sucChuaToiDa, v.donViSucChua, a.tenKhuVuc, k.maKho, k.tenKho
         FROM ViTriLuuTru v
         JOIN KhuVuc a ON a.maKhuVuc = v.maKhuVuc
         JOIN Kho k ON k.maKho = a.maKho
         WHERE v.maViTri <> ? AND v.trangThai = 'Đang hoạt động'
           AND a.trangThai = 'Đang hoạt động' AND k.trangThai = 'Đang hoạt động'
         ORDER BY k.tenKho, a.tenKhuVuc, v.tenViTri",
        's',
        [$source['maViTri']],
        DB_UC17
    );
    $availableDestinations = [];
    foreach ($destinations as $candidate) {
        try {
            inventory_kg_per_unit($candidate['donViSucChua'], $selectedLot);
            $usage = inventory_location_capacity_usage($candidate['maViTri'], $candidate['donViSucChua'], DB_UC17);
            if ($usage === null || $usage >= (float)$candidate['sucChuaToiDa']) {
                continue;
            }
            $candidate['daSuDung'] = $usage;
            $availableDestinations[] = $candidate;
        } catch (DomainException $ex) {
            continue;
        }
    }
    $destinations = $availableDestinations;
}
$destination = null;
if (!empty($wizard['maViTriDich'])) {
    $destination = db_one(
        "SELECT v.maViTri, v.tenViTri, v.sucChuaToiDa, v.donViSucChua, a.tenKhuVuc, k.maKho, k.tenKho
         FROM ViTriLuuTru v
         JOIN KhuVuc a ON a.maKhuVuc = v.maKhuVuc
         JOIN Kho k ON k.maKho = a.maKho
         WHERE v.maViTri = ?",
        's',
        [$wizard['maViTriDich']],
        DB_UC17
    );
    if ($destination) {
        $destination['daSuDung'] = inventory_location_capacity_usage(
            $destination['maViTri'],
            $destination['donViSucChua'],
            DB_UC17
        ) ?? 0;
    }
}
$availableSource = $source ? max(0, (float)$source['soLuongTon'] - (float)$source['soLuongDaGiu']) : 0;
$selectedUnit = (string)($_POST['donViTinh'] ?? $wizard['donViTinh'] ?? ($selectedLot['donViTinh'] ?? 'Kg'));
$unitFactors = [];
if ($selectedLot) {
    foreach (inventory_unit_options() as $optionUnit) {
        try {
            $unitFactors[$optionUnit] = inventory_kg_per_unit($optionUnit, $selectedLot);
        } catch (DomainException $ex) {
            continue;
        }
    }
}
if (!isset($unitFactors[$selectedUnit])) {
    $selectedUnit = isset($unitFactors[$selectedLot['donViTinh'] ?? '']) ? $selectedLot['donViTinh'] : 'Kg';
}
$unitFactor = $unitFactors[$selectedUnit] ?? 1.0;
if ($selectedLot && in_array($selectedUnit, inventory_unit_options(), true)) {
    try {
        $unitFactor = inventory_kg_per_unit($selectedUnit, $selectedLot);
    } catch (DomainException $ex) {
        $unitFactor = 1.0;
    }
}
$availableInSelectedUnit = $unitFactor > 0 ? $availableSource / $unitFactor : 0;
$savedPlanned = $_POST['soLuongDuKien'] ?? $wizard['soLuongDuKien'] ?? null;
$savedActual = $_POST['soLuongThucTe'] ?? $wizard['soLuongThucTe'] ?? null;
$actualIsCustom = $savedActual !== null && $savedPlanned !== null && (float)$savedActual !== (float)$savedPlanned;
$extra_scripts = '<script>
document.querySelectorAll("input[type=radio]").forEach(function (radio) {
    radio.addEventListener("change", function () {
        document.querySelectorAll("[data-selectable-row]").forEach(function (row) { row.classList.remove("selected-row"); });
        var row = radio.closest("[data-selectable-row]");
        if (row) row.classList.add("selected-row");
    });
});
document.querySelectorAll("[data-selectable-row]").forEach(function (row) {
    row.addEventListener("click", function (event) {
        if (event.target.closest("a, button, input, select, textarea, label")) return;
        var radio = row.querySelector("input[type=radio]");
        if (radio && !radio.checked) {
            radio.checked = true;
            radio.dispatchEvent(new Event("change", { bubbles: true }));
        }
    });
});
document.querySelectorAll(".sortable-table th[data-sortable]").forEach(function (header) {
    header.addEventListener("click", sortColumn);
    header.addEventListener("keydown", function (event) {
        if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            sortColumn.call(header);
        }
    });
});
function sortColumn() {
    var header = this;
    var table = header.closest("table");
    var column = Array.prototype.indexOf.call(header.parentElement.children, header);
    var body = table.tBodies[0];
    var rows = Array.prototype.slice.call(body.querySelectorAll("tr[data-selectable-row]"));
    var ascending = header.dataset.sortDirection !== "ascending";
    var collator = new Intl.Collator(undefined, { numeric: true, sensitivity: "base" });
    rows.sort(function (left, right) {
        var leftCell = left.children[column];
        var rightCell = right.children[column];
        var a = leftCell.dataset.sortValue || leftCell.textContent.trim();
        var b = rightCell.dataset.sortValue || rightCell.textContent.trim();
        var aNumber = Number(a);
        var bNumber = Number(b);
        var result;
        if (a !== "" && b !== "" && Number.isFinite(aNumber) && Number.isFinite(bNumber)) {
            result = aNumber - bNumber;
        } else {
            result = collator.compare(a, b);
        }
        return ascending ? result : -result;
    });
    table.querySelectorAll("th[data-sortable]").forEach(function (item) {
        item.removeAttribute("aria-sort");
        delete item.dataset.sortDirection;
    });
    header.dataset.sortDirection = ascending ? "ascending" : "descending";
    header.setAttribute("aria-sort", header.dataset.sortDirection);
    rows.forEach(function (row) { body.appendChild(row); });
}
var expectedInput = document.getElementById("expected");
var actualInput = document.getElementById("actual");
var unitSelect = document.getElementById("unit");
if (expectedInput && actualInput) {
    actualInput.addEventListener("input", function () { actualInput.dataset.manual = "true"; });
    expectedInput.addEventListener("input", function () {
        actualInput.max = expectedInput.value || expectedInput.max;
        if (actualInput.dataset.manual !== "true") actualInput.value = expectedInput.value;
    });
}
if (expectedInput && actualInput && unitSelect) {
    var sourceKg = ' . json_encode($availableSource) . ';
    var currentFactor = Number(unitSelect.options[unitSelect.selectedIndex].dataset.factor);
    unitSelect.addEventListener("change", function () {
        var nextFactor = Number(unitSelect.options[unitSelect.selectedIndex].dataset.factor);
        if (!(currentFactor > 0) || !(nextFactor > 0)) return;
        if (expectedInput.value !== "") expectedInput.value = (Number(expectedInput.value) * currentFactor / nextFactor).toFixed(3);
        if (actualInput.value !== "") actualInput.value = (Number(actualInput.value) * currentFactor / nextFactor).toFixed(3);
        currentFactor = nextFactor;
        expectedInput.max = (sourceKg / currentFactor).toFixed(3);
        actualInput.max = expectedInput.value;
        document.querySelectorAll("[data-unit-label]").forEach(function (label) { label.textContent = unitSelect.value; });
    });
}
</script>';

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<div class="card">
    <div class="card-head">
        <h2>Quy trình điều chuyển</h2><a class="link" href="<?= e(url(uc_url('UC17', 'form.php') . '?cancel=1')) ?>">Hủy và quay lại</a>
    </div>
    <div class="transfer-steps" role="list" aria-label="Tiến trình điều chuyển">
        <?php foreach ($steps as $number => $label): ?>
            <div class="transfer-step<?= $number === $currentStep ? ' active' : ($number < $currentStep ? ' done' : '') ?>" role="listitem" <?= $number === $currentStep ? ' aria-current="step"' : '' ?>>
                <span class="transfer-step-number" aria-hidden="true"><?= $number < $currentStep ? '✓' : $number ?></span>
                <small><?= e($label) ?></small>
                <?php if ($number < count($steps)): ?><i class="transfer-connector" aria-hidden="true"></i><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error" role="alert"><b>Chưa thể tiếp tục:</b>
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php if ($selectedLot): ?>
    <div class="alert alert-info">
        <b>Lô hàng đã chọn:</b> <?= e($selectedLot['maLoHang'] . ' · ' . $selectedLot['tenHangHoa']) ?>
        &nbsp; <b>Tồn khả dụng tại các kho:</b>
        <?php
        $lotAvailableRow = db_one(
            "SELECT COALESCE(SUM(t.soLuongTon - t.soLuongDaGiu), 0) AS khaDung FROM TonKho t
         WHERE t.maLoHang = ? AND t.trangThai = 'Đã nhập kho'",
            's',
            [$selectedLot['maLoHang']],
            DB_UC17
        );
        echo e(fmt_qty($lotAvailableRow['khaDung'] ?? 0) . ' Kg');
        ?>
    </div>
<?php endif; ?>

<section class="card transfer-form">
    <?php if ($currentStep === 1): ?>
        <div class="card-head">
            <h2>1. Chọn lô hàng cần điều chuyển</h2><span class="muted"><?= count($lots) ?> lô khả dụng</span>
        </div>
        <form method="get" class="filter-grid lot-filters" style="margin-bottom:14px">
            <div class="field"><label for="q">Mã/tên hàng hoặc mã lô</label><input type="search" id="q" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Ví dụ: cám, HH0001, LH00045"></div>
            <div class="field"><label for="category">Nhóm hàng</label><select id="category" name="category">
                    <option value="">Tất cả nhóm hàng</option><?php foreach ($lotCategories as $category): ?><option value="<?= e($category['loaiHangHoa']) ?>" <?= (($_GET['category'] ?? '') === $category['loaiHangHoa']) ? 'selected' : '' ?>><?= e($category['loaiHangHoa']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="field"><label for="warehouse">Kho có tồn</label><select id="warehouse" name="warehouse">
                    <option value="">Tất cả kho</option><?php foreach ($lotWarehouses as $warehouse): ?><option value="<?= e($warehouse['maKho']) ?>" <?= (($_GET['warehouse'] ?? '') === $warehouse['maKho']) ? 'selected' : '' ?>><?= e($warehouse['tenKho']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="field"><label for="expiry_from">Hạn sử dụng từ</label><input type="date" id="expiry_from" name="expiry_from" value="<?= e($_GET['expiry_from'] ?? '') ?>"></div>
            <div class="field"><label for="expiry_to">Hạn sử dụng đến</label><input type="date" id="expiry_to" name="expiry_to" value="<?= e($_GET['expiry_to'] ?? '') ?>"></div>
            <div class="toolbar-actions uc17-filter-actions lot-filter-actions"><button class="btn btn-primary" type="submit"><?= icon('search', 16) ?> Tìm lô hàng</button><a class="btn btn-ghost" href="<?= e(url(uc_url('UC17', 'form.php'))) ?>">Làm mới</a></div>
        </form>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="current_step" value="1">
            <div class="table-wrap">
                <table class="table sortable-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th data-sortable tabindex="0">Mã hàng</th>
                            <th data-sortable tabindex="0">Hàng hóa</th>
                            <th data-sortable tabindex="0">Mã lô</th>
                            <th data-sortable tabindex="0">Hạn sử dụng</th>
                            <th class="num" data-sortable tabindex="0">Tồn khả dụng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lots as $lot): ?>
                            <tr data-selectable-row>
                                <td><input type="radio" name="maLoHang" value="<?= e($lot['maLoHang']) ?>" required></td>
                                <td data-sort-value="<?= e($lot['maHangHoa']) ?>"><?= e($lot['maHangHoa']) ?></td>
                                <td data-sort-value="<?= e($lot['tenHangHoa']) ?>"><?= e($lot['tenHangHoa']) ?></td>
                                <td data-sort-value="<?= e($lot['maLoHang'] . ' / ' . $lot['soLo']) ?>"><?= e($lot['maLoHang'] . ' / ' . $lot['soLo']) ?></td>
                                <td data-sort-value="<?= e(date('Y-m-d H:i:s', strtotime($lot['hanSuDung']))) ?>"><?= e(fmt_date($lot['hanSuDung'])) ?></td>
                                <td class="num" data-sort-value="<?= e($lot['khaDung']) ?>"><?= e(fmt_qty($lot['khaDung']) . ' Kg') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$lots): ?><tr>
                                <td colspan="6" class="empty-row">Không có lô hàng còn hạn và có tồn khả dụng. Hãy kiểm tra dữ liệu hàng hóa/tồn kho trong QLK03.</td>
                            </tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-actions"><button class="btn btn-ghost" type="submit" name="action" value="cancel" formnovalidate>Hủy</button><button class="btn btn-primary" type="submit" name="action" value="next" <?= !$lots ? 'disabled' : '' ?>>Tiếp theo</button></div>
        </form>
    <?php elseif ($currentStep === 2): ?>
        <div class="card-head">
            <h2>2. Chọn vị trí nguồn</h2><span class="muted">Chỉ hiển thị vị trí có tồn chưa giữ</span>
        </div>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="current_step" value="2">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Kho</th>
                            <th>Khu vực</th>
                            <th>Vị trí</th>
                            <th class="num">Tồn hiện có</th>
                            <th class="num">Đã giữ</th>
                            <th class="num">Tồn khả dụng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sources as $row): ?><tr data-selectable-row>
                                <td><input type="radio" name="maTonKho" value="<?= e($row['maTonKho']) ?>" required></td>
                                <td><?= e($row['tenKho']) ?></td>
                                <td><?= e($row['tenKhuVuc']) ?></td>
                                <td><?= e($row['tenViTri']) ?></td>
                                <td class="num"><?= e(fmt_qty($row['soLuongTon']) . ' Kg') ?></td>
                                <td class="num"><?= e(fmt_qty($row['soLuongDaGiu']) . ' Kg') ?></td>
                                <td class="num"><?= e(fmt_qty((float)$row['soLuongTon'] - (float)$row['soLuongDaGiu']) . ' Kg') ?></td>
                            </tr><?php endforeach; ?>
                        <?php if (!$sources): ?><tr>
                                <td colspan="7" class="empty-row">Lô hàng hiện không có vị trí nguồn khả dụng.</td>
                            </tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-actions"><button class="btn btn-ghost" type="submit" name="action" value="back" formnovalidate>Quay lại</button><button class="btn btn-primary" type="submit" name="action" value="next" <?= !$sources ? 'disabled' : '' ?>>Tiếp theo</button></div>
        </form>
    <?php elseif ($currentStep === 3): ?>
        <div class="card-head">
            <h2>3. Chọn vị trí đích</h2><span class="muted">Khoảng trống = sức chứa tối đa - lượng hàng hiện tại</span>
        </div>
        <?php if ($source): ?><div class="alert alert-info">Nguồn: <b><?= e($source['tenKho'] . ' / ' . $source['tenKhuVuc'] . ' / ' . $source['tenViTri']) ?></b> · khả dụng <?= e(fmt_qty($availableSource) . ' Kg') ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="current_step" value="3">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Kho</th>
                            <th>Khu vực</th>
                            <th>Vị trí</th>
                            <th class="num">Sức chứa</th>
                            <th class="num">Đã sử dụng</th>
                            <th class="num">Còn trống</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($destinations as $row): $freeCapacity = max(0, (float)$row['sucChuaToiDa'] - (float)$row['daSuDung']); ?>
                            <tr data-selectable-row data-destination-row>
                                <td><input type="radio" data-destination name="maViTriDich" value="<?= e($row['maViTri']) ?>" required></td>
                                <td><?= e($row['tenKho']) ?></td>
                                <td><?= e($row['tenKhuVuc']) ?></td>
                                <td><?= e($row['tenViTri']) ?></td>
                                <td class="num"><?= e(fmt_qty($row['sucChuaToiDa']) . ' ' . $row['donViSucChua']) ?></td>
                                <td class="num"><?= e(fmt_qty($row['daSuDung']) . ' ' . $row['donViSucChua']) ?></td>
                                <td class="num"><?= e(fmt_qty($freeCapacity) . ' ' . $row['donViSucChua']) ?></td>
                                <td><span class="badge badge-ok">Đang hoạt động</span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$destinations): ?><tr>
                                <td colspan="8" class="empty-row">Không có vị trí đích đang hoạt động.</td>
                            </tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-actions"><button class="btn btn-ghost" type="submit" name="action" value="back" formnovalidate>Quay lại</button><button class="btn btn-primary" type="submit" name="action" value="next" <?= !$destinations ? 'disabled' : '' ?>>Tiếp theo</button></div>
        </form>
    <?php elseif ($currentStep === 4): ?>
        <div class="card-head">
            <h2>4. Nhập thông tin điều chuyển</h2><span class="muted">Tồn khả dụng nguồn: <?= e(fmt_qty($availableSource) . ' Kg') ?> (<?= e(fmt_qty($availableInSelectedUnit) . ' ' . $selectedUnit) ?>)</span>
        </div>
        <?php if ($source && $destination): ?><div class="alert alert-info"><?= e($source['tenKho'] . ' / ' . $source['tenViTri']) ?> → <?= e($destination['tenKho'] . ' / ' . $destination['tenViTri']) ?> · sức chứa đích còn <?= e(fmt_qty(max(0, (float)$destination['sucChuaToiDa'] - (float)$destination['daSuDung'])) . ' ' . $destination['donViSucChua']) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="current_step" value="4">
            <div class="field"><label for="unit">Đơn vị giao dịch <i class="req">*</i></label><select id="unit" name="donViTinh" required>
                    <?php foreach (inventory_unit_options() as $optionUnit): ?>
                        <?php if (isset($unitFactors[$optionUnit])): ?><option value="<?= e($optionUnit) ?>" data-factor="<?= e($unitFactors[$optionUnit]) ?>" <?= $selectedUnit === $optionUnit ? 'selected' : '' ?>><?= e($optionUnit) ?></option><?php endif; ?>
                    <?php endforeach; ?>
                </select><small class="hint">Tồn kho được lưu theo Kg. Hệ số được lưu cố định trong phiếu; m³ cần khối lượng riêng của mặt hàng.</small></div>
            <div class="filter-grid uc17-quantity-grid">
                <div class="field"><label for="expected">Số lượng dự kiến (<span data-unit-label><?= e($selectedUnit) ?></span>) <i class="req">*</i></label><input id="expected" name="soLuongDuKien" type="number" min="0.001" max="<?= e($availableInSelectedUnit) ?>" step="0.001" value="<?= e($_POST['soLuongDuKien'] ?? $wizard['soLuongDuKien'] ?? $availableInSelectedUnit) ?>" required></div>
                <div class="field"><label for="actual">Số thực nhận ước tính (<span data-unit-label><?= e($selectedUnit) ?></span>) <i class="req">*</i></label><input id="actual" name="soLuongThucTe" type="number" min="0.001" max="<?= e($_POST['soLuongDuKien'] ?? $wizard['soLuongDuKien'] ?? $availableInSelectedUnit) ?>" step="0.001" value="<?= e($_POST['soLuongThucTe'] ?? $wizard['soLuongThucTe'] ?? $_POST['soLuongDuKien'] ?? $wizard['soLuongDuKien'] ?? $availableInSelectedUnit) ?>" data-manual="<?= $actualIsCustom ? 'true' : 'false' ?>" required><small class="hint">Có thể thấp hơn số dự kiến; quản lý xác nhận số thực nhận khi hoàn tất phiếu.</small></div>
                <div class="field"><label for="reason">Lý do điều chuyển <i class="req">*</i></label><select id="reason" name="lyDoDieuChuyen" required>
                        <option value="">-- Chọn lý do --</option><?php foreach ($reasons as $reason): ?><option value="<?= e($reason) ?>" <?= (($_POST['lyDoDieuChuyen'] ?? $wizard['lyDoDieuChuyen'] ?? '') === $reason) ? ' selected' : '' ?>><?= e($reason) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="field"><label for="note">Ghi chú</label><textarea id="note" name="ghiChu" rows="3" maxlength="500"><?= e($_POST['ghiChu'] ?? $wizard['ghiChu'] ?? '') ?></textarea></div>
            </div>
            <div class="form-actions"><button class="btn btn-ghost" type="submit" name="action" value="back" formnovalidate>Quay lại</button><button class="btn btn-primary" type="submit" name="action" value="next">Tiếp theo · Xem xác nhận</button></div>
        </form>
    <?php else: ?>
        <div class="card-head">
            <h2>5. Gửi phiếu chờ phê duyệt</h2><span class="badge badge-warn">Kiểm tra trước khi gửi</span>
        </div>
        <div class="doc-layout">
            <div class="card">
                <h3 class="card-title">Thông tin hàng hóa và tuyến chuyển</h3>
                <p><b><?= e($selectedLot['maHangHoa'] ?? '') ?> · <?= e($selectedLot['tenHangHoa'] ?? '') ?></b></p>
                <p>Lô: <?= e(($selectedLot['maLoHang'] ?? '') . ' / ' . ($selectedLot['soLo'] ?? '')) ?></p>
                <p>Nguồn: <?= e(($source['tenKho'] ?? '') . ' / ' . ($source['tenKhuVuc'] ?? '') . ' / ' . ($source['tenViTri'] ?? '')) ?></p>
                <p>Đích: <?= e(($destination['tenKho'] ?? '') . ' / ' . ($destination['tenKhuVuc'] ?? '') . ' / ' . ($destination['tenViTri'] ?? '')) ?></p>
                <p>Lý do: <?= e($wizard['lyDoDieuChuyen'] ?? '') ?></p>
                <p>Ghi chú: <?= e($wizard['ghiChu'] ?? 'Không có') ?></p>
            </div>
            <div class="card">
                <h3 class="card-title">Số lượng và quy đổi</h3>
                <div class="field"><label>Số lượng dự kiến</label><input value="<?= e(fmt_qty($wizard['soLuongDuKien'] ?? 0) . ' ' . ($wizard['donViTinh'] ?? '')) ?>" readonly></div>
                <div class="field"><label>Số thực nhận ước tính</label><input value="<?= e(fmt_qty($wizard['soLuongThucTe'] ?? 0) . ' ' . ($wizard['donViTinh'] ?? '')) ?>" readonly></div>
                <div class="field"><label>Hệ số đã chốt</label><input value="<?= e('1 ' . ($wizard['donViTinh'] ?? '') . ' = ' . fmt_qty($wizard['heSoQuyDoiKg'] ?? 1) . ' Kg') ?>" readonly></div>
                <div class="field"><label>Dự kiến sau quy đổi</label><input value="<?= e(fmt_qty($wizard['soLuongDuKienKg'] ?? 0) . ' Kg') ?>" readonly></div>
                <div class="field"><label>Số thực nhận ước tính quy đổi</label><input value="<?= e(fmt_qty($wizard['soLuongThucTeKg'] ?? 0) . ' Kg') ?>" readonly></div>
                <p class="hint">Phiếu được tạo ở trạng thái chờ phê duyệt. Số tồn nguồn được giữ sau khi quản lý duyệt; tồn hai vị trí chỉ cập nhật khi xác nhận hoàn tất.</p>
            </div>
        </div>
        <form method="post" class="form-actions">
            <?= csrf_field() ?><input type="hidden" name="current_step" value="5">
            <button class="btn btn-ghost" type="submit" name="action" value="back" formnovalidate>Quay lại</button>
            <button class="btn btn-primary" type="submit" name="action" value="complete">Gửi phê duyệt</button>
        </form>
    <?php endif; ?>
</section>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
