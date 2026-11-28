<?php
/** UC08 - Ghi nhận kết quả kiểm tra chất lượng. */
$loHang = $loHang ?? [];
$danhSachTieuChi = $danhSachTieuChi ?? [];
$ketQua = $ketQua ?? [];
$loi = $loi ?? [];
$choXacNhan = $choXacNhan ?? false;
$maXacNhan = $maXacNhan ?? '';
$ketLuan = $ketLuan ?? '';
$danhSachBienBan = $danhSachBienBan ?? [];
$maBienBan = $maBienBan ?? '';
$maPhienNhap = $maPhienNhap ?? '';
$canChonBienBan = $canChonBienBan ?? false;
$cheDoCapNhat = $cheDoCapNhat ?? false;
$danhSachCoTheXoa = $danhSachCoTheXoa ?? [];
$coQuyenXoa = $coQuyenXoa ?? false;
$phienBanXoa = $phienBanXoa ?? '';
// Quyền được Controller xác định; mặc định không cho ghi nếu thiếu dữ liệu.
$bienBan = $bienBan ?? null;
$chiDoc = (bool)($chiDoc ?? false);
$danhSachCoTheSua = $danhSachCoTheSua ?? [];
$coQuyenSua = (bool)($coQuyenSua ?? false);
$coTheKiemTraMoi = (bool)($coTheKiemTraMoi ?? false);
$duocGhi = !$chiDoc && $maPhienNhap !== '' && (
    ($maBienBan !== '' && $coQuyenSua) ||
    ($maBienBan === '' && $coTheKiemTraMoi && empty($danhSachBienBan))
);
$coQuyenXoa = !$chiDoc && $coQuyenSua && $coQuyenXoa;
// Không mở form tạo mới nếu đã có biên bản chờ duyệt.
if ($maBienBan === '' && !empty($danhSachBienBan)) {
    $canChonBienBan = true;
}
$baseUrl = '/feed-warehouse-management/public';
$indexUrl = $baseUrl . '/chat-luong';
$recordUrl = $baseUrl . '/chat-luong/record-result';
$deleteUrl = $baseUrl . '/chat-luong/delete-result';
$e = static function ($value): string {
    return htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$dinhDangNgay = static function ($value): string {
    if (empty($value)) return '';
    $timestamp = strtotime((string)$value);
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '';
};
$layGiaTri = static function (string $maTieuChi, string $thuocTinh) use ($ketQua): string {
    $dong = $ketQua[$maTieuChi] ?? [];
    if (!is_array($dong)) return '';
    $giaTri = $dong[$thuocTinh] ?? '';
    return is_scalar($giaTri) ? (string)$giaTri : '';
};
$chonBienBanUrl = $recordUrl . '?' . http_build_query(['maLoHang' => $loHang['maLoHang'] ?? '']);
$thongTinLoHang = [
    'Mã lô hàng' => $loHang['maLoHang'] ?? '',
    'Tên lô hàng' => $loHang['tenLoHang'] ?? '',
    'Số lượng tiếp nhận' => $loHang['soLuongTiepNhan'] ?? '',
    'Đơn vị tính' => $loHang['donViTinh'] ?? '',
    'Thời gian tiếp nhận' => $dinhDangNgay($loHang['thoiGianTiepNhan'] ?? ''),
    'Trạng thái lô' => $loHang['trangThaiLo'] ?? '',
    'Trạng thái chất lượng' => $loHang['trangThaiChatLuong'] ?? ''
];
$iconSua = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/></svg>';
$iconXem = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
$iconXoa = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';
$styleIcon = 'display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;padding:0;';
// Quyền xóa vẫn do Controller và Model kiểm tra.
$soBienBan = count($danhSachBienBan);
?>

<style>
.field .uc08-fixed-field:disabled {
    background-color: #f5f7fa;
    color: #334155;
    -webkit-text-fill-color: #334155;
    opacity: 1;
    cursor: default;
    pointer-events: none;
}
</style>

<div class="card">
    <div class="card-head">
        <strong>Thông tin lô hàng</strong>
        <a href="<?= $e($indexUrl) ?>" class="btn btn-sm">Quay lại danh sách</a>
    </div>
    <div class="table-wrap">
        <table class="table" style="width:100%; table-layout:fixed;">
            <colgroup><col style="width:35%;"><col style="width:65%;"></colgroup>
            <thead>
                <tr><th scope="col">Thông tin</th><th scope="col">Giá trị</th></tr>
            </thead>
            <tbody>
                <?php foreach ($thongTinLoHang as $nhan => $giaTri): ?>
                    <tr>
                        <td><strong><?= $e($nhan) ?></strong></td>
                        <td style="overflow-wrap:anywhere;"><?= $e($giaTri) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($loi)): ?>
    <div class="alert alert-warning" style="display:block;" role="alert">
        <div style="margin-bottom:10px;"><strong>Vui lòng kiểm tra lại thông tin.</strong></div>
        <?php if (!empty($loi['_chung'])): ?>
            <div style="margin-bottom:8px;"><?= $e($loi['_chung']) ?></div>
        <?php endif; ?>
        <?php foreach ($danhSachTieuChi as $tc): ?>
            <?php
            $loiTieuChi = $loi[$tc['maTieuChi']] ?? [];
            $danhSachLoi = [];
            foreach ((array)$loiTieuChi as $noiDungLoi) {
                if ($noiDungLoi === 'Chưa chọn kết quả cho tiêu chí ' . $tc['tenTieuChi'] . '.') {
                    $noiDungLoi = 'Chưa chọn kết quả kiểm tra.';
                }
                $danhSachLoi[] = $noiDungLoi;
            }
            ?>
            <?php if (!empty($danhSachLoi)): ?>
                <div style="margin:6px 0;"><strong><?= $e($tc['tenTieuChi']) ?>:</strong> <?= $e(implode(' ', $danhSachLoi)) ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($canChonBienBan): ?>
    <!-- Hiển thị danh sách kể cả khi chỉ có một biên bản chờ duyệt. -->
    <div class="card">
        <div class="card-head">
            <strong>Danh sách biên bản kiểm định</strong>
            <span class="muted">Tổng số: <?= $soBienBan ?> biên bản</span>
        </div>
        <?php if ($soBienBan > 0): ?>
            <div class="alert alert-warning" style="display:block;">
                <?php if ($soBienBan === 1): ?>
                    Lô hàng đã có một biên bản đang chờ duyệt. Không thể thực hiện kiểm tra mới. Chỉ người kiểm định được sửa hoặc xóa biên bản; nhân viên khác chỉ được xem.
                <?php else: ?>
                    Lô hàng có nhiều biên bản đang chờ duyệt. Không thể thực hiện kiểm tra mới. Chỉ người kiểm định được sửa hoặc xóa biên bản của mình; nhân viên khác chỉ được xem.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th class="w-stt">STT</th><th>Mã biên bản</th><th>Nhân viên kiểm tra</th>
                    <th>Thời gian kiểm định</th><th>Kết luận</th><th>Trạng thái</th><th class="w-actions">Thao tác</th>
                </tr></thead>
                <tbody>
                    <?php if ($soBienBan === 0): ?>
                        <tr><td colspan="7" class="empty-row">Không còn biên bản chờ duyệt. Vui lòng mở lại lô hàng.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($danhSachBienBan as $i => $bb): ?>
                        <?php
                        $maBB = $bb['maBienBan'];
                        $linkChon = $recordUrl . '?' . http_build_query(['maLoHang' => $loHang['maLoHang'], 'maBienBan' => $maBB]);
                        $duocSuaBB = !empty($danhSachCoTheSua[$maBB]);
                        $phienBanBB = $duocSuaBB ? ($danhSachCoTheXoa[$maBB] ?? '') : '';
                        if (!$duocSuaBB) {
                            $linkChon .= '&hanhDong=xem';
                        }
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= $e($maBB) ?></td>
                            <td><?= $e($bb['tenNhanVienQC']) ?></td>
                            <td><?= $e($dinhDangNgay($bb['ngayKiemDinh'])) ?></td>
                            <td><span class="badge"><?= $e($bb['ketLuan']) ?></span></td>
                            <td><span class="badge badge-warn"><?= $e($bb['trangThai']) ?></span></td>
                            <td class="w-actions">
                                <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
                                    <a href="<?= $e($linkChon) ?>" class="btn btn-sm" title="<?= $duocSuaBB ? 'Sửa kết quả kiểm định' : 'Xem kết quả kiểm định' ?>" aria-label="<?= $duocSuaBB ? 'Sửa kết quả kiểm định' : 'Xem kết quả kiểm định' ?>" style="<?= $e($styleIcon) ?>color:#2563eb;">
                                        <?= $duocSuaBB ? $iconSua : $iconXem ?>
                                    </a>
                                    <?php if ($phienBanBB !== ''): ?>
                                        <form method="POST" action="<?= $e($deleteUrl) ?>" class="js-form-xoa-bien-ban" data-ma-bien-ban="<?= $e($maBB) ?>" style="margin:0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="maLoHang" value="<?= $e($loHang['maLoHang']) ?>">
                                            <input type="hidden" name="maBienBan" value="<?= $e($maBB) ?>">
                                            <input type="hidden" name="phienBan" value="<?= $e($phienBanBB) ?>">
                                            <button type="submit" class="btn btn-sm" title="Xóa kết quả kiểm định" aria-label="Xóa kết quả kiểm định" style="<?= $e($styleIcon) ?>color:#dc2626;">
                                                <?= $iconXoa ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="filter-actions"><a href="<?= $e($indexUrl) ?>" class="btn">Quay lại</a></div>
    </div>
