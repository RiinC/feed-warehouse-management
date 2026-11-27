<?php
/**
 * View kiem-ke/reconcile — UC26: Đối chiếu số sách với số thực tế, ghi nhận nguyên nhân chênh lệch.
 * Biến từ KiemKeController::reconcile(): $bb, $tienDo, $dong, $nguyenNhan.
 * Thiết kế phẳng, tinh gọn đồng bộ 100% với detail.php và create.php.
 */
$dongLech = [];
$dongKhop = [];
$daDoiChieu = 0;
foreach ($dong as $r) {
    if (abs((float)$r['chenhLech']) > 0.000001) {
        $dongLech[] = $r;
        if ($r['maChenhLech'] !== null && !empty($r['nguyenNhan'])) {
            $daDoiChieu++;
        }
    } else {
        $dongKhop[] = $r;
    }
}
$soLech = count($dongLech);
$soDaCoPhuongAn = (int)$tienDo['soChuaDeXuat'] === 0 && $soLech > 0 ? $soLech : max(0, $soLech - (int)$tienDo['soChuaDeXuat']);
?>

<style>
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
@keyframes popIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>

<!-- 1. TIÊU ĐỀ TRANG & NÚT QUAY LẠI (ĐỒNG BỘ DETAIL) -->
<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
    <h1 style="margin: 0; font-size: 22px; font-weight: 750; color: #0f172a; letter-spacing: -0.3px;">
        Đối chiếu số liệu kiểm kê <?= e($bb['maBienBanKiemKe']) ?>
    </h1>
    <div style="display: flex; align-items: center; gap: 8px;">
        <a class="btn btn-ghost" href="<?= e(route('kiem-ke/detail', ['ma' => $bb['maBienBanKiemKe']])) ?>" title="Quay lại" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px; text-decoration: none; font-size: 16px;">
            <span aria-hidden="true" style="line-height: 1;">←</span>
        </a>
    </div>
</div>

<!-- 2. CÁC CARD THỐNG KÊ (ĐỒNG BỘ FEEDWARE) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 20px;">
    <div class="card" style="padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; margin: 0;">
        <span style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;">Dòng đã ghi kết quả</span>
        <div style="font-size: 24px; font-weight: 700; color: #0f172a; margin-top: 6px;">
            <?= (int)$tienDo['soDaGhi'] ?><small style="font-size: 14px; font-weight: 500; color: #94a3b8;"> / <?= (int)$tienDo['soDong'] ?></small>
        </div>
    </div>
    <div class="card" style="padding: 16px 20px; background: #ffffff; border: 1px solid #fed7aa; border-radius: 8px; margin: 0;">
        <span style="font-size: 12px; font-weight: 600; color: #c2410c; text-transform: uppercase; letter-spacing: 0.3px;">Dòng chênh lệch</span>
        <div style="font-size: 24px; font-weight: 700; color: #ea580c; margin-top: 6px;">
            <?= $soLech ?>
        </div>
    </div>
    <div class="card" style="padding: 16px 20px; background: #ffffff; border: 1px solid <?= $daDoiChieu >= $soLech && $soLech > 0 ? '#bbf7d0' : '#fecaca' ?>; border-radius: 8px; margin: 0;">
        <span style="font-size: 12px; font-weight: 600; color: <?= $daDoiChieu >= $soLech && $soLech > 0 ? '#15803d' : '#b91c1c' ?>; text-transform: uppercase; letter-spacing: 0.3px;">Đã đối chiếu</span>
        <div style="font-size: 24px; font-weight: 700; color: <?= $daDoiChieu >= $soLech && $soLech > 0 ? '#16a34a' : '#dc2626' ?>; margin-top: 6px;">
            <?= $daDoiChieu ?><small style="font-size: 14px; font-weight: 500; color: #94a3b8;"> / <?= $soLech ?></small>
        </div>
    </div>
    <div class="card" style="padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; margin: 0;">
        <span style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;">Đã có phương án</span>
        <div style="font-size: 24px; font-weight: 700; color: #0f172a; margin-top: 6px;">
            <?= $soDaCoPhuongAn ?>
        </div>
    </div>
