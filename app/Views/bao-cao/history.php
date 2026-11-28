<?php
/** UC30 - Lịch sử báo cáo tồn kho. View chỉ hiển thị dữ liệu Controller cung cấp. */
$e = static fn($v): string => htmlspecialchars(is_scalar($v) ? (string)$v : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$date = static function ($v, string $format = 'd/m/Y'): string {
    $t = is_string($v) && $v !== '' ? strtotime($v) : false;
    return $t === false ? '—' : date($format, $t);
};
$url = '/feed-warehouse-management/public/bao-cao';
$urlTrucTiep = $url . '?tab=direct';
$urlLichSu = $url . '/history';
$laQuanLyKho = (bool)($laQuanLyKho ?? false);
$tieuDe = $laQuanLyKho ? 'Báo cáo đã gửi' : 'Báo cáo đã nhận';
$danhSachKho = $danhSachKho ?? [];
$danhSach = $danhSach ?? [];
$maBaoCao = $maBaoCao ?? '';
$maKho = $maKho ?? '';
$trangThai = $trangThai ?? '';
$tuNgay = $tuNgay ?? '';
$denNgay = $denNgay ?? '';
$thongBao = $thongBao ?? '';
$loi = (bool)($loi ?? false);
$tongSoBaoCao = max(0, (int)($tongSoBaoCao ?? 0));
$tongSoTrang = max(1, (int)($tongSoTrang ?? 1));
// Controller bước 2 truyền biến page thay vì trang.
$trang = max(1, min($tongSoTrang, (int)($page ?? $trang ?? 1)));
$soLuongMoiTrang = max(1, (int)($soLuongMoiTrang ?? 10));
$offset = ($trang - 1) * $soLuongMoiTrang;
$loc = ['maBaoCao'=>$maBaoCao, 'maKho'=>$maKho, 'trangThai'=>$trangThai, 'tuNgay'=>$tuNgay, 'denNgay'=>$denNgay];
if ($laQuanLyKho) unset($loc['maKho']);
$taoUrl = static fn(int $p): string => $urlLichSu . '?' . http_build_query(array_merge($loc, ['page'=>$p]));
$dsTrangThai = $laQuanLyKho ? ['Đã gửi','Đã xem'] : ['Chưa xem','Đã xem'];
$maNhanVienHienTai = \Core\Auth::staffId();
$khoCoDinh = count($danhSachKho) === 1 ? reset($danhSachKho) : [];
$coQuyenXoa = $laQuanLyKho && $maNhanVienHienTai !== '' && can('bao-cao', 'delete');
?>
<div class="uc30-page">
<div class="card">
    <nav class="filter-actions" aria-label="Chức năng báo cáo tồn kho">
        <?php if ($laQuanLyKho): ?>
            <a class="btn" href="<?= $e($urlTrucTiep) ?>">Xem báo cáo tồn kho</a>
            <a class="btn btn-primary" href="<?= $e($urlLichSu) ?>" aria-current="page">Báo cáo đã gửi</a>
        <?php else: ?>
            <a class="btn btn-primary" href="<?= $e($urlLichSu) ?>" aria-current="page">Báo cáo đã nhận</a>
            <a class="btn" href="<?= $e($urlTrucTiep) ?>">Xem báo cáo trực tiếp</a>
        <?php endif; ?>
    </nav>
</div>
<div class="card filter-card">
    <div class="card-head"><strong>Thông tin tra cứu</strong></div>
    <form method="GET" action="<?= $e($urlLichSu) ?>">
        <div class="filter-grid">
            <div class="field">
                <label for="uc30-h-maBaoCao">Mã báo cáo</label>
                <input type="text" id="uc30-h-maBaoCao" name="maBaoCao" maxlength="8" placeholder="Nhập mã báo cáo" value="<?= $e($maBaoCao) ?>">
            </div>
            <?php if (!$laQuanLyKho): ?>
            <div class="field">
                <label for="uc30-h-maKho">Mã kho</label>
                <select id="uc30-h-maKho" name="maKho">
                    <option value="">Tất cả kho</option>
                    <?php foreach ($danhSachKho as $k): ?>
                        <option value="<?= $e($k['maKho']) ?>" <?= $maKho === $k['maKho'] ? 'selected' : '' ?>><?= $e($k['maKho'].' - '.$k['tenKho']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="field">
                <label for="uc30-h-tuNgay">Ngày gửi bắt đầu</label>
                <input type="date" id="uc30-h-tuNgay" name="tuNgay" value="<?= $e($tuNgay) ?>">
            </div>
            <div class="field">
                <label for="uc30-h-denNgay">Ngày gửi kết thúc</label>
                <input type="date" id="uc30-h-denNgay" name="denNgay" value="<?= $e($denNgay) ?>">
            </div>
            <div class="field">
                <label for="uc30-h-trangThai">Trạng thái</label>
                <select id="uc30-h-trangThai" name="trangThai">
                    <option value="">Tất cả</option>
                    <?php foreach ($dsTrangThai as $tt): ?>
                        <option value="<?= $e($tt) ?>" <?= $trangThai === $tt ? 'selected' : '' ?>><?= $e($tt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Áp dụng bộ lọc</button>
                <a class="btn" href="<?= $e($urlLichSu) ?>">Đặt lại</a>
            </div>
        </div>
    </form>
</div>
<?php if ($thongBao !== ''): ?>
    <div class="alert <?= $loi ? 'alert-danger' : 'alert-warning' ?>" role="alert"><?= $e($thongBao) ?></div>
<?php endif; ?>
<div class="card">
    <div class="card-head"><strong>Danh sách <?= $laQuanLyKho ? 'báo cáo đã gửi' : 'báo cáo đã nhận' ?></strong><span class="muted">Tổng số: <?= $tongSoBaoCao ?> báo cáo</span></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th class="w-stt">STT</th><th>Mã báo cáo</th><?php if (!$laQuanLyKho): ?><th>Kho</th><th>Người gửi</th><?php endif; ?><th>Kỳ báo cáo</th><th>Thời gian gửi</th><th>Trạng thái</th><th class="w-actions">Thao tác</th></tr></thead>
            <tbody>
            <?php if (!$danhSach): ?>
                <tr><td colspan="<?= $laQuanLyKho ? 6 : 8 ?>" class="empty-row"><?= $loi ? 'Không thể hiển thị danh sách báo cáo.' : 'Không có báo cáo phù hợp.' ?></td></tr>
            <?php else: foreach ($danhSach as $i => $r): ?>
                <?php $ky = (empty($r['tuNgay']) ? 'Toàn bộ lịch sử' : $date($r['tuNgay'])) . ' – ' . (empty($r['denNgay']) ? 'Thời điểm tổng hợp' : $date($r['denNgay'])); ?>
                <tr>
                    <td><?= $offset + $i + 1 ?></td>
                    <td><?= $e($r['maBaoCao'] ?? '') ?></td>
                    <?php if (!$laQuanLyKho): ?>
                    <td><?= $e($r['tenKho'] ?? '—') ?></td>
                    <td><?= $e($r['tenNhanVienGui'] ?? '—') ?></td>
                    <?php endif; ?>
                    <td><?= $e($ky) ?></td>
                    <td><?= $e($date($r['thoiGianGui'] ?? null, 'd/m/Y H:i:s')) ?></td>
                    <td><?php if (($r['trangThai'] ?? '') === 'Chưa xem'): ?><strong>Chưa xem</strong><?php else: ?><?= $e($r['trangThai'] ?? '—') ?><?php endif; ?></td>
                    <td class="w-actions"<?= $laQuanLyKho ? ' style="min-width:110px"' : '' ?>><div class="cell-actions"<?= $laQuanLyKho ? ' style="display:flex;flex-direction:row;flex-wrap:nowrap;align-items:center;gap:6px"' : '' ?>>
                        <a class="btn btn-sm" style="color:#2563eb" href="<?= $e($url.'/detail?'.http_build_query(['maBaoCao'=>$r['maBaoCao'] ?? ''])) ?>" title="Xem chi tiết báo cáo" aria-label="<?= $e('Xem chi tiết báo cáo '.($r['maBaoCao'] ?? '')) ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <?php if ($coQuyenXoa
                            && ($r['trangThai'] ?? '') === 'Đã gửi'
                            && ($r['maNhanVienGui'] ?? '') === $maNhanVienHienTai
                            && ($khoCoDinh['maKho'] ?? '') !== ''
                            && ($r['maKho'] ?? '') === $khoCoDinh['maKho']): ?>
                            <form style="display:inline-flex;flex:0 0 auto;width:auto;margin:0" method="POST" action="<?= $e($url.'/delete') ?>" onsubmit="return confirm('Bạn có chắc chắn muốn xóa báo cáo này?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="maBaoCao" value="<?= $e($r['maBaoCao'] ?? '') ?>">
                                <button type="submit" class="btn btn-sm" style="color:#dc2626" title="Xóa báo cáo" aria-label="<?= $e('Xóa báo cáo '.($r['maBaoCao'] ?? '')) ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 10v7M14 10v7"/></svg>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-head">
        <span class="muted">Hiển thị <?= $danhSach ? $offset+1 : 0 ?> - <?= $danhSach ? $offset+count($danhSach) : 0 ?> / <?= $tongSoBaoCao ?> báo cáo</span>
        <nav class="pagination" aria-label="Phân trang">
            <?php if ($trang > 1): ?><a href="<?= $e($taoUrl($trang-1)) ?>" aria-label="Trang trước">&laquo;</a>
            <?php else: ?><button class="btn btn-sm" type="button" disabled aria-label="Trang trước">&laquo;</button><?php endif; ?>
            <?php $pages = array_unique(array_merge([1], range(max(1,$trang-2), min($tongSoTrang,$trang+2)), [$tongSoTrang])); $last = 0; ?>
            <?php foreach ($pages as $p): ?>
                <?php if ($p > $last+1): ?><span class="muted">…</span><?php endif; ?>
                <?php if ($p === $trang): ?><span class="current" aria-current="page"><?= $p ?></span>
                <?php else: ?><a href="<?= $e($taoUrl($p)) ?>"><?= $p ?></a><?php endif; $last = $p; ?>
            <?php endforeach; ?>
            <?php if ($trang < $tongSoTrang): ?><a href="<?= $e($taoUrl($trang+1)) ?>" aria-label="Trang sau">&raquo;</a>
            <?php else: ?><button class="btn btn-sm" type="button" disabled aria-label="Trang sau">&raquo;</button><?php endif; ?>
        </nav>
    </div>
</div>
</div>
