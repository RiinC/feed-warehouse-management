<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/inventory_units.php';
require_module('UC17');

$db = db(DB_UC17);
$listUrl = uc_url('UC17');
$page_title = 'Điều chuyển nội bộ';
$active_menu = 'UC17';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'configure_item_units') {
    csrf_check();
    if (!is_manager()) {
        forbidden();
    }

    $itemCode = trim((string)($_POST['maHangHoa'] ?? ''));
    $defaultUnit = trim((string)($_POST['donViTinh'] ?? ''));
    $kgPerBag = filter_var($_POST['kgMoiBao'] ?? null, FILTER_VALIDATE_FLOAT);
    $bagsPerPallet = filter_var($_POST['baoMoiPallet'] ?? null, FILTER_VALIDATE_FLOAT);
    $densityInput = trim((string)($_POST['khoiLuongRieng'] ?? ''));
    $density = $densityInput === '' ? null : filter_var($densityInput, FILTER_VALIDATE_FLOAT);
    $item = db_one('SELECT maHangHoa FROM HangHoa WHERE maHangHoa = ?', 's', [$itemCode], DB_UC17);

    if (
        !$item || !in_array($defaultUnit, inventory_unit_options(), true)
        || $kgPerBag === false || $kgPerBag <= 0
        || $bagsPerPallet === false || $bagsPerPallet <= 0
        || ($densityInput !== '' && ($density === false || $density <= 0))
        || ($defaultUnit === 'm³' && ($density === null || $density === false))
    ) {
        flash('error', 'Cấu hình không hợp lệ. Hệ số bao/pallet phải lớn hơn 0; đơn vị m³ yêu cầu khối lượng riêng lớn hơn 0.');
    } else {
        db_exec(
            'UPDATE HangHoa
             SET donViTinh = ?, kgMoiBao = ?, baoMoiPallet = ?, khoiLuongRieng = ?
             WHERE maHangHoa = ?',
            'sddds',
            [$defaultUnit, (float)$kgPerBag, (float)$bagsPerPallet, $density === null ? null : (float)$density, $itemCode],
            DB_UC17
        );
        flash('success', 'Đã lưu cấu hình đơn vị. Phiếu đã phát sinh vẫn giữ nguyên hệ số quy đổi lịch sử.');
    }
    redirect($listUrl . '#unit-configuration');
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && in_array(($_POST['action'] ?? ''), ['approve_transfer', 'reject_transfer', 'complete_transfer'], true)
) {
    csrf_check();
    if (!is_manager()) {
        forbidden();
    }
    $action = (string)$_POST['action'];
    $transferCode = trim((string)($_POST['transfer_id'] ?? ''));
    try {
        $db->begin_transaction();
        $transfer = db_one(
            'SELECT * FROM PhieuDieuChuyen WHERE maPhieuDieuChuyen = ? FOR UPDATE',
            's',
            [$transferCode],
            DB_UC17
        );
        if ($action === 'approve_transfer' || $action === 'reject_transfer') {
            if (!$transfer || $transfer['trangThai'] !== 'Chờ phê duyệt') {
                throw new DomainException('Phiếu không còn ở trạng thái chờ phê duyệt.');
            }
            $rejectionReason = trim((string)($_POST['lyDoTuChoi'] ?? ''));
            if ($action === 'reject_transfer' && ($rejectionReason === '' || mb_strlen($rejectionReason) > 500)) {
                throw new DomainException('Khi từ chối, vui lòng nhập lý do không quá 500 ký tự.');
            }
            if ($action === 'approve_transfer') {
                $sourceStock = db_one(
                    'SELECT maTonKho, soLuongTon, soLuongDaGiu FROM TonKho
                     WHERE maLoHang = ? AND maViTri = ? AND trangThai = \'Đã nhập kho\'
                     FOR UPDATE',
                    'ss',
                    [$transfer['maLoHang'], $transfer['maViTriNguon']],
                    DB_UC17
                );
                $plannedKg = (float)$transfer['soLuongDuKienKg'];
                if (
                    !$sourceStock || $plannedKg <= 0
                    || (float)$sourceStock['soLuongTon'] - (float)$sourceStock['soLuongDaGiu'] < $plannedKg
                ) {
                    throw new DomainException('Tồn khả dụng nguồn không còn đủ để duyệt phiếu.');
                }
                db_exec(
                    'UPDATE TonKho SET soLuongDaGiu = soLuongDaGiu + ?, thoiDiemCapNhat = NOW() WHERE maTonKho = ?',
                    'ds',
                    [$plannedKg, $sourceStock['maTonKho']],
                    DB_UC17
                );
            }
            $newStatus = $action === 'approve_transfer' ? 'Đang điều chuyển' : 'Bị từ chối';
            db_exec(
                'UPDATE PhieuDieuChuyen
             SET trangThai = ?, maNVXuLy = ?, thoiDiemXuLy = NOW(), lyDoTuChoi = ?,
                 tonKhoDaGiu = ?
             WHERE maPhieuDieuChuyen = ? AND trangThai = ?',
                'sssiss',
                [
                    $newStatus,
                    (string)(current_user()['id'] ?? ''),
                    $action === 'reject_transfer' ? $rejectionReason : null,
                    $action === 'approve_transfer' ? 1 : 0,
                    $transferCode,
                    'Chờ phê duyệt',
                ],
                DB_UC17
            );
            $db->commit();
            flash('success', $action === 'approve_transfer' ? 'Phiếu đã được duyệt và chuyển sang trạng thái đang điều chuyển.' : 'Phiếu đã bị từ chối.');
            redirect($listUrl . ($transferCode !== '' ? '?id=' . rawurlencode($transferCode) : ''));
        }

        if (!$transfer || $transfer['trangThai'] !== 'Đang điều chuyển') {
            throw new DomainException('Phiếu không còn ở trạng thái đang điều chuyển hoặc đã được hoàn tất.');
        }

        $locations = db_all(
            "SELECT v.maViTri, v.sucChuaToiDa, v.donViSucChua, v.trangThai AS trangThaiViTri,
                    a.trangThai AS trangThaiKhuVuc, k.trangThai AS trangThaiKho
             FROM ViTriLuuTru v
             JOIN KhuVuc a ON a.maKhuVuc = v.maKhuVuc
             JOIN Kho k ON k.maKho = a.maKho
             WHERE v.maViTri IN (?, ?)
             ORDER BY v.maViTri
             FOR UPDATE",
            'ss',
            [$transfer['maViTriNguon'], $transfer['maViTriDich']],
            DB_UC17
        );
        if (count($locations) !== 2) {
            throw new DomainException('Không tìm thấy đầy đủ vị trí nguồn và vị trí đích.');
        }
        foreach ($locations as $location) {
            if (
                $location['trangThaiViTri'] !== 'Đang hoạt động'
                || $location['trangThaiKhuVuc'] !== 'Đang hoạt động'
                || $location['trangThaiKho'] !== 'Đang hoạt động'
            ) {
                throw new DomainException('Kho, khu vực hoặc vị trí đã ngừng hoạt động; không thể hoàn tất phiếu.');
            }
        }

        $lot = db_one(
            "SELECT h.kgMoiBao, h.baoMoiPallet, h.khoiLuongRieng FROM LoHang l
             JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
             WHERE l.maLoHang = ?",
            's',
            [$transfer['maLoHang']],
            DB_UC17
        );
        $sourceStock = db_one(
            "SELECT maTonKho, soLuongTon, soLuongDaGiu FROM TonKho
             WHERE maLoHang = ? AND maViTri = ? AND trangThai = 'Đã nhập kho'
             FOR UPDATE",
            'ss',
            [$transfer['maLoHang'], $transfer['maViTriNguon']],
            DB_UC17
        );
        $plannedKg = (float)$transfer['soLuongDuKienKg'];
        $actualQuantity = filter_var($_POST['soLuongThucTe'] ?? null, FILTER_VALIDATE_FLOAT);
        $conversionFactor = (float)$transfer['heSoQuyDoiKg'];
        $actualKg = $actualQuantity !== false && $actualQuantity > 0
            ? round((float)$actualQuantity * $conversionFactor, 3)
            : 0.0;
        $hasReservation = (int)$transfer['tonKhoDaGiu'] === 1;
        if (
            !$lot || !$sourceStock
            || $actualQuantity === false || $actualQuantity <= 0
            || (float)$actualQuantity > (float)$transfer['soLuongDuKien']
            || $actualKg <= 0 || $actualKg > $plannedKg
            || ($hasReservation && (float)$sourceStock['soLuongDaGiu'] < $plannedKg)
            || (!$hasReservation && (float)$sourceStock['soLuongTon'] - (float)$sourceStock['soLuongDaGiu'] < $plannedKg)
            || (float)$sourceStock['soLuongTon'] < $plannedKg
        ) {
            throw new DomainException('Số thực nhận phải lớn hơn 0, không vượt số dự kiến và tồn nguồn phải còn đủ.');
        }

        $destinationMeta = null;
        foreach ($locations as $location) {
            if ($location['maViTri'] === $transfer['maViTriDich']) {
                $destinationMeta = $location;
                break;
            }
        }
        if (!$destinationMeta) {
            throw new DomainException('Vị trí đích không còn hoạt động.');
        }
        $occupied = inventory_location_capacity_usage(
            $transfer['maViTriDich'],
            $destinationMeta['donViSucChua'],
            DB_UC17
        );
        if ($occupied === null) {
            throw new DomainException('Không thể tính sức chứa vị trí m³ vì một mặt hàng đang lưu chưa có khối lượng riêng.');
        }
        $incomingCapacity = inventory_kg_to_capacity($actualKg, $destinationMeta['donViSucChua'], $lot);
        if (
            $occupied + $incomingCapacity > (float)$destinationMeta['sucChuaToiDa']
        ) {
            throw new DomainException('Vị trí đích không còn đủ sức chứa theo đơn vị quy đổi.');
        }

        db_exec(
            'UPDATE TonKho
             SET soLuongTon = soLuongTon - ?, soLuongDaGiu = soLuongDaGiu - ?, thoiDiemCapNhat = NOW()
             WHERE maTonKho = ?',
            'dds',
            [$plannedKg, $hasReservation ? $plannedKg : 0.0, $sourceStock['maTonKho']],
            DB_UC17
        );
        $destinationStock = db_one(
            'SELECT maTonKho FROM TonKho WHERE maLoHang = ? AND maViTri = ? FOR UPDATE',
            'ss',
            [$transfer['maLoHang'], $transfer['maViTriDich']],
            DB_UC17
        );
        if ($destinationStock) {
            db_exec(
                "UPDATE TonKho
                 SET soLuongTon = soLuongTon + ?, thoiDiemCapNhat = NOW(), trangThai = 'Đã nhập kho', maNV = ?
                 WHERE maTonKho = ?",
                'dss',
                [$actualKg, (string)(current_user()['id'] ?? ''), $destinationStock['maTonKho']],
                DB_UC17
            );
        } else {
            $newStockCode = 'TK' . strtoupper(bin2hex(random_bytes(8)));
            db_exec(
                "INSERT INTO TonKho (maTonKho, soLuongTon, soLuongDaGiu, thoiDiemCapNhat, trangThai, maLoHang, maViTri, maNV)
                 VALUES (?, ?, 0, NOW(), 'Đã nhập kho', ?, ?, ?)",
                'sdsss',
                [$newStockCode, $actualKg, $transfer['maLoHang'], $transfer['maViTriDich'], (string)(current_user()['id'] ?? '')],
                DB_UC17
            );
        }
        db_exec(
            "UPDATE PhieuDieuChuyen
             SET soLuongThucTe = ?, soLuongThucTeKg = ?,
                 trangThai = 'Hoàn thành', thoiDiemHoanTat = NOW(),
                 maNVHoanTat = ?
             WHERE maPhieuDieuChuyen = ? AND trangThai = 'Đang điều chuyển'",
            'ddss',
            [(float)$actualQuantity, $actualKg, (string)(current_user()['id'] ?? ''), $transferCode],
            DB_UC17
        );
        $db->commit();
        flash('success', 'Phiếu điều chuyển đã hoàn tất.');
    } catch (Throwable $ex) {
        $db->rollback();
        error_log('UC17 transfer completion failed: ' . $ex->getMessage());
        flash('error', $ex instanceof DomainException ? $ex->getMessage() : 'Không thể hoàn tất điều chuyển do lỗi cơ sở dữ liệu. Vui lòng thử lại.');
    }
    redirect($listUrl . ($transferCode !== '' ? '?id=' . rawurlencode($transferCode) : ''));
}