<?php elseif ($chiDoc || (!$duocGhi && $maBienBan !== '')): ?>
    <!-- Chế độ chỉ đọc: không tạo form POST, không có nút sửa, xóa hoặc lưu. -->
    <div class="card">
        <div class="card-head">
            <strong>Kết quả kiểm định chất lượng</strong>
            <span class="muted">Chỉ xem</span>
        </div>
        <div class="alert alert-warning" style="display:block;">
            <?php if (empty($danhSachCoTheSua[$maBienBan])): ?>
                Biên bản do nhân viên khác thực hiện hoặc bạn không còn quyền sửa. Bạn chỉ được xem kết quả kiểm định.
            <?php else: ?>
                Bạn đang xem kết quả kiểm định ở chế độ chỉ đọc.
            <?php endif; ?>
        </div>
        <div class="filter-grid">
            <div class="field"><label>Mã biên bản</label><input type="text" value="<?= $e($maBienBan) ?>" class="uc08-fixed-field" disabled></div>
            <div class="field"><label>Thời gian kiểm định</label><input type="text" value="<?= $e($dinhDangNgay($bienBan['ngayKiemDinh'] ?? '')) ?>" class="uc08-fixed-field" disabled></div>
            <div class="field"><label>Trạng thái biên bản</label><input type="text" value="<?= $e($bienBan['trangThai'] ?? '') ?>" class="uc08-fixed-field" disabled></div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th class="w-stt">STT</th><th>Tiêu chí</th><th>Giá trị tiêu chuẩn</th><th>Giá trị đo được</th><th>Giá trị định tính</th><th>Kết quả</th><th>Ghi chú</th></tr></thead>
                <tbody>
                    <?php if (empty($danhSachTieuChi)): ?>
                        <tr><td colspan="7" class="empty-row">Không có tiêu chí kiểm tra phù hợp.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($danhSachTieuChi as $i => $tc): ?>
                        <?php
                        $maTieuChi = $tc['maTieuChi'];
                        $min = $tc['giaTriToiThieu'] ?? null;
                        $max = $tc['giaTriToiDa'] ?? null;
                        $donViDo = $tc['donViDo'] ?? '';
                        $tieuChuan = '';
                        if ($min !== null && $max !== null) $tieuChuan = $min . ' - ' . $max;
                        elseif ($min !== null) $tieuChuan = '≥ ' . $min;
                        elseif ($max !== null) $tieuChuan = '≤ ' . $max;
                        $tieuChuan = $tieuChuan !== '' ? trim($tieuChuan . ' ' . $donViDo) : 'Không quy định ngưỡng';
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= $e($tc['tenTieuChi']) ?></strong>
                                <?php if (!empty($tc['phuongPhapKiemTra'])): ?><div class="muted"><?= $e($tc['phuongPhapKiemTra']) ?></div><?php endif; ?>
                            </td>
                            <td><?= $e($tieuChuan) ?></td>
                            <?php foreach (['giaTriDoDuoc', 'giaTriDinhTinh', 'danhGia', 'ghiChu'] as $cot): ?>
                                <?php $giaTri = $layGiaTri($maTieuChi, $cot); ?>
                                <td style="white-space:pre-wrap;overflow-wrap:anywhere;"><?= $e($giaTri !== '' ? $giaTri : '—') ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-head"><strong>Kết luận kiểm tra</strong><span class="badge"><?= $e($bienBan['ketLuan'] ?? '') ?></span></div>
        <?php if (!empty($bienBan['lyDoKhongDat'])): ?>
            <div class="field"><label>Lý do không đạt</label><textarea rows="2" class="uc08-fixed-field" disabled><?= $e($bienBan['lyDoKhongDat']) ?></textarea></div>
        <?php endif; ?>
        <div class="filter-actions">
            <a href="<?= $e($chonBienBanUrl) ?>" class="btn">Quay lại danh sách biên bản</a>
        </div>
    </div>
