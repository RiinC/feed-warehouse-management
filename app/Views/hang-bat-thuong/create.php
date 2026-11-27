<?php
/**
 * View hang-bat-thuong/create — UC28: Ghi nhận hàng bất thường (bố cục chuẩn Kiểm kê kho).
 * Luồng thao tác trực quan:
 *   1. Chọn Kho (nếu có nhiều kho) & Khu vực (kèm tùy chọn Vị trí) ở khung trên
 *   2. Bảng danh sách lô hàng hiện ngay ở dưới kèm bộ lọc (từ khóa, mức HSD, khoảng ngày HSD, sắp xếp FEFO)
 *   3. Chọn 1 lô hàng -> mở phần chọn Loại bất thường, Số lượng (nguyên > 0) & Mô tả -> Lưu ghi nhận.
 */

$lotsJs = [];
foreach ($stockLots as $sl) {
    $lotsJs[] = [
        'maTonKho'        => (int)$sl['maTonKho'],
        'maLoHang'        => (string)$sl['maLoHang'],
        'maHangHoa'       => (string)$sl['maHangHoa'],
        'tenHangHoa'      => (string)$sl['tenHangHoa'],
        'soLoNhaCungCap'  => (string)($sl['soLoNhaCungCap'] ?? ''),
        'donViTinhChuan'  => (string)($sl['donViTinhChuan'] ?? 'kg'),
        'maKhuVuc'        => (string)$sl['maKhuVuc'],
        'tenKhuVuc'       => (string)$sl['tenKhuVuc'],
        'maViTri'         => (string)$sl['maViTri'],
        'tenViTri'        => (string)$sl['tenViTri'],
        'ngaySanXuat'     => (string)($sl['ngaySanXuat'] ?? ''),
        'ngayHetHan'      => (string)($sl['ngayHetHan'] ?? ''),
        'soNgayConLai'    => (int)($sl['soNgayConLai'] ?? 0),
        'trangThaiQC'     => (string)($sl['trangThaiQC'] ?? ''),
        'soLuongTon'      => (float)$sl['soLuongTon'],
        'khaDung'         => (float)$sl['khaDung'],
        'ngayCapNhatCuoi' => (string)($sl['ngayCapNhatCuoi'] ?? ''),
        'hbtDangMo'       => (string)($sl['hbtDangMo'] ?? ''),
        'hbtTrangThai'    => (string)($sl['hbtTrangThai'] ?? ''),
    ];
}
?>

<style>
.hbt-create-top-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    align-items: stretch;
}
@media (max-width: 992px) {
    .hbt-create-top-grid { grid-template-columns: 1fr; }
}
.hbt-kv-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}
.hbt-kv-table tr {
    border-bottom: 1px solid #f1f5f9;
}
.hbt-kv-table tr:last-child {
    border-bottom: 0;
}
.hbt-kv-table th,
.hbt-kv-table td {
    height: 44px;
    box-sizing: border-box;
    vertical-align: middle;
}
.hbt-kv-table th {
    width: 142px;
    padding: 6px 16px;
    font-size: 13px;
    font-weight: 400;
    color: #64748b;
    text-align: left;
    white-space: nowrap;
}
.hbt-kv-table td {
    padding: 5px 16px 5px 8px;
    font-size: 13px;
    font-weight: 400;
    color: #0f172a;
}
.hbt-kv-table td .cukcuk-input-control {
    height: 32px;
    font-size: 13px;
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

.hbt-lot-filter-wrap {
    background: #fafbfd;
    border-bottom: 1px solid var(--line);
}
.hbt-lot-filter-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-end;
    padding: 12px 18px;
    background: #fafbfd;
}
.hbt-lot-filter-wrap.is-collapsed .hbt-lf-collapsible { display: none; }
.hbt-lf-toggle {
    position: relative;
    flex: 0 0 auto;
    margin-left: auto;
    width: 34px;
    height: 34px;
    padding: 0;
    border: 1px solid #d0d5dd;
    border-radius: 6px;
    background: #ffffff;
    color: #344054;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: border-color .15s ease, color .15s ease, background .15s ease;
}
.hbt-lf-toggle:hover { border-color: #94a3b8; }
.hbt-lf-toggle.is-open,
.hbt-lf-toggle.is-filtered {
    border-color: #2563eb;
    color: #2563eb;
    background: #eff6ff;
}
.hbt-lf-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #2563eb;
    border: 1.5px solid #fafbfd;
}
.hbt-lf-actions {
    display: flex;
    align-items: flex-end;
    height: 52px;
    flex: 0 0 auto;
}
.hbt-lf-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1 1 135px;
    min-width: 125px;
}
.hbt-lf-group.lf-search {
    flex: 0 1 420px;
    min-width: 220px;
    max-width: 460px;
}
.hbt-lf-group label {
    font-size: 11.5px;
    font-weight: 600;
    color: #475467;
}
.hbt-lf-group input,
.hbt-lf-group select {
    height: 34px;
    padding: 0 9px;
    font-size: 12.5px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #fff;
    width: 100%;
    box-sizing: border-box;
}

