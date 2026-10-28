<?php
/**
 * UC24 - Kiểm kê kho: LẬP BIÊN BẢN, XEM CHI TIẾT & GHI NHẬN KIỂM ĐẾM
 * Kiến trúc Modular Monolith: Điều hướng theo router tham số mode rõ ràng:
 *   - mode=create: Lập biên bản kiểm kê mới (chọn riêng Nguyên liệu hoặc Thành phẩm)
 *   - mode=detail: Xem chi tiết biên bản, đối chiếu chênh lệch, theo dõi hàng bất thường
 *   - mode=count:  Ghi nhận số lượng kiểm kê thực tế theo từng lô / vị trí trong phạm vi
 *
 * Tuân thủ schema QLK05, chuẩn chuỗi VARCHAR(15), prepared statements, transaction và CSRF.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/uc24_helper.php';
require_module('UC24');

$currentUser = current_user();
$empCode = trim((string)($currentUser['id'] ?? ''));
if ($empCode === '') {
    flash('error', 'Không xác định được mã nhân viên đăng nhập.');
    redirect(uc_url('UC24'));
}
$empName = uc24_get_employee_name($empCode);

// Xác định router mode
$mode = trim((string)($_GET['mode'] ?? ($_POST['mode'] ?? '')));
$id = trim((string)($_GET['id'] ?? ($_POST['id'] ?? '')));

if ($mode === '') {
    $mode = ($id !== '') ? 'detail' : 'create';
}

if (!in_array($mode, ['create', 'detail', 'count'], true)) {
    $mode = 'create';
}

// Chế độ detail hoặc count bắt buộc phải có mã biên bản $id
if (($mode === 'detail' || $mode === 'count') && $id === '') {
    flash('error', 'Vui lòng cung cấp mã biên bản kiểm kê.');
    redirect(uc_url('UC24'));
}

/* ========================================================================== */
/* MODE: DETAIL (Xem chi tiết, đối chiếu, hủy & báo hàng bất thường)          */
/* ========================================================================== */
if ($mode === 'detail') {
    // Xử lý các thao tác POST trong mode detail
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $action = trim((string)($_POST['action'] ?? ''));

        // 1. Thao tác đối chiếu kết quả kiểm kê
        if ($action === 'reconcile') {
            $bbCheck = uc24_db_one("SELECT * FROM BienBanKiemKe WHERE maBienBanKiemKe = ?", 's', [$id]);
            if (!$bbCheck) {
                flash('error', 'Không tìm thấy biên bản kiểm kê.');
            } elseif ($bbCheck['trangThai'] === 'Đã hủy') {
                flash('error', 'Biên bản đã bị hủy, không thể đối chiếu.');
            } elseif ($bbCheck['trangThai'] === 'Đã đối chiếu') {
                flash('info', 'Biên bản này đã được đối chiếu trước đó.');
            } else {
                $inScope = uc24_get_bb_inventory($bbCheck);
                $results = uc24_db_all("SELECT * FROM KetQuaKiemKe WHERE maBienBanKiemKe = ?", 's', [$id]);
                $countedMap = [];
                foreach ($results as $r) {
                    $countedMap[$r['maTonKho']] = $r;
                }

                $uncountedLots = [];
                foreach ($inScope as $inv) {
                    if (!isset($countedMap[$inv['maTonKho']])) {
                        $uncountedLots[] = $inv['maLoHang'] . ' (' . $inv['tenHang'] . ')';
                    }
                }

                if (!empty($uncountedLots)) {
                    $rem = count($uncountedLots);
                    flash('error', "Chưa thể đối chiếu: còn {$rem} lô hàng trong phạm vi chưa được ghi nhận số lượng thực tế. Vui lòng kiểm đếm đầy đủ trước khi đối chiếu.");
                } elseif ($bbCheck['trangThai'] !== 'Chờ kiểm kê') {
                    flash('error', 'Chỉ có thể đối chiếu biên bản đang ở trạng thái "Chờ kiểm kê".');
                } else {
                    $mysqli = db(DB_UC24);
                    $mysqli->begin_transaction();
                    try {
                        $diffCreated = 0;
                        foreach ($results as $kq) {
                            $diff = round((float)$kq['soLuongThucTe'] - (float)$kq['soLuongHeThong'], 2);
                            $oldCl = uc24_db_one("SELECT maChenhLech FROM ChenhLechKiemKe WHERE maKetQuaKiemKe = ?", 's', [$kq['maKetQuaKiemKe']]);

                            if ($diff != 0) {
                                $ketLuan = ($diff > 0) ? 'Thừa' : 'Thiếu';
                                $nguyenNhan = !empty($kq['ghiChu']) ? $kq['ghiChu'] : ('Phát hiện lệch ' . $ketLuan . ' khi kiểm kê');

                                if ($oldCl) {
                                    $stUp = $mysqli->prepare(
                                        "UPDATE ChenhLechKiemKe
                                         SET soLuongHeThong = ?, soLuongThucTe = ?, chenhLech = ?, nguyenNhan = ?, ketLuan = ?
                                         WHERE maChenhLech = ?"
                                    );
                                    $stUp->bind_param('dddsss', $kq['soLuongHeThong'], $kq['soLuongThucTe'], $diff, $nguyenNhan, $ketLuan, $oldCl['maChenhLech']);
                                    $stUp->execute();
                                    $stUp->close();
                                } else {
                                    $clCode = uc24_next_code('CL', 'ChenhLechKiemKe', 'maChenhLech');
                                    $stIn = $mysqli->prepare(
                                        "INSERT INTO ChenhLechKiemKe
                                         (maChenhLech, soLuongHeThong, soLuongThucTe, chenhLech, nguyenNhan, ketLuan, trangThai, ghiChu, maKetQuaKiemKe, maKho)
                                         VALUES (?, ?, ?, ?, ?, ?, 'Chưa xử lý', ?, ?, ?)"
                                    );
                                    $stIn->bind_param(
                                        'sdddsssss',
                                        $clCode,
                                        $kq['soLuongHeThong'],
                                        $kq['soLuongThucTe'],
                                        $diff,
                                        $nguyenNhan,
                                        $ketLuan,
                                        $kq['ghiChu'],
                                        $kq['maKetQuaKiemKe'],
                                        $bbCheck['maKho']
                                    );
                                    $stIn->execute();
                                    $stIn->close();
                                }
                                $diffCreated++;
                            } else {
                                if ($oldCl) {
                                    $stDel = $mysqli->prepare("DELETE FROM ChenhLechKiemKe WHERE maChenhLech = ?");
                                    $stDel->bind_param('s', $oldCl['maChenhLech']);
                                    $stDel->execute();
                                    $stDel->close();
                                }
                            }
                        }

                        $stBb = $mysqli->prepare(
                            "UPDATE BienBanKiemKe
                             SET trangThai = 'Đã đối chiếu', tienDoKiemKe = 100, thoiDiemKetThucThucTe = NOW()
                             WHERE maBienBanKiemKe = ?"
                        );
                        $stBb->bind_param('s', $id);
                        $stBb->execute();
                        $stBb->close();

                        $mysqli->commit();

                        if ($diffCreated > 0) {
                            flash('success', "Đối chiếu thành công! Đã phát hiện và ghi nhận <b>{$diffCreated}</b> dòng chênh lệch (Thừa/Thiếu). Biên bản đã chuyển sang trạng thái <b>Đã đối chiếu</b>.");
                        } else {
                            flash('success', "Đối chiếu thành công! Toàn bộ số lượng kiểm kê thực tế trùng khớp với hệ thống (0 chênh lệch). Biên bản đã chuyển sang trạng thái <b>Đã đối chiếu</b>.");
                        }
                    } catch (Throwable $ex) {
                        $mysqli->rollback();
                        flash('error', 'Lỗi trong quá trình đối chiếu: ' . $ex->getMessage());
                    }
                }
            }
            redirect(uc_url('UC24', 'form.php') . '?mode=detail&id=' . urlencode($id));
        }

        // 2. Thao tác hủy biên bản (khi chưa thực hiện)
        elseif ($action === 'cancel') {
            $bbCheck = uc24_db_one("SELECT trangThai FROM BienBanKiemKe WHERE maBienBanKiemKe = ?", 's', [$id]);
            if (!$bbCheck) {
                flash('error', 'Không tìm thấy biên bản kiểm kê.');
            } elseif ($bbCheck['trangThai'] === 'Đã hủy') {
                flash('info', 'Biên bản này đã bị hủy trước đó.');
            } elseif ($bbCheck['trangThai'] !== 'Chờ kiểm kê') {
                flash('error', 'Chỉ có thể hủy biên bản ở trạng thái "Chờ kiểm kê".');
            } else {
                uc24_db_exec("UPDATE BienBanKiemKe SET trangThai = 'Đã hủy' WHERE maBienBanKiemKe = ?", 's', [$id]);
                flash('success', 'Đã hủy biên bản kiểm kê <b>' . e($id) . '</b> thành công.');
            }
            redirect(uc_url('UC24'));
        }

        // 3. Thao tác ghi nhận hàng bất thường từ modal chi tiết
        elseif ($action === 'add_abnormal') {
            $kqId = trim((string)($_POST['maKetQuaKiemKe'] ?? ''));
            $tinhTrang = trim((string)($_POST['tinhTrangBatThuong'] ?? ''));
            $slAh = (float)($_POST['soLuongBiAnhHuong'] ?? 0);
            $nguyenNhan = trim((string)($_POST['nguyenNhanBatThuong'] ?? ''));
            $ghiChu = trim((string)($_POST['ghiChu'] ?? ''));

            $kq = uc24_db_one(
                "SELECT kq.*, b.trangThai AS trangThaiBienBan
                 FROM KetQuaKiemKe kq
                 JOIN BienBanKiemKe b ON b.maBienBanKiemKe = kq.maBienBanKiemKe
                 WHERE kq.maKetQuaKiemKe = ? AND kq.maBienBanKiemKe = ?",
                'ss', [$kqId, $id]
            );
            if (!$kq) {
                flash('error', 'Kết quả kiểm kê không hợp lệ.');
            } elseif ($kq['trangThaiBienBan'] !== 'Chờ kiểm kê') {
                flash('error', 'Chỉ được ghi nhận hàng bất thường khi biên bản đang ở trạng thái "Chờ kiểm kê".');
            } elseif ($tinhTrang === '' || $tinhTrang === 'Bình thường') {
                flash('error', 'Vui lòng chọn tình trạng bất thường cụ thể (ẩm mốc, rách bao, hết hạn...).');
            } elseif ($slAh <= 0) {
                flash('error', 'Số lượng bị ảnh hưởng phải lớn hơn 0.');
            } elseif ((float)$kq['soLuongThucTe'] <= 0 || $slAh > (float)$kq['soLuongThucTe']) {
                flash('error', 'Số lượng bị ảnh hưởng không được vượt quá số lượng kiểm đếm thực tế (' . fmt_qty($kq['soLuongThucTe']) . ').');
            } else {
                $tk = uc24_db_one("SELECT maLoHang FROM TonKho WHERE maTonKho = ?", 's', [$kq['maTonKho']]);
                $loId = $tk['maLoHang'] ?? '';

                $oldHbt = uc24_db_one("SELECT maHangBatThuong FROM HangBatThuong WHERE maKetQuaKiemKe = ?", 's', [$kqId]);
                if ($oldHbt) {
                    uc24_db_exec(
                        "UPDATE HangBatThuong
                         SET tinhTrangBatThuong = ?, soLuongBiAnhHuong = ?, nguyenNhanBatThuong = ?, ghiChu = ?, thoiDiemPhatHien = NOW()
                         WHERE maHangBatThuong = ?",
                        'sdsss',
                        [$tinhTrang, $slAh, $nguyenNhan, $ghiChu, $oldHbt['maHangBatThuong']]
                    );
                    flash('success', 'Đã cập nhật thông tin hàng bất thường ' . e($oldHbt['maHangBatThuong']) . '.');
                } else {
                    $hbtCode = uc24_next_code('HBT', 'HangBatThuong', 'maHangBatThuong');
                    uc24_db_exec(
                        "INSERT INTO HangBatThuong
                         (maHangBatThuong, tinhTrangBatThuong, soLuongBiAnhHuong, nguyenNhanBatThuong, thoiDiemPhatHien, trangThai, ghiChu, maLoHang, maTonKho, maKetQuaKiemKe, maPhuongAn)
                         VALUES (?, ?, ?, ?, NOW(), 'Chưa xử lý', ?, ?, ?, ?, NULL)",
                        'ssdsssss',
                        [$hbtCode, $tinhTrang, $slAh, $nguyenNhan, $ghiChu, $loId, $kq['maTonKho'], $kqId]
                    );
                    flash('success', 'Đã ghi nhận hàng bất thường mã <b>' . e($hbtCode) . '</b> thành công.');
                }
                uc24_db_exec("UPDATE KetQuaKiemKe SET tinhTrangHangHoa = ? WHERE maKetQuaKiemKe = ?", 'ss', [$tinhTrang, $kqId]);
            }
            redirect(uc_url('UC24', 'form.php') . '?mode=detail&id=' . urlencode($id));
        }
    }

    // Truy vấn dữ liệu hiển thị mode detail
    $bb = uc24_db_one(
        "SELECT b.*, k.tenKho, k.loaiKho, k.diaChi, kv.tenKhuVuc, kv.loaiKhuVuc, vt.tenViTri
         FROM BienBanKiemKe b
         JOIN Kho k ON k.maKho = b.maKho
         LEFT JOIN KhuVuc kv ON kv.maKhuVuc = b.maKhuVuc AND kv.maKho = b.maKho
         LEFT JOIN ViTriLuuTru vt ON vt.maViTri = b.maViTri AND vt.maKhuVuc = b.maKhuVuc
         WHERE b.maBienBanKiemKe = ?",
        's', [$id]
    );

    if (!$bb) {
        flash('error', 'Không tìm thấy biên bản kiểm kê ' . e($id));
        redirect(uc_url('UC24'));
    }

    $inScopeInventory = uc24_get_bb_inventory($bb);
    $totalScope = count($inScopeInventory);
    $loaiKiemKe = $bb['loaiKiemKe'] ?? 'NGUYEN_LIEU';

    $results = uc24_db_all(
        "SELECT kq.*, tk.soLuongTon AS tk_soLuongTon, lh.tenLoHang, lh.donViTinh, lh.hanSuDung, lh.ngaySanXuat, lh.trangThaiChatLuong,
                hh.maHang, hh.tenHang, hh.nhomHang, hh.quyCachDongGoi, hh.phanLoaiHangHoa,
                vt.tenViTri, kv.tenKhuVuc
         FROM KetQuaKiemKe kq
         JOIN TonKho tk ON tk.maTonKho = kq.maTonKho
         JOIN LoHang lh ON lh.maLoHang = tk.maLoHang
         JOIN HangHoa hh ON hh.maHang = lh.maHang
         JOIN ViTriLuuTru vt ON vt.maViTri = kq.maViTri
         JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc AND kv.maKho = tk.maKho
         WHERE kq.maBienBanKiemKe = ?
         ORDER BY kv.tenKhuVuc, vt.tenViTri, hh.tenHang",
        's', [$id]
    );

    $resultsByTonKho = [];
    $countMatched = 0;
    $countDiff = 0;
    foreach ($results as $r) {
        $resultsByTonKho[$r['maTonKho']] = $r;
        if ((float)$r['chenhLech'] == 0) {
            $countMatched++;
        } else {
            $countDiff++;
        }
    }
    $countRecorded = count($results);

    $differences = uc24_db_all(
        "SELECT cl.*, kq.soLuongHeThong AS kq_heThong, kq.soLuongThucTe AS kq_thucTe,
                lh.maLoHang, lh.tenLoHang, lh.donViTinh,
                hh.maHang, hh.tenHang, hh.quyCachDongGoi,
                vt.tenViTri, kv.tenKhuVuc
         FROM ChenhLechKiemKe cl
         JOIN KetQuaKiemKe kq ON kq.maKetQuaKiemKe = cl.maKetQuaKiemKe
         JOIN TonKho tk ON tk.maTonKho = kq.maTonKho
         JOIN LoHang lh ON lh.maLoHang = tk.maLoHang
         JOIN HangHoa hh ON hh.maHang = lh.maHang
         JOIN ViTriLuuTru vt ON vt.maViTri = kq.maViTri
         JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc AND kv.maKho = tk.maKho
         WHERE kq.maBienBanKiemKe = ?
         ORDER BY cl.maChenhLech",
        's', [$id]
    );

    $abnormalItems = uc24_db_all(
        "SELECT hbt.*, lh.tenLoHang, lh.donViTinh, hh.maHang, hh.tenHang, vt.tenViTri, kv.tenKhuVuc,
                pa.hinhThucXuLy, pa.trangThaiPheDuyet
         FROM HangBatThuong hbt
         JOIN TonKho tk ON tk.maTonKho = hbt.maTonKho
         JOIN LoHang lh ON lh.maLoHang = hbt.maLoHang
         JOIN HangHoa hh ON hh.maHang = lh.maHang
         JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
         JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc AND kv.maKho = tk.maKho
         LEFT JOIN PhuongAnXuLy pa ON pa.maPhuongAn = hbt.maPhuongAn
         JOIN KetQuaKiemKe kq ON kq.maKetQuaKiemKe = hbt.maKetQuaKiemKe
         WHERE kq.maBienBanKiemKe = ?
         ORDER BY hbt.thoiDiemPhatHien DESC",
        's', [$id]
    );

    $creatorName = uc24_get_employee_name($bb['maNhanVien']);
    $tienDo = (float)$bb['tienDoKiemKe'];
    $isReconciled = ($bb['trangThai'] === 'Đã đối chiếu');
    $isCanceled = ($bb['trangThai'] === 'Đã hủy');
    $canReconcile = !$isReconciled && !$isCanceled && ($countRecorded === $totalScope && $totalScope > 0);
    $canCount = !$isReconciled && !$isCanceled;

    $flash = pull_flash();
    $page_title = 'Chi tiết ' . e($bb['maBienBanKiemKe']);
    $active_menu = 'UC24';

    include ROOT_PATH . '/includes/head.php';
    include ROOT_PATH . '/includes/sidebar.php';
    include ROOT_PATH . '/includes/header.php';
    ?>

    <style>
    .cukcuk-detail-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }
    .cukcuk-field-label {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 6px;
        font-weight: 500;
    }
    .cukcuk-field-value {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.35;
        word-break: break-word;
    }
    .cukcuk-field-note {
        font-size: 14px;
        font-weight: 400;
        color: #334155;
        line-height: 1.5;
        word-break: break-word;
    }
    .cukcuk-grid-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
        text-align: left;
        background: #ffffff;
        margin: 0;
    }
    .cukcuk-grid-table th {
        background: #f8fafc;
        color: #475467;
        font-weight: 600;
        font-size: 13px;
        padding: 11px 10px;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        white-space: nowrap;
        vertical-align: middle;
    }
    .cukcuk-grid-table th:last-child {
        border-right: none;
    }
    .cukcuk-grid-table td {
        padding: 11px 10px;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        color: #1d2939;
        vertical-align: middle;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cukcuk-grid-table td:last-child {
        border-right: none;
    }
    .cukcuk-grid-table tbody tr:hover {
        background: #f8fbff;
    }
    .cukcuk-grid-table tbody tr:last-child td {
        border-bottom: none;
    }
    @media (max-width: 900px) {
        .cukcuk-row-1 {
            grid-template-columns: repeat(2, 1fr) !important;
        }
        .cukcuk-row-2 {
            grid-template-columns: 1fr !important;
        }
    }
    @media print {
        .sidebar, .topbar, .btn, .breadcrumb, form {
            display: none !important;
        }
        body, .content {
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .cukcuk-detail-card {
            border: 1px solid #ccc !important;
            box-shadow: none !important;
            page-break-inside: avoid;
        }
    }
    </style>

    <!-- Đường dẫn Breadcrumb đồng bộ theo ảnh tham khảo -->
    <nav class="breadcrumb" aria-label="breadcrumb" style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #64748b; margin-bottom: 10px;">
        <a href="<?= e(url('index.php')) ?>" style="display: inline-flex; align-items: center; color: #64748b; text-decoration: none;">
            <?= icon('home', 15) ?>
        </a>
        <span class="sep" style="color: #94a3b8; font-size: 11px;">›</span>
        <a href="<?= e(url('index.php')) ?>" style="color: #64748b; text-decoration: none;">Tổng quan</a>
        <span class="sep" style="color: #94a3b8; font-size: 11px;">›</span>
        <a href="<?= e(url(uc_url('UC24'))) ?>" style="color: #64748b; text-decoration: none;">Kiểm kê</a>
        <span class="sep" style="color: #94a3b8; font-size: 11px;">›</span>
        <span class="current" style="font-weight: 600; color: #0f172a;"><?= e($bb['maBienBanKiemKe']) ?></span>
    </nav>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert" style="margin-bottom: 16px;">
            <?= e($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Tiêu đề trang & Thao tác (Bỏ cục chữ xanh thành phẩm, Trạng thái chuẩn theo hệ thống) -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 18px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <h1 style="margin: 0; font-size: 22px; font-weight: 750; color: #0f172a; letter-spacing: -0.3px;">
                Chi tiết <?= e($bb['maBienBanKiemKe']) ?>
            </h1>
            <?= uc24_status_dot($bb['trangThai']) ?>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <!-- Nút quay lại danh sách biên bản -->
            <a class="btn btn-ghost" href="<?= e(url(uc_url('UC24'))) ?>" title="Quay lại" style="height: 36px; padding: 0 14px; display: inline-flex; align-items: center; gap: 7px; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px;">
                <span aria-hidden="true" style="font-size: 18px; line-height: 1;">←</span>
                <span>Quay lại</span>
            </a>

            <!-- Nút In biên bản: dạng icon -->
            <button type="button" class="btn btn-ghost" onclick="window.print()" title="In biên bản" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px;">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9V3h12v6" />
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                    <path d="M6 14h12v7H6z" />
                    <path d="M18 12h.01" />
                </svg>
            </button>

            <!-- Nút Đối chiếu kết quả (nếu có): dạng icon -->
            <?php if ($canReconcile): ?>
                <form method="post" class="inline-form" data-confirm="Xác nhận đối chiếu số liệu kiểm kê thực tế với hệ thống cho biên bản <?= e($id) ?>? Thao tác này sẽ ghi nhận các khoản chênh lệch và chuyển trạng thái sang Đã đối chiếu.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reconcile">
                    <button type="submit" class="btn btn-success" title="Đối chiếu kết quả" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px;">
                        <span aria-hidden="true" style="font-size: 18px; line-height: 1;">✓</span>
                    </button>
                </form>
            <?php endif; ?>

            <!-- Nút Hủy biên bản (nếu có): dạng icon -->
            <?php if ($bb['trangThai'] === 'Chờ kiểm kê'): ?>
                <form method="post" class="inline-form" data-confirm="Bạn có chắc chắn muốn hủy biên bản kiểm kê <?= e($id) ?>?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn btn-danger" title="Hủy biên bản" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px;">
                        <?= icon('trash', 16) ?>
                    </button>
                </form>
            <?php endif; ?>

            <!-- Nút Ghi nhận kiểm đếm được giữ lại để minh họa luồng UC24; nghiệp vụ sẽ hoàn thiện sau. -->
            <?php if ($canCount): ?>
                <a class="btn btn-primary" href="<?= e(url(uc_url('UC24', 'form.php') . '?mode=count&id=' . urlencode($id))) ?>" onclick="alert('Chức năng Ghi nhận kết quả kiểm đếm thuộc quy trình kiểm đếm kho (UC25), không nằm trong phạm vi demo UC24 hiện tại.'); return false;" title="Ghi nhận" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px;">
                    <?= icon('edit', 16) ?>
                </a>
            <?php endif; ?>

        </div>
    </div>

    <!-- Card Thông tin chung (Khớp 100% mẫu tham khảo với đường kẻ ngăn cách) -->
    <div class="cukcuk-detail-card" style="padding: 22px 24px;">
        <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 20px 0;">Thông tin chung</h2>

        <!-- Hàng 1: 4 cột có đường kẻ dọc và đường kẻ ngang dưới -->
        <div class="cukcuk-row-1" style="display: grid; grid-template-columns: repeat(4, 1fr); border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 20px;">
            <div style="padding-right: 20px; border-right: 1px solid #f1f5f9;">
                <div class="cukcuk-field-label">Mã biên bản</div>
                <div class="cukcuk-field-value"><?= e($bb['maBienBanKiemKe']) ?></div>
            </div>
            <div style="padding: 0 20px; border-right: 1px solid #f1f5f9;">
                <div class="cukcuk-field-label">Kho</div>
                <div class="cukcuk-field-value"><?= e($bb['tenKho']) ?></div>
            </div>
            <div style="padding: 0 20px; border-right: 1px solid #f1f5f9;">
                <div class="cukcuk-field-label">Khu vực</div>
                <div class="cukcuk-field-value">
                    <?= !empty($bb['tenKhuVuc']) ? e($bb['tenKhuVuc']) : 'Toàn kho' ?>
                    <?= !empty($bb['tenViTri']) ? (' • ' . e($bb['tenViTri'])) : '' ?>
                </div>
            </div>
            <div style="padding-left: 20px;">
                <div class="cukcuk-field-label">Ngày kiểm kê</div>
                <div class="cukcuk-field-value">
                    <?= date('d/m/Y', strtotime($bb['ngayLap'])) ?>
                </div>
            </div>
        </div>

        <!-- Hàng 2: 3 cột có đường kẻ dọc đồng bộ -->
        <div class="cukcuk-row-2" style="display: grid; grid-template-columns: repeat(4, 1fr); align-items: flex-start;">
            <div style="padding-right: 20px; border-right: 1px solid #f1f5f9;">
                <div class="cukcuk-field-label">Loại kiểm kê</div>
                <div class="cukcuk-field-value"><?= $loaiKiemKe === 'THANH_PHAM' ? 'Thành phẩm' : 'Nguyên liệu' ?></div>
            </div>
            <div style="padding: 0 20px; border-right: 1px solid #f1f5f9;">
                <div class="cukcuk-field-label">Người lập</div>
                <div class="cukcuk-field-value">
                    <?= e($creatorName) ?>
                </div>
            </div>
            <div style="grid-column: span 2; padding-left: 20px;">
                <div class="cukcuk-field-label">Ghi chú</div>
                <div class="cukcuk-field-note">
                    <?php
                    $realNote = trim($bb['ghiChu'] ?? '');
                    echo ($realNote !== '') ? e($realNote) : '<span style="color: #94a3b8;">—</span>';
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Danh sách lô hàng kiểm kê (Hiển thị vừa khít bảng, không cuộn ngang, bỏ cột STT) -->
    <div class="cukcuk-detail-card" style="padding: 22px 24px;">
        <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0;">
            Danh sách lô hàng kiểm kê (<?= $totalScope ?> lô)
        </h2>

        <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #ffffff;">
            <table class="cukcuk-grid-table" style="table-layout: fixed; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 13%;">Mã lô</th>
                        <th style="width: 25%;">Tên hàng hóa</th>
                        <th style="width: 14%;">Vị trí</th>
                        <th style="width: 11%; text-align: center;">Hạn sử dụng</th>
                        <th style="width: 6%; text-align: center;">ĐVT</th>
                        <th style="width: 10%; text-align: center;">Theo sổ</th>
                        <th style="width: 10%; text-align: center;">Kiểm kê</th>
                        <th style="width: 11%; text-align: center;">Chênh lệch</th>
                        <th style="width: 10%;">Tình trạng</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($inScopeInventory)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 32px; color: #64748b;">
                            Không có lô hàng nào trong phạm vi biên bản kiểm kê này.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inScopeInventory as $idx => $inv):
                        $tkId = $inv['maTonKho'];
                        $kq = $resultsByTonKho[$tkId] ?? null;
                        $hasCounted = ($kq !== null);
                        $heThong = (float)$inv['soLuongTon'];
                        $thucTe = $hasCounted ? (float)$kq['soLuongThucTe'] : null;
                        $diff = $hasCounted ? (float)$kq['chenhLech'] : null;
                        $tinhTrang = $hasCounted ? $kq['tinhTrangHangHoa'] : 'Chưa kiểm';
                    ?>
                        <tr>
                            <td style="color: #0f172a; font-weight: 500;"><?= e($inv['maLoHang']) ?></td>
                            <td><strong style="color: #0f172a;"><?= e($inv['tenHang']) ?></strong></td>
                            <td style="color: #475467;"><?= e($inv['tenViTri']) ?></td>
                            <td style="text-align: center; color: #475467;"><?= !empty($inv['hanSuDung']) ? e(date('d/m/Y', strtotime($inv['hanSuDung']))) : '—' ?></td>
                            <td style="text-align: center; color: #475467;"><?= e($inv['donViTinh']) ?></td>
                            <td style="text-align: center; font-weight: 700; color: #0f172a;"><?= fmt_qty($heThong) ?></td>
                            <td style="text-align: center;">
                                <?php if ($hasCounted): ?>
                                    <strong style="color: #2563eb;"><?= fmt_qty($thucTe) ?></strong>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($hasCounted): ?>
                                    <?php if ($diff == 0): ?>
                                        <strong style="color: #16a34a;">0</strong>
                                    <?php elseif ($diff > 0): ?>
                                        <strong style="color: #16a34a;">+<?= fmt_qty($diff) ?></strong>
                                    <?php else: ?>
                                        <strong style="color: #dc2626;"><?= fmt_qty($diff) ?></strong>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$hasCounted): ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php elseif ($tinhTrang === 'Bình thường'): ?>
                                    <span style="color: #16a34a; font-weight: 600;">Bình thường</span>
                                <?php else: ?>
                                    <strong style="color: #d97706;"><?= e($tinhTrang) ?></strong>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    include ROOT_PATH . '/includes/footer.php';
    include ROOT_PATH . '/includes/scripts.php';
    exit;
}