$selectedId = trim((string)($_GET['id'] ?? ''));
$params = [];
$types = '';
$where = [];

if ($selectedId !== '') {
    $detail = db_one(
        "SELECT p.*, h.maHangHoa, h.tenHangHoa, l.soLo, l.hanSuDung,
                src.tenViTri AS tenViTriNguon, srcArea.tenKhuVuc AS khuVucNguon,
                srcKho.maKho AS maKhoNguon, srcKho.tenKho AS tenKhoNguon,
                dst.tenViTri AS tenViTriDich, dstArea.tenKhuVuc AS khuVucDich,
                dstKho.maKho AS maKhoDich, dstKho.tenKho AS tenKhoDich,
                n.hoTen AS nguoiLap, nx.hoTen AS nguoiXuLy, nd.hoTen AS nguoiHoanTat
         FROM PhieuDieuChuyen p
         JOIN LoHang l ON l.maLoHang = p.maLoHang
         JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
         JOIN ViTriLuuTru src ON src.maViTri = p.maViTriNguon
         JOIN KhuVuc srcArea ON srcArea.maKhuVuc = src.maKhuVuc
         JOIN Kho srcKho ON srcKho.maKho = srcArea.maKho
         JOIN ViTriLuuTru dst ON dst.maViTri = p.maViTriDich
         JOIN KhuVuc dstArea ON dstArea.maKhuVuc = dst.maKhuVuc
         JOIN Kho dstKho ON dstKho.maKho = dstArea.maKho
         LEFT JOIN NhanVien n ON n.maNV = p.maNV
         LEFT JOIN NhanVien nx ON nx.maNV = p.maNVXuLy
         LEFT JOIN NhanVien nd ON nd.maNV = p.maNVHoanTat
         WHERE p.maPhieuDieuChuyen = ?",
        's',
        [$selectedId],
        DB_UC17
    );
    if (!$detail) {
        http_response_code(404);
        flash('error', 'Không tìm thấy phiếu điều chuyển.');
        redirect($listUrl);
    }

    $page_title = 'Chi tiết phiếu điều chuyển';
    $breadcrumb = [['Tổng quan', 'index.php'], ['Điều chuyển nội bộ', $listUrl], [$page_title, null]];
    include ROOT_PATH . '/includes/head.php';
    include ROOT_PATH . '/includes/sidebar.php';
    include ROOT_PATH . '/includes/header.php';
    include ROOT_PATH . '/includes/toolbar.php';
?>
    <div class="card uc17-detail">
        <div class="card-head">
            <h2>Phiếu <?= e($detail['maPhieuDieuChuyen']) ?></h2>
            <span class="badge <?= $detail['trangThai'] === 'Hoàn thành' ? 'badge-ok' : ($detail['trangThai'] === 'Đang điều chuyển' ? 'badge-info' : 'badge-warn') ?>"><?= e($detail['trangThai']) ?></span>
        </div>
        <div class="filter-grid">
            <div class="field"><label>Mã phiếu</label><input value="<?= e($detail['maPhieuDieuChuyen']) ?>" readonly></div>
            <div class="field"><label>Thời điểm lập</label><input value="<?= e(date('d/m/Y H:i', strtotime($detail['thoiDiemLap']))) ?>" readonly></div>
            <div class="field"><label>Người lập</label><input value="<?= e($detail['nguoiLap'] ?? $detail['maNV']) ?>" readonly></div>
            <div class="field"><label>Trạng thái</label><input value="<?= e($detail['trangThai']) ?>" readonly></div>
        </div>
    </div>

    <div class="doc-layout uc17-detail">
        <section class="card">
            <h2 class="card-title">Thông tin điều chuyển</h2>
            <div class="field"><label>Mã hàng hóa</label><input value="<?= e($detail['maHangHoa']) ?>" readonly></div>
            <div class="field"><label>Tên hàng hóa</label><input value="<?= e($detail['tenHangHoa']) ?>" readonly></div>
            <div class="field"><label>Lô hàng</label><input value="<?= e($detail['maLoHang'] . ' / ' . $detail['soLo']) ?>" readonly></div>
            <div class="field"><label>Hạn sử dụng</label><input value="<?= e(fmt_date($detail['hanSuDung'])) ?>" readonly></div>
            <div class="field"><label>Lý do</label><input value="<?= e($detail['lyDoDieuChuyen']) ?>" readonly></div>
            <div class="field"><label>Ghi chú</label><textarea rows="3" readonly><?= e($detail['ghiChu'] ?? '') ?></textarea></div>
        </section>
        <section class="card">
            <h2 class="card-title">Vị trí và số lượng</h2>
            <div class="field"><label>Vị trí nguồn</label><input value="<?= e($detail['tenKhoNguon'] . ' / ' . $detail['khuVucNguon'] . ' / ' . $detail['tenViTriNguon']) ?>" readonly></div>
            <div class="field"><label>Vị trí đích</label><input value="<?= e($detail['tenKhoDich'] . ' / ' . $detail['khuVucDich'] . ' / ' . $detail['tenViTriDich']) ?>" readonly></div>
            <div class="field"><label>Số lượng dự kiến</label><input value="<?= e(fmt_qty($detail['soLuongDuKien']) . ' ' . $detail['donViTinh']) ?>" readonly></div>
            <div class="field"><label>Số lượng thực nhận<?= $detail['trangThai'] === 'Hoàn thành' ? '' : ' (ước tính)' ?></label><input value="<?= e(fmt_qty($detail['soLuongThucTe']) . ' ' . $detail['donViTinh']) ?>" readonly></div>
            <div class="field"><label>Quy đổi tồn kho chuẩn</label><input value="<?= e('1 ' . $detail['donViTinh'] . ' = ' . fmt_qty($detail['heSoQuyDoiKg']) . ' Kg') ?>" readonly></div>
            <div class="field"><label>Số lượng dự kiến quy đổi</label><input value="<?= e(fmt_qty($detail['soLuongDuKienKg']) . ' Kg') ?>" readonly></div>
            <div class="field"><label>Số thực nhận quy đổi<?= $detail['trangThai'] === 'Hoàn thành' ? '' : ' (ước tính)' ?></label><input value="<?= e(fmt_qty($detail['soLuongThucTeKg']) . ' Kg') ?>" readonly></div>
            <div class="field"><label>Chênh lệch <?= $detail['trangThai'] === 'Hoàn thành' ? 'thực nhận' : 'ước tính' ?> - dự kiến</label><input value="<?= e(fmt_qty((float)$detail['soLuongThucTeKg'] - (float)$detail['soLuongDuKienKg']) . ' Kg') ?>" readonly></div>
            <div class="field"><label>Người duyệt/từ chối</label><input value="<?= e($detail['nguoiXuLy'] ?? 'Chưa xử lý') ?>" readonly></div>
            <div class="field"><label>Thời điểm duyệt/từ chối</label><input value="<?= e($detail['thoiDiemXuLy'] ? date('d/m/Y H:i', strtotime($detail['thoiDiemXuLy'])) : 'Chưa xử lý') ?>" readonly></div>
            <div class="field"><label>Người xác nhận hoàn tất</label><input value="<?= e($detail['nguoiHoanTat'] ?? 'Chưa hoàn tất') ?>" readonly></div>
            <?php if ($detail['lyDoTuChoi']): ?><div class="field"><label>Lý do từ chối</label><textarea rows="3" readonly><?= e($detail['lyDoTuChoi']) ?></textarea></div><?php endif; ?>
            <div class="field"><label>Thời điểm hoàn tất</label><input value="<?= e($detail['thoiDiemHoanTat'] ? date('d/m/Y H:i', strtotime($detail['thoiDiemHoanTat'])) : 'Chưa hoàn tất') ?>" readonly></div>
        </section>
    </div>
    <div class="toolbar-actions uc17-detail-actions">
        <a class="btn btn-ghost" href="<?= e(url($listUrl)) ?>">Quay lại danh sách</a>
        <a class="btn btn-primary" href="<?= e(url(uc_url('UC17', 'form.php'))) ?>">Tạo phiếu mới</a>
        <?php if ($detail['trangThai'] === 'Chờ phê duyệt' && is_manager()): ?>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="approve_transfer">
                <input type="hidden" name="transfer_id" value="<?= e($detail['maPhieuDieuChuyen']) ?>">
                <button class="btn btn-primary" type="submit">Duyệt phiếu</button>
            </form>
            <form method="post" class="uc17-reject-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reject_transfer">
                <input type="hidden" name="transfer_id" value="<?= e($detail['maPhieuDieuChuyen']) ?>">
                <label class="sr-only" for="rejection-reason">Lý do từ chối</label>
                <input id="rejection-reason" name="lyDoTuChoi" maxlength="500" required aria-label="Lý do từ chối" placeholder="Lý do từ chối">
                <button class="btn btn-ghost" type="submit">Từ chối</button>
            </form>
        <?php endif; ?>
        <?php if ($detail['trangThai'] === 'Đang điều chuyển' && is_manager()): ?>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="complete_transfer">
                <input type="hidden" name="transfer_id" value="<?= e($detail['maPhieuDieuChuyen']) ?>">
                <label for="received-quantity">Thực nhận (<?= e($detail['donViTinh']) ?>)</label>
                <input id="received-quantity" name="soLuongThucTe" type="number" min="0.001" max="<?= e($detail['soLuongDuKien']) ?>" step="0.001" value="<?= e($detail['soLuongThucTe']) ?>" required>
                <button class="btn btn-primary" type="submit">Xác nhận hoàn tất</button>
            </form>
        <?php endif; ?>
    </div>
<?php
    include ROOT_PATH . '/includes/footer.php';
    include ROOT_PATH . '/includes/scripts.php';
    exit;
}