</div>

<!-- 3. FORM BẢNG ĐỐI CHIẾU SỐ LIỆU -->
<form id="reconcileForm" class="card sticky-card" method="post" action="<?= e(route('kiem-ke/save-reconcile')) ?>" style="padding: 22px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 20px;">
    <?= csrf_field() ?>
    <input type="hidden" name="ma" value="<?= e($bb['maBienBanKiemKe']) ?>">
    <input type="hidden" name="is_ajax" value="1">
    <?php foreach ($dongKhop as $khop): ?>
        <input type="hidden" name="dong[<?= e((string)$khop['maKetQuaKiemKe']) ?>][nguyenNhan]" value="">
    <?php endforeach; ?>

    <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0;">
        Danh sách lô hàng cần đối chiếu (<?= count($dongLech) ?> lô)
    </h2>

    <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #ffffff;">
        <table class="table" style="table-layout: fixed; width: 100%; margin: 0; border-collapse: collapse;">
            <thead>
                <tr style="background: #fafbfd; border-bottom: 1px solid #e2e8f0;">
                    <th style="width: 13%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Mã lô</th>
                    <th style="width: 20%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Tên hàng hóa</th>
                    <th style="width: 15%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Vị trí</th>
                    <th style="width: 6%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: center;">ĐVT</th>
                    <th style="width: 9%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: right;">Sổ sách</th>
                    <th style="width: 9%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #2563eb; text-align: right;">Kiểm kê</th>
                    <th style="width: 10%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: center;">Chênh lệch</th>
                    <th style="width: 18%; padding: 12px 10px; font-size: 12.5px; font-weight: 600; color: #475467; text-align: left;">Nguyên nhân</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$dongLech): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 32px; color: #64748b;">
                        <?= !$dong ? 'Chưa có kết quả kiểm kê nào để đối chiếu.' : 'Không có dòng chênh lệch nào cần đối chiếu — tất cả lô hàng đều khớp sổ sách.' ?>
                    </td>
                </tr>
            <?php endif; ?>
            <?php
            $fmtQty = static function ($num): string {
                return rtrim(rtrim(number_format((float)$num, 3, ',', '.'), '0'), ',');
            };
            foreach ($dongLech as $r):
                $maKQ = (string)$r['maKetQuaKiemKe'];
                $lech = (float)$r['chenhLech'];
                $khoa = in_array($r['trangThaiPhuongAn'], ['Đã phê duyệt', 'Đã thi hành', 'Hoàn tất'], true);
            ?>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px; color: #0f172a; font-weight: 500; font-size: 13px; vertical-align: middle;">
                        <?= e($r['maLoHang']) ?>
                    </td>
                    <td style="padding: 10px; color: #0f172a; font-weight: 400; font-size: 13px; vertical-align: middle;">
                        <?= e($r['tenHangHoa']) ?>
                    </td>
                    <td style="padding: 10px; color: #334155; font-weight: 400; font-size: 13px; vertical-align: middle;">
                        <?= e($r['tenViTri'] ?? $r['maViTri']) ?>
                    </td>
                    <td style="padding: 10px; text-align: center; color: #475467; font-size: 12.5px; vertical-align: middle;">
                        <?= e($r['donViTinhChuan'] ?? 'kg') ?>
                    </td>
                    <td style="padding: 10px; text-align: right; font-weight: 500; color: #0f172a; font-size: 13px; vertical-align: middle;">
                        <?= $fmtQty($r['soLuongSoSach']) ?>
                    </td>
                    <td style="padding: 10px; text-align: right; font-weight: 500; color: #2563eb; font-size: 13px; vertical-align: middle;">
                        <?= $fmtQty($r['soLuongThucTe']) ?>
                    </td>
                    <td style="padding: 10px; text-align: center; vertical-align: middle;">
                        <?php if ($lech > 0): ?>
                            <span style="font-weight: 400; color: #0284c7; font-size: 13px;">+<?= $fmtQty($lech) ?></span>
                        <?php else: ?>
                            <span style="font-weight: 400; color: #dc2626; font-size: 13px;"><?= $fmtQty($lech) ?></span>
                        <?php endif; ?>
                    </td>
                    <?php if ($khoa): ?>
                        <td style="padding: 10px; vertical-align: middle; color: #334155; font-size: 13px;">
                            <?= e(DISCREPANCY_REASON_MAP[$r['nguyenNhan']] ?? (string)$r['nguyenNhan']) ?>
                        </td>
                    <?php else: ?>
                        <td style="padding: 8px 10px; vertical-align: middle;">
                            <select name="dong[<?= e($maKQ) ?>][nguyenNhan]" class="cukcuk-input-control">
                                <option value="">— Chọn —</option>
                                <?php foreach ($nguyenNhan as $k => $label):
                                    $isSelected = ($r['nguyenNhan'] === $k || $r['nguyenNhan'] === $label);
                                ?>
                                    <option value="<?= e($label) ?>" <?= $isSelected ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 4. THANH HÀNH ĐỘNG DƯỚI BẢNG -->
    <div class="sticky-footer-bar" style="margin: 20px -24px -22px; border-radius: 0 0 8px 8px;">
        <a href="<?= e(route('kiem-ke/detail', ['ma' => $bb['maBienBanKiemKe']])) ?>" class="btn btn-ghost" style="height: 36px; padding: 0 16px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; color: #344054; background: #ffffff;">
            Hủy bỏ
        </a>
        <button id="btnSaveReconcile" type="button" style="height: 36px; padding: 0 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; background: #2563eb; color: #ffffff; font-size: 13.5px; font-weight: 600; border: none; cursor: pointer;">
            Lưu thay đổi
        </button>
    </div>