/* ========================================================================== */
/* MODE: COUNT (Chức năng ghi nhận kiểm đếm thuộc UC25 — ngoài phạm vi UC24) */
/* ========================================================================== */
if ($mode === 'count') {
    flash('info', 'Chức năng Ghi nhận kết quả kiểm đếm thuộc quy trình kiểm đếm kho (UC25), không nằm trong phạm vi đề bài UC24.');
    redirect(uc_url('UC24', 'form.php') . '?mode=detail&id=' . urlencode($id));
}

/* ========================================================================== */
/* MODE: CREATE (Lập biên bản kiểm kê mới — Nguyên liệu vs Thành phẩm)        */
/* ========================================================================== */
$type = trim((string)($_GET['type'] ?? ($_POST['loaiKiemKe'] ?? 'NGUYEN_LIEU')));
$validTypes = ['NGUYEN_LIEU', 'THANH_PHAM'];
if (!in_array($type, $validTypes, true)) {
    $type = 'NGUYEN_LIEU';
}

$now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
$defaultStart = $now->format('Y-m-d\TH:i');

$rawEnd = trim((string)($_POST['thoiDiemKetThucDuKien'] ?? ($_GET['end'] ?? '')));
if (!empty($rawEnd)) {
    if (strlen($rawEnd) === 10) {
        $defaultEnd = $rawEnd . ' 23:59:59';
    } else {
        $defaultEnd = $rawEnd;
    }
} else {
    $defaultEnd = (clone $now)->modify('+1 day')->format('Y-m-d 23:59:59');
}

