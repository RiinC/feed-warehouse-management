<?php
/**
 * View hang-bat-thuong/detail — UC28: Chi tiết hàng bất thường và phương án xử lý.
 * Thiết kế giao diện phẳng, tinh gọn chuẩn MISA CukCuk / FeedWare đồng bộ với Kiểm kê kho:
 * - 1. Tiêu đề + Trạng thái + Nút thao tác (Quay lại, In, Đề xuất phương án)
 * - 2. Card 1: Thông tin chung dạng bảng 4 cột phẳng (Hàng 1: Mã, Hàng hóa, Lô hàng/HSD, Vị trí/Kho; Hàng 2: Loại, Số lượng, Người phát hiện, Mô tả)
 * - 3. Card 2: Danh sách phương án xử lý (Lịch sử, trạng thái, nút Duyệt, Từ chối, Thi hành)
 */

$badgeTT = [
    'Đang cách ly' => 'badge badge-warn',
    'Đang xử lý'   => 'badge badge-info',
    'Đã xử lý'     => 'badge badge-ok',
];
$badgeLoai = [
    'Rách vỡ' => 'badge badge-warn',
    'Hư hỏng' => 'badge badge-danger',
    'Hết hạn' => 'badge badge-danger',
];

$dangXuLy = null;
foreach ($phuongAn as $pa) {
    if (in_array($pa['trangThai'], ['Chờ xử lý', 'Đang xử lý'], true)) {
        $dangXuLy = $pa;
        break;
    }
}
$daKetThuc = ($hbt['trangThai'] === 'Đã xử lý');
$canRecord = can('hang-bat-thuong', 'record');
$canPropose = can('hang-bat-thuong', 'propose');
$canHandle = can('hang-bat-thuong', 'handle');
$canExecute = can('hang-bat-thuong', 'execute');
$currentStaffId = \Core\Auth::staffId();
?>

<style>
.cukcuk-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 22px 24px;
    margin-bottom: 20px;
}
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
    background: #f1f5f9;
    color: #475569;
}
.badge-info {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.badge-ok {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #dcfce7;
}
.badge-warn {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #ffedd5;
}
.badge-danger {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

/* Modal Popup chuẩn */
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
    padding: 16px;
    box-sizing: border-box;
}
.cukcuk-modal-box {
    background: #ffffff;
    width: 500px;
    max-width: 95vw;
    border-radius: 10px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.05);
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
    padding: 16px 22px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
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
    padding: 4px 8px;
    border-radius: 4px;
    transition: all .12s ease;
}
.cukcuk-modal-close:hover {
    color: #0f172a;
    background: #f1f5f9;
}
.cukcuk-modal-body {
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: #ffffff;
}
.cukcuk-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 22px;
    background: #ffffff;
    border-top: 1px solid #f1f5f9;
}
</style>

<!-- 1. TIÊU ĐỀ TRANG & THANH THAO TÁC -->
<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <h1 style="margin: 0; font-size: 22px; font-weight: 750; color: #0f172a; letter-spacing: -0.3px;">
            Chi tiết <?= e($hbt['maHangBatThuong']) ?>
        </h1>
    </div>

    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;" class="no-print">
        <!-- Nút quay lại danh sách -->
        <a class="btn btn-ghost" href="<?= e(route('hang-bat-thuong')) ?>" title="Quay lại danh sách" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px; text-decoration: none; font-size: 16px;">
            <span aria-hidden="true" style="line-height: 1;">←</span>
        </a>

        <!-- Nút In chứng từ A4 ở tab riêng -->
        <a class="btn btn-ghost" href="<?= e(route('hang-bat-thuong/print', ['ma' => $hbt['maHangBatThuong']])) ?>" target="_blank" rel="noopener" title="In chứng từ" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px; text-decoration: none;">
            <?= icon('printer', 16) ?>
        </a>

        <!-- Nút Đề xuất phương án: màu xanh, không icon -->
        <?php if ($canPropose && !$daKetThuc && !$dangXuLy): ?>
            <button type="button" class="btn btn-primary" onclick="openProposeModal()" style="height: 36px; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; background: #2563eb; color: #ffffff; font-size: 13.5px; font-weight: 600; border: none; cursor: pointer;">
                Đề xuất phương án
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Cảnh báo nếu lô hàng không còn tồn kho tại vị trí -->
<?php if (!$hbt['coDongTon']): ?>
    <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; color: #92400e; font-size: 13px;">
        <?= icon('alert-triangle', 18) ?>
        <span><b>Lưu ý:</b> Lô hàng này hiện không còn số lượng tồn tại vị trí lưu trữ trên sổ sách. Hãy kiểm tra lại trước khi thi hành các biện pháp giảm tồn.</span>
    </div>