</form>

<!-- POPUP 1: MODAL XÁC NHẬN LƯU THAY ĐỔI -->
<div id="confirmReconcileModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px); align-items: center; justify-content: center; z-index: 9999; padding: 16px;">
    <div style="background: #ffffff; border-radius: 12px; width: 440px; max-width: 95vw; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15); overflow: hidden; animation: popIn 0.2s ease-out;">
        <div style="padding: 24px 24px 16px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; font-size: 22px; font-weight: 700;">
                ?
            </div>
            <h3 style="margin: 0 0 8px; font-size: 17px; font-weight: 700; color: #0f172a;">Xác nhận đối chiếu số liệu</h3>
            <p style="margin: 0; font-size: 13.5px; color: #475467; line-height: 1.5;">
                Bạn có chắc chắn muốn xác nhận lưu kết quả đối chiếu chênh lệch cho biên bản <strong><?= e($bb['maBienBanKiemKe']) ?></strong> không?
            </p>
        </div>
        <div style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
            <button type="button" id="btnCancelConfirm" style="height: 36px; padding: 0 16px; border: 1px solid #d0d5dd; background: #ffffff; color: #344054; border-radius: 6px; font-size: 13.5px; font-weight: 600; cursor: pointer;">
                Hủy bỏ
            </button>
            <button type="button" id="btnSubmitConfirm" style="height: 36px; padding: 0 20px; border: none; background: #2563eb; color: #ffffff; border-radius: 6px; font-size: 13.5px; font-weight: 600; cursor: pointer;">
                Xác nhận
            </button>
        </div>
    </div>
</div>

