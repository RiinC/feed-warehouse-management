<?php
/**
 * View in Biên bản kiểm kê Mẫu số 05-VT A4 độc lập ($layout = null).
 */
$ngayKKTs = !empty($bb['ngayBatDau']) ? strtotime((string)$bb['ngayBatDau']) : time();
$tongSoSachPrint = 0.0;
$tongThucTePrint = 0.0;
$tongThuaPrint   = 0.0;
$tongThieuPrint  = 0.0;
foreach ($scopeItems as $it) {
    $daKiemIt = ($it['maKetQuaKiemKe'] !== null && $it['soLuongThucTe'] !== null);
    $ssIt = (float)($daKiemIt ? $it['soLuongSoSach'] : $it['soLuongSoSachHienTai']);
    $tongSoSachPrint += $ssIt;
    if ($daKiemIt) {
        $ttIt = (float)$it['soLuongThucTe'];
        $dfIt = (float)$it['chenhLech'];
        $tongThucTePrint += $ttIt;
        if ($dfIt > 0.000001) {
            $tongThuaPrint += $dfIt;
        } elseif ($dfIt < -0.000001) {
            $tongThieuPrint += abs($dfIt);
        }
    }
}
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? ('In biên bản kiểm kê ' . $bb['maBienBanKiemKe'])) ?></title>
    <?php partial('components/print-voucher-style'); ?>
</head>
<body>
<div class="pv-toolbar no-print">
    <div>Xem trước bản in Mẫu 05-VT khổ A4 — <strong><?= e($bb['maBienBanKiemKe']) ?></strong></div>
    <div class="pv-toolbar-actions">
        <button type="button" class="pv-btn pv-btn-primary" onclick="window.print()">In biên bản</button>
        <button type="button" class="pv-btn" onclick="window.close()">Đóng</button>
    </div>
</div>