$filters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'source' => trim((string)($_GET['source'] ?? '')),
    'destination' => trim((string)($_GET['destination'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'from' => trim((string)($_GET['from'] ?? '')),
    'to' => trim((string)($_GET['to'] ?? '')),
];

if ($filters['q'] !== '') {
    $where[] = '(p.maPhieuDieuChuyen LIKE ? OR p.maLoHang LIKE ? OR l.soLo LIKE ? OR h.maHangHoa LIKE ? OR h.tenHangHoa LIKE ?)';
    $types .= 'sssss';
    $like = '%' . $filters['q'] . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
foreach (['source' => 'srcKho.maKho', 'destination' => 'dstKho.maKho'] as $key => $column) {
    if ($filters[$key] !== '') {
        $where[] = $column . ' = ?';
        $types .= 's';
        $params[] = $filters[$key];
    }
}
$validStatuses = ['Chờ phê duyệt', 'Đã phê duyệt', 'Bị từ chối', 'Đang điều chuyển', 'Hoàn thành', 'Đã hủy'];
if (in_array($filters['status'], $validStatuses, true)) {
    $where[] = 'p.trangThai = ?';
    $types .= 's';
    $params[] = $filters['status'];
}
foreach (['from' => 'p.thoiDiemLap >= ?', 'to' => 'p.thoiDiemLap < DATE_ADD(?, INTERVAL 1 DAY)'] as $key => $condition) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters[$key])) {
        $where[] = $condition;
        $types .= 's';
        $params[] = $filters[$key];
    }
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stats = db_one(
    "SELECT COUNT(*) AS total,
            SUM(trangThai = 'Chờ phê duyệt') AS waiting,
            SUM(trangThai = 'Đang điều chuyển') AS moving,
            SUM(trangThai = 'Hoàn thành') AS completed
     FROM PhieuDieuChuyen",
    '',
    [],
    DB_UC17
) ?? ['total' => 0, 'waiting' => 0, 'moving' => 0, 'completed' => 0];
$warehouses = db_all("SELECT maKho, tenKho FROM Kho WHERE trangThai = 'Đang hoạt động' ORDER BY tenKho", '', [], DB_UC17);
$unitItems = db_all(
    "SELECT maHangHoa, tenHangHoa, donViTinh, kgMoiBao, baoMoiPallet, khoiLuongRieng
     FROM HangHoa ORDER BY tenHangHoa",
    '',
    [],
    DB_UC17
);

$pageNumber = max(1, (int)($_GET['p'] ?? 1));
$pageSize = 10;
$countRow = db_one(
    "SELECT COUNT(*) AS total
     FROM PhieuDieuChuyen p
     JOIN LoHang l ON l.maLoHang = p.maLoHang
     JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
     JOIN ViTriLuuTru src ON src.maViTri = p.maViTriNguon
     JOIN KhuVuc srcArea ON srcArea.maKhuVuc = src.maKhuVuc
     JOIN Kho srcKho ON srcKho.maKho = srcArea.maKho
     JOIN ViTriLuuTru dst ON dst.maViTri = p.maViTriDich
     JOIN KhuVuc dstArea ON dstArea.maKhuVuc = dst.maKhuVuc
     JOIN Kho dstKho ON dstKho.maKho = dstArea.maKho
     $whereSql",
    $types,
    $params,
    DB_UC17
) ?? ['total' => 0];
$totalRows = (int)$countRow['total'];
$totalPages = max(1, (int)ceil($totalRows / $pageSize));
$pageNumber = min($pageNumber, $totalPages);
$offset = ($pageNumber - 1) * $pageSize;
$rows = db_all(
    "SELECT p.maPhieuDieuChuyen, p.maLoHang, p.soLuongDuKien, p.soLuongThucTe,
            p.donViTinh, p.thoiDiemLap, p.trangThai, h.tenHangHoa,
            srcKho.tenKho AS tenKhoNguon, src.tenViTri AS tenViTriNguon,
            dstKho.tenKho AS tenKhoDich, dst.tenViTri AS tenViTriDich
     FROM PhieuDieuChuyen p
     JOIN LoHang l ON l.maLoHang = p.maLoHang
     JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
     JOIN ViTriLuuTru src ON src.maViTri = p.maViTriNguon
     JOIN KhuVuc srcArea ON srcArea.maKhuVuc = src.maKhuVuc
     JOIN Kho srcKho ON srcKho.maKho = srcArea.maKho
     JOIN ViTriLuuTru dst ON dst.maViTri = p.maViTriDich
     JOIN KhuVuc dstArea ON dstArea.maKhuVuc = dst.maKhuVuc
     JOIN Kho dstKho ON dstKho.maKho = dstArea.maKho
     $whereSql
     ORDER BY p.thoiDiemLap DESC, p.maPhieuDieuChuyen DESC
     LIMIT $pageSize OFFSET $offset",
    $types,
    $params,
    DB_UC17
);

$breadcrumb = [['Tổng quan', 'index.php'], ['Điều chuyển nội bộ', null]];
$toolbar_actions = '<a class="btn btn-primary" href="' . e(url(uc_url('UC17', 'form.php'))) . '">' . icon('plus', 16) . ' Tạo phiếu</a>';
include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<div class="stat-grid">
    <div class="stat-card"><span class="stat-icon c-blue"><?= icon('repeat', 22) ?></span><span class="stat-body"><small>Tổng phiếu điều chuyển</small><b><?= (int)$stats['total'] ?></b></span></div>
    <div class="stat-card"><span class="stat-icon c-orange"><?= icon('bell', 22) ?></span><span class="stat-body"><small>Chờ phê duyệt</small><b><?= (int)$stats['waiting'] ?></b></span></div>
    <div class="stat-card"><span class="stat-icon c-purple"><?= icon('upload', 22) ?></span><span class="stat-body"><small>Đang điều chuyển</small><b><?= (int)$stats['moving'] ?></b></span></div>
    <div class="stat-card"><span class="stat-icon c-green"><?= icon('list', 22) ?></span><span class="stat-body"><small>Hoàn thành</small><b><?= (int)$stats['completed'] ?></b></span></div>
</div>

<?php if (is_manager()): ?>
<details class="card uc17-unit-config" id="unit-configuration">
    <summary>Cấu hình đơn vị và hệ số quy đổi theo mặt hàng</summary>
    <p class="hint">Tồn kho luôn được lưu bằng Kg. 1 Tấn = 1.000 Kg; Bao và Pallet dùng hệ số từng mặt hàng; m³ chỉ dùng khi có khối lượng riêng (Kg/m³). Phiếu cũ giữ nguyên hệ số đã áp dụng.</p>
    <div class="uc17-unit-config-list">
        <?php foreach ($unitItems as $unitItem): ?>
            <form method="post" class="uc17-unit-config-row">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="configure_item_units">
                <input type="hidden" name="maHangHoa" value="<?= e($unitItem['maHangHoa']) ?>">
                <b><?= e($unitItem['maHangHoa'] . ' · ' . $unitItem['tenHangHoa']) ?></b>
                <div class="filter-grid">
                    <div class="field"><label for="unit-<?= e($unitItem['maHangHoa']) ?>">Đơn vị mặc định</label><select id="unit-<?= e($unitItem['maHangHoa']) ?>" name="donViTinh"><?php foreach (inventory_unit_options() as $optionUnit): ?><option value="<?= e($optionUnit) ?>" <?= $unitItem['donViTinh'] === $optionUnit ? 'selected' : '' ?>><?= e($optionUnit) ?></option><?php endforeach; ?></select></div>
                    <div class="field"><label>Kg / Bao</label><input type="number" name="kgMoiBao" min="0.001" step="0.001" value="<?= e($unitItem['kgMoiBao']) ?>" required></div>
                    <div class="field"><label>Bao / Pallet</label><input type="number" name="baoMoiPallet" min="0.001" step="0.001" value="<?= e($unitItem['baoMoiPallet']) ?>" required></div>
                    <div class="field"><label>Khối lượng riêng (Kg/m³)</label><input type="number" name="khoiLuongRieng" min="0.001" step="0.001" value="<?= e($unitItem['khoiLuongRieng'] ?? '') ?>" placeholder="Bắt buộc nếu dùng m³"></div>
                    <div class="toolbar-actions"><button class="btn btn-ghost" type="submit">Lưu hệ số</button></div>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</details>
<?php endif; ?>

<?php if (!empty($_SESSION['uc17_transfer']) && is_array($_SESSION['uc17_transfer'])): ?>
    <div class="alert alert-info uc17-draft">
        <span>Bạn đang có phiếu điều chuyển tạo dở dang.</span>
        <div class="toolbar-actions">
            <a class="btn btn-primary" href="<?= e(url(uc_url('UC17', 'form.php'))) ?>">Tiếp tục phiếu dở dang</a>
            <a class="btn btn-ghost" href="<?= e(url(uc_url('UC17', 'form.php') . '?cancel=1')) ?>">Hủy phiếu dở dang</a>
        </div>
    </div>
<?php endif; ?>

<form class="card filter-card" method="get">
    <div class="filter-grid">
        <div class="field"><label for="q">Tìm mã phiếu, lô hàng, hàng hóa</label><input id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Nhập từ khóa"></div>
        <div class="field"><label for="source">Kho nguồn</label><select id="source" name="source">
                <option value="">Tất cả</option><?php foreach ($warehouses as $warehouse): ?><option value="<?= e($warehouse['maKho']) ?>" <?= $filters['source'] === $warehouse['maKho'] ? ' selected' : '' ?>><?= e($warehouse['tenKho']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="field"><label for="destination">Kho đích</label><select id="destination" name="destination">
                <option value="">Tất cả</option><?php foreach ($warehouses as $warehouse): ?><option value="<?= e($warehouse['maKho']) ?>" <?= $filters['destination'] === $warehouse['maKho'] ? ' selected' : '' ?>><?= e($warehouse['tenKho']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="field"><label for="status">Trạng thái</label><select id="status" name="status">
                <option value="">Tất cả</option><?php foreach ($validStatuses as $status): ?><option value="<?= e($status) ?>" <?= $filters['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
            </select></div>
        <div class="field"><label for="from">Từ ngày</label><input id="from" type="date" name="from" value="<?= e($filters['from']) ?>"></div>
        <div class="field"><label for="to">Đến ngày</label><input id="to" type="date" name="to" value="<?= e($filters['to']) ?>"></div>
        <div class="toolbar-actions uc17-filter-actions"><button class="btn btn-primary" type="submit"><?= icon('search', 16) ?> Tìm kiếm</button><a class="btn btn-ghost" href="<?= e(url($listUrl)) ?>">Làm mới</a></div>
    </div>
</form>

<div class="card">
    <div class="card-head">
        <h2>Danh sách phiếu điều chuyển</h2><span class="muted"><?= $totalRows ?> phiếu</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã phiếu</th>
                    <th>Lô hàng</th>
                    <th>Hàng hóa</th>
                    <th>Vị trí nguồn</th>
                    <th>Vị trí đích</th>
                    <th class="num">Dự kiến</th>
                    <th class="num">Thực nhận</th>
                    <th>Ngày lập</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><a class="link" href="<?= e(url($listUrl . '?id=' . rawurlencode($row['maPhieuDieuChuyen']))) ?>"><?= e($row['maPhieuDieuChuyen']) ?></a></td>
                        <td><?= e($row['maLoHang']) ?></td>
                        <td><?= e($row['tenHangHoa']) ?></td>
                        <td><?= e($row['tenKhoNguon'] . ' / ' . $row['tenViTriNguon']) ?></td>
                        <td><?= e($row['tenKhoDich'] . ' / ' . $row['tenViTriDich']) ?></td>
                        <td class="num"><?= e(fmt_qty($row['soLuongDuKien']) . ' ' . $row['donViTinh']) ?></td>
                        <td class="num"><?= e(fmt_qty($row['soLuongThucTe']) . ' ' . $row['donViTinh']) ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($row['thoiDiemLap']))) ?></td>
                        <td><span class="badge <?= $row['trangThai'] === 'Hoàn thành' ? 'badge-ok' : ($row['trangThai'] === 'Đang điều chuyển' ? 'badge-info' : 'badge-warn') ?>"><?= e($row['trangThai']) ?></span></td>
                        <td><a class="act" title="Xem chi tiết" aria-label="Xem chi tiết" href="<?= e(url($listUrl . '?id=' . rawurlencode($row['maPhieuDieuChuyen']))) ?>"><?= icon('eye', 16) ?></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr>
                        <td class="empty-row" colspan="10">Không tìm thấy phiếu điều chuyển phù hợp.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="pager"><span>Trang <?= $pageNumber ?> / <?= $totalPages ?></span>
            <div class="pager-links">
                <?php for ($p = 1; $p <= $totalPages; $p++): $query = http_build_query(array_merge($filters, ['p' => $p])); ?>
                    <a class="<?= $p === $pageNumber ? 'on' : '' ?>" href="<?= e(url($listUrl . '?' . $query)) ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