<?php endif; ?>

<!-- 2. THÔNG TIN CHUNG CHIA 2 BẢNG GỌN ĐỒNG BỘ (1:1) -->
<?php
$hbtBadgeHtml = '<span class="' . e($badgeTT[$hbt['trangThai']] ?? 'badge') . '">' . e($hbt['trangThai']) . '</span>';
render_kv_pair_cards(
    [
        'title' => 'Thông tin lô hàng',
        'rows'  => [
            'Hàng hóa'        => (string)$hbt['tenHangHoa'],
            'Mã lô hàng'      => (string)$hbt['maLoHang'],
            'Khu vực'         => (string)(!empty($hbt['tenKhuVuc']) ? $hbt['tenKhuVuc'] : ($hbt['tenKho'] ?? '—')),
            'Loại bất thường' => (string)$hbt['loaiBatThuong'],
        ],
    ],
    [
        'title'       => 'Thông tin chung',
        'headerRight' => $hbtBadgeHtml,
        'rows'        => [
            'Mã bản ghi'      => (string)$hbt['maHangBatThuong'],
            'Số lượng'        => number_format((float)$hbt['soLuong'], 0, ',', '.') . ' ' . $hbt['donViTinhChuan'],
            'Người phát hiện' => (string)($hbt['tenNhanVienPhatHien'] ?? '—'),
            'Mô tả chi tiết'  => !empty($hbt['moTa']) ? (string)$hbt['moTa'] : '—',
        ],
    ]
);
?>

