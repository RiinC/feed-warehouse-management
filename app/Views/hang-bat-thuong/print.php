<?php
/**
 * View in chứng từ A4 độc lập cho Bản ghi hàng bất thường ($layout = null).
 */
$paHieuLuc = null;
foreach ($phuongAn as $pa) {
    if (in_array($pa['trangThai'], ['Hoàn tất', 'Đang xử lý', 'Chờ xử lý'], true)) {
        $paHieuLuc = $pa;
        break;
    }
}
$bpCodePrint = $paHieuLuc ? resolution_method_code($paHieuLuc['bienPhapXuLy']) : '';
$tieuDeChungTu = 'BIÊN BẢN GHI NHẬN VÀ ĐỀ XUẤT XỬ LÝ HÀNG BẤT THƯỜNG';
if ($bpCodePrint === 'TieuHuy') {
    $tieuDeChungTu = 'BIÊN BẢN XỬ LÝ TIÊU HỦY HÀNG HÓA HƯ HỎNG, HẾT HẠN';
} elseif ($bpCodePrint === 'XuatTraNhaCungCap') {
    $tieuDeChungTu = 'BIÊN BẢN XỬ LÝ VÀ XUẤT TRẢ HÀNG NHÀ CUNG CẤP';
} elseif ($bpCodePrint === 'GhiNhanHaoHut') {
    $tieuDeChungTu = 'BIÊN BẢN XÁC NHẬN HAO HỤT, TỔN THẤT HÀNG HÓA';
} elseif ($bpCodePrint === 'HaCapChatLuong') {
    $tieuDeChungTu = 'BIÊN BẢN ĐÁNH GIÁ VÀ HẠ CẤP CHẤT LƯỢNG HÀNG HÓA';
}
$ngayLapTs = !empty($hbt['ngayPhatHien']) ? strtotime((string)$hbt['ngayPhatHien']) : time();
$phieuChinh = !empty($phieu) ? $phieu[0] : null;
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? ('In chứng từ ' . $hbt['maHangBatThuong'])) ?></title>
    <?php partial('components/print-voucher-style'); ?>
</head>
<body>
<div class="pv-toolbar no-print">
    <div>Xem trước bản in khổ A4 — <strong><?= e($hbt['maHangBatThuong']) ?></strong></div>
    <div class="pv-toolbar-actions">
        <button type="button" class="pv-btn pv-btn-primary" onclick="window.print()">In chứng từ</button>
        <button type="button" class="pv-btn" onclick="window.close()">Đóng</button>
    </div>
</div>