<!-- POPUP 2: MODAL THÔNG BÁO OK & ĐẾM LÙI TỰ ĐỘNG QUAY LẠI TRANG TRƯỚC (2-5s) -->
<div id="successReconcileModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px); align-items: center; justify-content: center; z-index: 10000; padding: 16px;">
    <div style="background: #ffffff; border-radius: 12px; width: 420px; max-width: 95vw; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden; text-align: center; padding: 28px 24px; animation: popIn 0.2s ease-out;">
        <div style="width: 54px; height: 54px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; font-size: 26px; font-weight: 700;">
            ✓
        </div>
        <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 700; color: #0f172a;">Lưu đối chiếu thành công!</h3>
        <p id="successMsgText" style="margin: 0 0 16px; font-size: 13.5px; color: #475467; line-height: 1.5;">
            Đã ghi nhận đối chiếu chênh lệch cho biên bản <?= e($bb['maBienBanKiemKe']) ?>.
        </p>
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; margin-bottom: 20px; font-size: 13px; color: #166534;">
            Tự động quay lại trang chi tiết sau <strong id="countdownNum" style="font-size: 16px; color: #15803d;">3</strong> giây...
        </div>
        <button type="button" id="btnGoBackNow" style="height: 36px; padding: 0 22px; border: none; background: #16a34a; color: #ffffff; border-radius: 6px; font-size: 13.5px; font-weight: 600; cursor: pointer;">
            Quay lại ngay
        </button>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('reconcileForm');
    var btnSave = document.getElementById('btnSaveReconcile');
    var confirmModal = document.getElementById('confirmReconcileModal');
    var btnCancelConfirm = document.getElementById('btnCancelConfirm');
    var btnSubmitConfirm = document.getElementById('btnSubmitConfirm');
    var successModal = document.getElementById('successReconcileModal');
    var countdownNum = document.getElementById('countdownNum');
    var btnGoBackNow = document.getElementById('btnGoBackNow');
    var redirectUrl = '<?= e(route('kiem-ke/detail', ['ma' => $bb['maBienBanKiemKe']])) ?>';
    var countdownInterval = null;

    // 1. Bấm "Lưu thay đổi" -> Hiện popup xác nhận
    btnSave.addEventListener('click', function(e) {
        e.preventDefault();
        confirmModal.style.display = 'flex';
    });

    // 2. Bấm "Hủy bỏ" trên popup xác nhận -> Đóng popup
    btnCancelConfirm.addEventListener('click', function() {
        confirmModal.style.display = 'none';
    });

    // 3. Bấm "Quay lại ngay" trên popup thành công
    btnGoBackNow.addEventListener('click', function() {
        if (countdownInterval) clearInterval(countdownInterval);
        window.location.href = redirectUrl;
    });

    // 4. Bấm "Xác nhận" -> Gửi form AJAX, nhận kết quả và mở popup thành công có đếm ngược 3s
    btnSubmitConfirm.addEventListener('click', function() {
        btnSubmitConfirm.disabled = true;
        btnSubmitConfirm.textContent = 'Đang lưu...';

        var formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(res) {
            return res.json().catch(function() {
                return { success: res.ok, message: 'Đã lưu đối chiếu số liệu thành công!' };
            });
        })
        .then(function(data) {
            confirmModal.style.display = 'none';
            btnSubmitConfirm.disabled = false;
            btnSubmitConfirm.textContent = 'Xác nhận';

            if (data.success) {
                // Hiện popup thành công
                if (data.message) {
                    var msgElem = document.getElementById('successMsgText');
                    if (msgElem) msgElem.textContent = data.message;
                }
                successModal.style.display = 'flex';

                // Đếm ngược 3 giây (khoảng 2-5s) rồi tự động quay lại
                var seconds = 3;
                if (countdownNum) countdownNum.textContent = seconds;

                countdownInterval = setInterval(function() {
                    seconds--;
                    if (countdownNum) countdownNum.textContent = seconds;
                    if (seconds <= 0) {
                        clearInterval(countdownInterval);
                        window.location.href = redirectUrl;
                    }
                }, 1000);
            } else {
                fwModal.alert({
                    title: 'Không thể lưu đối chiếu',
                    message: data.message || 'Có lỗi xảy ra khi lưu đối chiếu. Vui lòng thử lại.',
                    tone: 'danger'
                });
            }
        })
        .catch(function(err) {
            confirmModal.style.display = 'none';
            btnSubmitConfirm.disabled = false;
            btnSubmitConfirm.textContent = 'Xác nhận';
            fwModal.alert({
                title: 'Lỗi kết nối',
                message: 'Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại kết nối mạng.',
                tone: 'danger'
            });
        });
    });
})();
</script>