$posted = [
    'loaiKiemKe' => $type,
    'maKho' => trim((string)($_POST['maKho'] ?? ($_GET['kho'] ?? ''))),
    'maKhuVuc' => trim((string)($_POST['maKhuVuc'] ?? ($_GET['khuvuc'] ?? ''))),
    'maViTri' => trim((string)($_POST['maViTri'] ?? ($_GET['vitri'] ?? ''))),
    'thoiDiemBatDau' => trim((string)($_POST['thoiDiemBatDau'] ?? ($_GET['start'] ?? $defaultStart))),
    'thoiDiemKetThucDuKien' => $defaultEnd,
    'ghiChu' => trim((string)($_POST['ghiChu'] ?? '')),
];

// Lấy danh sách kho hợp lệ của loại kiểm kê
$availableWarehouses = uc24_get_warehouses_by_type($posted['loaiKiemKe']);
$validWhIds = array_column($availableWarehouses, 'maKho');
if ($posted['maKho'] === '' && !empty($validWhIds)) {
    $posted['maKho'] = $validWhIds[0];
} elseif ($posted['maKho'] !== '' && !in_array($posted['maKho'], $validWhIds, true)) {
    $posted['maKho'] = !empty($validWhIds) ? $validWhIds[0] : '';
    $posted['maKhuVuc'] = '';
    $posted['maViTri'] = '';
}

