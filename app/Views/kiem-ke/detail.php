<?php
/**
 * View kiem-ke/detail — UC24: Chi tiết biên bản kiểm kê.
 * Thiết kế giao diện phẳng, tinh gọn chuẩn MISA CukCuk / FeedWare theo mẫu tham khảo:
 * - Tiêu đề trang: Chi tiết <Mã biên bản> + Badge trạng thái + Nút thao tác (Quay lại, In, Hủy, Sửa)
 * - Card 1: Thông tin chung (Mã biên bản, Kho, Khu vực, Ngày kiểm kê, Loại kiểm kê, Người lập, Ghi chú)
 * - Card 2: Danh sách lô hàng kiểm kê (vừa khít bảng, Mã lô, Tên hàng, Vị trí, HSD, ĐVT, Số lượng, Kiểm kê, Chênh lệch, Tình trạng)
 * - Đã loại bỏ hoàn toàn khối thống kê và khối dòng chênh lệch cần xử lý.
 */

$daGhi = (int)($bb['soDaGhi'] ?? 0);
$soChenhLech = (int)($bb['soChenhLech'] ?? 0);
$trangThai = $bb['trangThai'] ?? '';

$isPending = in_array($trangThai, ['Chờ xử lý', 'Từ chối'], true);
$isReviewing = ($trangThai === 'Đang xử lý'); // Chờ xác nhận
$isCompleted = in_array($trangThai, ['Hoàn tất', 'Đã hủy'], true);

$isManager = can('kiem-ke', 'create') || can('kiem-ke', 'handle') || can('kiem-ke', 'reconcile');
$isStaff = can('kiem-ke', 'record') && (\Core\Auth::role() !== ROLE_MANAGER);

$canEdit = $isManager && ($trangThai === 'Chờ xử lý') && ($daGhi === 0);
$canCancel = $isManager && ($trangThai === 'Chờ xử lý') && ($daGhi === 0);
$canRecord = $isStaff && in_array($trangThai, ['Chờ xử lý', 'Từ chối'], true);
$canReconcile = can('kiem-ke', 'reconcile') && ($isPending || $isReviewing);
$canReview = $isManager && $isReviewing;

// Kiểm tra xem biên bản có dòng chênh lệch nào không
$coChenhLech = false;
foreach ($scopeItems as $it) {
    if ($it['maKetQuaKiemKe'] !== null && $it['soLuongThucTe'] !== null && abs((float)$it['chenhLech']) > 0.000001) {
        $coChenhLech = true;
        break;
    }
}
if ($soChenhLech > 0) {
    $coChenhLech = true;
}
if (!empty($chenhLech)) {
    foreach ($chenhLech as $cl) {
        if (abs((float)$cl['chenhLech']) > 0.000001) {
            $coChenhLech = true;
            break;
        }
    }
}

// Nút Đối chiếu chênh lệch hiển thị khi:
// 1. Biên bản có chênh lệch ($coChenhLech = true)
// 2. Người dùng có quyền ($canReconcile = true)
// 3. Chưa vừa thực hiện lưu đối chiếu xong trong phiên này (khi vừa lưu xong -> tự động ẩn nút)
$daLuuDoiChieu = !empty($_SESSION['da_doi_chieu_' . $bb['maBienBanKiemKe']]);
$canShowReconcileBtn = $canReconcile && $coChenhLech && !$daLuuDoiChieu;
?>