<div class="pv-sheet">
    <div class="print-voucher">
        <table class="pv-head-table">
            <tr>
                <td style="text-align: left;">
                    <div style="font-weight: 700; text-transform: uppercase;">NHÀ MÁY THỨC ĂN CHĂN NUÔI FEEDWARE</div>
                    <div><b>Đơn vị / Kho:</b> <?= e($bb['tenKho']) ?></div>
                    <div><b>Khu vực:</b> <?= !empty($bb['tenKhuVuc']) ? e($bb['tenKhuVuc']) : 'Toàn kho' ?></div>
                </td>
                <td style="text-align: center;">
                    <div style="font-weight: 700;">Mẫu số 05 - VT</div>
                    <div style="font-size: 9.5pt; font-style: italic;">
                        (Ban hành theo Thông tư số 200/2014/TT-BTC<br>Ngày 22/12/2014 của Bộ Tài chính)
                    </div>
                </td>
            </tr>
        </table>

        <div class="pv-title">BIÊN BẢN KIỂM KÊ VẬT TƯ, CÔNG CỤ, SẢN PHẨM, HÀNG HÓA</div>
        <div class="pv-sub">
            Thời điểm kiểm kê: <?= date('H', $ngayKKTs) ?> giờ <?= date('i', $ngayKKTs) ?> phút, ngày <?= date('d', $ngayKKTs) ?> tháng <?= date('m', $ngayKKTs) ?> năm <?= date('Y', $ngayKKTs) ?>
            &nbsp;&nbsp;|&nbsp;&nbsp;<b>Số:</b> <?= e($bb['maBienBanKiemKe']) ?>
        </div>

        <div class="pv-meta">
            <p>- <b>Loại kiểm kê:</b> <?= e(inventory_type_label($bb['loaiKiemKe'] ?? '')) ?> &nbsp;&nbsp;|&nbsp;&nbsp; <b>Trạng thái:</b> <?= e(inventory_status_label($bb['trangThai'] ?? '')) ?></p>
            <p>- <b>Ban kiểm kê gồm:</b> Ông/Bà <b><?= e($bb['tenTruongDoan'] ?? '—') ?></b> (Trưởng ban kiểm kê) cùng đại diện thủ kho, kế toán kho.</p>
            <p>- <b>Đã kiểm kê tại kho:</b> <?= e($bb['tenKho']) ?> (<?= !empty($bb['tenKhuVuc']) ? e($bb['tenKhuVuc']) : 'Toàn kho' ?>), kết quả như sau:</p>
        </div>

        <table class="pv-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 5%;">STT</th>
                    <th rowspan="2" style="width: 24%;">Tên, nhãn hiệu, quy cách vật tư, hàng hóa</th>
                    <th rowspan="2" style="width: 12%;">Mã lô</th>
                    <th rowspan="2" style="width: 15%;">Vị trí</th>
                    <th rowspan="2" style="width: 6%;">ĐVT</th>
                    <th style="width: 11%;">Theo sổ kế toán</th>
                    <th style="width: 11%;">Theo kiểm kê</th>
                    <th colspan="2" style="width: 16%;">Chênh lệch</th>
                </tr>
                <tr>
                    <th>Số lượng</th>
                    <th>Số lượng</th>
                    <th>Thừa (SL)</th>
                    <th>Thiếu (SL)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($scopeItems)): ?>
                    <tr><td colspan="9" class="pv-center">Không có mặt hàng trong phạm vi kiểm kê.</td></tr>
                <?php else: ?>
                    <?php foreach ($scopeItems as $i => $it):
                        $daKiemIt = ($it['maKetQuaKiemKe'] !== null && $it['soLuongThucTe'] !== null);
                        $ssIt = (float)($daKiemIt ? $it['soLuongSoSach'] : $it['soLuongSoSachHienTai']);
                        $ttIt = $daKiemIt ? (float)$it['soLuongThucTe'] : null;
                        $dfIt = $daKiemIt ? (float)$it['chenhLech'] : null;
                    ?>
                        <tr>
                            <td class="pv-center"><?= $i + 1 ?></td>
                            <td><?= e($it['tenHangHoa']) ?></td>
                            <td class="pv-center"><?= e($it['maLoHang']) ?></td>
                            <td><?= e($it['tenViTri'] ?? $it['maViTri']) ?></td>
                            <td class="pv-center"><?= e($it['donViTinhChuan'] ?? 'kg') ?></td>
                            <td class="pv-num"><?= fmt_number($ssIt, 2) ?></td>
                            <td class="pv-num"><?= $daKiemIt ? fmt_number($ttIt, 2) : '—' ?></td>
                            <td class="pv-num"><?= ($daKiemIt && $dfIt > 0.000001) ? fmt_number($dfIt, 2) : '' ?></td>
                            <td class="pv-num"><?= ($daKiemIt && $dfIt < -0.000001) ? fmt_number(abs($dfIt), 2) : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="5" class="pv-center"><b>Cộng</b></td>
                        <td class="pv-num"><b><?= fmt_number($tongSoSachPrint, 2) ?></b></td>
                        <td class="pv-num"><b><?= fmt_number($tongThucTePrint, 2) ?></b></td>
                        <td class="pv-num"><b><?= $tongThuaPrint > 0 ? fmt_number($tongThuaPrint, 2) : '0' ?></b></td>
                        <td class="pv-num"><b><?= $tongThieuPrint > 0 ? fmt_number($tongThieuPrint, 2) : '0' ?></b></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pv-meta">
            <p>- <b>Ý kiến của Ban kiểm kê về các khoản chênh lệch (nguyên nhân và kiến nghị xử lý):</b> <?= !empty($bb['ghiChu']) ? e($bb['ghiChu']) : '........................................................................................................................................' ?></p>
            <p>- <b>Ý kiến của Thủ kho:</b> ................................................................................................................................................</p>
        </div>

        <table class="pv-sign-table">
            <tr>
                <td>
                    <div class="pv-sign-title">Thủ kho</div>
                    <div class="pv-sign-note">(Ký, họ tên)</div>
                    <div class="pv-sign-space"></div>
                    <div class="pv-sign-name"></div>
                </td>
                <td>
                    <div class="pv-sign-title">Kế toán trưởng</div>
                    <div class="pv-sign-note">(Ký, họ tên)</div>
                    <div class="pv-sign-space"></div>
                    <div class="pv-sign-name"></div>
                </td>
                <td>
                    <div class="pv-sign-title">Trưởng ban kiểm kê</div>
                    <div class="pv-sign-note">(Ký, họ tên)</div>
                    <div class="pv-sign-space"></div>
                    <div class="pv-sign-name"><?= e($bb['tenTruongDoan'] ?? '') ?></div>
                </td>
            </tr>
        </table>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 200);
});
</script>
</body>
</html>