<?php elseif (!$duocGhi): ?>
    <div class="card">
        <div class="alert alert-warning" style="display:block;">Không thể ghi nhận kết quả kiểm tra. Vui lòng mở lại lô hàng để kiểm tra quyền và trạng thái hiện tại.</div>
        <div class="filter-actions"><a href="<?= $e($indexUrl) ?>" class="btn">Quay lại danh sách</a></div>
    </div>
<?php elseif (!$choXacNhan): ?>
    <!-- Form xóa riêng, không lồng trong form nhập kết quả. -->
    <?php if ($coQuyenXoa && $phienBanXoa !== ''): ?>
        <form id="form-xoa-bien-ban-hien-tai" method="POST" action="<?= $e($deleteUrl) ?>" class="js-form-xoa-bien-ban" data-ma-bien-ban="<?= $e($maBienBan) ?>" hidden>
            <?= csrf_field() ?>
            <input type="hidden" name="maLoHang" value="<?= $e($loHang['maLoHang'] ?? '') ?>">
            <input type="hidden" name="maBienBan" value="<?= $e($maBienBan) ?>">
            <input type="hidden" name="phienBan" value="<?= $e($phienBanXoa) ?>">
        </form>
    <?php endif; ?>
    <form method="POST" action="<?= $e($recordUrl) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="maPhienNhap" value="<?= $e($maPhienNhap) ?>">
        <input type="hidden" name="maLoHang" value="<?= $e($loHang['maLoHang'] ?? '') ?>">
        <div class="card">
            <div class="card-head">
                <strong>Danh sách tiêu chí kiểm tra</strong>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="muted">Tổng số: <?= count($danhSachTieuChi) ?> tiêu chí</span>
                    <?php if ($coQuyenXoa && $phienBanXoa !== ''): ?>
                        <button type="submit" form="form-xoa-bien-ban-hien-tai" class="btn btn-sm" title="Xóa kết quả kiểm định" aria-label="Xóa kết quả kiểm định" style="<?= $e($styleIcon) ?>color:#dc2626;">
                            <?= $iconXoa ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($cheDoCapNhat && $soBienBan > 0): ?>
                <div class="filter-actions">
                    <a href="<?= $e($chonBienBanUrl) ?>" class="btn btn-sm">Quay lại danh sách biên bản</a>
                </div>
            <?php endif; ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>
                        <th class="w-stt">STT</th><th>Tiêu chí</th><th>Giá trị tiêu chuẩn</th><th>Giá trị đo được</th><th>Giá trị định tính</th><th>Kết quả</th><th>Ghi chú</th>
                    </tr></thead>
                    <tbody>
                        <?php if (empty($danhSachTieuChi)): ?>
                            <tr><td colspan="7" class="empty-row">Không có tiêu chí kiểm tra phù hợp.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($danhSachTieuChi as $i => $tc): ?>
                            <?php
                            $maTieuChi = $tc['maTieuChi'];
                            $danhGia = $layGiaTri($maTieuChi, 'danhGia');
                            $giaTriDoDuoc = $layGiaTri($maTieuChi, 'giaTriDoDuoc');
                            $giaTriDinhTinh = $layGiaTri($maTieuChi, 'giaTriDinhTinh');
                            $ghiChu = $layGiaTri($maTieuChi, 'ghiChu');
                            $min = $tc['giaTriToiThieu'] ?? null;
                            $max = $tc['giaTriToiDa'] ?? null;
                            $donViDo = $tc['donViDo'] ?? '';
                            $tieuChuan = '';
                            if ($min !== null && $max !== null) $tieuChuan = $min . ' - ' . $max;
                            elseif ($min !== null) $tieuChuan = '≥ ' . $min;
                            elseif ($max !== null) $tieuChuan = '≤ ' . $max;
                            $tieuChuan = $tieuChuan !== '' ? $tieuChuan . ' ' . $donViDo : 'Không quy định ngưỡng';
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><strong><?= $e($tc['tenTieuChi']) ?></strong>
                                    <?php if (!empty($tc['phuongPhapKiemTra'])): ?><div class="muted"><?= $e($tc['phuongPhapKiemTra']) ?></div><?php endif; ?>
                                </td>
                                <td><?= $e($tieuChuan) ?></td>
                                <td><div class="field"><input type="number" step="any" name="ketQua[<?= $e($maTieuChi) ?>][giaTriDoDuoc]" value="<?= $e($giaTriDoDuoc) ?>" placeholder="Nhập số" aria-label="Giá trị đo được của <?= $e($tc['tenTieuChi']) ?>"></div></td>
                                <td><div class="field"><input type="text" maxlength="150" name="ketQua[<?= $e($maTieuChi) ?>][giaTriDinhTinh]" value="<?= $e($giaTriDinhTinh) ?>" placeholder="Nhập giá trị" aria-label="Giá trị định tính của <?= $e($tc['tenTieuChi']) ?>"></div></td>
                                <td><div class="field"><select name="ketQua[<?= $e($maTieuChi) ?>][danhGia]" class="js-danh-gia" data-tieu-chi="<?= $e($maTieuChi) ?>" aria-label="Kết quả của <?= $e($tc['tenTieuChi']) ?>">
                                    <option value="">-- Chọn --</option>
                                    <option value="Đạt" <?= $danhGia === 'Đạt' ? 'selected' : '' ?>>Đạt</option>
                                    <option value="Không đạt" <?= $danhGia === 'Không đạt' ? 'selected' : '' ?>>Không đạt</option>
                                </select></div></td>
                                <td><div class="field"><textarea name="ketQua[<?= $e($maTieuChi) ?>][ghiChu]" id="ghiChu-<?= $e($maTieuChi) ?>" maxlength="255" rows="2" placeholder="Nhập ghi chú" aria-label="Ghi chú của <?= $e($tc['tenTieuChi']) ?>"><?= $e($ghiChu) ?></textarea></div></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="muted">Mỗi tiêu chí phải có ít nhất một giá trị đo được hoặc giá trị định tính. Bắt buộc nhập ghi chú nếu chọn Không đạt.</p>
            <div class="filter-actions">
                <a href="<?= $e($indexUrl) ?>" class="btn">Quay lại</a>
                <button type="submit" name="hanhDong" value="kiemTra" class="btn btn-primary" <?= (empty($danhSachTieuChi) || $maPhienNhap === '') ? 'disabled' : '' ?>>Lưu kết quả kiểm tra</button>
            </div>
        </div>
    </form>