$errors = [];

// Xử lý tạo biên bản kiểm kê mới (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();

    // 1. Kiểm tra loại kiểm kê
    if (!in_array($posted['loaiKiemKe'], $validTypes, true)) {
        $errors[] = 'Vui lòng chọn loại kiểm kê hợp lệ: Nguyên liệu hoặc Thành phẩm.';
    }

    // 2. Kiểm tra kho hợp lệ có chứa hàng của loại này
    $khoValid = false;
    if ($posted['maKho'] !== '') {
        $availableWarehouses = uc24_get_warehouses_by_type($posted['loaiKiemKe']);
        $whIds = array_column($availableWarehouses, 'maKho');
        if (in_array($posted['maKho'], $whIds, true)) {
            $khoValid = true;
        }
    }
    if (!$khoValid) {
        $errors[] = 'Vui lòng chọn kho kiểm kê hợp lệ có chứa hàng hóa thuộc loại ' . uc24_type_label($posted['loaiKiemKe']) . '.';
    }

    // Cột thoiDiemKetThucDuKien có CHECK phải lớn hơn thời điểm bắt đầu (NOW()).
    // Kiểm tra trước để không trả lỗi SQL khó hiểu khi người dùng chọn ngày đã qua.
    $endTimestamp = strtotime($posted['thoiDiemKetThucDuKien']);
    if ($endTimestamp === false || $endTimestamp <= time()) {
        $errors[] = 'Ngày kết thúc kiểm kê phải lớn hơn thời điểm hiện tại.';
    }

    // 3. Kiểm tra khu vực (nếu có chọn)
    if (!empty($posted['maKhuVuc'])) {
        $kvValid = uc24_db_one("SELECT maKhuVuc FROM KhuVuc WHERE maKhuVuc = ? AND maKho = ? AND trangThai = 'Hoạt động'", 'ss', [$posted['maKhuVuc'], $posted['maKho']]);
        if (!$kvValid) {
            $errors[] = 'Khu vực đã chọn không thuộc kho kiểm kê đang chọn.';
        }
    }

    // 4. Kiểm tra vị trí lưu trữ (nếu có chọn)
    if (!empty($posted['maViTri'])) {
        if (empty($posted['maKhuVuc'])) {
            $errors[] = 'Để chọn vị trí cụ thể, vui lòng chọn khu vực tương ứng trước.';
        } else {
            $vtValid = uc24_db_one("SELECT maViTri FROM ViTriLuuTru WHERE maViTri = ? AND maKhuVuc = ? AND trangThai = 'Hoạt động'", 'ss', [$posted['maViTri'], $posted['maKhuVuc']]);
            if (!$vtValid) {
                $errors[] = 'Vị trí lưu trữ đã chọn không thuộc khu vực đang chọn.';
            }
        }
    }

    // 5. Kiểm tra người lập
    $employee = $empCode !== '' ? uc24_get_employee($empCode) : null;
    if (!$employee) {
        $errors[] = 'Tài khoản đăng nhập chưa được liên kết với nhân viên trong hệ thống QLK00.';
    }

    // 6. Tải đúng danh sách hàng/lô trong phạm vi đã xác định
    $scopeLots = uc24_get_scope_preview(
        $posted['loaiKiemKe'],
        $posted['maKho'],
        !empty($posted['maKhuVuc']) ? $posted['maKhuVuc'] : null,
        !empty($posted['maViTri']) ? $posted['maViTri'] : null
    );

    if (empty($scopeLots)) {
        $errors[] = 'Không tìm thấy lô hàng tồn kho nào trong phạm vi đã chọn (Kho → Khu vực → Vị trí) để lập biên bản.';
    }

    // Ghi dữ liệu trong Transaction
    if (empty($errors)) {
        $mysqli = db(DB_UC24);
        $mysqli->begin_transaction();
        try {
            $newCode = uc24_next_code('BB', 'BienBanKiemKe', 'maBienBanKiemKe');

            $cleanNote = trim((string)($posted['ghiChu'] ?? ''));
            if ($cleanNote === '') {
                $cleanNote = 'Kiểm kê kho định kỳ';
            }
            if (mb_strlen($cleanNote, 'UTF-8') > 500) {
                $cleanNote = mb_substr($cleanNote, 0, 500, 'UTF-8');
            }

            $thoiDiemBatDau = date('Y-m-d H:i:s');
            $thoiDiemKetThucDuKien = date('Y-m-d H:i:s', strtotime($posted['thoiDiemKetThucDuKien']));

            $sqlInsert = "INSERT INTO BienBanKiemKe
                (maBienBanKiemKe, ngayLap, loaiKiemKe, trangThai, tienDoKiemKe, thoiDiemBatDau, thoiDiemKetThucDuKien, ghiChu, maKho, maKhuVuc, maViTri, maNhanVien)
                VALUES (?, NOW(), ?, 'Chờ kiểm kê', 0, ?, ?, ?, ?, ?, ?, ?)";

            $st = $mysqli->prepare($sqlInsert);
            $khuVucVal = !empty($posted['maKhuVuc']) ? $posted['maKhuVuc'] : null;
            $viTriVal = !empty($posted['maViTri']) ? $posted['maViTri'] : null;

            $st->bind_param(
                'sssssssss',
                $newCode,
                $posted['loaiKiemKe'],
                $thoiDiemBatDau,
                $thoiDiemKetThucDuKien,
                $cleanNote,
                $posted['maKho'],
                $khuVucVal,
                $viTriVal,
                $empCode
            );
            $st->execute();
            $st->close();

            // Chụp số tồn mốc ban đầu vào bảng PhamViKiemKe từ $scopeLots
            $stPv = $mysqli->prepare(
                "INSERT INTO PhamViKiemKe (maBienBanKiemKe, maTonKho, soLuongTonChup, maLoHang, maViTri)
                 VALUES (?, ?, ?, ?, ?)"
            );

            foreach ($scopeLots as $item) {
                $tkId = $item['maTonKho'];
                $soLuongChup = (float)$item['soLuongTon'];
                $loHangId = $item['maLoHang'];
                $viTriId = $item['maViTri'];

                $stPv->bind_param('ssdss', $newCode, $tkId, $soLuongChup, $loHangId, $viTriId);
                $stPv->execute();
            }
            $stPv->close();

            $mysqli->commit();

            flash('success', 'Biên bản kiểm kê đã được lập thành công.');
            redirect(uc_url('UC24', 'form.php') . '?mode=detail&id=' . urlencode($newCode));
        } catch (Throwable $ex) {
            $mysqli->rollback();
            $errors[] = 'Lỗi hệ thống khi lưu biên bản: ' . $ex->getMessage();
        }
    }
}