<!-- 3. CARD DANH SÁCH PHƯƠNG ÁN XỬ LÝ -->
<div class="card cukcuk-card" style="padding: 22px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">
            Danh sách phương án xử lý (<?= count($phuongAn) ?>)
        </h2>
    </div>

    <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #ffffff;">
        <table class="table" style="table-layout: fixed; width: 100%; margin: 0; border-collapse: collapse;">
            <thead>
                <tr style="background: #fafbfd; border-bottom: 1px solid #e2e8f0;">
                    <th style="width: 13%; padding: 12px 14px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Mã phương án</th>
                    <th style="width: 21%; padding: 12px 14px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Biện pháp xử lý</th>
                    <th style="width: 18%; padding: 12px 14px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Người đề xuất</th>
                    <th style="width: 19%; padding: 12px 14px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Người phê duyệt</th>
                    <th style="width: 12%; padding: 12px 14px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: center;">Trạng thái</th>
                    <th style="width: 17%; padding: 12px 14px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($phuongAn)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 36px; color: #64748b;">
                        Chưa có phương án xử lý nào được đề xuất cho bản ghi hàng bất thường này.
                        <?php if ($canPropose && !$daKetThuc && !$dangXuLy): ?>
                            <div style="margin-top: 12px;">
                                <button type="button" class="btn btn-primary btn-sm" onclick="openProposeModal()" style="height: 32px; padding: 0 16px; border-radius: 6px; font-size: 13px; font-weight: 600; background: #2563eb; color: #ffffff; border: none; cursor: pointer;">
                                    Tạo đề xuất phương án
                                </button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($phuongAn as $pa):
                    $isChoDuyet  = ($pa['trangThai'] === 'Chờ xử lý');
                    $isDaDuyet   = ($pa['trangThai'] === 'Đang xử lý');
                    $isDaThiHanh = ($pa['trangThai'] === 'Hoàn tất');
                    $isTuChoi    = ($pa['trangThai'] === 'Từ chối');
                    $isDaHuy     = ($pa['trangThai'] === 'Đã hủy');
                ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 14px; font-weight: 600; color: #0f172a; font-size: 13px;">
                            <?= e($pa['maPhuongAn']) ?>
                        </td>
                        <td style="padding: 12px 14px;">
                            <strong style="color: #0f172a; font-size: 13px;"><?= e(resolution_method_label($pa['bienPhapXuLy'])) ?></strong>
                            <?php if (!empty($pa['moTa'])): ?>
                                <div style="font-size: 12px; color: #475467; margin-top: 3px; line-height: 1.4;"><?= e($pa['moTa']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px 14px;">
                            <div style="color: #0f172a; font-weight: 500; font-size: 13px;"><?= e((string)$pa['tenNguoiDeXuat']) ?></div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;"><?= !empty($pa['ngayDeXuat']) ? date('d/m/Y H:i', strtotime($pa['ngayDeXuat'])) : '—' ?></div>
                        </td>
                        <td style="padding: 12px 14px;">
                            <?php if (!empty($pa['tenNguoiPheDuyet'])): ?>
                                <div style="color: #0f172a; font-weight: 500; font-size: 13px;"><?= e($pa['tenNguoiPheDuyet']) ?></div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;"><?= !empty($pa['ngayPheDuyet']) ? date('d/m/Y H:i', strtotime($pa['ngayPheDuyet'])) : '—' ?></div>
                                <?php if (!empty($pa['yKienPheDuyet'])): ?>
                                    <div style="font-size: 12px; color: #475467; font-style: italic; margin-top: 3px;">"<?= e($pa['yKienPheDuyet']) ?>"</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <?= proposal_status_badge($pa['trangThai']) ?>
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <?php if ($isChoDuyet && ($canHandle || ($canPropose && ($pa['maNhanVienDeXuat'] ?? '') === $currentStaffId))): ?>
                                <div style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: nowrap;">
                                    <?php if ($isChoDuyet && $canHandle): ?>
                                        <button type="button" class="btn btn-sm" onclick="doApprove('<?= e($pa['maPhuongAn']) ?>')" style="height: 30px; padding: 0 12px; font-size: 12.5px; font-weight: 600; background: #16a34a; color: #ffffff; border: none; border-radius: 6px; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center;">
                                            Duyệt
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="doReject('<?= e($pa['maPhuongAn']) ?>')" style="height: 30px; padding: 0 12px; font-size: 12.5px; font-weight: 600; background: #dc2626; color: #ffffff; border: none; border-radius: 6px; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center;">
                                            Từ chối
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($isChoDuyet && $canPropose && ($pa['maNhanVienDeXuat'] ?? '') === $currentStaffId): ?>
                                        <button type="button" class="btn btn-sm" onclick="doCancel('<?= e($pa['maPhuongAn']) ?>')" style="height: 30px; padding: 0 12px; font-size: 12.5px; font-weight: 600; background: #f1f5f9; color: #475467; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center;">
                                            Hủy
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($isDaDuyet && $canExecute): ?>
                                <button type="button" class="btn btn-sm" onclick="doExecute('<?= e($pa['maPhuongAn']) ?>')" style="height: 30px; padding: 0 14px; font-size: 12.5px; font-weight: 600; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center;">
                                    Thi hành
                                </button>
                            <?php elseif ($isDaThiHanh): ?>
                                <span style="font-size: 12.5px; color: #16a34a; font-weight: 600;">Đã thi hành</span>
                            <?php elseif ($isTuChoi): ?>
                                <span style="font-size: 12.5px; color: #dc2626; font-weight: 500;">Từ chối</span>
                            <?php elseif ($isDaHuy): ?>
                                <span style="font-size: 12.5px; color: #64748b; font-weight: 500;">Đã hủy</span>
                            <?php else: ?>
                                <span style="color: #94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($phieu)): ?>
<!-- 3b. CARD PHIẾU ĐIỀU CHỈNH TỒN KHO ĐÃ PHÁT SINH -->
<div class="card cukcuk-card" style="padding: 22px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">
            Phiếu điều chỉnh tồn kho đã phát sinh (<?= count($phieu) ?>)
        </h2>
        <a href="<?= e(route('ton-kho')) ?>" style="font-size: 13px; color: #2563eb; font-weight: 600; text-decoration: none;">
            Xem tồn kho →
        </a>
    </div>
    <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #ffffff;">
        <table class="table" style="table-layout: fixed; width: 100%; margin: 0; border-collapse: collapse;">
            <thead>
                <tr style="background: #fafbfd; border-bottom: 1px solid #e2e8f0;">
                    <th style="width: 16%; padding: 12px 14px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Mã phiếu</th>
                    <th style="width: 22%; padding: 12px 14px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Biện pháp</th>
                    <th style="width: 12%; padding: 12px 14px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: center;">Loại</th>
                    <th style="width: 12%; padding: 12px 14px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: right;">Số lượng</th>
                    <th style="width: 18%; padding: 12px 14px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Người thi hành</th>
                    <th style="width: 20%; padding: 12px 14px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($phieu as $pd): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 14px; font-weight: 600; color: #0f172a; font-size: 13px;">
                            <?= e($pd['maPhieuDieuChinh']) ?>
                        </td>
                        <td style="padding: 12px 14px; font-size: 13px; color: #334155;">
                            <?= e(resolution_method_label($pd['bienPhapXuLy'])) ?>
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <span class="badge <?= ($pd['loaiDieuChinh'] === 'Tang' || $pd['loaiDieuChinh'] === 'Tăng') ? 'badge-ok' : 'badge-danger' ?>">
                                <?= e(adjustment_type_label($pd['loaiDieuChinh'])) ?>
                            </span>
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-weight: 600; color: #0f172a; font-size: 13px;">
                            <?= fmt_number($pd['soLuongDieuChinh'], 2) ?>
                        </td>
                        <td style="padding: 12px 14px;">
                            <div style="color: #0f172a; font-weight: 500; font-size: 13px;"><?= e((string)$pd['tenNhanVienThiHanh']) ?></div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;"><?= !empty($pd['ngayThiHanh']) ? date('d/m/Y H:i', strtotime($pd['ngayThiHanh'])) : '—' ?></div>
                        </td>
                        <td style="padding: 12px 14px; font-size: 12.5px; color: #475467;">
                            <?= e((string)($pd['ghiChu'] ?? '—')) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- 4. MODAL ĐỀ XUẤT PHƯƠNG ÁN XỬ LÝ -->
<div id="proposeModal" class="cukcuk-modal-backdrop no-print" style="display: none;" onclick="handleProposeModalBackdrop(event)">
    <div class="cukcuk-modal-box">
        <div class="cukcuk-modal-header">
            <h3 class="cukcuk-modal-title">Đề xuất phương án xử lý</h3>
            <button type="button" class="cukcuk-modal-close" onclick="closeProposeModal()" title="Đóng">&times;</button>
        </div>
        <form method="post" action="<?= e(route('hang-bat-thuong/propose')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="ma" value="<?= e($hbt['maHangBatThuong']) ?>">
            <input type="hidden" name="maHangBatThuong" value="<?= e($hbt['maHangBatThuong']) ?>">

            <div class="cukcuk-modal-body">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; font-size: 13px; font-weight: 400; color: #334155;">
                    Bản ghi: <?= e($hbt['maHangBatThuong']) ?> · 
                    Hàng: <?= e($hbt['tenHangHoa']) ?> · 
                    Số lượng: <?= number_format((float)$hbt['soLuong'], 0, ',', '.') ?> <?= e($hbt['donViTinhChuan']) ?>
                </div>

                <div>
                    <label for="modal_bienPhap" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                        Biện pháp xử lý <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="modal_bienPhap" name="bienPhapXuLy" required style="width: 100%; height: 38px; padding: 0 12px; font-size: 13.5px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                        <?php foreach ($bienPhap as $bpCode => $bpLabel): ?>
                            <option value="<?= e($bpCode) ?>"><?= e($bpLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="modal_moTa" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                        Mô tả đề xuất
                    </label>
                    <textarea id="modal_moTa" name="moTa" rows="3" maxlength="255" placeholder="Nhập lý do hoặc chi tiết hướng xử lý đề xuất..." style="width: 100%; padding: 9px 12px; font-size: 13.5px; font-family: inherit; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; resize: vertical;"></textarea>
                </div>
            </div>

            <div class="cukcuk-modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeProposeModal()" style="height: 38px; padding: 0 18px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 13.5px;">Hủy bỏ</button>
                <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 22px; border-radius: 6px; font-size: 13.5px; font-weight: 600; background: #2563eb; color: #ffffff;">
                    Gửi đề xuất
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openProposeModal() {
    var modal = document.getElementById('proposeModal');
    var moTa = document.getElementById('modal_moTa');
    if (moTa) moTa.value = '';
    if (modal) modal.style.display = 'flex';
}
function closeProposeModal() {
    var modal = document.getElementById('proposeModal');
    if (modal) modal.style.display = 'none';
}
function handleProposeModalBackdrop(e) {
    if (e.target && e.target.id === 'proposeModal') closeProposeModal();
}
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeProposeModal();
});

