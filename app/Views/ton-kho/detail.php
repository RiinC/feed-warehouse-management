<?php
/**
 * View ton-kho/detail — Chi tiết một dòng tồn kho (lô + vị trí).
 * Biến từ TonKhoController::detail(): $item, $hbtRecords.
 */
$ton       = (float)($item['soLuongTon'] ?? 0);
$khaDung   = (float)($item['soLuongKhaDung'] ?? 0);
$conLai    = isset($item['soNgayConLai']) ? (int)$item['soNgayConLai'] : null;
$mucHsd    = (string)($item['mucHanSuDung'] ?? '');

$hsdBadgeClass = 'badge-ok';
if ($mucHsd === 'Đã hết hạn') {
    $hsdBadgeClass = 'badge-danger';
} elseif ($mucHsd === 'Còn ≤ 7 ngày' || $mucHsd === 'Còn ≤ 30 ngày') {
    $hsdBadgeClass = 'badge-warn';
} elseif ($mucHsd === 'Còn ≤ 90 ngày') {
    $hsdBadgeClass = 'badge-info';
}
?>

<style>
.tkd-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
}
.tkd-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}
.tkd-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    overflow: hidden;
    margin-bottom: 16px;
}
.tkd-card-head {
    padding: 13px 18px;
    border-bottom: 1px solid var(--line);
    background: #fafbfd;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.tkd-card-head h2 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}
.tkd-kv {
    width: 100%;
    border-collapse: collapse;
}
.tkd-kv th, .tkd-kv td {
    padding: 10px 18px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13.5px;
    text-align: left;
}
.tkd-kv tr:last-child th, .tkd-kv tr:last-child td { border-bottom: none; }
.tkd-kv th {
    width: 175px;
    color: #64748b;
    font-weight: 500;
}
@media (max-width: 900px) {
    .tkd-grid-2 { grid-template-columns: 1fr; }
}
</style>

<!-- 1. TIÊU ĐỀ & ĐIỀU HƯỚNG -->
<div class="tkd-head">
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <h1 style="margin: 0; font-size: 22px; font-weight: 750; color: #0f172a;">
            Chi tiết tồn kho <?= e($item['maLoHang']) ?>
        </h1>
        <span class="badge <?= $hsdBadgeClass ?>"><?= e($mucHsd) ?></span>
    </div>

    <div style="display: flex; align-items: center; gap: 8px;">
        <!-- Nút quay lại danh sách tra cứu tồn kho (chỉ icon) -->
        <a class="btn btn-ghost" href="<?= e(route('ton-kho')) ?>" title="Quay lại tra cứu tồn kho" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px; text-decoration: none; font-size: 16px;">
            <?= icon('arrow-left', 16) ?>
        </a>

        <!-- Nút In bảng kê tồn kho (A4, mở tab riêng) -->
        <a class="btn btn-ghost" href="<?= e(route('ton-kho/print', ['id' => $item['maTonKho']])) ?>" target="_blank" rel="noopener" title="In bảng kê tồn kho" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px; text-decoration: none;">
            <?= icon('printer', 16) ?>
        </a>
    </div>
</div>

<!-- 2. THÔNG TIN CHI TIẾT LÔ HÀNG & VỊ TRÍ -->
<div class="tkd-grid-2">
    <div class="tkd-card" style="margin-bottom: 0;">
        <div class="tkd-card-head">
            <h2>Thông tin hàng hóa &amp; lô hàng</h2>
        </div>
        <table class="tkd-kv">
            <tr><th>Hàng hóa</th><td><?= e($item['tenHangHoa']) ?></td></tr>
            <tr><th>Loại hàng hóa</th><td><?= e(product_type_label($item['loaiHangHoa'])) ?></td></tr>
            <tr><th>Mã lô hệ thống</th><td><?= e($item['maLoHang']) ?></td></tr>
            <tr><th>Số lô NCC</th><td><?= !empty($item['soLoNhaCungCap']) ? e($item['soLoNhaCungCap']) : '—' ?></td></tr>
            <tr><th>Ngày SX / Hạn SD</th><td><?= e(fmt_date($item['ngaySanXuat'])) ?> &rarr; <?= e(fmt_date($item['ngayHetHan'])) ?></td></tr>
            <tr><th>Thời hạn còn lại</th><td><?= $conLai === null ? '—' : ($conLai < 0 ? 'Quá ' . abs($conLai) . ' ngày' : (string)$conLai . ' ngày') ?></td></tr>
            <tr><th>Điều kiện bảo quản</th><td><?= !empty($item['dieuKienBaoQuan']) ? e($item['dieuKienBaoQuan']) : '—' ?></td></tr>
        </table>
    </div>

    <div class="tkd-card" style="margin-bottom: 0;">
        <div class="tkd-card-head">
            <h2>Thông tin tồn &amp; vị trí lưu trữ</h2>
        </div>
        <table class="tkd-kv">
            <tr><th>Kho</th><td><?= e($item['tenKho']) ?></td></tr>
            <tr><th>Khu vực</th><td><?= e($item['tenKhuVuc']) ?></td></tr>
            <tr><th>Vị trí lưu trữ</th><td><?= e($item['tenViTri']) ?></td></tr>
            <tr><th>Tồn thực tế</th><td><?= fmt_number($ton, 2) ?> <?= e($item['donViTinhChuan']) ?></td></tr>
            <tr><th>Khả dụng xuất kho</th><td><?= fmt_number($khaDung, 2) ?> <?= e($item['donViTinhChuan']) ?></td></tr>
            <tr><th>Sức chứa tối đa</th><td><?= fmt_number($item['sucChuaToiDa'] ?? 0, 2) ?> kg</td></tr>
            <tr><th>Cập nhật tồn cuối</th><td><?= e(fmt_datetime($item['ngayCapNhatCuoi'])) ?></td></tr>
        </table>
    </div>
</div>

<?php if (!empty($hbtRecords)): ?>
    <div class="tkd-card">
        <div class="tkd-card-head">
            <h2>Hồ sơ hàng bất thường</h2>
        </div>
        <div class="table-wrap" style="margin: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã bản ghi</th>
                        <th>Loại bất thường</th>
                        <th class="num">Số lượng</th>
                        <th>Trạng thái</th>
                        <th>Ngày phát hiện</th>
                        <?php if (can('hang-bat-thuong', 'view')): ?><th></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($hbtRecords as $hb): ?>
                    <tr>
                        <td><strong><?= e($hb['maHangBatThuong']) ?></strong></td>
                        <td><?= e($hb['loaiBatThuong']) ?></td>
                        <td class="num"><?= fmt_number($hb['soLuong'], 2) ?> <?= e($item['donViTinhChuan']) ?></td>
                        <td><span class="badge"><?= e($hb['trangThai']) ?></span></td>
                        <td><?= e(fmt_datetime($hb['ngayPhatHien'])) ?></td>
                        <?php if (can('hang-bat-thuong', 'view')): ?>
                            <td><a class="btn btn-sm" href="<?= e(route('hang-bat-thuong/detail', ['ma' => $hb['maHangBatThuong']])) ?>">Xem hồ sơ</a></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