// Chuẩn bị dữ liệu danh mục kho, khu vực, vị trí và xem trước phạm vi lô hàng
$nextCodePreview = uc24_next_code('BB', 'BienBanKiemKe', 'maBienBanKiemKe');
$typeLabel = uc24_type_label($posted['loaiKiemKe']);
$warehouses = uc24_get_warehouses_by_type($posted['loaiKiemKe']);
$areas = !empty($posted['maKho']) ? uc24_get_areas_by_warehouse_and_type($posted['maKho'], $posted['loaiKiemKe']) : [];
$locations = [];
if (!empty($posted['maKho'])) {
    $locations = uc24_db_all(
        "SELECT DISTINCT vt.maViTri, vt.tenViTri, vt.maKhuVuc
         FROM ViTriLuuTru vt
         JOIN TonKho tk ON tk.maViTri = vt.maViTri
         JOIN LoHang lh ON lh.maLoHang = tk.maLoHang
         JOIN HangHoa hh ON hh.maHang = lh.maHang
         JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc AND kv.maKho = tk.maKho
         WHERE tk.maKho = ? AND hh.phanLoaiHangHoa = ? AND vt.trangThai = 'Hoạt động'
         ORDER BY vt.tenViTri",
        'ss', [$posted['maKho'], $posted['loaiKiemKe']]
    );
}