<!-- 1. TIÊU ĐỀ TRANG & THANH THAO TÁC -->
<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <h1 style="margin: 0; font-size: 22px; font-weight: 750; color: #0f172a; letter-spacing: -0.3px;">
            Chi tiết <?= e($bb['maBienBanKiemKe']) ?>
        </h1>
    </div>
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;" class="no-print">
        <!-- Nút quay lại danh sách biên bản (chỉ icon mũi tên quay về, không chữ) -->
        <a class="btn btn-ghost" href="<?= e(route('kiem-ke')) ?>" title="Quay lại" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px; text-decoration: none; font-size: 16px;">
            <?= icon('arrow-left', 16) ?>
        </a>

        <!-- Nút In biên bản Mẫu 05-VT ở tab riêng -->
        <a class="btn btn-ghost" href="<?= e(route('kiem-ke/print', ['ma' => $bb['maBienBanKiemKe']])) ?>" target="_blank" rel="noopener" title="In biên bản" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px; text-decoration: none;">
            <?= icon('printer', 16) ?>
        </a>

        <!-- Nút Hủy biên bản (chỉ khi chưa ghi nhận kết quả và có quyền) -->
        <?php if ($canCancel): ?>
            <button type="button" class="btn btn-ghost" title="Hủy biên bản" onclick="confirmCancel('<?= e($bb['maBienBanKiemKe']) ?>')" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #fecaca; color: #b91c1c; border-radius: 6px; cursor: pointer;">
                <?= icon('trash', 16) ?>
            </button>
        <?php endif; ?>

        <!-- Nút Sửa biên bản (nếu còn đang xử lý) -->
        <?php if ($canEdit): ?>
            <a class="btn btn-ghost" href="<?= e(route('kiem-ke/edit', ['ma' => $bb['maBienBanKiemKe']])) ?>" title="Chỉnh sửa biên bản" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid #d0d5dd; color: #344054; text-decoration: none;">
                <?= icon('edit', 16) ?>
            </a>
        <?php endif; ?>

        <!-- Nút Đối chiếu chênh lệch: màu xanh, bỏ icon, khi có chênh lệch mới hiện -->
        <?php if ($canShowReconcileBtn): ?>
            <a class="btn" href="<?= e(route('kiem-ke/reconcile', ['ma' => $bb['maBienBanKiemKe']])) ?>" style="height: 36px; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; background: #2563eb; color: #ffffff; text-decoration: none; font-size: 13.5px; font-weight: 600; border: none;">
                Đối chiếu chênh lệch
            </a>
        <?php endif; ?>

        <!-- Nút Từ chối (màu đỏ) & Xác nhận (màu xanh lá) cho Quản lý kho, bỏ icon -->
        <?php if ($canReview): ?>
            <button type="button" class="btn" onclick="confirmReturn('<?= e($bb['maBienBanKiemKe']) ?>')" style="height: 36px; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: none; background: #dc2626; color: #ffffff; font-size: 13.5px; font-weight: 600; cursor: pointer;">
                Từ chối
            </button>
            <button type="button" class="btn" onclick="confirmAccept('<?= e($bb['maBienBanKiemKe']) ?>')" style="height: 36px; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: none; background: #16a34a; color: #ffffff; font-size: 13.5px; font-weight: 600; cursor: pointer;">
                Xác nhận
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- 2. THÔNG TIN CHUNG CHIA 2 BẢNG GỌN ĐỒNG BỘ (1:1) -->
<?php
render_kv_pair_cards(
    [
        'title' => 'Phạm vi kiểm kê',
        'rows'  => [
            'Kho'          => (string)$bb['tenKho'],
            'Khu vực'      => !empty($bb['tenKhuVuc']) ? (string)$bb['tenKhuVuc'] : 'Toàn kho',
            'Loại kiểm kê' => (string)inventory_type_label($bb['loaiKiemKe'] ?? ''),
            'Ghi chú'      => !empty($bb['ghiChu']) ? (string)$bb['ghiChu'] : '—',
        ],
    ],
    [
        'title'       => 'Thông tin chung',
        'headerRight' => inventory_status_badge($bb['trangThai']),
        'rows'        => [
            'Mã biên bản'     => (string)$bb['maBienBanKiemKe'],
            'Ngày kiểm kê'    => !empty($bb['ngayBatDau']) ? date('d/m/Y', strtotime($bb['ngayBatDau'])) : '—',
            'Người lập'       => (string)($bb['tenTruongDoan'] ?? '—'),
            'Số lô kiểm đếm'  => count($scopeItems) . ' lô',
        ],
    ]
);
?>

