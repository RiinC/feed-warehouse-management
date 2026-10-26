<?php
/** UC10 - Tạo phiếu nhập kho (QLK02), nhân viên thuộc QLK00. */
require_once __DIR__ . '/../../includes/auth.php';
require_module('UC10');
$page_title = 'Tạo phiếu nhập kho';
$active_menu = 'UC10';
$breadcrumb = [['Tổng quan', 'index.php'], ['Nhập kho', 'index.php'], ['Tạo phiếu', null]];
$conn = db(DB_UC10);
$errors = [];
$form = [
    'maLoHang' => trim((string)($_POST['maLoHang'] ?? $_GET['maLoHang'] ?? '')),
    'maKho' => trim((string)($_POST['maKho'] ?? '')),
    'soLuongThucNhap' => trim((string)($_POST['soLuongThucNhap'] ?? '')),
    'chungTuLienQuan' => trim((string)($_POST['chungTuLienQuan'] ?? '')),
    'ghiChu' => trim((string)($_POST['ghiChu'] ?? '')),
];
function uc10_rows(mysqli $conn, string $sql, string $types = '', array $values = []): array {
    $stmt = $conn->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}
function uc10_one(mysqli $conn, string $sql, string $types = '', array $values = []): ?array {
    return uc10_rows($conn, $sql, $types, $values)[0] ?? null;
}
function uc10_amount(string $input): ?float {
    $value = str_replace(',', '.', trim($input));
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $value)) return null;
    $number = (float)$value;
    return is_finite($number) && $number > 0 ? $number : null;
}
function uc10_session_values(): array {
    $items = [$_SESSION];
    foreach (['user', 'nguoi_dung', 'nhanvien', 'nhan_vien', 'account', 'taiKhoan', 'taikhoan', 'auth'] as $key) {
        if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) $items[] = $_SESSION[$key];
    }
    $result = [];
    foreach ($items as $item) {
        foreach ($item as $key => $value) {
            if (is_scalar($value) && trim((string)$value) !== '') $result[$key][] = trim((string)$value);
        }
    }
    return $result;
}
/** Only trust an employee ID or a uniquely matched full name in the authenticated session. */
function uc10_employee(mysqli $conn): ?array {
    $session = uc10_session_values();
    foreach (['maNV', 'maNhanVien', 'ma_nv', 'employee_code', 'employee_id'] as $key) {
        foreach ($session[$key] ?? [] as $value) {
            $person = uc10_one($conn, 'SELECT maNV, hoTen, trangThai FROM QLK00.nhanvien WHERE CONVERT(maNV USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci LIMIT 1', 's', [$value]);
            if ($person) return $person;
        }
    }
    // Some login systems store the employee code as the username.
    foreach (['username', 'tenDangNhap', 'ten_dang_nhap'] as $key) {
        foreach ($session[$key] ?? [] as $value) {
            $person = uc10_one($conn, 'SELECT maNV, hoTen, trangThai FROM QLK00.nhanvien WHERE CONVERT(maNV USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci LIMIT 1', 's', [$value]);
            if ($person) return $person;
        }
    }
    // Match session display name only if exactly one employee has this name.
    foreach (['hoTen', 'ho_ten', 'full_name', 'fullName', 'name', 'tenNhanVien'] as $key) {
        foreach ($session[$key] ?? [] as $value) {
            $people = uc10_rows($conn, 'SELECT maNV, hoTen, trangThai FROM QLK00.nhanvien WHERE CONVERT(hoTen USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci LIMIT 2', 's', [$value]);
            if (count($people) === 1) return $people[0];
        }
    }
    // Resolve via QLK00.taikhoan only when its column names establish an explicit link.
    try { $columns = uc10_rows($conn, 'SHOW COLUMNS FROM QLK00.taikhoan'); } catch (Throwable $ignored) { return null; }
    $colNames = array_column($columns, 'Field');
    $linkCol = null;
    foreach (['maNV', 'maNhanVien', 'ma_nv'] as $candidate) if (in_array($candidate, $colNames, true)) { $linkCol = $candidate; break; }
    if ($linkCol !== null) {
        foreach (['tenDangNhap', 'ten_dang_nhap', 'username', 'taiKhoan', 'email'] as $candidate) {
            if (!in_array($candidate, $colNames, true)) continue;
            $values = [];
            foreach ([$candidate, 'username', 'tenDangNhap', 'ten_dang_nhap', 'email'] as $key) {
                foreach ($session[$key] ?? [] as $value) $values[$value] = true;
            }
            foreach (array_keys($values) as $value) {
                $sql = 'SELECT nv.maNV, nv.hoTen, nv.trangThai FROM QLK00.taikhoan tk JOIN QLK00.nhanvien nv ON CONVERT(nv.maNV USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(tk.`' . $linkCol . '` USING utf8mb4) COLLATE utf8mb4_unicode_ci WHERE CONVERT(tk.`' . $candidate . '` USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci LIMIT 1';
                $person = uc10_one($conn, $sql, 's', [$value]);
                if ($person) return $person;
            }
        }
    }
    return null;
}
function uc10_next_code(mysqli $conn): string {
    $year = date('Y');
    $pattern = '^PN' . $year . '-[0-9]{4,}$';
    $row = uc10_one($conn, 'SELECT COALESCE(MAX(CAST(SUBSTRING(maPhieuNhapKho, 8) AS UNSIGNED)), 0) AS maxNumber FROM PhieuNhapKho WHERE maPhieuNhapKho REGEXP ?', 's', [$pattern]);
    return 'PN' . $year . '-' . str_pad((string)((int)($row['maxNumber'] ?? 0) + 1), 4, '0', STR_PAD_LEFT);
}
$employee = null;
$lots = [];
$warehouses = [];
$previewCode = '';
try {
    try { $employee = uc10_employee($conn); } catch (Throwable $employeeEx) { error_log('UC10 employee lookup: ' . $employeeEx->getMessage()); $errors[] = 'Không đọc được thông tin nhân viên: ' . $employeeEx->getMessage(); }
    $previewCode = uc10_next_code($conn);
    $warehouses = uc10_rows($conn, "SELECT maKho, tenKho FROM Kho WHERE trangThai = 'Đang hoạt động' ORDER BY tenKho");
    $lots = uc10_rows($conn, "SELECT lh.maLoHang, lh.soLo, lh.maHangHoa, hh.tenHangHoa, hh.donViTinh, lh.soLuongTiepNhan, lh.soLuongTon, lh.ngaySanXuat, lh.hanSuDung, lh.tinhTrang, lh.trangThai
        FROM LoHang lh JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa
        WHERE lh.trangThai = 'Đang sử dụng'
          AND EXISTS (SELECT 1 FROM BienBanKiemDinhChatLuong bb WHERE bb.maLoHang = lh.maLoHang AND bb.ketQua = 'Đạt' AND bb.trangThai = 'Đã xác nhận')
          AND NOT EXISTS (SELECT 1 FROM PhieuNhapKho pn WHERE pn.maLoHang = lh.maLoHang)
        ORDER BY lh.maLoHang DESC");
} catch (Throwable $ex) {
    error_log('UC10 form load: ' . $ex->getMessage());
    $errors[] = 'Không tải được dữ liệu. Hãy kiểm tra cột LoHang.soLuongTiepNhan và các bảng QLK00, QLK02. Chi tiết: ' . $ex->getMessage();
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $qty = uc10_amount($form['soLuongThucNhap']);
    // Kiểm tra số lượng tiếp nhận từ QLK02 trước khi lưu.
    if ($qty !== null && $form['maLoHang'] !== '') {
        try {
            $selectedLot = uc10_one(
                $conn,
                'SELECT soLuongTiepNhan FROM LoHang WHERE maLoHang = ?',
                's',
                [$form['maLoHang']]
            );
            if ($selectedLot === null) {
                $errors[] = 'Không tìm thấy lô hàng đã chọn.';
            } elseif (!array_key_exists('soLuongTiepNhan', $selectedLot)) {
                $errors[] = 'Không đọc được số lượng tiếp nhận của lô hàng.';
            } elseif ($qty > (float)$selectedLot['soLuongTiepNhan']) {
                $errors[] = 'Số lượng thực nhập không được lớn hơn số lượng tiếp nhận ('
                    . number_format((float)$selectedLot['soLuongTiepNhan'], 2, ',', '.') . ').';
            }
        } catch (Throwable $ex) {
            error_log('UC10 received quantity lookup: ' . $ex->getMessage());
            $errors[] = 'Không kiểm tra được số lượng tiếp nhận. Vui lòng kiểm tra bảng LoHang.';
        }
    }
    if ($form['maLoHang'] === '') $errors[] = 'Vui lòng chọn lô hàng.';
    if ($form['maKho'] === '') $errors[] = 'Vui lòng chọn kho nhập.';
    if ($qty === null) $errors[] = 'Số lượng thực nhập phải lớn hơn 0, tối đa 2 chữ số thập phân.';
    if ($form['chungTuLienQuan'] === '') $errors[] = 'Vui lòng nhập chứng từ liên quan.';
    if (mb_strlen($form['chungTuLienQuan']) > 200) $errors[] = 'Chứng từ tối đa 200 ký tự.';
    if (mb_strlen($form['ghiChu']) > 250) $errors[] = 'Ghi chú tối đa 250 ký tự.';
    if (!$employee || $employee['trangThai'] !== 'Đang hoạt động') $errors[] = 'Chưa xác định được nhân viên đăng nhập từ session. Cần kiểm tra includes/auth.php và QLK00.taikhoan.';
    if (!$errors) {
        $lockName = 'uc10_receipt_code_' . date('Y');
        $lockHeld = false;
        $transactionStarted = false;
        try {
            $conn->begin_transaction();
            $transactionStarted = true;
            $lot = uc10_one($conn, 'SELECT lh.maLoHang, lh.trangThai, lh.soLuongTiepNhan, hh.donViTinh FROM LoHang lh JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa WHERE lh.maLoHang = ? FOR UPDATE', 's', [$form['maLoHang']]);
            if (!$lot || $lot['trangThai'] !== 'Đang sử dụng') throw new RuntimeException('Lô hàng không tồn tại hoặc không còn sử dụng.');
            if ($qty > (float)$lot['soLuongTiepNhan']) throw new RuntimeException('Số lượng thực nhập không được lớn hơn số lượng tiếp nhận (' . number_format((float)$lot['soLuongTiepNhan'], 2, ',', '.') . ').');
            $quality = uc10_one($conn, "SELECT maBienBan FROM BienBanKiemDinhChatLuong WHERE maLoHang = ? AND ketQua = 'Đạt' AND trangThai = 'Đã xác nhận' LIMIT 1", 's', [$form['maLoHang']]);
            if (!$quality) throw new RuntimeException('Lô chưa có biên bản UC09 xác nhận Đạt.');
            $duplicate = uc10_one($conn, 'SELECT maPhieuNhapKho FROM PhieuNhapKho WHERE maLoHang = ? LIMIT 1', 's', [$form['maLoHang']]);
            if ($duplicate) throw new RuntimeException('Lô hàng đã có phiếu nhập kho.');
            $warehouse = uc10_one($conn, "SELECT maKho FROM Kho WHERE maKho = ? AND trangThai = 'Đang hoạt động'", 's', [$form['maKho']]);
            if (!$warehouse) throw new RuntimeException('Kho nhập không hợp lệ.');
            $got = uc10_one($conn, 'SELECT GET_LOCK(?, 10) AS ok', 's', [$lockName]);
            if ((int)($got['ok'] ?? 0) !== 1) throw new RuntimeException('Không thể cấp mã phiếu. Vui lòng thử lại.');
            $lockHeld = true;
            $maPhieu = uc10_next_code($conn);
            $now = date('Y-m-d H:i:s');
            $note = 'Chứng từ liên quan: ' . $form['chungTuLienQuan'];
            if ($form['ghiChu'] !== '') $note .= "\n" . $form['ghiChu'];
            if (mb_strlen($note) > 500) throw new RuntimeException('Tổng nội dung chứng từ và ghi chú vượt 500 ký tự.');
            $stmt = $conn->prepare("INSERT INTO PhieuNhapKho (maPhieuNhapKho, ngayLapPhieu, soLuongThucNhap, donViTinh, thoiGianLap, trangThai, ghiChu, maLoHang, maKho, maNhanVien) VALUES (?, ?, ?, ?, ?, 'Đã nhập kho', ?, ?, ?, ?)");
            $stmt->bind_param('ssdssssss', $maPhieu, $now, $qty, $lot['donViTinh'], $now, $note, $form['maLoHang'], $form['maKho'], $employee['maNV']);
            $stmt->execute(); $stmt->close();
            $stock = uc10_one($conn, 'SELECT maTonKho FROM TonKho WHERE maLoHang = ? AND maKho = ? FOR UPDATE', 'ss', [$form['maLoHang'], $form['maKho']]);
            if ($stock) {
                $stmt = $conn->prepare("UPDATE TonKho SET soLuongTon = soLuongTon + ?, thoiDiemCapNhat = ?, maNhanVien = ?, trangThai = 'Đã nhập kho' WHERE maTonKho = ?");
                $stmt->bind_param('dsss', $qty, $now, $employee['maNV'], $stock['maTonKho']);
            } else {
                $maTonKho = 'TK' . strtoupper(bin2hex(random_bytes(7)));
                $stmt = $conn->prepare("INSERT INTO TonKho (maTonKho, soLuongTon, soLuongDaGiu, thoiDiemCapNhat, trangThai, maLoHang, maKho, maNhanVien) VALUES (?, ?, 0, ?, 'Đã nhập kho', ?, ?, ?)");
                $stmt->bind_param('sdssss', $maTonKho, $qty, $now, $form['maLoHang'], $form['maKho'], $employee['maNV']);
            }
            $stmt->execute(); $stmt->close();
            $stmt = $conn->prepare('UPDATE LoHang SET soLuongTon = soLuongTon + ? WHERE maLoHang = ?');
            $stmt->bind_param('ds', $qty, $form['maLoHang']);
            $stmt->execute(); $stmt->close();
            $conn->commit(); $transactionStarted = false;
            if ($lockHeld) { uc10_one($conn, 'SELECT RELEASE_LOCK(?) AS released', 's', [$lockName]); $lockHeld = false; }
            header('Location: index.php?created=' . rawurlencode($maPhieu));
            exit;
        } catch (Throwable $ex) {
            if ($transactionStarted) $conn->rollback();
            if ($lockHeld) { try { uc10_one($conn, 'SELECT RELEASE_LOCK(?) AS released', 's', [$lockName]); } catch (Throwable $ignored) {} }
            error_log('UC10 create: ' . $ex->getMessage());
            $errors[] = 'Không lập được phiếu: ' . $ex->getMessage();
        }
    }
}
$lotMap = [];
foreach ($lots as $lot) $lotMap[$lot['maLoHang']] = $lot;
include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<style>
.uc10-form-grid{display:grid;grid-template-columns:minmax(280px,1fr) minmax(400px,1.3fr);gap:20px;align-items:start}
.uc10-form-card{padding:24px}.uc10-form-card h2{margin:0 0 20px;color:#123466;font-size:18px}
.uc10-field{margin-bottom:17px}.uc10-field label{display:block;margin-bottom:7px;font-weight:600;color:#243b60}
.uc10-input{box-sizing:border-box;width:100%;padding:10px 12px;min-height:44px;border:1px solid #d0daea;border-radius:8px;font:inherit;background:#fff}
.uc10-input[readonly]{background:#f2f5fa}.uc10-help{font-size:13px;color:#64748b;margin-top:6px}
.uc10-details{width:100%;border-collapse:collapse}.uc10-details td{padding:11px;border-bottom:1px solid #e4eaf2}.uc10-details td:first-child{color:#64748b;width:43%}
.uc10-actions{display:flex;justify-content:flex-end;gap:12px;margin-top:24px}.uc10-error{padding:14px 18px;margin-bottom:18px;background:#fff1f1;border:1px solid #f0b0b0;border-radius:9px;color:#a12727}
@media(max-width:1000px){.uc10-form-grid{grid-template-columns:1fr}}@media(max-width:600px){.uc10-form-card{padding:16px}}
</style>
<?php if ($errors): ?><div class="uc10-error"><strong>Không thể lập phiếu:</strong><ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="" id="uc10-form" class="uc10-form-grid">
  <div class="card uc10-form-card">
    <h2>Thông tin phiếu nhập</h2>
    <div class="uc10-field"><label>Số phiếu nhập</label><input class="uc10-input" readonly value="<?= e($previewCode) ?>"><div class="uc10-help"></div></div>
    <div class="uc10-field"><label>Ngày lập</label><input class="uc10-input" readonly value="<?= e(date('d/m/Y H:i')) ?>"></div>
    <div class="uc10-field"><label for="uc10-kho">Kho nhập *</label><select class="uc10-input" id="uc10-kho" name="maKho" required><option value="">-- Chọn kho nhập --</option><?php foreach ($warehouses as $k): ?><option value="<?= e($k['maKho']) ?>" <?= $form['maKho'] === $k['maKho'] ? 'selected' : '' ?>><?= e($k['maKho'] . ' - ' . $k['tenKho']) ?></option><?php endforeach; ?></select></div>
    <div class="uc10-field"><label>Người lập</label><input class="uc10-input" readonly value="<?= e($employee ? $employee['hoTen'] : 'Chưa xác định từ phiên đăng nhập') ?>"></div>
    <div class="uc10-field"><label for="uc10-doc">Chứng từ liên quan *</label><input class="uc10-input" id="uc10-doc" name="chungTuLienQuan" maxlength="200" required placeholder="VD: TN2026-0004" value="<?= e($form['chungTuLienQuan']) ?>"><div class="uc10-help"></div></div>
    <div class="uc10-field"><label for="uc10-note">Ghi chú</label><textarea class="uc10-input" id="uc10-note" name="ghiChu" maxlength="250" rows="3"><?= e($form['ghiChu']) ?></textarea></div>
  </div>
  <div class="card uc10-form-card">
    <h2>Chi tiết lô hàng</h2>
    <div class="uc10-field"><label for="uc10-lot"></label><select class="uc10-input" id="uc10-lot" name="maLoHang" required><option value="">-- Chọn lô hàng --</option><?php foreach ($lots as $lot): ?><option value="<?= e($lot['maLoHang']) ?>" <?= $form['maLoHang'] === $lot['maLoHang'] ? 'selected' : '' ?>><?= e($lot['maLoHang'] . ' - ' . $lot['tenHangHoa']) ?></option><?php endforeach; ?></select><div class="uc10-help">Chỉ hiện lô có biên bản kiểm định Đạt đã xác nhận và chưa có phiếu nhập.</div></div>
    <table class="uc10-details"><tbody>
      <tr><td>Mã lô</td><td id="lot-code">—</td></tr>
      <tr><td>Số lô sản xuất</td><td id="lot-batch">—</td></tr>
      <tr><td>Tên hàng hóa</td><td id="lot-name">—</td></tr>
      <tr><td>Số lượng tiếp nhận</td><td id="lot-received">—</td></tr>
      <tr><td>Đơn vị tính</td><td id="lot-unit">—</td></tr>
      <tr><td>Ngày sản xuất</td><td id="lot-made">—</td></tr>
      <tr><td>Hạn sử dụng</td><td id="lot-expiry">—</td></tr>
      <tr><td>Tình trạng</td><td id="lot-condition">—</td></tr>
    </tbody></table>
    <div class="uc10-field" style="margin-top:22px"><label for="uc10-qty">Số lượng thực nhập *</label><input class="uc10-input" type="number" min="0.01" step="0.01" id="uc10-qty" name="soLuongThucNhap" value="<?= e($form['soLuongThucNhap']) ?>" required placeholder="Nhập số lượng thực nhập"></div>
    <?php if (!$lots): ?><div class="uc10-error">Chưa có lô đủ điều kiện. Cần biên bản UC09 “Đạt”, “Đã xác nhận” và lô chưa có phiếu nhập.</div><?php endif; ?>
    <?php if (!$employee): ?><div class="uc10-error">Chưa tìm được nhân viên tương ứng với phiên đăng nhập. Hãy đối chiếu session trong includes/auth.php và liên kết trong QLK00.taikhoan.</div><?php endif; ?>
    <div class="uc10-actions"><a class="btn btn-ghost" href="index.php">Quay lại</a><button type="submit" class="btn btn-primary" <?= (!$lots || !$employee || $employee['trangThai'] !== 'Đang hoạt động') ? 'disabled' : '' ?>><?= icon('check', 16) ?> Lập phiếu nhập</button></div>
  </div>
</form>
<script>
(function(){
 const lots = <?= json_encode($lotMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
 const select = document.getElementById('uc10-lot');
 const qtyInput = document.getElementById('uc10-qty');
 function validateQty(){
   const lot = lots[select.value];
   qtyInput.setCustomValidity('');
   if (lot && qtyInput.value !== '' && Number(qtyInput.value) > Number(lot.soLuongTiepNhan)) {
     qtyInput.setCustomValidity('Số lượng thực nhập không được lớn hơn số lượng tiếp nhận (' + Number(lot.soLuongTiepNhan).toLocaleString('vi-VN') + ' ' + lot.donViTinh + ').');
   }
 }
 const ids = {maLoHang:'lot-code',soLo:'lot-batch',tenHangHoa:'lot-name',donViTinh:'lot-unit',ngaySanXuat:'lot-made',hanSuDung:'lot-expiry',tinhTrang:'lot-condition'};
 function refresh(){
   const lot = lots[select.value];
   for(const [field,id] of Object.entries(ids)) document.getElementById(id).textContent = lot && lot[field] != null ? lot[field] : '—';
   document.getElementById('lot-received').textContent = lot ? Number(lot.soLuongTiepNhan).toLocaleString('vi-VN', {maximumFractionDigits:2}) + ' ' + lot.donViTinh : '—';
   document.getElementById('lot-quality').textContent = lot ? 'Đạt (đã xác nhận)' : '—';
   if (lot) qtyInput.max = String(lot.soLuongTiepNhan);
   else qtyInput.removeAttribute('max');
   validateQty();
 }
 select.addEventListener('change', refresh);
 qtyInput.addEventListener('input', validateQty);
 refresh();
 document.getElementById('uc10-form').addEventListener('submit', function(event){ if(!confirm('Xác nhận lập phiếu nhập kho và cập nhật tồn kho?')) event.preventDefault(); });
})();
</script>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