<?php else: ?>
    <div class="card">
        <div class="card-head"><strong>Kết quả kiểm tra</strong><span class="muted">Tổng số: <?= count($danhSachTieuChi) ?> tiêu chí</span></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th class="w-stt">STT</th><th>Tiêu chí</th><th>Giá trị đo được</th><th>Giá trị định tính</th><th>Kết quả</th><th>Ghi chú</th></tr></thead>
                <tbody>
                    <?php foreach ($danhSachTieuChi as $i => $tc): ?>
                        <?php $maTieuChi = $tc['maTieuChi']; ?>
                        <tr>
                            <td><?= $i + 1 ?></td><td><?= $e($tc['tenTieuChi']) ?></td>
                            <td><?= $e($layGiaTri($maTieuChi, 'giaTriDoDuoc')) ?></td>
                            <td><?= $e($layGiaTri($maTieuChi, 'giaTriDinhTinh')) ?></td>
                            <td><span class="badge"><?= $e($layGiaTri($maTieuChi, 'danhGia')) ?></span></td>
                            <td><?= $e($layGiaTri($maTieuChi, 'ghiChu')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-head"><strong>Kết luận chung dự kiến</strong><span class="badge"><?= $e($ketLuan) ?></span></div>
    </div>
    <div class="card">
        <div class="card-head"><strong>Xác nhận lưu kết quả kiểm tra</strong></div>
        <div class="alert alert-warning" style="display:block;">
            Bạn có chắc chắn muốn lưu kết quả kiểm tra chất lượng của lô hàng <strong><?= $e($loHang['maLoHang'] ?? '') ?></strong> không?
            <?php if ($cheDoCapNhat): ?><p>Kết quả của biên bản <strong><?= $e($maBienBan) ?></strong> sẽ được cập nhật. Kết quả cũ sẽ được thay thế.</p><?php endif; ?>
            <p>Sau khi xác nhận, kết quả kiểm tra sẽ được lưu và chờ xác nhận chất lượng.</p>
        </div>
        <form method="POST" action="<?= $e($recordUrl) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="maXacNhan" value="<?= $e($maXacNhan) ?>">
            <div class="filter-actions">
                <button type="submit" name="hanhDong" value="quayLai" class="btn">Quay lại</button>
                <button type="submit" name="hanhDong" value="xacNhan" class="btn btn-primary">Xác nhận</button>
            </div>
        </form>
    </div>
<?php endif; ?>
<script>
document.querySelectorAll('.js-danh-gia').forEach(function (select) {
    function capNhatGhiChu() {
        const ghiChu = document.getElementById('ghiChu-' + select.dataset.tieuChi);
        if (!ghiChu) return;
        ghiChu.placeholder = select.value === 'Không đạt' ? 'Bắt buộc nhập lý do không đạt' : 'Nhập ghi chú';
    }
    select.addEventListener('change', capNhatGhiChu);
    capNhatGhiChu();
});
document.querySelectorAll('.js-form-xoa-bien-ban').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (form.dataset.dangXuLy === '1') { event.preventDefault(); return; }
        const maBienBan = form.dataset.maBienBan || '';
        if (!window.confirm('Bạn có chắc chắn muốn xóa biên bản kiểm định ' + maBienBan + ' không?\n\nToàn bộ kết quả kiểm tra thuộc biên bản này sẽ bị xóa.\nThao tác này không thể hoàn tác.')) {
            event.preventDefault(); return;
        }
        form.dataset.dangXuLy = '1';
    });
});
</script>