// Duyệt phương án
function doApprove(maPA) {
    fwModal.confirm({
        title: 'Phê duyệt phương án xử lý',
        message: 'Xác nhận phê duyệt phương án?',
        tone: 'success',
        icon: '✓',
        okText: 'Phê duyệt',
        okColor: '#16a34a',
        onConfirm: function () {
            fwModal.postForm('<?= e(route('hang-bat-thuong/approve')) ?>', {
                csrf_token: '<?= e(csrf_token()) ?>',
                ma: '<?= e($hbt['maHangBatThuong']) ?>',
                maPhuongAn: maPA,
                yKien: ''
            });
        }
    });
}

// Từ chối phương án
function doReject(maPA) {
    fwModal.prompt({
        title: 'Từ chối phương án xử lý',
        htmlMessage: 'Nhập lý do từ chối phương án <strong>' + maPA + '</strong>:',
        label: 'Lý do từ chối',
        placeholder: 'Nhập lý do từ chối (bắt buộc)...',
        required: true,
        requiredMsg: 'Vui lòng nhập lý do từ chối phương án.',
        tone: 'danger',
        icon: '!',
        okText: 'Từ chối',
        okColor: '#dc2626',
        onConfirm: function (lyDo) {
            fwModal.postForm('<?= e(route('hang-bat-thuong/reject')) ?>', {
                csrf_token: '<?= e(csrf_token()) ?>',
                ma: '<?= e($hbt['maHangBatThuong']) ?>',
                maPhuongAn: maPA,
                lyDo: lyDo
            });
        }
    });
}

