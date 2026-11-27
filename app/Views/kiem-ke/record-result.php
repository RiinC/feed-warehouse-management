<?php
/**
 * View kiem-ke/record-result — UC25: Ghi nhận số lượng thực tế kiểm kê.
 * Bố cục đồng bộ với kiem-ke/detail.php:
 * - Header 1 dòng: Tiêu đề bên trái, các nút thao tác căn phải
 * - Card "Thông tin chung" dạng bảng 2 cột giống giao diện quản lý
 * - Bảng danh sách lô hàng: không dòng phụ mờ, vị trí dạng chữ, trạng thái dạng badge đổi tức thì khi nhập
 */

$totalItems = count($scopeItems);
$countedCount = 0;
$diffCount = 0;
foreach ($scopeItems as $item) {
    if ($item['maKetQuaKiemKe'] !== null && $item['soLuongThucTe'] !== null) {
        $countedCount++;
        if (abs((float)$item['chenhLech']) > 0.000001) {
            $diffCount++;
        }
    }
}
$uncountedCount = $totalItems - $countedCount;
?>

<style>
.qty-input {
    width: 100%;
    height: 32px;
    padding: 0 10px;
    font-size: 13px;
    font-weight: 500;
    text-align: right;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    outline: none;
    transition: all .15s ease;
    box-sizing: border-box;
}
.qty-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
}
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    text-align: center;
}
</style>

<!-- 1. TIÊU ĐỀ TRANG & THANH THAO TÁC (CĂN PHẢI) -->
<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #0f172a; letter-spacing: -0.3px;">
            Ghi nhận kiểm đếm <?= e($bb['maBienBanKiemKe']) ?>
        </h1>
    </div>
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <a class="btn btn-ghost" href="<?= e(route('kiem-ke')) ?>" title="Quay lại danh sách" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px; text-decoration: none; font-size: 16px;">
            <span aria-hidden="true" style="line-height: 1;">&larr;</span>
        </a>
        <button type="button" class="btn btn-ghost" id="btnFillBook" style="height: 36px; padding: 0 14px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 13.5px; font-weight: 600; color: #2563eb; background: #eff6ff; display: inline-flex; align-items: center; cursor: pointer;">
            Tự động điền
        </button>
        <button type="button" class="btn btn-ghost" onclick="saveDraftCounting()" style="height: 36px; padding: 0 16px; border: 1px solid #2563eb; border-radius: 6px; font-size: 13.5px; font-weight: 600; color: #2563eb; background: #ffffff; display: inline-flex; align-items: center; cursor: pointer;">
            Ghi lại
        </button>
        <button type="button" class="btn btn-primary" onclick="submitForApproval()" style="height: 36px; padding: 0 16px; border-radius: 6px; font-size: 13.5px; font-weight: 600; background: #2563eb; color: #ffffff; border: none; display: inline-flex; align-items: center; cursor: pointer;">
            Gửi duyệt
        </button>
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
            'Mã biên bản'         => (string)$bb['maBienBanKiemKe'],
            'Ngày bắt đầu'        => !empty($bb['ngayBatDau']) ? date('d/m/Y H:i', strtotime($bb['ngayBatDau'])) : '—',
            'Phân công kiểm đếm'  => (string)($bb['tenTruongDoan'] ?? 'Chưa phân công'),
            'Số lô kiểm đếm'      => $totalItems . ' lô',
        ],
    ]
);
?>

<!-- 3. BẢNG NHẬP KẾT QUẢ KIỂM ĐẾM -->
<form id="countingForm" method="post" action="<?= e(route('kiem-ke/save-result')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="ma" value="<?= e($bb['maBienBanKiemKe']) ?>">

    <div class="card cukcuk-card sticky-card" style="padding: 0; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
        <div style="padding: 12px 18px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
            <h2 style="font-size: 14px; font-weight: 600; color: #0f172a; margin: 0;">
                Danh sách lô hàng cần kiểm đếm (<?= $totalItems ?> lô)
            </h2>
        </div>

