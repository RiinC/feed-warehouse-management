<?php
/**
 * View ton-kho/print — Bảng kê chi tiết tồn kho theo lô & vị trí, bản A4 độc lập ($layout = null).
 * Dữ liệu từ TonKhoController::printVoucher(): $item, $hbtRecords, $history, $historyLimit, $nguoiLap.
 */
$ton      = (float)($item['soLuongTon'] ?? 0);
$daGiu    = (float)($item['soLuongDaGiu'] ?? 0);
$khaDung  = (float)($item['soLuongKhaDung'] ?? ($ton - $daGiu));
$sucChua  = (float)($item['sucChuaToiDa'] ?? 0);
$conLai   = isset($item['soNgayConLai']) ? (int)$item['soNgayConLai'] : null;
$dvt      = (string)($item['donViTinhChuan'] ?? 'kg');
$mucHsd   = (string)($item['mucHanSuDung'] ?? '');
$ngayInTs = time();

$conLaiText = '—';
if ($conLai !== null) {
    $conLaiText = $conLai < 0 ? ('Quá ' . abs($conLai) . ' ngày') : ($conLai . ' ngày');
}

$tongHbt = 0.0;
foreach ($hbtRecords as $hb) {
    $tongHbt += (float)$hb['soLuong'];
}

$tenKho    = (string)($item['tenKho'] ?? '');
$tenKhuVuc = (string)($item['tenKhuVuc'] ?? '');
$tenViTri  = (string)($item['tenViTri'] ?? '');
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? ('In bảng kê tồn kho ' . $item['maLoHang'])) ?></title>
    <?php partial('components/print-voucher-style'); ?>
    <style>
        .print-voucher table.pv-table-kv th {
            text-align: left;
            width: 22%;
            font-weight: 600;
            background: #f7f7f7;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .print-voucher table.pv-table-kv td { width: 28%; }
        .print-voucher .pv-foot-note { margin-top: 10px; font-size: 9.5pt; font-style: italic; }
        /* Bảng nhiều dòng: không cắt dòng giữa 2 trang, lặp lại tiêu đề cột ở trang sau */
        .print-voucher table.pv-table tr { page-break-inside: avoid; break-inside: avoid; }
        .print-voucher table.pv-table thead { display: table-header-group; }
    </style>
</head>
<body>
<div class="pv-toolbar no-print">
    <div>Xem trước bản in khổ A4 — <strong><?= e($item['maLoHang']) ?></strong> (<?= e($tenViTri) ?>)</div>
    <div class="pv-toolbar-actions">
        <button type="button" class="pv-btn pv-btn-primary" onclick="window.print()">In bảng kê</button>
        <button type="button" class="pv-btn" onclick="window.close()">Đóng</button>
    </div>
</div>

<div class="pv-sheet">
    <div class="print-voucher">
        <table class="pv-head-table">
            <tr>
                <td style="text-align: left;">
                    <div style="font-weight: 700; text-transform: uppercase;">NHÀ MÁY THỨC ĂN CHĂN NUÔI FEEDWARE</div>
                    <div><b>Kho:</b> <?= e($tenKho) ?></div>
                    <div><b>Khu vực:</b> <?= $tenKhuVuc !== '' ? e($tenKhuVuc) : '—' ?> — <b>Vị trí:</b> <?= e($tenViTri) ?></div>
                </td>
                <td style="text-align: center;">
                    <div style="font-weight: 700; text-transform: uppercase;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                    <div style="font-weight: 700;">Độc lập – Tự do – Hạnh phúc</div>
                    <div style="margin: 2px auto 4px; width: 130px; border-bottom: 1px solid #000;"></div>
                    <div style="font-size: 9.5pt; font-style: italic;">
                        (Tham chiếu Mẫu số S09-DN — TT 200/2014/TT-BTC)
                    </div>
                </td>
            </tr>
        </table>

        <div class="pv-title">BẢNG KÊ CHI TIẾT TỒN KHO THEO LÔ HÀNG HÓA</div>
        <div class="pv-sub">
            Lập ngày <?= date('d', $ngayInTs) ?> tháng <?= date('m', $ngayInTs) ?> năm <?= date('Y', $ngayInTs) ?>
            <?php if ($mucHsd !== ''): ?>
                &nbsp;&nbsp;|&nbsp;&nbsp;<b>Mức hạn sử dụng:</b> <?= e($mucHsd) ?>
            <?php endif; ?>
        </div>

        <div class="pv-sec">I. Thông tin hàng hóa &amp; lô hàng</div>
        <table class="pv-table pv-table-kv">
            <tbody>
                <tr>
                    <th>Tên hàng hóa</th>
                    <td colspan="3"><?= e($item['tenHangHoa']) ?></td>
                </tr>
                <tr>
                    <th>Mã hàng hóa</th>
                    <td><?= e($item['maHangHoa']) ?></td>
                    <th>Loại hàng hóa</th>
                    <td><?= e(product_type_label($item['loaiHangHoa'] ?? '')) ?></td>
                </tr>
                <tr>
                    <th>Mã lô (hệ thống)</th>
                    <td><?= e($item['maLoHang']) ?></td>
                    <th>Số lô NCC</th>
                    <td><?= !empty($item['soLoNhaCungCap']) ? e($item['soLoNhaCungCap']) : '—' ?></td>
                </tr>
                <tr>
                    <th>Ngày sản xuất</th>
                    <td><?= e(fmt_date($item['ngaySanXuat'] ?? null) ?: '—') ?></td>
                    <th>Hạn sử dụng</th>
                    <td><?= e(fmt_date($item['ngayHetHan'] ?? null) ?: '—') ?></td>
                </tr>
                <tr>
                    <th>Thời hạn còn lại</th>
                    <td><?= e($conLaiText) ?></td>
                    <th>Đơn vị tính</th>
                    <td><?= e($dvt) ?></td>
                </tr>
                <tr>
                    <th>Điều kiện bảo quản</th>
                    <td colspan="3"><?= !empty($item['dieuKienBaoQuan']) ? e($item['dieuKienBaoQuan']) : '—' ?></td>
                </tr>
            </tbody>
        </table>

        <div class="pv-sec">II. Thông tin tồn &amp; vị trí lưu trữ</div>
        <table class="pv-table pv-table-kv">
            <tbody>
                <tr>
                    <th>Kho</th>
                    <td><?= e($tenKho) ?></td>
                    <th>Khu vực</th>
                    <td><?= $tenKhuVuc !== '' ? e($tenKhuVuc) : '—' ?></td>
                </tr>
                <tr>
                    <th>Vị trí lưu trữ</th>
                    <td><?= e($tenViTri) ?></td>
                    <th>Sức chứa tối đa</th>
                    <td><?= fmt_number($sucChua, 2) ?> kg</td>
                </tr>
                <tr>
                    <th>Tồn thực tế</th>
                    <td><b><?= fmt_number($ton, 2) ?> <?= e($dvt) ?></b></td>
                    <th>Khả dụng xuất kho</th>
                    <td><?= fmt_number($khaDung, 2) ?> <?= e($dvt) ?></td>
                </tr>
                <tr>
                    <th>Số lượng đã giữ</th>
                    <td><?= fmt_number($daGiu, 2) ?> <?= e($dvt) ?></td>
                    <th>Cập nhật tồn cuối</th>
                    <td><?= e(fmt_datetime($item['ngayCapNhatCuoi'] ?? null) ?: '—') ?></td>
                </tr>
            </tbody>
        </table>


        <div class="pv-sec">III. Hồ sơ hàng bất thường của lô tại vị trí</div>
        <?php if (empty($hbtRecords)): ?>
            <div class="pv-meta"><p>- Không có bản ghi hàng bất thường nào cho lô hàng tại vị trí này.</p></div>
        <?php else: ?>
            <table class="pv-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">STT</th>
                        <th style="width: 19%;">Mã bản ghi</th>
                        <th style="width: 26%;">Loại bất thường</th>
                        <th style="width: 14%;">Số lượng</th>
                        <th style="width: 14%;">Trạng thái</th>
                        <th style="width: 21%;">Ngày phát hiện</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hbtRecords as $i => $hb): ?>
                        <tr>
                            <td class="pv-center"><?= $i + 1 ?></td>
                            <td class="pv-center"><?= e($hb['maHangBatThuong']) ?></td>
                            <td><?= e($hb['loaiBatThuong']) ?></td>
                            <td class="pv-num"><?= fmt_number($hb['soLuong'], 2) ?> <?= e($dvt) ?></td>
                            <td class="pv-center"><?= e($hb['trangThai']) ?></td>
                            <td class="pv-center" style="white-space: nowrap;"><?= e(fmt_datetime($hb['ngayPhatHien']) ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="3" class="pv-center"><b>Tổng số lượng đã ghi nhận (mọi trạng thái)</b></td>
                        <td class="pv-num"><b><?= fmt_number($tongHbt, 2) ?></b></td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
            <div class="pv-foot-note">Tổng trên cộng toàn bộ bản ghi bất thường của lô tại vị trí, gồm cả bản ghi đã xử lý; đây không phải số lượng đang bị giữ.</div>
        <?php endif; ?>

        <div class="pv-sec">IV. Biến động tồn kho gần nhất (<?= (int)($historyLimit ?? 20) ?> lần gần nhất)</div>
        <?php if (empty($history)): ?>
            <div class="pv-meta"><p>- Chưa có biến động tồn kho nào được ghi nhận cho lô hàng tại vị trí này.</p></div>
        <?php else: ?>
            <table class="pv-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">STT</th>
                        <th style="width: 16%;">Thời gian</th>
                        <th style="width: 15%;">Loại giao dịch</th>
                        <th style="width: 13%;">Biến động</th>
                        <th style="width: 16%;">Tồn trước &rarr; Sau</th>
                        <th style="width: 17%;">Chứng từ</th>
                        <th style="width: 18%;">Người thực hiện</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $i => $h):
                        $delta = (float)$h['soLuongBienDong'];
                    ?>
                        <tr>
                            <td class="pv-center"><?= $i + 1 ?></td>
                            <td class="pv-center" style="white-space: nowrap;"><?= e(fmt_datetime($h['thoiGianPhatSinh']) ?: '—') ?></td>
                            <td><?= e(stock_tx_type_label($h['loaiGiaoDich'] ?? '')) ?></td>
                            <td class="pv-num" style="white-space: nowrap;"><?= ($delta > 0 ? '+' : '') . fmt_number($delta, 2) ?> <?= e($dvt) ?></td>
                            <td class="pv-num" style="white-space: nowrap;"><?= fmt_number($h['soLuongTonTruoc'], 2) ?> &rarr; <?= fmt_number($h['soLuongTonSau'], 2) ?></td>
                            <td class="pv-center"><?= e($h['maChungTuLienQuan'] ?: '—') ?></td>
                            <td><?= e($h['hoTen'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>


        <div class="pv-meta">
            <p>- <b>Ghi chú:</b> Số liệu tồn kho được trích xuất từ hệ thống FeedWare tại thời điểm lập bảng kê; đơn vị tính theo ĐVT chuẩn của hàng hóa.</p>
            <p>- Bảng kê được lập thành 02 bản, lưu tại Bộ phận Kho và Phòng Kế toán để theo dõi, đối chiếu số liệu tồn kho.</p>
        </div>

        <table class="pv-sign-table">
            <tr>
                <td>
                    <div class="pv-sign-title">Người lập bảng kê</div>
                    <div class="pv-sign-note">(Ký, ghi rõ họ tên)</div>
                    <div class="pv-sign-space"></div>
                    <div class="pv-sign-name"><?= e($nguoiLap ?? '') ?></div>
                </td>
                <td>
                    <div class="pv-sign-title">Thủ kho</div>
                    <div class="pv-sign-note">(Ký, ghi rõ họ tên)</div>
                    <div class="pv-sign-space"></div>
                    <div class="pv-sign-name"></div>
                </td>
                <td>
                    <div class="pv-sign-title">Kế toán trưởng</div>
                    <div class="pv-sign-note">(Ký, ghi rõ họ tên)</div>
                    <div class="pv-sign-space"></div>
                    <div class="pv-sign-name"></div>
                </td>
            </tr>
        </table>

        <div class="pv-foot-note">
            Bản in phát hành lúc <?= date('H:i', $ngayInTs) ?> ngày <?= date('d/m/Y', $ngayInTs) ?> — FeedWare Quản lý kho.
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 200);
});
</script>
</body>
</html>