// Lấy danh sách hàng hóa/lô hàng trong phạm vi đã chọn
$scopeLots = !empty($posted['maKho'])
    ? uc24_get_scope_preview(
        $posted['loaiKiemKe'],
        $posted['maKho'],
        !empty($posted['maKhuVuc']) ? $posted['maKhuVuc'] : null,
        !empty($posted['maViTri']) ? $posted['maViTri'] : null
    )
    : [];

$selectedKhoName = '';
foreach ($warehouses as $wh) {
    if ($wh['maKho'] === $posted['maKho']) {
        $selectedKhoName = $wh['tenKho'];
        break;
    }
}

// Lấy danh sách toàn bộ kho theo từng loại phục vụ Modal khởi tạo nhanh
$allWhRaw = uc24_get_warehouses_by_type('NGUYEN_LIEU');
$allWhProd = uc24_get_warehouses_by_type('THANH_PHAM');

$displayEndDate = !empty($posted['thoiDiemKetThucDuKien']) ? date('d/m/Y', strtotime($posted['thoiDiemKetThucDuKien'])) : date('d/m/Y', strtotime('+1 day'));

// Render màn hình lập biên bản (create)
$page_title = 'Lập biên bản kiểm kê';
$active_menu = 'UC24';
$breadcrumb = [
    ['Tổng quan', 'index.php'],
    ['Kiểm kê', uc_url('UC24')],
    ['Lập biên bản', null]
];
$toolbar_actions = '<a class="btn btn-ghost" href="' . e(url(uc_url('UC24'))) . '"><span aria-hidden="true">←</span> Quay lại</a>';

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';

$flash = pull_flash();
?>

<style>
/* 1. Modal khởi tạo nhanh */
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

/* 2. Bố cục Top 7:3 */
.cukcuk-create-top-grid {
    display: grid;
    grid-template-columns: 7fr 3fr;
    gap: 16px;
    align-items: stretch;
}
@media (max-width: 992px) {
    .cukcuk-create-top-grid {
        grid-template-columns: 1fr;
    }
}
.cukcuk-card {
    background: #ffffff;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
    height: 100%;
}
.cukcuk-static-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.cukcuk-static-label {
    font-size: 12px;
    color: #64748b;
    font-weight: 500;
    line-height: 1.2;
}
.cukcuk-static-value {
    font-size: 13.5px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.35;
    word-break: break-word;
}
.cukcuk-field-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #344054;
    margin-bottom: 6px;
    line-height: 1.2;
}
.cukcuk-input-control {
    width: 100%;
    height: 34px;
    padding: 0 10px;
    font-size: 13px;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    box-sizing: border-box;
    outline: none;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.cukcuk-input-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
textarea.cukcuk-input-control {
    height: auto;
    min-height: 52px;
    padding: 6px 10px;
    resize: vertical;
    font-family: inherit;
    line-height: 1.4;
}

/* 3. Tiêu đề loại hàng đã chọn */
.cukcuk-clean-tab-bar {
    display: flex;
    align-items: center;
    border-bottom: 1px solid var(--line);
    background: #ffffff;
    padding: 0 18px;
}
.cukcuk-tab-item {
    display: inline-flex;
    align-items: center;
    padding: 12px 2px;
    font-size: 14px;
    font-weight: 700;
    color: #2563eb;
    border-bottom: 2px solid #2563eb;
    text-decoration: none;
    user-select: none;
}

/* 4. Bảng hàng/lô chuẩn 5 cột đồng bộ như bên kiểm kê */
#lotTable {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
    table-layout: fixed;
}
#lotTable th {
    background: #f8fafc;
    font-size: 12.5px;
    font-weight: 700;
    color: #475467;
    padding: 11px 14px;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
    text-align: left;
}
#lotTable th.num, #lotTable td.num {
    text-align: right;
}
#lotTable th.center, #lotTable td.center {
    text-align: center;
}
#lotTable td {
    padding: 11px 14px;
    font-size: 13px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #1d2939;
}
#lotTable tbody tr {
    transition: background .12s ease;
}
#lotTable tbody tr:hover {
    background: #f8fbff;
}
#lotTable tbody tr:last-child td {
    border-bottom: none;
}
#lotTable tfoot td {
    background: #fafbfd;
    border-top: 1px solid var(--line);
    border-bottom: none;
    padding: 11px 14px;
    font-size: 13px;
}
</style>