<?php
$canReportHbt = can('hang-bat-thuong', 'record');
?>
        <div style="overflow-x: auto;">
            <table class="table" style="table-layout: fixed; width: 100%; margin: 0; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <th style="width: 48px; padding: 10px 8px; font-size: 12px; font-weight: 600; color: #475467; text-align: center;">STT</th>
                        <th style="width: 13%; padding: 10px 12px; font-size: 12px; font-weight: 600; color: #475467; text-align: left;">Mã lô</th>
                        <th style="width: 21%; padding: 10px 12px; font-size: 12px; font-weight: 600; color: #475467; text-align: left;">Tên hàng hóa</th>
                        <th style="width: 16%; padding: 10px 12px; font-size: 12px; font-weight: 600; color: #475467; text-align: left;">Vị trí lưu trữ</th>
                        <th style="width: 10%; padding: 10px 12px; font-size: 12px; font-weight: 600; color: #475467; text-align: right;">Sổ sách</th>
                        <th style="width: 13%; padding: 10px 12px; font-size: 12px; font-weight: 600; color: #2563eb; text-align: right;">Thực tế (*)</th>
                        <th style="width: 11%; padding: 10px 8px; font-size: 12px; font-weight: 600; color: #475467; text-align: center;">Chênh lệch</th>
                        <th style="width: 11%; padding: 10px 8px; font-size: 12px; font-weight: 600; color: #475467; text-align: center;">Trạng thái</th>
                        <?php if ($canReportHbt): ?>
                            <th style="width: 56px; padding: 10px 6px; font-size: 12px; font-weight: 600; color: #475467; text-align: center;" title="Báo hàng bất thường (rách vỡ / hư hỏng)">HBT</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($scopeItems)): ?>
                    <tr>
                        <td colspan="<?= $canReportHbt ? 9 : 8 ?>" style="text-align: center; padding: 36px; color: #64748b;">
                            Không có lô hàng nào trong kho hoặc khu vực đã chọn.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($scopeItems as $idx => $item):
                        $daKiem = ($item['maKetQuaKiemKe'] !== null && $item['soLuongThucTe'] !== null);
                        $soSach = (float)($daKiem ? $item['soLuongSoSach'] : $item['soLuongSoSachHienTai']);
                        $thucTe = $daKiem ? (float)$item['soLuongThucTe'] : null;
                        $diff = $daKiem ? (float)$item['chenhLech'] : null;
                        $valInput = $daKiem ? (string)(int)round($thucTe) : '';
                        $dvt = $item['donViTinhChuan'] ?? 'kg';
                    ?>
                        <tr class="count-row" style="border-bottom: 1px solid #f1f5f9;" data-row-idx="<?= $idx ?>">
                            <td style="padding: 9px 8px; text-align: center; color: #64748b; font-size: 13px;">
                                <?= $idx + 1 ?>
                                <input type="hidden" name="items[<?= $idx ?>][maLoHang]" value="<?= e($item['maLoHang']) ?>">
                                <input type="hidden" name="items[<?= $idx ?>][maViTri]" value="<?= e($item['maViTri']) ?>">
                                <input type="hidden" name="items[<?= $idx ?>][soLuongSoSach]" value="<?= $soSach ?>">
                            </td>
                            <td style="padding: 9px 12px; font-weight: 500; color: #0f172a; font-size: 13px;">
                                <?= e($item['maLoHang']) ?>
                            </td>
                            <td style="padding: 9px 12px; font-weight: 400; color: #0f172a; font-size: 13px;">
                                <?= e($item['tenHangHoa']) ?>
                            </td>
                            <td style="padding: 9px 12px; color: #334155; font-size: 13px;">
                                <?= e($item['tenViTri'] ?? $item['maViTri']) ?>
                            </td>
                            <td style="padding: 9px 12px; text-align: right; font-weight: 500; color: #0f172a; font-size: 13px;" data-so-sach="<?= $soSach ?>">
                                <?= fmt_qty($soSach) ?> <span style="font-weight: 400; color: #64748b;"><?= e($dvt) ?></span>
                            </td>
                            <td style="padding: 7px 12px;">
                                <input type="text" inputmode="numeric" data-qty="nonneg"
                                       class="qty-input actual-input"
                                       name="items[<?= $idx ?>][soLuongThucTe]"
                                       value="<?= e($valInput) ?>"
                                       placeholder="Nhập SL"
                                       data-so-sach="<?= e((string)round($soSach)) ?>">
                            </td>
                            <td style="padding: 9px 8px; text-align: center; font-size: 13px;" class="cell-diff">
                                <?php if (!$daKiem): ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php elseif (abs($diff) < 0.000001): ?>
                                    <span style="color: #16a34a; font-weight: 400;">0</span>
                                <?php elseif ($diff > 0): ?>
                                    <span style="color: #0284c7; font-weight: 400;">+<?= fmt_qty($diff) ?></span>
                                <?php else: ?>
                                    <span style="color: #dc2626; font-weight: 400;">-<?= fmt_qty(abs($diff)) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 9px 8px; text-align: center;" class="cell-status">
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
                            <?php if ($canReportHbt): ?>
                                <td style="padding: 7px 6px; text-align: center;">
                                    <?php if ($soSach > 0):
                                        $hbtUrl = route('hang-bat-thuong/create', [
                                            'maKho'    => $bb['maKho'],
                                            'maKhuVuc' => $item['maKhuVuc'],
                                            'maViTri'  => $item['maViTri'],
                                            'maLoHang' => $item['maLoHang'],
                                        ]);
                                    ?>
                                        <a href="<?= e($hbtUrl) ?>" target="_blank" rel="noopener" title="Báo hàng bất thường cho lô này"
                                           style="display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 6px; color: #c2410c; background: #fff7ed; border: 1px solid #ffedd5; text-decoration: none;">
                                            <?= icon('alert-triangle', 14) ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="sticky-footer-bar">
            <a class="btn btn-ghost" href="<?= e(route('kiem-ke')) ?>" style="height: 36px; padding: 0 16px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; color: #344054; background: #fff;">
                Hủy bỏ
            </a>
            <button type="button" class="btn btn-ghost" onclick="saveDraftCounting()" style="height: 36px; padding: 0 16px; border: 1px solid #2563eb; border-radius: 6px; font-size: 13.5px; font-weight: 600; color: #2563eb; background: #ffffff; cursor: pointer;">
                Ghi lại
            </button>
            <button type="button" class="btn btn-primary" onclick="submitForApproval()" style="height: 36px; padding: 0 20px; border-radius: 6px; font-size: 13.5px; font-weight: 600; background: #2563eb; color: #ffffff; border: none; cursor: pointer;">
                Gửi duyệt
            </button>
        </div>
    </div>