<!-- 3. CARD DANH SÁCH LÔ HÀNG KIỂM KÊ -->
<div class="card cukcuk-card" style="padding: 22px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
    <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0;">
        Danh sách lô hàng kiểm kê (<?= count($scopeItems) ?> lô)
    </h2>

    <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #ffffff;">
        <table class="table" style="table-layout: fixed; width: 100%; margin: 0; border-collapse: collapse;">
            <thead>
                <tr style="background: #fafbfd; border-bottom: 1px solid #e2e8f0;">
                    <th style="width: 12%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Mã lô</th>
                    <th style="width: 22%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Tên hàng hóa</th>
                    <th style="width: 16%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Vị trí</th>
                    <th style="width: 10%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: center;">Hạn sử dụng</th>
                    <th style="width: 6%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: center;">ĐVT</th>
                    <th style="width: 8%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: right;">Sổ sách</th>
                    <th style="width: 8%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #2563eb; text-align: right;">Kiểm kê</th>
                    <th style="width: 9%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: center;">Chênh lệch</th>
                    <th style="width: 9%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: center; white-space: nowrap;">Tình trạng</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($scopeItems)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 32px; color: #64748b;">
                        Không có lô hàng nào trong phạm vi biên bản kiểm kê này.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($scopeItems as $idx => $item):
                    $daKiem = ($item['maKetQuaKiemKe'] !== null && $item['soLuongThucTe'] !== null);
                    $soSach = (float)($daKiem ? $item['soLuongSoSach'] : $item['soLuongSoSachHienTai']);
                    $thucTe = $daKiem ? (float)$item['soLuongThucTe'] : null;
                    $diff = $daKiem ? (float)$item['chenhLech'] : null;
                ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px; color: #0f172a; font-weight: 500; font-size: 13px;"><?= e($item['maLoHang']) ?></td>
                        <td style="padding: 10px; color: #0f172a; font-weight: 400; font-size: 13px;">
                            <?= e($item['tenHangHoa']) ?>
                        </td>
                        <td style="padding: 10px; color: #334155; font-weight: 400; font-size: 13px;">
                            <?= e($item['tenViTri'] ?? $item['maViTri']) ?>
                        </td>
                        <td style="padding: 10px; text-align: center; color: #475467; font-size: 12.5px;"><?= !empty($item['ngayHetHan']) ? date('d/m/Y', strtotime($item['ngayHetHan'])) : '—' ?></td>
                        <td style="padding: 10px; text-align: center; color: #475467; font-size: 12.5px;"><?= e($item['donViTinhChuan'] ?? 'kg') ?></td>
                        <td style="padding: 10px; text-align: right; font-weight: 500; color: #0f172a; font-size: 13px;"><?= fmt_qty($soSach) ?></td>
                        <td style="padding: 10px; text-align: right;">
                            <?php if ($daKiem): ?>
                                <span style="color: #2563eb; font-weight: 500; font-size: 13px;"><?= fmt_qty($thucTe) ?></span>
                            <?php else: ?>
                                <span style="color: #94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px; text-align: center; font-size: 13px;">
                            <?php if ($daKiem): ?>
                                <?php if (abs($diff) < 0.000001): ?>
                                    <span style="color: #16a34a; font-weight: 400;">0</span>
                                <?php elseif ($diff > 0): ?>
                                    <span style="color: #0284c7; font-weight: 400;">+<?= fmt_qty($diff) ?></span>
                                <?php else: ?>
                                    <span style="color: #dc2626; font-weight: 400;">-<?= fmt_qty(abs($diff)) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px; text-align: center;">
                            <?php if (!$daKiem): ?>
                                <span class="badge" style="background: #f1f5f9; color: #64748b;">Chưa kiểm</span>
                            <?php elseif (abs($diff) < 0.000001): ?>
                                <span class="badge" style="background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7;">Khớp</span>
                            <?php elseif ($diff > 0): ?>
                                <span class="badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">Thừa</span>
                            <?php else: ?>
                                <span class="badge" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">Thiếu</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmAccept(ma) {
    fwModal.confirm({
        title: 'Xác nhận hoàn thành biên bản',
        htmlMessage: 'Bạn có chắc chắn muốn xác nhận hoàn thành biên bản kiểm kê <strong>' + ma + '</strong>? Biên bản sẽ được đóng và chuyển sang trạng thái "Đã hoàn thành".',
        tone: 'success',
        icon: '✓',
        okText: 'Xác nhận',
        okColor: '#16a34a',
        onConfirm: function () {
            fwModal.postForm('<?= e(route('kiem-ke/close')) ?>', {
                csrf_token: '<?= e(csrf_token()) ?>',
                ma: ma
            });
        }
    });
}

function confirmReturn(ma) {
    fwModal.prompt({
        title: 'Từ chối kết quả kiểm kê',
        htmlMessage: 'Nhập lý do từ chối biên bản kiểm kê <strong>' + ma + '</strong> để nhân viên kiểm đếm điều chỉnh:',
        label: 'Lý do từ chối',
        placeholder: 'Nhập lý do từ chối...',
        required: true,
        requiredMsg: 'Vui lòng nêu lý do từ chối để nhân viên kiểm đếm điều chỉnh.',
        tone: 'danger',
        icon: '!',
        okText: 'Từ chối',
        okColor: '#dc2626',
        onConfirm: function (lyDo) {
            fwModal.postForm('<?= e(route('kiem-ke/return')) ?>', {
                csrf_token: '<?= e(csrf_token()) ?>',
                ma: ma,
                lyDo: lyDo
            });
        }
    });
}

function confirmCancel(ma) {
    fwModal.prompt({
        title: 'Hủy biên bản kiểm kê',
        htmlMessage: 'Bạn có chắc chắn muốn hủy biên bản kiểm kê <strong>' + ma + '</strong>? Vui lòng nhập lý do hủy:',
        label: 'Lý do hủy biên bản',
        placeholder: 'Nhập lý do hủy...',
        required: true,
        requiredMsg: 'Vui lòng nhập lý do hủy biên bản.',
        tone: 'danger',
        icon: '!',
        okText: 'Xác nhận hủy',
        okColor: '#dc2626',
        onConfirm: function (lyDo) {
            fwModal.postForm('<?= e(route('kiem-ke/cancel')) ?>', {
                csrf_token: '<?= e(csrf_token()) ?>',
                ma: ma,
                lyDo: lyDo
            });
        }
    });
}
</script>