<!-- BƯỚC 1: POPUP (MODAL) KHỞI TẠO / THAY ĐỔI KHO & NGÀY -->
<div id="initKiemKeModal" class="cukcuk-modal-backdrop" style="display: none;">
    <div class="cukcuk-modal-box">
        <div class="cukcuk-modal-header">
            <h3 class="cukcuk-modal-title">Kiểm kê kho</h3>
            <button type="button" class="cukcuk-modal-close" onclick="cancelInitModal()" title="Đóng">&times;</button>
        </div>
        <div class="cukcuk-modal-body">
            <!-- 1. Loại kiểm kê * -->
            <div>
                <label class="cukcuk-field-label">Loại kiểm kê <span style="color: #ef4444;">*</span></label>
                <div class="modal-segmented-radio">
                    <label class="seg-radio-option <?= $posted['loaiKiemKe'] === 'NGUYEN_LIEU' ? 'active' : '' ?>" id="optNguyenLieu">
                        <input type="radio" name="modal_loaiKiemKe" value="NGUYEN_LIEU" <?= $posted['loaiKiemKe'] === 'NGUYEN_LIEU' ? 'checked' : '' ?> onchange="onModalTypeChange('NGUYEN_LIEU')">
                        <span>Nguyên liệu</span>
                    </label>
                    <label class="seg-radio-option <?= $posted['loaiKiemKe'] === 'THANH_PHAM' ? 'active' : '' ?>" id="optThanhPham">
                        <input type="radio" name="modal_loaiKiemKe" value="THANH_PHAM" <?= $posted['loaiKiemKe'] === 'THANH_PHAM' ? 'checked' : '' ?> onchange="onModalTypeChange('THANH_PHAM')">
                        <span>Thành phẩm</span>
                    </label>
                </div>
            </div>

            <!-- 2. Kiểm kê kho * -->
            <div>
                <label for="modal_maKho" class="cukcuk-field-label">Kiểm kê kho <span style="color: #ef4444;">*</span></label>
                <select id="modal_maKho" class="cukcuk-input-control">
                    <!-- Populated dynamically via JS -->
                </select>
            </div>

            <!-- 3. Kiểm kê đến ngày * -->
            <div>
                <label for="modal_thoiDiemKetThuc" class="cukcuk-field-label">Kiểm kê đến ngày <span style="color: #ef4444;">*</span></label>
                <input type="date" id="modal_thoiDiemKetThuc" class="cukcuk-input-control" value="<?= substr($posted['thoiDiemKetThucDuKien'], 0, 10) ?>">
            </div>
        </div>
        <div class="cukcuk-modal-footer">
            <button type="button" class="btn btn-ghost" onclick="cancelInitModal()" style="height: 36px; padding: 0 16px; font-size: 13.5px;">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="confirmInitModal()" style="height: 36px; padding: 0 18px; font-size: 13.5px; font-weight: 600;">Đồng ý</button>
        </div>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert" style="margin-bottom: 16px;">
        <?= e($flash['msg']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error" role="alert" style="margin-bottom: 16px;">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $er): ?>
                <li><?= e($er) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Khung thông báo lỗi Client Alert -->
<div id="uc24ClientAlert" class="alert alert-error" role="alert" style="display: none; margin-bottom: 16px;">
    <div style="display: flex; align-items: flex-start; gap: 10px;">
        <?= icon('alert', 20) ?>
        <div style="flex: 1;">
            <strong id="uc24ClientAlertTitle" style="display: block; margin-bottom: 4px;">Vui lòng kiểm tra lại thông tin:</strong>
            <ul id="uc24ClientAlertList" style="margin: 0; padding-left: 20px;"></ul>
        </div>
    </div>
</div>

<form method="post" id="uc24CreateForm" novalidate action="<?= e(url(uc_url('UC24', 'form.php') . '?mode=create&type=' . urlencode($posted['loaiKiemKe']))) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="mode" value="create">
    <input type="hidden" name="loaiKiemKe" id="form_loaiKiemKe" value="<?= e($posted['loaiKiemKe']) ?>">
    <input type="hidden" name="maKho" id="form_maKho" value="<?= e($posted['maKho']) ?>">
    <input type="hidden" name="thoiDiemBatDau" id="form_thoiDiemBatDau" value="<?= e($posted['thoiDiemBatDau']) ?>">
    <input type="hidden" name="thoiDiemKetThucDuKien" id="form_thoiDiemKetThucDuKien" value="<?= e($posted['thoiDiemKetThucDuKien']) ?>">

    <!-- 1. Khung trên chia 2 Box ngang (Tỷ lệ 7:3, cao bằng nhau) -->
    <div class="cukcuk-create-top-grid">
        <!-- Box 1 - "Thông tin chung" (Bên trái - 70%) -->
        <div class="card cukcuk-card" style="margin-bottom: 0;">
            <div class="card-head" style="height: 40px; padding: 0 16px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; box-sizing: border-box; background: #ffffff;">
                <h2 style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin: 0;">Thông tin chung</h2>
                <button type="button" class="btn btn-ghost btn-sm" onclick="openInitModal()" style="font-size: 12px; color: #2563eb; padding: 2px 8px; display: inline-flex; align-items: center; gap: 4px;">
                    <?= icon('edit', 13) ?> Thay đổi
                </button>
            </div>
            <div style="padding: 16px 18px; display: flex; flex-direction: column; gap: 16px; flex: 1;">
                <!-- HÀNG 1: Gom thông tin tĩnh (Kho kiểm kê, Loại kiểm kê, Ngày kiểm kê đến) -->
                <div style="display: grid; grid-template-columns: 1.4fr 1fr 1.2fr; gap: 20px;">
                    <!-- Cột 1: Kho kiểm kê -->
                    <div class="cukcuk-static-field">
                        <span class="cukcuk-static-label">Kho kiểm kê</span>
                        <span class="cukcuk-static-value"><?= e($selectedKhoName ?: 'Chưa chọn kho') ?></span>
                    </div>
                    <!-- Cột 2: Loại kiểm kê -->
                    <div class="cukcuk-static-field">
                        <span class="cukcuk-static-label">Loại kiểm kê</span>
                        <span class="cukcuk-static-value"><?= $posted['loaiKiemKe'] === 'THANH_PHAM' ? 'Thành phẩm' : 'Nguyên liệu' ?></span>
                    </div>
                    <!-- Cột 3: Ngày kiểm kê đến -->
                    <div class="cukcuk-static-field">
                        <span class="cukcuk-static-label">Ngày kiểm kê đến</span>
                        <span class="cukcuk-static-value"><?= e($displayEndDate) ?></span>
                    </div>
                </div>

                <!-- HÀNG 2: Khu vực kiểm kê và Vị trí lưu trữ -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: flex-start;">
                    <!-- Cột 1: Khu vực kiểm kê -->
                    <div>
                        <label for="create_maKhuVuc" class="cukcuk-field-label">Khu vực kiểm kê</label>
                        <select id="create_maKhuVuc" name="maKhuVuc" class="cukcuk-input-control" onchange="onScopeChange()">
                            <option value="">Toàn bộ khu vực</option>
                            <?php foreach ($areas as $ar): ?>
                                <option value="<?= e($ar['maKhuVuc']) ?>" <?= $posted['maKhuVuc'] === $ar['maKhuVuc'] ? 'selected' : '' ?>>
                                    <?= e($ar['tenKhuVuc']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Cột 2: Vị trí lưu trữ (nếu có) -->
                    <div>
                        <label for="create_maViTri" class="cukcuk-field-label">Vị trí lưu trữ</label>
                        <select id="create_maViTri" name="maViTri" class="cukcuk-input-control" onchange="onScopeChange()">
                            <option value="">Tất cả vị trí</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= e($loc['maViTri']) ?>" data-kv="<?= e($loc['maKhuVuc']) ?>" <?= $posted['maViTri'] === $loc['maViTri'] ? 'selected' : '' ?>>
                                    <?= e($loc['tenViTri']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- HÀNG 3: Ghi chú -->
                <div>
                    <label for="create_ghiChu" class="cukcuk-field-label">Ghi chú</label>
                    <textarea id="create_ghiChu" name="ghiChu" class="cukcuk-input-control" rows="2"
                              placeholder="Nhập ghi chú kiểm kê..." style="min-height: 56px;"><?= e($posted['ghiChu'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Box 2 - "Chứng từ" (Bên phải - 30%) -->
        <div class="card cukcuk-card" style="margin-bottom: 0;">
            <div class="card-head" style="height: 40px; padding: 0 16px; display: flex; align-items: center; border-bottom: 1px solid #e2e8f0; box-sizing: border-box; background: #ffffff;">
                <h2 style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin: 0;">Chứng từ</h2>
            </div>
            <div style="padding: 18px 20px; display: flex; flex-direction: column; gap: 20px; flex: 1; justify-content: flex-start;">
                <!-- Dòng 1: Mã biên bản -->
                <div class="cukcuk-static-field">
                    <span class="cukcuk-static-label">Mã biên bản</span>
                    <span class="cukcuk-static-value"><?= e($nextCodePreview) ?></span>
                </div>
                <!-- Dòng 2: Trạng thái (lấy đúng từ database) -->
                <div class="cukcuk-static-field">
                    <span class="cukcuk-static-label">Trạng thái</span>
                    <span class="cukcuk-static-value"><?= uc24_status_dot('Chờ kiểm kê') ?></span>
                </div>
                <!-- Dòng 3: Người lập -->
                <div class="cukcuk-static-field">
                    <span class="cukcuk-static-label">Người lập</span>
                    <span class="cukcuk-static-value"><?= e($empName) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Khu vực Chuyển Loại hàng và Bảng dữ liệu hàng/lô -->
    <!-- 2. Khối bảng danh sách hàng/lô theo loại đã chọn -->
    <div class="card cukcuk-card" style="margin-top: 16px; margin-bottom: 0; border-radius: var(--radius); overflow: hidden;">
        <!-- Tiêu đề loại hàng đã chọn: Nguyên vật liệu hoặc Thành phẩm -->
        <div class="cukcuk-clean-tab-bar">
            <span class="cukcuk-tab-item active">
                <?= $posted['loaiKiemKe'] === 'THANH_PHAM' ? 'Thành phẩm' : 'Nguyên liệu' ?>
            </span>
        </div>

        <!-- Bảng danh sách hàng/lô (Đồng bộ chuẩn 100% như bảng bên kiểm kê, không thanh cuộn ngang) -->
        <div class="table-wrap" style="overflow-x: hidden; margin: 0; max-height: 520px; overflow-y: auto;">
            <table class="table" id="lotTable">
                <thead>
                    <tr>
                        <th style="width: 18%;">Mã lô</th>
                        <th style="width: 42%;">Tên hàng hóa</th>
                        <th class="center" style="width: 14%;">Hạn sử dụng</th>
                        <th class="center" style="width: 10%;">Đơn vị tính</th>
                        <th class="num" style="width: 16%;">Theo sổ</th>
                    </tr>
                </thead>
                <tbody id="lotTableBody">
                <?php if (empty($posted['maKho'])): ?>
                    <tr id="emptyScopeRow">
                        <td colspan="5" style="text-align: center; padding: 48px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                <span style="display: inline-flex; width: 44px; height: 44px; border-radius: 50%; background: #e0ecff; color: #1859c4; align-items: center; justify-content: center;">
                                    <span aria-hidden="true" style="font-weight: 700; font-size: 20px;">i</span>
                                </span>
                                <b style="font-size: 14px; color: #0f172a;">Vui lòng chọn Kho kiểm kê để tải danh sách hàng hóa</b>
                                <span style="font-size: 13px; color: #64748b;">Bấm nút "Thay đổi" ở trên để chọn kho kiểm kê.</span>
                            </div>
                        </td>
                    </tr>
                <?php elseif (empty($scopeLots)): ?>
                    <tr id="emptyScopeRow">
                        <td colspan="5" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                <span style="display: inline-flex; width: 44px; height: 44px; border-radius: 50%; background: #fef3c7; color: #b45309; align-items: center; justify-content: center;">
                                    <?= icon('alert', 22) ?>
                                </span>
                                <b style="font-size: 14px; color: #0f172a;">Không tìm thấy lô hàng tồn kho</b>
                                <span style="font-size: 13px; color: #64748b;">Không có lô hàng nào thuộc loại <b><?= e($typeLabel) ?></b> trong phạm vi đã chọn.</span>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $totalScopeQty = 0;
                    foreach ($scopeLots as $lot):
                        $hsdText = !empty($lot['hanSuDung']) ? date('d/m/Y', strtotime($lot['hanSuDung'])) : '—';
                        $totalScopeQty += (float)$lot['soLuongTon'];
                    ?>
                        <tr class="lot-row" data-kv="<?= e($lot['maKhuVuc']) ?>" data-vt="<?= e($lot['maViTri']) ?>" data-qty="<?= (float)$lot['soLuongTon'] ?>">
                            <td style="font-weight: 600; color: #1e293b;"><?= e($lot['maLoHang']) ?></td>
                            <td style="font-weight: 500;"><?= e($lot['tenHang']) ?></td>
                            <td class="center"><?= e($hsdText) ?></td>
                            <td class="center"><?= e($lot['donViTinh']) ?></td>
                            <td class="num" style="font-weight: 700; color: #0f172a;"><?= fmt_qty($lot['soLuongTon']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($scopeLots)): ?>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align: right; padding: 11px 14px; color: #0f172a; font-weight: 700; background: #fafbfd; border-top: 1px solid var(--line); border-bottom: none;">Tổng theo sổ:</td>
                        <td class="num" style="padding: 11px 14px; color: #0f172a; font-weight: 700; font-size: 13.5px; background: #fafbfd; border-top: 1px solid var(--line); border-bottom: none;" id="totalBookQty"><?= fmt_qty($totalScopeQty) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- Footer nút thao tác: Đóng & Lập biên bản -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 12px 18px; border-top: 1px solid var(--line); background: #ffffff;">
            <a href="<?= e(url(uc_url('UC24'))) ?>" class="btn btn-ghost" style="height: 36px; padding: 0 18px; font-size: 13.5px; font-weight: 500;">
                Đóng
            </a>
            <button type="button" class="btn btn-primary" id="btnLapBienBan" onclick="handleLapBienBan()" style="height: 36px; padding: 0 20px; font-size: 13.5px; font-weight: 600;">
                Lập biên bản
            </button>
        </div>
    </div>
</form>

<script>
// Dữ liệu kho theo loại
var WAREHOUSES_BY_TYPE = {
    'NGUYEN_LIEU': <?= json_encode($allWhRaw, JSON_UNESCAPED_UNICODE) ?>,
    'THANH_PHAM': <?= json_encode($allWhProd, JSON_UNESCAPED_UNICODE) ?>
};

// Dữ liệu vị trí lưu trữ trong kho hiện tại
var ALL_LOCATIONS = <?= json_encode($locations, JSON_UNESCAPED_UNICODE) ?>;

// Đổi loại kiểm kê trong modal
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

function renderModalWarehouses(type, selectedKho) {
    var selKho = document.getElementById('modal_maKho');
    if (!selKho) return;
    selKho.innerHTML = '';
    var list = WAREHOUSES_BY_TYPE[type] || [];
    list.forEach(function (wh) {
        var opt = document.createElement('option');
        opt.value = wh.maKho;
        opt.textContent = wh.tenKho;
        if (selectedKho && selectedKho === wh.maKho) {
            opt.selected = true;
        }
        selKho.appendChild(opt);
    });
}

function openInitModal() {
    var modal = document.getElementById('initKiemKeModal');
    if (modal) {
        modal.style.display = 'flex';
        var currentType = <?= json_encode($posted['loaiKiemKe']) ?>;
        var currentKho = <?= json_encode($posted['maKho']) ?>;
        renderModalWarehouses(currentType, currentKho);
    }
}

function closeInitModal() {
    var modal = document.getElementById('initKiemKeModal');
    if (modal) modal.style.display = 'none';
}

function cancelInitModal() {
    var currentKho = <?= json_encode($posted['maKho']) ?>;
    if (!currentKho) {
        window.location.href = <?= json_encode(url(uc_url('UC24'))) ?>;
    } else {
        closeInitModal();
    }
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
        alert('Vui lòng chọn ngày kết thúc kiểm kê.');
        return;
    }

    var baseUrl = <?= json_encode(url(uc_url('UC24', 'form.php'))) ?>;
    var url = baseUrl + '?mode=create'
        + '&type=' + encodeURIComponent(type)
        + '&kho=' + encodeURIComponent(maKho)
        + '&end=' + encodeURIComponent(end);
    window.location.href = url;
}

// Lọc động bảng hàng/lô và dropdown Vị trí lưu trữ theo Kho → Khu vực → Vị trí
function onScopeChange() {
    var selKv = document.getElementById('create_maKhuVuc');
    var selVt = document.getElementById('create_maViTri');
    var selectedKv = selKv ? selKv.value : '';
    var selectedVt = selVt ? selVt.value : '';

    // Cập nhật các option của dropdown Vị trí theo Khu vực
    if (selVt && ALL_LOCATIONS) {
        var prevVt = selVt.value;
        selVt.innerHTML = '<option value="">Tất cả vị trí</option>';
        ALL_LOCATIONS.forEach(function (loc) {
            if (!selectedKv || loc.maKhuVuc === selectedKv) {
                var opt = document.createElement('option');
                opt.value = loc.maViTri;
                opt.textContent = loc.tenViTri;
                opt.setAttribute('data-kv', loc.maKhuVuc);
                if (loc.maViTri === prevVt) {
                    opt.selected = true;
                }
                selVt.appendChild(opt);
            }
        });
        selectedVt = selVt.value;
    }

    // Lọc các dòng hàng trong bảng
    var rows = document.querySelectorAll('#lotTableBody .lot-row');
    var visibleCount = 0;
    var sumQty = 0;

    rows.forEach(function (row) {
        var rowKv = row.getAttribute('data-kv');
        var rowVt = row.getAttribute('data-vt');
        var matchKv = !selectedKv || rowKv === selectedKv;
        var matchVt = !selectedVt || rowVt === selectedVt;

        if (matchKv && matchVt) {
            row.style.display = '';
            visibleCount++;
            sumQty += parseFloat(row.getAttribute('data-qty')) || 0;
        } else {
            row.style.display = 'none';
        }
    });

    // Cập nhật tổng số lượng tồn hệ thống ở chân bảng
    var totalElem = document.getElementById('totalBookQty');
    if (totalElem) {
        totalElem.textContent = new Intl.NumberFormat('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(sumQty);
    }
}

// Xử lý bấm nút "Lập biên bản"
function handleLapBienBan() {
    var maKho = document.getElementById('form_maKho').value;
    if (!maKho) {
        alert('Vui lòng chọn Kho kiểm kê.');
        return;
    }

    var visibleRows = document.querySelectorAll('#lotTableBody .lot-row:not([style*="display: none"])');
    if (visibleRows.length === 0) {
        alert('Không có lô hàng tồn kho nào trong phạm vi đã chọn (Kho → Khu vực → Vị trí) để lập biên bản.');
        return;
    }

    var bbCode = <?= json_encode($nextCodePreview) ?>;
    if (confirm("Xác nhận lập biên bản kiểm kê " + bbCode + " với " + visibleRows.length + " lô hàng trong phạm vi đã chọn?")) {
        document.getElementById('uc24CreateForm').submit();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var initialKho = <?= json_encode($posted['maKho']) ?>;
    if (!initialKho) {
        openInitModal();
    } else {
        renderModalWarehouses(<?= json_encode($posted['loaiKiemKe']) ?>, initialKho);
    }
});
</script>

<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