#hbtLotTable {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}
#hbtLotTable th {
    background: #f8fafc;
    font-size: 12px;
    font-weight: 600;
    color: #475467;
    padding: 10px 12px;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
    text-align: left;
}
#hbtLotTable td {
    padding: 9px 12px;
    font-size: 13px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
    color: #0f172a;
}
#hbtLotTable tbody tr.lot-choice {
    cursor: pointer;
    transition: background .12s ease;
}
#hbtLotTable tbody tr.lot-choice:hover {
    background: #f8fbff;
}
#hbtLotTable tbody tr.lot-choice.selected {
    background: #eff6ff !important;
}
#hbtLotTable tbody tr.lot-choice.selected td:first-child {
    box-shadow: inset 3px 0 0 #2563eb;
}
.hbt-row-control {
    width: 100%;
    height: 32px;
    padding: 0 9px;
    font-size: 12.5px;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    box-sizing: border-box;
    outline: none;
}
.hbt-row-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
}
.hbt-bottom-bar {
    justify-content: space-between;
}

.badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 9999px;
    font-size: 11.5px;
    font-weight: 600;
    white-space: nowrap;
}
.badge-info { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.badge-ok { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
.badge-warn { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
.badge-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

.cukcuk-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 16px;
}
.cukcuk-modal-box {
    background: #ffffff;
    width: 470px;
    max-width: 95vw;
    border-radius: 10px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
    overflow: hidden;
}
.cukcuk-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 20px;
    border-bottom: 1px solid #f1f5f9;
}
.cukcuk-modal-title { margin: 0; font-size: 15.5px; font-weight: 700; color: #0f172a; }
.cukcuk-modal-close { background: none; border: none; font-size: 22px; color: #64748b; cursor: pointer; }
.cukcuk-modal-body { padding: 18px 20px; display: flex; flex-direction: column; gap: 14px; }
.cukcuk-modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 20px; border-top: 1px solid #f1f5f9; }
</style>

<form method="post" id="createHbtForm" action="<?= e(route('hang-bat-thuong/store')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="maKho" value="<?= e($selectedKho) ?>">
    <input type="hidden" name="itemsJson" id="form_itemsJson" value="">

    <!-- 1. KHUNG TRÊN CHIA 2 CỘT ĐỀU (1:1): TRÁI LÀ KHO / KHU VỰC / VỊ TRÍ, PHẢI LÀ THÔNG TIN CHỨNG TỪ -->
    <div class="hbt-create-top-grid">
        <!-- Cột trái: Kho, Khu vực, Vị trí lưu trữ -->
        <div class="card cukcuk-card" style="margin-bottom: 0; overflow: hidden;">
            <div class="card-head" style="height: 40px; padding: 0 16px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0;">
                <h2 style="font-size: 13.5px; font-weight: 600; color: #0f172a; margin: 0;">Thông tin phạm vi kiểm tra</h2>
            </div>

            <table class="hbt-kv-table">
                <tbody>
                    <tr>
                        <th scope="row">Kho</th>
                        <td>
                            <?php if ($canSwitchKho): ?>
                                <select id="hbt_kho" class="cukcuk-input-control" onchange="onKhoSwitch(this.value)">
                                    <?php foreach ($kho as $kId => $kName): ?>
                                        <option value="<?= e($kId) ?>" <?= $kId === $selectedKho ? 'selected' : '' ?>><?= e($kName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <?= e($selectedKhoName) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hbt_khuVuc" style="margin: 0; font-weight: 400; cursor: pointer;">Khu vực</label></th>
                        <td>
                            <select id="hbt_khuVuc" class="cukcuk-input-control" onchange="onKhuVucChange()">
                                <option value="">Tất cả khu vực</option>
                                <?php foreach ($areas as $maKV => $tenKV): ?>
                                    <option value="<?= e($maKV) ?>"><?= e($tenKV) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hbt_viTri" style="margin: 0; font-weight: 400; cursor: pointer;">Vị trí lưu trữ</label></th>
                        <td>
                            <select id="hbt_viTri" class="cukcuk-input-control" onchange="onViTriFilterChange()">
                                <option value="">Tất cả vị trí</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= e($loc['maViTri']) ?>" data-kv="<?= e($loc['maKhuVuc']) ?>"><?= e($loc['tenViTri']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cột phải: Thông tin chứng từ -->
        <div class="card cukcuk-card" style="margin-bottom: 0; overflow: hidden;">
            <div class="card-head" style="height: 40px; padding: 0 16px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0;">
                <h2 style="font-size: 13.5px; font-weight: 600; color: #0f172a; margin: 0;">Thông tin chứng từ</h2>
                <span class="badge badge-warn">Đang cách ly</span>
            </div>

            <table class="hbt-kv-table">
                <tbody>
                    <tr>
                        <th scope="row">Mã bản ghi dự kiến</th>
                        <td><?= e($nextCodePreview) ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Ngày phát hiện</th>
                        <td><?= date('d/m/Y H:i') ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Người phát hiện</th>
                        <td><?= e($creatorName) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. BẢNG LÔ HÀNG KÈM CHỌN LOẠI BẤT THƯỜNG & SỐ LƯỢNG TRỰC TIẾP Ở 2 CỘT CUỐI -->
    <div class="card cukcuk-card sticky-card" style="margin-top: 14px; margin-bottom: 0;">
        <div class="hbt-lot-filter-wrap is-collapsed" id="hbtLotFilterWrap">
            <div class="hbt-lot-filter-bar">
                <div class="hbt-lf-group lf-search">
                    <label for="flt_q">Tìm kiếm lô / hàng hóa</label>
                    <input type="text" id="flt_q" placeholder="Mã lô, tên hàng hóa, mã hàng..." oninput="renderLotTable()">
                </div>

                <!-- Các bộ lọc phụ: ẩn mặc định, bấm icon lọc để hiện cùng hàng -->
                <div class="hbt-lf-group hbt-lf-collapsible">
                    <label for="flt_hsdBucket">Mức hạn sử dụng</label>
                    <select id="flt_hsdBucket" onchange="renderLotTable()">
                        <option value="">Tất cả hạn</option>
                        <option value="expired">Đã hết hạn</option>
                        <option value="7">Gần nhất (&le; 7 ngày)</option>
                        <option value="30">Sắp hết hạn (&le; 30 ngày)</option>
                        <option value="90">Trong 90 ngày tới</option>
                        <option value="safe">Còn hạn dài (&gt; 90 ngày)</option>
                    </select>
                </div>
                <div class="hbt-lf-group hbt-lf-collapsible">
                    <label for="flt_hsdTu">Hạn sử dụng từ ngày</label>
                    <input type="date" id="flt_hsdTu" onchange="renderLotTable()">
                </div>
                <div class="hbt-lf-group hbt-lf-collapsible">
                    <label for="flt_hsdDen">Hạn sử dụng đến ngày</label>
                    <input type="date" id="flt_hsdDen" onchange="renderLotTable()">
                </div>
                <div class="hbt-lf-actions hbt-lf-collapsible">
                    <button type="button" class="btn btn-ghost" onclick="resetLotFilters()" title="Đặt lại bộ lọc lô hàng" style="height: 34px; width: 34px; padding: 0; border: 1px solid #d0d5dd; border-radius: 6px; background: #fff; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;">
                        <?= icon('repeat', 15) ?>
                    </button>
                </div>

                <button type="button" id="btnToggleLotFilter" class="hbt-lf-toggle" onclick="toggleLotFilters()"
                        title="Bộ lọc nâng cao" aria-expanded="false" aria-controls="hbtLotFilterWrap">
                    <?= icon('filter', 15) ?>
                    <span id="hbtLotFilterBadge" class="hbt-lf-badge" style="display: none;"></span>
                </button>
            </div>
        </div>

        <div class="table-wrap" style="overflow-x: auto; margin: 0;">
            <table class="table" id="hbtLotTable">
                <thead>
                    <tr>
                        <th style="width: 46px; text-align: center;">Chọn</th>
                        <th style="width: 125px;">Mã lô</th>
                        <th>Hàng hóa</th>
                        <th style="width: 130px; text-align: center;">Hạn sử dụng</th>
                        <th style="width: 115px; text-align: right;">Tồn hiện có</th>
                        <th style="width: 115px; text-align: center;">Trạng thái</th>
                        <th style="width: 155px;">Loại bất thường</th>
                        <th style="width: 150px;">Số lượng</th>
                    </tr>
                </thead>
                <tbody id="hbtLotTableBody"></tbody>
            </table>
        </div>

        <div class="sticky-footer-bar hbt-bottom-bar">
            <div style="display: flex; align-items: center; gap: 10px; flex: 1 1 320px;">
                <label for="hbt_moTa" style="font-size: 13px; color: #475467; white-space: nowrap; margin: 0;">Mô tả tình trạng</label>
                <input type="text" id="hbt_moTa" name="moTa" class="cukcuk-input-control"
                       maxlength="255" placeholder="VD: Rách bao do va chạm xe nâng..." style="height: 36px;">
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="<?= e(route('hang-bat-thuong')) ?>" class="btn btn-ghost" style="height: 36px; padding: 0 16px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; color: #344054; background: #ffffff;">
                    Hủy bỏ
                </a>
                <button type="button" class="btn btn-primary" onclick="openConfirmHbtModal()" style="height: 36px; padding: 0 20px; border-radius: 6px; font-size: 13.5px; font-weight: 600; background: #2563eb; color: #ffffff; border: none; cursor: pointer;">
                    Lưu ghi nhận
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Modal xác nhận ghi nhận hàng bất thường -->
<div id="confirmCreateHbtModal" class="cukcuk-modal-backdrop" style="display: none;" onclick="if(event.target===this) closeConfirmHbtModal();">
    <div class="cukcuk-modal-box">
        <div class="cukcuk-modal-header">
            <h3 class="cukcuk-modal-title">Xác nhận ghi nhận hàng bất thường</h3>
            <button type="button" class="cukcuk-modal-close" onclick="closeConfirmHbtModal()">&times;</button>
        </div>
        <div class="cukcuk-modal-body">
            <p id="cf_intro" style="margin: 0; font-size: 13.5px; color: #334155;">Bạn có chắc chắn muốn lưu bản ghi hàng bất thường này không?</p>
            <div id="cf_itemsList" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; font-size: 13px; max-height: 240px; overflow-y: auto;"></div>
        </div>
        <div class="cukcuk-modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeConfirmHbtModal()" style="height: 36px; padding: 0 18px; border: 1px solid #d0d5dd; border-radius: 6px;">Hủy bỏ</button>
            <button type="button" id="btnConfirmSubmitHbt" class="btn btn-primary" onclick="submitHbtForm()" style="height: 36px; padding: 0 20px; border-radius: 6px; background: #2563eb; color: #fff; font-weight: 600;">Xác nhận lưu</button>
        </div>
    </div>
</div>

<script>
var hbtLotsData = <?= json_encode($lotsJs, JSON_UNESCAPED_UNICODE) ?>;
var hbtLoaiOptions = <?= json_encode($loai, JSON_UNESCAPED_UNICODE) ?>;
var preKhuVuc = <?= json_encode($preKhuVuc ?? '', JSON_UNESCAPED_UNICODE) ?>;
var preViTri  = <?= json_encode($preViTri ?? '', JSON_UNESCAPED_UNICODE) ?>;
var preLoHang = <?= json_encode($preLoHang ?? '', JSON_UNESCAPED_UNICODE) ?>;
var allViTriOpts = [];
// rowState[key] = { checked: bool, loai: string, qty: string }
var rowState = {};

document.addEventListener('DOMContentLoaded', function () {
    var vtSel = document.getElementById('hbt_viTri');
    if (vtSel) {
        for (var i = 0; i < vtSel.options.length; i++) {
            var o = vtSel.options[i];
            if (o.value) {
                allViTriOpts.push({
                    value: o.value,
                    text: o.textContent,
                    kv: o.getAttribute('data-kv') || ''
                });
            }
        }
    }

    var kvSel = document.getElementById('hbt_khuVuc');
    if (preKhuVuc && kvSel) {
        for (var j = 0; j < kvSel.options.length; j++) {
            if (kvSel.options[j].value === preKhuVuc) {
                kvSel.value = preKhuVuc;
                onKhuVucChange();
                break;
            }
        }
    }
    if (preViTri && vtSel) {
        for (var k = 0; k < vtSel.options.length; k++) {
            if (vtSel.options[k].value === preViTri) {
                vtSel.value = preViTri;
                onViTriFilterChange();
                break;
            }
        }
    }
    if (preLoHang && preViTri) {
        selectHbtLot(preLoHang, preViTri);
    } else {
        renderLotTable();
    }
});

function escHtml(s) {
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function fmtDateVN(ymd) {
    if (!ymd) return '—';
    var p = ymd.slice(0, 10).split('-');
    return p.length === 3 ? (p[2] + '/' + p[1] + '/' + p[0]) : ymd;
}

function fmtNumVN(n) {
    return Number(n || 0).toLocaleString('vi-VN', { maximumFractionDigits: 2 });
}

function onKhoSwitch(maKho) {
    if (!maKho) return;
    window.location.href = <?= json_encode(route('hang-bat-thuong/create')) ?> + '?maKho=' + encodeURIComponent(maKho);
}

function lotKeyOf(maLo, maViTri) {
    return maLo + '|' + maViTri;
}

function getRowState(key) {
    return rowState[key] || { checked: false, loai: '', qty: '' };
}

function clearHiddenSelections() {
    rowState = {};
    document.getElementById('form_itemsJson').value = '';
    renderLotTable();
}

function onKhuVucChange() {
    var kv = document.getElementById('hbt_khuVuc').value;
    var vtSel = document.getElementById('hbt_viTri');
    var curVt = vtSel.value;

    vtSel.innerHTML = '<option value="">Tất cả vị trí</option>';
    var keepVt = false;
    allViTriOpts.forEach(function (o) {
        if (!kv || o.kv === kv) {
            var opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            if (o.value === curVt) {
                opt.selected = true;
                keepVt = true;
            }
            vtSel.appendChild(opt);
        }
    });
    if (!keepVt) vtSel.value = '';
    renderLotTable();
}

function onViTriFilterChange() {
    renderLotTable();
}

function resetLotFilters() {
    document.getElementById('flt_q').value = '';
    document.getElementById('flt_hsdBucket').value = '';
    document.getElementById('flt_hsdTu').value = '';
    document.getElementById('flt_hsdDen').value = '';
    renderLotTable();
}

function toggleLotFilters() {
    var wrap = document.getElementById('hbtLotFilterWrap');
    var btn = document.getElementById('btnToggleLotFilter');
    if (!wrap || !btn) return;
    var willOpen = wrap.classList.contains('is-collapsed');
    wrap.classList.toggle('is-collapsed', !willOpen);
    btn.classList.toggle('is-open', willOpen);
    btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
}

function updateLotFilterBadge() {
    var btn = document.getElementById('btnToggleLotFilter');
    var badge = document.getElementById('hbtLotFilterBadge');
    if (!btn || !badge) return;
    var bucket = document.getElementById('flt_hsdBucket');
    var tu = document.getElementById('flt_hsdTu');
    var den = document.getElementById('flt_hsdDen');
    var hasFilter = !!((bucket && bucket.value) || (tu && tu.value) || (den && den.value));
    badge.style.display = hasFilter ? 'inline-block' : 'none';
    btn.classList.toggle('is-filtered', hasFilter);
}

function findLotRecord(maLo, maViTri) {
    for (var i = 0; i < hbtLotsData.length; i++) {
        if (hbtLotsData[i].maLoHang === maLo && hbtLotsData[i].maViTri === maViTri) {
            return hbtLotsData[i];
        }
    }
    return null;
}

function setLotRowChecked(maLo, maViTri, checked) {
    var key = lotKeyOf(maLo, maViTri);
    if (!checked) {
        delete rowState[key];
    } else {
        var cur = getRowState(key);
        rowState[key] = { checked: true, loai: cur.loai || '', qty: cur.qty || '' };
    }
    renderLotTable();
    if (checked) {
        var sel = document.querySelector('select.row-loai-select[data-key="' + CSS.escape(key) + '"]');
        if (sel) sel.focus();
    }
}

function onRowLoaiChange(maLo, maViTri, selEl) {
    var key = lotKeyOf(maLo, maViTri);
    var loaiVal = selEl ? selEl.value : '';
    var cur = getRowState(key);
    rowState[key] = {
        checked: true,
        loai: loaiVal,
        qty: loaiVal === '' ? '' : (cur.qty || '')
    };
    renderLotTable();
    if (loaiVal !== '') {
        var qtyInp = document.querySelector('input.row-qty-input[data-key="' + CSS.escape(key) + '"]');
        if (qtyInp) qtyInp.focus();
    }
}

function onRowQtyInput(maLo, maViTri, inpEl) {
    var key = lotKeyOf(maLo, maViTri);
    var clean = inpEl ? inpEl.value.replace(/\D+/g, '') : '';
    if (inpEl && inpEl.value !== clean) inpEl.value = clean;
    var cur = getRowState(key);
    rowState[key] = { checked: true, loai: cur.loai || '', qty: clean };
}

function renderLotTable() {
    var kv = document.getElementById('hbt_khuVuc').value;
    var vt = document.getElementById('hbt_viTri').value;
    var tbody = document.getElementById('hbtLotTableBody');

    var q = (document.getElementById('flt_q').value || '').trim().toLowerCase();
    var bucket = document.getElementById('flt_hsdBucket').value;
    var hsdTu = document.getElementById('flt_hsdTu').value;
    var hsdDen = document.getElementById('flt_hsdDen').value;
    updateLotFilterBadge();

    var scoped = hbtLotsData.filter(function (r) {
        if (kv && r.maKhuVuc !== kv) return false;
        if (vt && r.maViTri !== vt) return false;
        return true;
    });

    var filtered = scoped.filter(function (r) {
        if (q !== '') {
            var hay = (r.maLoHang + ' ' + r.tenHangHoa + ' ' + r.maHangHoa + ' ' + r.soLoNhaCungCap).toLowerCase();
            if (hay.indexOf(q) === -1) return false;
        }
        var d = r.soNgayConLai;
        if (bucket === 'expired' && d >= 0) return false;
        if (bucket === '7' && (d < 0 || d > 7)) return false;
        if (bucket === '30' && (d < 0 || d > 30)) return false;
        if (bucket === '90' && (d < 0 || d > 90)) return false;
        if (bucket === 'safe' && d <= 90) return false;
        var hsd = (r.ngayHetHan || '').slice(0, 10);
        if (hsdTu && hsd && hsd < hsdTu) return false;
        if (hsdDen && hsd && hsd > hsdDen) return false;
        return true;
    });

    // Sắp xếp tự động theo hạn sử dụng thấp nhất trước (không đẩy dòng đã có hồ sơ lên đầu)
    filtered.sort(function (a, b) {
        if (a.soNgayConLai !== b.soNgayConLai) return a.soNgayConLai - b.soNgayConLai;
        var cmpHsd = (a.ngayHetHan || '').localeCompare(b.ngayHetHan || '');
        if (cmpHsd !== 0) return cmpHsd;
        return (a.maLoHang || '').localeCompare(b.maLoHang || '');
    });

    if (filtered.length === 0) {
        var msg = scoped.length === 0
            ? 'Phạm vi đang chọn hiện chưa có lô hàng nào còn tồn kho.'
            : 'Không có lô hàng nào khớp bộ lọc hiện tại.';
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:36px 20px;color:#64748b;">' + msg + '</td></tr>';
        return;
    }

    var loaiKeys = Object.keys(hbtLoaiOptions);
    var html = '';
    filtered.forEach(function (r) {
        var key = lotKeyOf(r.maLoHang, r.maViTri);
        var st = getRowState(key);
        var isSel = !!st.checked;

        var d = r.soNgayConLai;
        var hsdBadge = '<span class="badge badge-ok">Còn ' + d + ' ngày</span>';
        if (d < 0) {
            hsdBadge = '<span class="badge badge-danger">Quá hạn ' + Math.abs(d) + ' ngày</span>';
        } else if (d <= 7) {
            hsdBadge = '<span class="badge badge-danger">Còn ' + d + ' ngày</span>';
        } else if (d <= 30) {
            hsdBadge = '<span class="badge badge-warn">Còn ' + d + ' ngày</span>';
        } else if (d <= 90) {
            hsdBadge = '<span class="badge badge-info">Còn ' + d + ' ngày</span>';
        }

        // Trạng thái hiển thị đúng trạng thái xử lý (Đang cách ly / Đang xử lý / Bình thường) thay vì gắn tên loại Hư hỏng
        var stBadge = '<span class="badge badge-ok">Bình thường</span>';
        if (r.hbtTrangThai === 'Đang cách ly') {
            stBadge = '<span class="badge badge-warn" title="Đang có hồ sơ: ' + escHtml(r.hbtDangMo) + '">Đang cách ly</span>';
        } else if (r.hbtTrangThai === 'Đang xử lý') {
            stBadge = '<span class="badge badge-info" title="Đang có hồ sơ: ' + escHtml(r.hbtDangMo) + '">Đang xử lý</span>';
        }

        var jsLo = escHtml(r.maLoHang);
        var jsVt = escHtml(r.maViTri);

        var loaiCellHtml = '<span style="color:#cbd5e1;">—</span>';
        var qtyCellHtml = '<span style="color:#cbd5e1;">—</span>';
        if (isSel) {
            var optsHtml = '<option value="">-- Chọn loại --</option>';
            loaiKeys.forEach(function (lk) {
                optsHtml += '<option value="' + escHtml(lk) + '"' + (st.loai === lk ? ' selected' : '') + '>' + escHtml(hbtLoaiOptions[lk]) + '</option>';
            });
            loaiCellHtml = '<select class="hbt-row-control row-loai-select" data-key="' + escHtml(key) + '" onchange="onRowLoaiChange(\'' + jsLo + '\',\'' + jsVt + '\',this)">'
                + optsHtml
                + '</select>';

            if (st.loai !== '') {
                var maxInt = Math.floor(r.soLuongTon);
                var ph = maxInt >= 1 ? ('1 - ' + fmtNumVN(maxInt)) : 'Tồn < 1';
                qtyCellHtml = '<input type="text" inputmode="numeric" data-qty="positive" data-min="1"' + (maxInt >= 1 ? ' data-max="' + maxInt + '"' : '')
                    + ' class="hbt-row-control row-qty-input" data-key="' + escHtml(key) + '" value="' + escHtml(st.qty) + '" placeholder="' + ph + '"'
                    + ' oninput="onRowQtyInput(\'' + jsLo + '\',\'' + jsVt + '\',this)">';
            }
        }

        html += '<tr class="lot-choice' + (isSel ? ' selected' : '') + '" data-key="' + escHtml(key) + '" onclick="if(!getRowState(\'' + escHtml(key) + '\').checked) setLotRowChecked(\'' + jsLo + '\',\'' + jsVt + '\',true)">'
            + '<td style="text-align:center;" onclick="event.stopPropagation();"><input type="checkbox" ' + (isSel ? 'checked' : '') + ' onchange="setLotRowChecked(\'' + jsLo + '\',\'' + jsVt + '\',this.checked)" style="cursor:pointer; width:15px; height:15px;"></td>'
            + '<td style="font-weight:500;color:#0f172a;">' + jsLo + '</td>'
            + '<td style="color:#0f172a;">' + escHtml(r.tenHangHoa) + '</td>'
            + '<td style="text-align:center;">' + hsdBadge + '</td>'
            + '<td style="text-align:right;font-weight:500;color:#0f172a;">' + fmtNumVN(r.soLuongTon) + ' <small style="font-weight:400;color:#64748b;">' + escHtml(r.donViTinhChuan) + '</small></td>'
            + '<td style="text-align:center;">' + stBadge + '</td>'
            + '<td onclick="event.stopPropagation();">' + loaiCellHtml + '</td>'
            + '<td onclick="event.stopPropagation();">' + qtyCellHtml + '</td>'
            + '</tr>';
    });
    tbody.innerHTML = html;
}

function selectHbtLot(maLo, maViTri) {
    setLotRowChecked(maLo, maViTri, true);
}

function collectSelectedItemsForSubmit() {
    var items = [];
    var kv = document.getElementById('hbt_khuVuc').value;
    var vt = document.getElementById('hbt_viTri').value;

    for (var i = 0; i < hbtLotsData.length; i++) {
        var r = hbtLotsData[i];
        if (kv && r.maKhuVuc !== kv) continue;
        if (vt && r.maViTri !== vt) continue;

        var key = lotKeyOf(r.maLoHang, r.maViTri);
        var st = getRowState(key);
        if (!st.checked) continue;

        if (!st.loai) {
            fwModal.alert({
                title: 'Thiếu loại bất thường',
                message: 'Vui lòng chọn loại bất thường cho lô ' + r.maLoHang + '.',
                tone: 'warn',
                onConfirm: function () {
                    var s = document.querySelector('select.row-loai-select[data-key="' + CSS.escape(key) + '"]');
                    if (s) s.focus();
                }
            });
            return null;
        }
        if (r.hbtDangMo && r.hbtDangMo.indexOf(st.loai) !== -1) {
            fwModal.alert({
                title: 'Trùng bản ghi đang mở',
                message: 'Lô ' + r.maLoHang + ' tại ' + r.tenViTri + ' đã có bản ghi "' + st.loai + '" đang xử lý.',
                tone: 'warn'
            });
            return null;
        }
        var qtyInp = document.querySelector('input.row-qty-input[data-key="' + CSS.escape(key) + '"]');
        var rawQty = String(st.qty || '').trim();
        var err = (qtyInp && window.fwQty) ? window.fwQty.validateInput(qtyInp) : '';
        if (err === '' && (!/^\d+$/.test(rawQty) || parseInt(rawQty, 10) < 1)) {
            err = 'Vui lòng nhập số nguyên lớn hơn 0 cho lô ' + r.maLoHang + '.';
        }
        var maxInt = Math.floor(r.soLuongTon);
        if (err === '' && parseInt(rawQty, 10) > maxInt) {
            err = 'Số lượng của lô ' + r.maLoHang + ' không được vượt quá tồn hiện có (' + maxInt + ').';
        }
        if (err !== '') {
            fwModal.alert({
                title: 'Số lượng không hợp lệ',
                message: err,
                tone: 'warn',
                onConfirm: function () { if (qtyInp) qtyInp.focus(); }
            });
            return null;
        }
        items.push({
            maLoHang: r.maLoHang,
            maViTri: r.maViTri,
            tenHangHoa: r.tenHangHoa,
            tenKhuVuc: r.tenKhuVuc,
            tenViTri: r.tenViTri,
            donViTinhChuan: r.donViTinhChuan,
            loaiBatThuong: st.loai,
            soLuong: parseInt(rawQty, 10)
        });
    }

    if (items.length === 0) {
        fwModal.alert({
            title: 'Chưa chọn lô hàng',
            message: 'Vui lòng chọn ít nhất một lô hàng, chọn loại bất thường và nhập số lượng.',
            tone: 'warn'
        });
        return null;
    }
    return items;
}

function openConfirmHbtModal() {
    var items = collectSelectedItemsForSubmit();
    if (!items) return;

    document.getElementById('form_itemsJson').value = JSON.stringify(items.map(function (it) {
        return {
            maLoHang: it.maLoHang,
            maViTri: it.maViTri,
            loaiBatThuong: it.loaiBatThuong,
            soLuong: it.soLuong
        };
    }));

    var intro = document.getElementById('cf_intro');
    if (intro) {
        intro.textContent = items.length === 1
            ? 'Bạn có chắc chắn muốn lưu bản ghi hàng bất thường này không?'
            : ('Bạn có chắc chắn muốn lưu ' + items.length + ' bản ghi hàng bất thường sau không?');
    }

    var listEl = document.getElementById('cf_itemsList');
    var html = '';
    items.forEach(function (it, idx) {
        html += '<div style="display:flex; justify-content:space-between; align-items:center; gap:12px;' + (idx > 0 ? ' border-top:1px solid #e2e8f0; padding-top:8px;' : '') + '">'
            + '<div>'
            + '<div style="color:#0f172a; font-weight:500;">' + escHtml(it.maLoHang) + ' · ' + escHtml(it.tenHangHoa) + '</div>'
            + '<div style="font-size:12px; color:#64748b;">' + escHtml(it.tenKhuVuc) + ' · ' + escHtml(it.tenViTri) + '</div>'
            + '</div>'
            + '<div style="text-align:right; white-space:nowrap;">'
            + '<span class="badge badge-warn" style="margin-right:6px;">' + escHtml(it.loaiBatThuong) + '</span>'
            + '<span style="color:#b91c1c; font-weight:600;">' + fmtNumVN(it.soLuong) + ' ' + escHtml(it.donViTinhChuan) + '</span>'
            + '</div>'
            + '</div>';
    });
    listEl.innerHTML = html;
    document.getElementById('confirmCreateHbtModal').style.display = 'flex';
}

function closeConfirmHbtModal() {
    document.getElementById('confirmCreateHbtModal').style.display = 'none';
}

function submitHbtForm() {
    var btn = document.getElementById('btnConfirmSubmitHbt');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Đang lưu...';
    }
    document.getElementById('createHbtForm').submit();
}
</script>