// Hủy phương án chưa thi hành
function doCancel(maPA) {
    fwModal.prompt({
        title: 'Hủy phương án xử lý',
        htmlMessage: 'Xác nhận hủy phương án <strong>' + maPA + '</strong>? Bản ghi sẽ quay về trạng thái Đang cách ly để đề xuất lại nếu cần.',
        label: 'Lý do hủy (không bắt buộc)',
        placeholder: 'Nhập lý do hủy...',
        required: false,
        tone: 'warn',
        icon: '!',
        okText: 'Xác nhận hủy',
        okColor: '#64748b',
        onConfirm: function (lyDo) {
            fwModal.postForm('<?= e(route('hang-bat-thuong/cancel')) ?>', {
                csrf_token: '<?= e(csrf_token()) ?>',
                ma: '<?= e($hbt['maHangBatThuong']) ?>',
                maPhuongAn: maPA,
                lyDo: lyDo
            });
        }
    });
}

// Thi hành phương án
function doExecute(maPA) {
    fwModal.confirm({
        title: 'Xác nhận thi hành phương án',
        message: 'Xác nhận thi hành phương án?',
        tone: 'info',
        icon: '?',
        okText: 'Thi hành',
        okColor: '#2563eb',
        onConfirm: function () {
            fwModal.postForm('<?= e(route('hang-bat-thuong/execute')) ?>', {
                csrf_token: '<?= e(csrf_token()) ?>',
                ma: '<?= e($hbt['maHangBatThuong']) ?>',
                maPhuongAn: maPA
            });
        }
    });
}
</script>