</form>

<script>
function parseRecordQty(raw) {
    var s = String(raw == null ? '' : raw).trim();
    if (!/^\d+$/.test(s)) return null;
    var n = parseInt(s, 10);
    return (isFinite(n) && n >= 0) ? n : null;
}

document.addEventListener('DOMContentLoaded', function () {
    var inputs = document.querySelectorAll('.actual-input');

    function formatNumber(num) {
        return Math.round(num).toLocaleString('vi-VN', { maximumFractionDigits: 0 });
    }

    function recalcRow(input) {
        var row = input.closest('tr');
        if (!row) return;

        var cellDiff = row.querySelector('.cell-diff');
        var cellStatus = row.querySelector('.cell-status');
        var soSach = parseFloat(input.dataset.soSach) || 0;
        var cleaned = String(input.value || '').replace(/\D+/g, '');
        if (input.value !== cleaned) {
            input.value = cleaned;
        }
        var rawVal = cleaned.trim();

        if (rawVal === '') {
            input.style.borderColor = '';
            cellDiff.innerHTML = '<span style="color: #94a3b8;">—</span>';
            cellStatus.innerHTML = '<span class="badge" style="background: #f1f5f9; color: #64748b;">Chưa kiểm</span>';
            return;
        }

        var thucTe = parseRecordQty(rawVal);
        if (thucTe === null) {
            input.style.borderColor = '#dc2626';
            cellDiff.innerHTML = '<span style="color: #94a3b8;">—</span>';
            cellStatus.innerHTML = '<span class="badge" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">Không hợp lệ</span>';
            return;
        }
        input.style.borderColor = '';
        var diff = Math.round(thucTe - soSach);

        if (diff === 0) {
            cellDiff.innerHTML = '<span style="color: #16a34a; font-weight: 400;">0</span>';
            cellStatus.innerHTML = '<span class="badge" style="background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7;">Khớp</span>';
        } else if (diff > 0) {
            cellDiff.innerHTML = '<span style="color: #0284c7; font-weight: 400;">+' + formatNumber(diff) + '</span>';
            cellStatus.innerHTML = '<span class="badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">Thừa</span>';
        } else {
            cellDiff.innerHTML = '<span style="color: #dc2626; font-weight: 400;">-' + formatNumber(Math.abs(diff)) + '</span>';
            cellStatus.innerHTML = '<span class="badge" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">Thiếu</span>';
        }
    }

    function updateSummary() {
        var total = inputs.length;
        var counted = 0;
        var diffCount = 0;

        inputs.forEach(function (inp) {
            var raw = inp.value.trim();
            var thucTe = raw === '' ? null : parseRecordQty(raw);
            if (thucTe !== null) {
                counted++;
                var soSach = parseFloat(inp.dataset.soSach) || 0;
                if (Math.abs(thucTe - soSach) > 0.0001) {
                    diffCount++;
                }
            }
        });

        var uncounted = total - counted;
        var elCounted = document.getElementById('statCounted');
        var elUncounted = document.getElementById('statUncounted');
        var elDiff = document.getElementById('statDiff');

        if (elCounted) elCounted.textContent = counted;
        if (elUncounted) elUncounted.textContent = uncounted;
        if (elDiff) elDiff.textContent = diffCount;
    }

    inputs.forEach(function (input) {
        input.addEventListener('input', function () {
            recalcRow(input);
            updateSummary();
        });
        input.addEventListener('change', function () {
            recalcRow(input);
            updateSummary();
        });
    });

    var btnFill = document.getElementById('btnFillBook');
    if (btnFill) {
        btnFill.addEventListener('click', function () {
            inputs.forEach(function (input) {
                if (input.value.trim() === '') {
                    input.value = input.dataset.soSach;
                    recalcRow(input);
                }
            });
            updateSummary();
        });
    }
});