<div class="pv-sheet">
    <div class="print-voucher">
        <table class="pv-head-table">
            <tr>
                <td style="text-align: left;">
                    <div style="font-weight: 700; text-transform: uppercase;">NHÀ MÁY THỨC ĂN CHĂN NUÔI FEEDWARE</div>
                    <div><b>Kho:</b> <?= e($hbt['tenKho'] ?? '—') ?></div>
                    <div><b>Bộ phận:</b> <?= e(!empty($hbt['tenKhuVuc']) ? $hbt['tenKhuVuc'] : 'Quản lý kho') ?></div>
                </td>
                <td style="text-align: center;">
                    <div style="font-weight: 700; text-transform: uppercase;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                    <div style="font-weight: 700;">Độc lập – Tự do – Hạnh phúc</div>
                    <div style="margin: 2px auto 4px; width: 130px; border-bottom: 1px solid #000;"></div>
                    <div style="font-size: 9.5pt; font-style: italic;">(Tham chiếu Mẫu 03-VT / 02-VT · TT 200/2014/TT-BTC)</div>
                </td>
            </tr>
        </table>

        <div class="pv-title"><?= e($tieuDeChungTu) ?></div>
        <div class="pv-sub">
            Ngày <?= date('d', $ngayLapTs) ?> tháng <?= date('m', $ngayLapTs) ?> năm <?= date('Y', $ngayLapTs) ?>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <b>Số:</b> <?= e($hbt['maHangBatThuong']) ?>
            <?php if ($phieuChinh): ?>
                &nbsp;&nbsp;|&nbsp;&nbsp;<b>Phiếu ĐC:</b> <?= e($phieuChinh['maPhieuDieuChinh']) ?>
            <?php endif; ?>
        </div>

        <div class="pv-meta">
            <p>- <b>Căn cứ:</b> Quy trình kiểm soát chất lượng, bảo quản hàng hóa tại kho và kết quả kiểm tra thực tế ngày <?= date('d/m/Y', $ngayLapTs) ?>.</p>
            <p>- <b>Địa điểm / Vị trí lưu giữ:</b> <?= e($hbt['tenViTri']) ?> — <?= e(!empty($hbt['tenKhuVuc']) ? $hbt['tenKhuVuc'] : '') ?> (<?= e($hbt['tenKho']) ?>).</p>
            <p>- <b>Thành phần lập biên bản:</b></p>
            <p style="padding-left: 16px;">1. Ông/Bà: <b><?= e($hbt['tenNhanVienPhatHien'] ?? '—') ?></b> — Người phát hiện / Lập biên bản (<?= !empty($hbt['ngayPhatHien']) ? date('H:i d/m/Y', $ngayLapTs) : '—' ?>)</p>
            <?php if ($paHieuLuc): ?>
                <p style="padding-left: 16px;">2. Ông/Bà: <b><?= e((string)$paHieuLuc['tenNguoiDeXuat']) ?></b> — Người đề xuất phương án xử lý</p>
                <?php if (!empty($paHieuLuc['tenNguoiPheDuyet'])): ?>
                    <p style="padding-left: 16px;">3. Ông/Bà: <b><?= e($paHieuLuc['tenNguoiPheDuyet']) ?></b> — Quản lý / Người phê duyệt</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="pv-sec">I. Danh mục hàng hóa bất thường</div>
        <table class="pv-table">
            <thead>
                <tr>
                    <th style="width: 5%;">STT</th>
                    <th style="width: 12%;">Mã hàng</th>
                    <th style="width: 22%;">Tên hàng hóa, quy cách</th>
                    <th style="width: 14%;">Mã lô</th>
                    <th style="width: 11%;">Hạn sử dụng</th>
                    <th style="width: 7%;">ĐVT</th>
                    <th style="width: 10%;">Số lượng</th>
                    <th style="width: 19%;">Loại / Hiện trạng</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="pv-center">1</td>
                    <td class="pv-center"><?= e($hbt['maHangHoa'] ?? '—') ?></td>
                    <td><?= e($hbt['tenHangHoa']) ?></td>
                    <td class="pv-center"><?= e($hbt['maLoHang']) ?></td>
                    <td class="pv-center"><?= !empty($hbt['ngayHetHan']) ? date('d/m/Y', strtotime((string)$hbt['ngayHetHan'])) : '—' ?></td>
                    <td class="pv-center"><?= e($hbt['donViTinhChuan']) ?></td>
                    <td class="pv-num"><b><?= fmt_number($hbt['soLuong'], 2) ?></b></td>
                    <td><?= e($hbt['loaiBatThuong']) ?><?= !empty($hbt['moTa']) ? ': ' . e($hbt['moTa']) : '' ?></td>
                </tr>
                <tr>
                    <td colspan="6" class="pv-center"><b>Cộng</b></td>
                    <td class="pv-num"><b><?= fmt_number($hbt['soLuong'], 2) ?></b></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="pv-sec">II. Phương án xử lý, phê duyệt và kết quả thực hiện</div>
        <?php if ($paHieuLuc): ?>
            <div class="pv-meta">
                <p>- <b>Mã phương án:</b> <?= e($paHieuLuc['maPhuongAn']) ?> &nbsp;|&nbsp; <b>Biện pháp xử lý:</b> <?= e(resolution_method_label($paHieuLuc['bienPhapXuLy'])) ?> &nbsp;|&nbsp; <b>Trạng thái:</b> <?= e(proposal_status_label($paHieuLuc['trangThai'])) ?></p>
                <p>- <b>Ý kiến phê duyệt / chỉ đạo:</b> <?= !empty($paHieuLuc['yKienPheDuyet']) ? e($paHieuLuc['yKienPheDuyet']) : '........................................................................................................................' ?></p>
                <?php if ($phieuChinh): ?>
                    <p>- <b>Phiếu điều chỉnh kho kèm theo:</b> <b><?= e($phieuChinh['maPhieuDieuChinh']) ?></b> (<?= e(adjustment_type_label($phieuChinh['loaiDieuChinh'])) ?> <?= fmt_number($phieuChinh['soLuongDieuChinh'], 2) ?> <?= e($hbt['donViTinhChuan']) ?>) — Thi hành bởi: <?= e($phieuChinh['tenNhanVienThiHanh']) ?> lúc <?= date('H:i d/m/Y', strtotime((string)$phieuChinh['ngayThiHanh'])) ?>.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="pv-meta">
                <p>- <b>Biện pháp đề xuất xử lý:</b> .................................................................................................................................</p>
                <p>- <b>Ý kiến phê duyệt của Lãnh đạo / Quản lý kho:</b> ...........................................................................................................</p>
                <p>- <b>Thời gian &amp; địa điểm thực hiện:</b> ........................................................................................................................</p>
            </div>
        <?php endif; ?>

        <div class="pv-sec">III. Kết luận</div>
        <p style="margin: 3px 0 8px;">
            Các bên đã kiểm tra thực tế đúng số lượng, chủng loại và tình trạng lô hàng nêu trên. Biên bản được lập thành 02 bản có giá trị như nhau, lưu tại Bộ phận Kho và Phòng Kế toán làm căn cứ ghi sổ kho và hạch toán theo quy định.
        </p>

        <table class="pv-sign-table">
            <tr>
                <td>
                    <div class="pv-sign-title">Người phát hiện / Lập</div>
                    <div class="pv-sign-note">(Ký, ghi rõ họ tên)</div>
                    <div class="pv-sign-space"></div>
                    <div class="pv-sign-name"><?= e($hbt['tenNhanVienPhatHien'] ?? '') ?></div>
                </td>
                <td>
                    <div class="pv-sign-title">Đại diện QC / Đề xuất</div>
                    <div class="pv-sign-note">(Ký, ghi rõ họ tên)</div>
                    <div class="pv-sign-space"></div>
                    <div class="pv-sign-name"><?= e($paHieuLuc['tenNguoiDeXuat'] ?? '') ?></div>
                </td>
                <td>
                    <div class="pv-sign-title">Quản lý kho / Thủ kho</div>
                    <div class="pv-sign-note">(Ký, ghi rõ họ tên)</div>
                    <div class="pv-sign-space"></div>
                    <div class="pv-sign-name"><?= e($phieuChinh['tenNhanVienThiHanh'] ?? ($paHieuLuc['tenNguoiPheDuyet'] ?? '')) ?></div>
                </td>
                <td>
                    <div class="pv-sign-title">Kế toán / Ban giám đốc</div>
                    <div class="pv-sign-note">(Ký, đóng dấu, họ tên)</div>
                    <div class="pv-sign-space"></div>
                    <div class="pv-sign-name"><?= e($paHieuLuc['tenNguoiPheDuyet'] ?? '') ?></div>
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
