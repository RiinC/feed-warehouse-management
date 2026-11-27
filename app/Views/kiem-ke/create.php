<?php
/**
 * View kiem-ke/create — UC24: Lập biên bản kiểm kê kho (Chuẩn Master-Detail MISA CukCuk).
 * Biến từ KiemKeController::create():
 *   $kho, $selectedKho, $selectedKhoName, $selectedLoai, $selectedLoaiName,
 *   $selectedNgay, $displayNgay, $areas, $locations, $scopeLots,
 *   $tabTitle, $nextCodePreview, $creatorName, $creatorId.
 */
$formToken = uniqid('bbkk_' . date('Ymd_His_'), true);
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

/* Badge trạng thái & loại kiểm kê dạng cũ chuẩn FeedWare */
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

/* 4. Modal Thay đổi nhanh kho & ngày */
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
    width: 450px;
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
.cukcuk-modal-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.cukcuk-modal-input {
    width: 100%;
    height: 40px;
    padding: 0 12px;
    font-size: 13.5px;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    box-sizing: border-box;
    outline: none;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.cukcuk-modal-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
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

<form method="post" id="createKiemKeForm" action="<?= e(route('kiem-ke/store')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="form_token" value="<?= e($formToken) ?>">
    <input type="hidden" name="maKho" id="form_maKho" value="<?= e($selectedKho) ?>">
    <input type="hidden" name="loaiKiemKe" id="form_loaiKiemKe" value="<?= e($selectedLoai) ?>">
    <input type="hidden" name="maNhanVienTruongDoan" id="form_maNhanVienTruongDoan" value="<?= e($creatorId) ?>">
    <input type="hidden" name="thoiDiemKetThucDuKien" id="form_thoiDiemKetThuc" value="<?= e($selectedNgay) ?>">

    <!-- 1. THÔNG TIN CHUNG & CHỨNG TỪ CHIA 2 BẢNG GỌN ĐỒNG BỘ (1:1) -->
    <?php
    ob_start();
    ?>
    <select id="create_maKhuVuc" name="maKhuVuc" class="cukcuk-input-control" onchange="onScopeFilterChange()">
        <option value="">Toàn bộ khu vực</option>
        <?php foreach ($areas as $maKV => $tenKV): ?>
            <option value="<?= e($maKV) ?>"><?= e($tenKV) ?></option>
        <?php endforeach; ?>
    </select>
    <?php
    $kvSelectHtml = ob_get_clean();

    ob_start();
    ?>
    <select id="create_maViTri" name="maViTri" class="cukcuk-input-control" onchange="onScopeFilterChange()">
        <option value="">Tất cả vị trí</option>
        <?php foreach ($locations as $loc): ?>
            <option value="<?= e($loc['maViTri']) ?>" data-kv="<?= e($loc['maKhuVuc']) ?>">
                <?= e($loc['tenViTri']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
    $vtSelectHtml = ob_get_clean();

    ob_start();
    ?>
    <select id="create_maNhanVienGiaoViec" name="maNhanVienGiaoViec" class="cukcuk-input-control">
        <option value="">Chọn nhân viên giao việc</option>
        <?php foreach ($staffList as $sId => $sName): ?>
            <option value="<?= e($sId) ?>"><?= e($sName) ?></option>
        <?php endforeach; ?>
    </select>
    <?php
    $nvSelectHtml = ob_get_clean();

    $ghiChuInputHtml = '<textarea id="create_ghiChu" name="ghiChu" class="cukcuk-input-control" rows="3" placeholder="Nhập ghi chú (nếu có)..." maxlength="255"></textarea>';
    $changeScopeBtnHtml = '<button type="button" class="btn btn-ghost btn-sm" onclick="openInitModal()" style="font-size: 12px; color: #2563eb; padding: 3px 10px; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #bfdbfe; border-radius: 4px; background: #eff6ff;">'
        . icon('edit', 13) . ' Thay đổi</button>';

    render_kv_pair_cards(
        [
            'title'       => 'Thông tin phạm vi kiểm kê',
            'headerRight' => $changeScopeBtnHtml,
            'rows'        => [
                'Kho kiểm kê'       => (string)$selectedKhoName,
                'Khu vực kiểm kê'   => ['html' => $kvSelectHtml],
                'Vị trí lưu trữ'    => ['html' => $vtSelectHtml],
                'Nhân viên kiểm kê' => ['html' => $nvSelectHtml],
                'Ghi chú'           => ['html' => $ghiChuInputHtml],
            ],
        ],
        [
            'title'       => 'Thông tin chứng từ',
            'headerRight' => inventory_status_badge('Chờ xử lý'),
            'rows'        => [
                'Mã biên bản'      => (string)$nextCodePreview,
                'Loại kiểm kê'     => (string)$selectedLoaiName,
                'Ngày kiểm kê đến' => (string)$displayNgay,
                'Người lập'        => (string)$creatorName,
            ],
        ],
        'kv-grid kv-grid--60-40'
    );
    ?>

    <!-- 2. KHỐI BẢNG DANH SÁCH HÀNG HÓA TỒN KHO TRONG PHẠM VI -->
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
                        <th style="width: 135px;">Mã lô</th>
                        <th>Hàng hóa</th>
                        <th class="center" style="width: 120px;">Ngày SX</th>
                        <th class="center" style="width: 120px;">Hạn sử dụng</th>
                        <th class="center" style="width: 80px;">ĐVT</th>
                        <th class="num" style="width: 130px;">Số lượng</th>
                    </tr>
                </thead>
                <tbody id="lotTableBody">
                <?php if (empty($scopeLots)): ?>
                    <tr id="emptyScopeRow">
                        <td colspan="6" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                <span style="display: inline-flex; width: 44px; height: 44px; border-radius: 50%; background: #eff6ff; color: #2563eb; align-items: center; justify-content: center;">
                                    <?= icon('inbox', 22) ?>
                                </span>
                                <b style="font-size: 14px; color: #0f172a;">Không có tồn kho trong phạm vi đã chọn</b>
                                <span style="font-size: 13px; color: #64748b;">Kho hiện tại không có dữ liệu tồn kho hợp lệ để kiểm kê.</span>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $totalScopeQty = 0;
                    foreach ($scopeLots as $lot):
                        $nsxText = !empty($lot['ngaySanXuat']) ? date('d/m/Y', strtotime($lot['ngaySanXuat'])) : '—';
                        $hsdText = !empty($lot['ngayHetHan']) ? date('d/m/Y', strtotime($lot['ngayHetHan'])) : '—';
                        $qty = (float)$lot['soLuongTon'];
                        $totalScopeQty += $qty;
                    ?>
                        <tr class="lot-row" data-kv="<?= e($lot['maKhuVuc']) ?>" data-vt="<?= e($lot['maViTri']) ?>" data-qty="<?= $qty ?>">
                            <td style="font-weight: 600; color: #1e293b;"><?= e($lot['maLoHang']) ?></td>
                            <td>
                                <strong style="color: #0f172a; font-size: 13px;"><?= e($lot['tenHangHoa']) ?></strong>
                            </td>
                            <td class="center" style="color: #475467; font-size: 12.5px;"><?= e($nsxText) ?></td>
                            <td class="center" style="color: #475467; font-size: 12.5px;"><?= e($hsdText) ?></td>
                            <td class="center" style="color: #475467;"><?= e($lot['donViTinhChuan'] ?? 'kg') ?></td>
                            <td class="num" style="font-weight: 700; color: #0f172a;"><?= number_format($qty, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="emptyScopeRow" style="display: none;">
                        <td colspan="6" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                <span style="display: inline-flex; width: 44px; height: 44px; border-radius: 50%; background: #eff6ff; color: #2563eb; align-items: center; justify-content: center;">
                                    <?= icon('inbox', 22) ?>
                                </span>
                                <b style="font-size: 14px; color: #0f172a;">Không có tồn kho trong phạm vi đã chọn</b>
                                <span style="font-size: 13px; color: #64748b;">Khu vực hoặc vị trí đã chọn hiện không có lô hàng tồn kho để kiểm kê.</span>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($scopeLots)): ?>
                <tfoot id="lotTableFooter">
                    <tr>
                        <td colspan="5" style="text-align: right; padding: 11px 14px; color: #0f172a; font-weight: 700; background: #fafbfd; border-top: 1px solid var(--line);">Tổng số lượng:</td>
                        <td class="num" style="padding: 11px 14px; color: #0f172a; font-weight: 700; font-size: 13.5px; background: #fafbfd; border-top: 1px solid var(--line);" id="totalBookQty">
                            <?= number_format($totalScopeQty, 0, ',', '.') ?>
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- Footer nút thao tác dính đáy thẻ: Hủy bỏ & Lập biên bản -->
        <div class="sticky-footer-bar">
            <a href="<?= e(route('kiem-ke')) ?>" class="btn btn-ghost" style="height: 36px; padding: 0 16px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; color: #344054; background: #ffffff;">
                Hủy bỏ
            </a>
            <button type="button" class="btn btn-primary" id="btnLapBienBan" onclick="openConfirmModal()" style="height: 36px; padding: 0 20px; border-radius: 6px; font-size: 13.5px; font-weight: 600; background: #2563eb; color: #ffffff; border: none; cursor: pointer;">
                Lập biên bản
            </button>
        </div>
    </div>
</form>

<!-- 4. POPUP MODAL XÁC NHẬN CHẮC CHẮN LẬP BIÊN BẢN KIỂM KÊ -->
<div id="confirmCreateModal" class="cukcuk-modal-backdrop" style="display: none;" onclick="handleConfirmBackdropClick(event)">
    <div class="cukcuk-modal-box" style="width: 480px;">
        <div class="cukcuk-modal-header" style="border-bottom: 1px solid #f1f5f9; padding: 16px 22px;">
            <h3 class="cukcuk-modal-title" style="color: #0f172a; font-size: 16px;">Xác nhận lập biên bản kiểm kê</h3>
            <button type="button" class="cukcuk-modal-close" onclick="closeConfirmModal()" title="Đóng">&times;</button>
        </div>
        <div class="cukcuk-modal-body" style="padding: 20px 22px; gap: 14px;">
            <p style="margin: 0; font-size: 13.5px; color: #334155; line-height: 1.5;">
                Bạn có chắc chắn muốn lập biên bản kiểm kê này không?
            </p>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Mã biên bản dự kiến:</span>
                    <span style="color: #2563eb;"><?= e($nextCodePreview) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Kho kiểm kê:</span>
                    <span style="color: #0f172a;"><?= e($selectedKhoName) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Loại kiểm kê:</span>
                    <span style="color: #0f172a;"><?= e($selectedLoaiName) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Khu vực:</span>
                    <span id="confirmKhuVucName" style="color: #0f172a;">Toàn bộ khu vực</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Nhân viên kiểm đếm:</span>
                    <span id="confirmStaffName" style="color: #0f172a;">—</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Số lượng lô hàng:</span>
                    <span id="confirmLotCount" style="color: #0f172a;"><?= count($scopeLots) ?> lô</span>
                </div>
            </div>
        </div>
        <div class="cukcuk-modal-footer" style="padding: 14px 22px; gap: 10px; background: #ffffff; border-top: 1px solid #f1f5f9;">
            <button type="button" class="btn btn-ghost" onclick="closeConfirmModal()" style="height: 38px; padding: 0 20px; font-size: 13.5px; border: 1px solid #d0d5dd; border-radius: 6px;">
                Hủy bỏ
            </button>
            <button type="button" class="btn btn-primary" id="btnSubmitConfirmed" onclick="submitConfirmedCreate()" style="height: 38px; padding: 0 22px; font-size: 13.5px; font-weight: 600; border-radius: 6px; background: #2563eb; color: #ffffff; display: inline-flex; align-items: center; gap: 6px;">
                <?= icon('check', 16) ?> Xác nhận lập
            </button>
        </div>
    </div>
</div>

<!-- 3. POPUP MODAL THAY ĐỔI KHO & NGÀY -->
<div id="initKiemKeModal" class="cukcuk-modal-backdrop" style="display: none;" onclick="handleModalBackdropClick(event)">
    <div class="cukcuk-modal-box">
        <div class="cukcuk-modal-header">
            <h3 class="cukcuk-modal-title">Kiểm kê kho</h3>
            <button type="button" class="cukcuk-modal-close" onclick="closeInitModal()" title="Đóng">&times;</button>
        </div>
        <div class="cukcuk-modal-body">
            <!-- 1. Loại kiểm kê (Select chọn 3 cái) -->
            <div>
                <label for="modal_loaiKiemKe" class="cukcuk-modal-label">Loại kiểm kê <span style="color: #ef4444;">*</span></label>
                <select id="modal_loaiKiemKe" class="cukcuk-modal-input">
                    <?php foreach (inventory_type_options() as $loaiCode => $loaiName): ?>
                        <option value="<?= e($loaiCode) ?>" <?= $loaiCode === $selectedLoai ? 'selected' : '' ?>>
                            <?= e($loaiName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 2. Kiểm kê kho (chỉ hiển thị khi có nhiều hơn 1 kho) -->
            <?php if (count($kho) === 1): ?>
                <input type="hidden" id="modal_maKho" value="<?= e((string)array_key_first($kho)) ?>">
            <?php else: ?>
                <div>
                    <label for="modal_maKho" class="cukcuk-modal-label">Kiểm kê kho <span style="color: #ef4444;">*</span></label>
                    <select id="modal_maKho" class="cukcuk-modal-input">
                        <option value="">— Chọn kho kiểm kê —</option>
                        <?php foreach ($kho as $kId => $kName): ?>
                            <option value="<?= e($kId) ?>" <?= $kId === $selectedKho ? 'selected' : '' ?>><?= e($kName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- 3. Kiểm kê đến ngày -->
            <div>
                <label for="modal_thoiDiemKetThuc" class="cukcuk-modal-label">Kiểm kê đến ngày <span style="color: #ef4444;">*</span></label>
                <input type="date" id="modal_thoiDiemKetThuc" class="cukcuk-modal-input" value="<?= e($selectedNgay) ?>">
            </div>
        </div>
        <div class="cukcuk-modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeInitModal()" style="height: 38px; padding: 0 20px; font-size: 13.5px; border: 1px solid #d0d5dd; border-radius: 6px;">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="confirmInitModal()" style="height: 38px; padding: 0 22px; font-size: 13.5px; font-weight: 600; border-radius: 6px; background: #2563eb;">Đồng ý</button>
        </div>
    </div>
</div>

<script>
// Quản lý Modal Thay đổi
function openInitModal() {
    var modal = document.getElementById('initKiemKeModal');
    if (modal) {
        modal.style.display = 'flex';
        var selKho = document.getElementById('modal_maKho');
        var selLoai = document.getElementById('modal_loaiKiemKe');
        if (selKho && selKho.tagName === 'SELECT') selKho.focus();
        else if (selLoai) selLoai.focus();
    }
}

function closeInitModal() {
    var modal = document.getElementById('initKiemKeModal');
    if (modal) modal.style.display = 'none';
}

function handleModalBackdropClick(e) {
    if (e.target && e.target.id === 'initKiemKeModal') {
        closeInitModal();
    }
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeInitModal();
    }
});

function confirmInitModal() {
    var loaiSel = document.getElementById('modal_loaiKiemKe');
    var loai = loaiSel ? loaiSel.value : 'DinhKy';
    var khoSel = document.getElementById('modal_maKho');
    var maKho = khoSel ? khoSel.value : '';
    var ngayKetThuc = document.getElementById('modal_thoiDiemKetThuc').value;

    if (!maKho) {
        fwModal.alert({
            title: 'Thiếu thông tin',
            message: 'Vui lòng chọn kho kiểm kê.',
            tone: 'warn',
            onConfirm: function () { if (khoSel) khoSel.focus(); }
        });
        return;
    }
    if (!ngayKetThuc) {
        fwModal.alert({
            title: 'Thiếu thông tin',
            message: 'Vui lòng chọn ngày kiểm kê đến ngày.',
            tone: 'warn'
        });
        return;
    }

    var baseUrl = <?= json_encode(route('kiem-ke/create')) ?>;
    var url = baseUrl + '?loaiKiemKe=' + encodeURIComponent(loai)
        + '&maKho=' + encodeURIComponent(maKho)
        + '&ngayKetThuc=' + encodeURIComponent(ngayKetThuc);
    window.location.href = url;
}

// Bộ nhớ đệm danh sách vị trí gốc để lọc mượt mà theo khu vực
var allVtOptions = [];
document.addEventListener('DOMContentLoaded', function () {
    var vtSel = document.getElementById('create_maViTri');
    if (vtSel) {
        for (var i = 0; i < vtSel.options.length; i++) {
            var opt = vtSel.options[i];
            allVtOptions.push({
                value: opt.value,
                text: opt.textContent,
                kv: opt.getAttribute('data-kv') || ''
            });
        }
    }
});

// Lọc thời gian thực danh sách tồn kho theo Khu vực và Vị trí
function onScopeFilterChange() {
    var selKv = document.getElementById('create_maKhuVuc').value;
    var vtSel = document.getElementById('create_maViTri');
    var selVt = vtSel.value;

    // Tái cấu trúc dropdown vị trí dựa trên khu vực đã chọn
    if (allVtOptions.length > 0) {
        var currentSelectedStillValid = false;
        vtSel.innerHTML = '';
        allVtOptions.forEach(function (item) {
            if (!item.value || !selKv || item.kv === selKv) {
                var newOpt = document.createElement('option');
                newOpt.value = item.value;
                newOpt.textContent = item.text;
                if (item.kv) {
                    newOpt.setAttribute('data-kv', item.kv);
                }
                if (item.value === selVt) {
                    newOpt.selected = true;
                    currentSelectedStillValid = true;
                }
                vtSel.appendChild(newOpt);
            }
        });
        if (!currentSelectedStillValid) {
            vtSel.value = '';
            selVt = '';
        }
    }

    var rows = document.querySelectorAll('.lot-row');
    var totalQty = 0;
    var visibleCount = 0;

    rows.forEach(function (row) {
        var rowKv = row.getAttribute('data-kv');
        var rowVt = row.getAttribute('data-vt');
        var rowQty = parseFloat(row.getAttribute('data-qty')) || 0;

        var matchKv = (!selKv || rowKv === selKv);
        var matchVt = (!selVt || rowVt === selVt);

        if (matchKv && matchVt) {
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

    var footerEl = document.getElementById('lotTableFooter');
    if (footerEl) {
        footerEl.style.display = (visibleCount === 0) ? 'none' : '';
    }

    var totalEl = document.getElementById('totalBookQty');
    if (totalEl) {
        totalEl.textContent = new Intl.NumberFormat('vi-VN').format(totalQty);
    }
}

function getVisibleLotCount() {
    var rows = document.querySelectorAll('.lot-row');
    var count = 0;
    rows.forEach(function (row) {
        if (row.style.display !== 'none') {
            count++;
        }
    });
    return count;
}

function warnNoLotsInScope() {
    fwModal.alert({
        title: 'Không có hàng hóa kiểm kê',
        message: 'Phạm vi đã chọn hiện không có lô hàng tồn kho nào để kiểm kê. Vui lòng chọn kho, khu vực hoặc vị trí khác.',
        tone: 'warn'
    });
}

// Quản lý Modal Xác nhận chắc chắn lập biên bản
function openConfirmModal() {
    var visibleCount = getVisibleLotCount();
    if (visibleCount <= 0) {
        warnNoLotsInScope();
        return;
    }

    var kvSel = document.getElementById('create_maKhuVuc');
    var kvText = (kvSel && kvSel.selectedIndex >= 0 && kvSel.value) ? kvSel.options[kvSel.selectedIndex].text : 'Toàn bộ khu vực';
    var elKv = document.getElementById('confirmKhuVucName');
    if (elKv) elKv.textContent = kvText;

    var staffSel = document.getElementById('create_maNhanVienGiaoViec');
    var staffText = (staffSel && staffSel.selectedIndex > 0) ? staffSel.options[staffSel.selectedIndex].text : '<?= e($creatorName) ?>';
    var elStaff = document.getElementById('confirmStaffName');
    if (elStaff) elStaff.textContent = staffText;

    var elLots = document.getElementById('confirmLotCount');
    if (elLots) elLots.textContent = visibleCount + ' lô';

    var modal = document.getElementById('confirmCreateModal');
    if (modal) modal.style.display = 'flex';
}

function closeConfirmModal() {
    var modal = document.getElementById('confirmCreateModal');
    if (modal) modal.style.display = 'none';
}

function handleConfirmBackdropClick(e) {
    if (e.target && e.target.id === 'confirmCreateModal') {
        closeConfirmModal();
    }
}

function submitConfirmedCreate() {
    if (getVisibleLotCount() <= 0) {
        closeConfirmModal();
        warnNoLotsInScope();
        return;
    }
    var btn = document.getElementById('btnSubmitConfirmed');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Đang lập biên bản...';
    }
    var btnMain = document.getElementById('btnLapBienBan');
    if (btnMain) {
        btnMain.disabled = true;
    }
    document.getElementById('createKiemKeForm').submit();
}

// Chặn submit trực tiếp (VD nhấn Enter trong ô ghi chú) khi chưa qua kiểm tra phạm vi & xác nhận
document.getElementById('createKiemKeForm').addEventListener('submit', function (e) {
    e.preventDefault();
    openConfirmModal();
});
</script>