function validateAllCountingInputs() {
    var inputs = document.querySelectorAll('.actual-input');
    for (var i = 0; i < inputs.length; i++) {
        var raw = inputs[i].value.trim();
        if (raw !== '' && parseRecordQty(raw) === null) {
            inputs[i].style.borderColor = '#dc2626';
            inputs[i].focus();
            fwModal.alert({
                title: 'Số lượng thực tế không hợp lệ',
                message: 'Chỉ được nhập số nguyên không âm (0-9), không nhập số âm, dấu chấm hoặc dấu phẩy.',
                tone: 'warn'
            });
            return false;
        }
    }
    return true;
}

function saveDraftCounting() {
    if (!validateAllCountingInputs()) return;
    var form = document.getElementById('countingForm');
    form.action = <?= json_encode(route('kiem-ke/save-result')) ?>;
    form.submit();
}

function submitForApproval() {
    if (!validateAllCountingInputs()) return;
    var inputs = document.querySelectorAll('.actual-input');
    var emptyCount = 0;
    inputs.forEach(function (inp) {
        if (inp.value.trim() === '') emptyCount++;
    });

    if (emptyCount > 0) {
        fwModal.alert({
            title: 'Chưa kiểm đếm đủ số lượng',
            htmlMessage: 'Còn <strong>' + emptyCount + '</strong> lô hàng chưa kiểm đếm. Bạn phải nhập số lượng thực tế cho 100% lô hàng trong phạm vi (nhập 0 nếu hết hàng) trước khi gửi duyệt.',
            tone: 'warn'
        });
        return;
    }

    fwModal.confirm({
        title: 'Xác nhận gửi duyệt',
        message: 'Bạn có chắc chắn muốn gửi toàn bộ kết quả kiểm đếm cho Quản lý kho xem xét và xác nhận? Sau khi gửi, bạn sẽ không thể chỉnh sửa kết quả trừ khi bị Quản lý trả lại.',
        tone: 'info',
        okText: 'Gửi duyệt',
        onConfirm: function () {
            var form = document.getElementById('countingForm');
            form.action = <?= json_encode(route('kiem-ke/submit')) ?>;
            form.submit();
        }
    });
}
</script>
