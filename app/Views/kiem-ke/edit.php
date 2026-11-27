<?php
/**
 * View kiem-ke/edit — UC24: Chỉnh sửa biên bản kiểm kê kho (Chuẩn Master-Detail MISA CukCuk đồng bộ).
 * Biến từ KiemKeController::edit():
 *   $bb, $areas, $staffList, $scopeLots, $tabTitle.
 */
?>

<style>
/* 1. Bố cục Top 2 Card tỷ lệ 7:3 */
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
    height: 36px;
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
    padding: 8px 10px;
    resize: vertical;
    font-family: inherit;
    line-height: 1.4;
}

/* 2. Tiêu đề loại hàng đã chọn (Tab bar) */
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
    padding: 12px 4px;
    font-size: 14px;
    font-weight: 700;
    color: #2563eb;
    border-bottom: 2px solid #2563eb;
    text-decoration: none;
    user-select: none;
}

/* 3. Bảng hàng/lô tồn kho */
#lotTable {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
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

<form method="post" id="editKiemKeForm" action="<?= e(route('kiem-ke/update')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="maBienBanKiemKe" value="<?= e($bb['maBienBanKiemKe']) ?>">

    <!-- 1. THÔNG TIN CHUNG & CHỨNG TỪ CHIA 2 BẢNG GỌN ĐỒNG BỘ (1:1) -->
    <?php
    ob_start();
    ?>
    <select id="f_maKhuVuc" name="maKhuVuc" class="cukcuk-input-control" onchange="onScopeFilterChange()">
        <option value="">Toàn bộ kho (mọi khu vực)</option>
        <?php foreach ($areas as $mkv => $tkv): ?>
            <option value="<?= e($mkv) ?>" <?= $mkv === $bb['maKhuVuc'] ? 'selected' : '' ?>>
                <?= e($tkv) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
    $kvEditHtml = ob_get_clean();

    ob_start();
    ?>
    <select id="f_maTruongDoan" name="maNhanVienTruongDoan" class="cukcuk-input-control" required>
        <option value="">Chọn nhân viên phụ trách</option>
        <?php foreach ($staffList as $sId => $sName): ?>
            <option value="<?= e($sId) ?>" <?= $sId === $bb['maNhanVienTruongDoan'] ? 'selected' : '' ?>>
                <?= e($sName) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
    $nvEditHtml = ob_get_clean();

    $ghiChuEditHtml = '<input type="text" id="f_ghiChu" name="ghiChu" class="cukcuk-input-control" placeholder="Nhập ghi chú (nếu có)..." maxlength="255" value="' . e($bb['ghiChu'] ?? '') . '">';

    render_kv_pair_cards(
        [
            'title' => 'Thông tin phạm vi kiểm kê',
            'rows'  => [
                'Kho kiểm kê'     => (string)$bb['tenKho'],
                'Khu vực kiểm kê' => ['html' => $kvEditHtml],
                'Loại kiểm kê'    => (string)inventory_type_label($bb['loaiKiemKe'] ?? ''),
                'Ghi chú'         => ['html' => $ghiChuEditHtml],
            ],
        ],
        [
            'title'       => 'Thông tin chứng từ',
            'headerRight' => inventory_status_badge($bb['trangThai']),
            'rows'        => [
                'Mã biên bản'       => (string)$bb['maBienBanKiemKe'],
                'Ngày kiểm kê'      => !empty($bb['ngayBatDau']) ? date('d/m/Y', strtotime($bb['ngayBatDau'])) : '—',
                'Nhân viên kiểm kê' => ['html' => $nvEditHtml, 'required' => true],
                'Người phụ trách'   => (string)($bb['tenTruongDoan'] ?? '—'),
            ],
        ]
    );
    ?>

    <!-- 2. KHỐI BẢNG DANH SÁCH LÔ HÀNG TRONG PHẠM VI BIÊN BẢN -->
    <div class="card cukcuk-card sticky-card" style="margin-top: 16px; margin-bottom: 0; border-radius: var(--radius);">
        <!-- Tab bar tiêu đề loại hàng hóa -->
        <div class="cukcuk-clean-tab-bar">
            <span class="cukcuk-tab-item active">
                <?= e($tabTitle) ?>
            </span>
        </div>

        <!-- Bảng danh sách hàng hóa/lô -->
        <div class="table-wrap" style="overflow-x: auto; margin: 0;">
            <table class="table" id="lotTable" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 125px;">Mã lô</th>
                        <th style="width: 240px;">Hàng hóa</th>
                        <th style="width: 170px;">Khu vực</th>
                        <th style="width: 180px;">Vị trí lưu trữ</th>
                        <th class="center" style="width: 105px;">Ngày SX</th>
                        <th class="center" style="width: 105px;">Hạn sử dụng</th>
                        <th class="center" style="width: 65px;">ĐVT</th>
                        <th class="num" style="width: 120px;">Số lượng</th>
                    </tr>
                </thead>
                <tbody id="lotTableBody">
                <?php if (empty($scopeLots)): ?>
                    <tr id="emptyScopeRow">
                        <td colspan="8" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                <span style="display: inline-flex; width: 44px; height: 44px; border-radius: 50%; background: #eff6ff; color: #2563eb; align-items: center; justify-content: center;">
                                    <?= icon('inbox', 22) ?>
                                </span>
                                <b style="font-size: 14px; color: #0f172a;">Không có tồn kho trong kho này</b>
                                <span style="font-size: 13px; color: #64748b;">Kho hiện tại không có dữ liệu tồn kho hợp lệ để kiểm kê.</span>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $initKv = (string)($bb['maKhuVuc'] ?? '');
                    $totalScopeQty = 0;
                    $matchedCount = 0;
                    foreach ($scopeLots as $lot):
                        $nsxText = !empty($lot['ngaySanXuat']) ? date('d/m/Y', strtotime($lot['ngaySanXuat'])) : '—';
                        $hsdText = !empty($lot['ngayHetHan']) ? date('d/m/Y', strtotime($lot['ngayHetHan'])) : '—';
                        $qty = (float)$lot['soLuongTon'];
                        $isMatch = ($initKv === '' || $lot['maKhuVuc'] === $initKv);
                        if ($isMatch) {
                            $totalScopeQty += $qty;
                            $matchedCount++;
                        }
                    ?>
                        <tr class="lot-row" data-kv="<?= e($lot['maKhuVuc']) ?>" data-qty="<?= $qty ?>" style="<?= $isMatch ? '' : 'display: none;' ?>">
                            <td style="font-weight: 600; color: #1e293b;"><?= e($lot['maLoHang']) ?></td>
                            <td>
                                <strong style="color: #0f172a; font-size: 13px;"><?= e($lot['tenHangHoa']) ?></strong>
                            </td>
                            <td style="color: #334155;"><?= e($lot['tenKhuVuc']) ?></td>
                            <td>
                                <span class="badge" style="background: #f1f5f9; color: #334155; font-size: 11.5px; font-weight: 500; border-radius: 9999px; padding: 2.5px 9px; white-space: nowrap; display: inline-block;">
                                    <?= e($lot['tenViTri'] ?? $lot['maViTri']) ?>
                                </span>
                            </td>
                            <td class="center" style="color: #475467; font-size: 12.5px;"><?= e($nsxText) ?></td>
                            <td class="center" style="color: #475467; font-size: 12.5px;"><?= e($hsdText) ?></td>
                            <td class="center" style="color: #475467;"><?= e($lot['donViTinhChuan'] ?? 'kg') ?></td>
                            <td class="num" style="font-weight: 700; color: #0f172a;"><?= number_format($qty, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="emptyScopeRow" style="<?= $matchedCount === 0 ? '' : 'display: none;' ?>">
                        <td colspan="8" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                <span style="display: inline-flex; width: 44px; height: 44px; border-radius: 50%; background: #eff6ff; color: #2563eb; align-items: center; justify-content: center;">
                                    <?= icon('inbox', 22) ?>
                                </span>
                                <b style="font-size: 14px; color: #0f172a;">Không có lô hàng trong khu vực đã chọn</b>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($scopeLots)): ?>
                <tfoot>
                    <tr>
                        <td colspan="7" style="text-align: right; padding: 11px 14px; color: #0f172a; font-weight: 700; background: #fafbfd; border-top: 1px solid var(--line);">Tổng số lượng:</td>
                        <td class="num" style="padding: 11px 14px; color: #0f172a; font-weight: 700; font-size: 13.5px; background: #fafbfd; border-top: 1px solid var(--line);" id="totalBookQty">
                            <?= number_format($totalScopeQty, 0, ',', '.') ?>
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- Footer nút thao tác dính đáy thẻ: Hủy bỏ & Cập nhật biên bản -->
        <div class="sticky-footer-bar">
            <a href="<?= e(route('kiem-ke/detail', ['ma' => $bb['maBienBanKiemKe']])) ?>" class="btn btn-ghost" style="height: 36px; padding: 0 16px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; color: #344054; background: #ffffff;">
                Hủy bỏ
            </a>
            <button type="submit" class="btn btn-primary" style="height: 36px; padding: 0 20px; border-radius: 6px; font-size: 13.5px; font-weight: 600; background: #2563eb; color: #ffffff; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <?= icon('check', 16) ?> Cập nhật biên bản
            </button>
        </div>
    </div>
</form>

<script>
// Lọc thời gian thực danh sách lô hàng theo Khu vực đã chọn
function onScopeFilterChange() {
    var selKv = document.getElementById('f_maKhuVuc').value;
    var rows = document.querySelectorAll('.lot-row');
    var totalQty = 0;
    var visibleCount = 0;

    rows.forEach(function (row) {
        var rowKv = row.getAttribute('data-kv');
        var rowQty = parseFloat(row.getAttribute('data-qty')) || 0;

        var matchKv = (!selKv || rowKv === selKv);
        if (matchKv) {
            row.style.display = '';
            totalQty += rowQty;
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    var emptyRow = document.getElementById('emptyScopeRow');
    if (emptyRow) {
        emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
    }

    var totalEl = document.getElementById('totalBookQty');
    if (totalEl) {
        totalEl.textContent = new Intl.NumberFormat('vi-VN').format(totalQty);
    }
}
</script>
