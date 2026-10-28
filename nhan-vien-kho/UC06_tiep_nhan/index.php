<?php
/**
 * UC06 - Tiếp nhận hàng vào kho (Actor chính: Nhân viên kho)
 * Bước 1-3: chọn chức năng -> danh sách chứng từ chờ tiếp nhận -> chọn chứng từ.
 * Tìm kiếm theo: Số chứng từ, Loại chứng từ, Nhà cung cấp / nguồn giao, khoảng ngày (Từ ngày - Đến ngày).
 * Bộ lọc áp dụng cho cả hai bảng: chứng từ chờ (theo ngày giao) và phiếu tiếp nhận gần đây (theo ngày nhận).
 * Nhấn vào một dòng ở "Phiếu tiếp nhận gần đây" -> mở popup chi tiết phiếu (chi_tiet.php).
 * Alternative 3.1: tìm lại chứng từ | Exception 3.1.1: không có chứng từ hợp lệ.
 */
require_once __DIR__ . '/_uc06.php';
require_module('UC06');

$dsNguon = uc06_ds_nguon_giao();
[$loc, $loiLoc] = uc06_doc_bo_loc($_GET, $dsNguon);

$ds = uc06_ds_chung_tu($loc);
$coChungTuHopLe = !$loc['dangLoc'] ? count($ds) > 0 : count(uc06_ds_chung_tu()) > 0;

$gd = uc06_ds_phieu_gan_day($loc);
$ganDay = $gd['rows'];
$tenNV = uc06_ten_nhan_vien(array_column($ganDay, 'maNhanVien'));

$page_title = 'Tiếp nhận hàng vào kho';
$active_menu = 'UC06';
$breadcrumb = [['Tổng quan', 'index.php'], ['Tiếp nhận hàng vào kho', null]];

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/sidebar.php';
include ROOT_PATH . '/includes/header.php';
include ROOT_PATH . '/includes/toolbar.php';
?>
<form class="card filter-card" method="get" action="<?= e(url(uc06_url())) ?>">
    <div class="filter-grid">
        <div class="field">
            <label for="q">Số chứng từ</label>
            <input type="text" id="q" name="q" value="<?= e($loc['q']) ?>" maxlength="50" placeholder="VD: PGH-2026-0001">
        </div>
        <div class="field">
            <label for="loai">Loại chứng từ</label>
            <select id="loai" name="loai">
                <option value="">Tất cả</option>
                <?php foreach (uc06_ds_loai_chung_tu() as $l): ?>
                    <option value="<?= e($l) ?>"<?= $loc['loai'] === $l ? ' selected' : '' ?>><?= e($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="nguon">Nhà cung cấp / Nguồn giao</label>
            <select id="nguon" name="nguon">
                <option value="">Tất cả</option>
                <?php foreach ($dsNguon as $n): ?>
                    <option value="<?= e($n) ?>" title="<?= e($n) ?>"<?= $loc['nguon'] === $n ? ' selected' : '' ?>><?= e($n) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field<?= isset($loiLoc['tuNgay']) ? ' invalid' : '' ?>">
            <label for="tuNgay">Từ ngày</label>
            <input type="date" id="tuNgay" name="tuNgay" value="<?= e($loc['tuNgay']) ?>">
            <?php if (isset($loiLoc['tuNgay'])): ?><span class="err-msg"><?= e($loiLoc['tuNgay']) ?></span><?php endif; ?>
        </div>
        <div class="field<?= isset($loiLoc['denNgay']) ? ' invalid' : '' ?>">
            <label for="denNgay">Đến ngày</label>
            <input type="date" id="denNgay" name="denNgay" value="<?= e($loc['denNgay']) ?>">
            <?php if (isset($loiLoc['denNgay'])): ?><span class="err-msg"><?= e($loiLoc['denNgay']) ?></span><?php endif; ?>
        </div>
        <div class="field filter-actions">
            <button class="btn btn-primary" type="submit"><?= icon('search', 16) ?> Tìm kiếm</button>
            <a class="btn btn-ghost" href="<?= e(url(uc06_url())) ?>">Đặt lại</a>
        </div>
    </div>
    <p class="hint">Khoảng thời gian lọc theo <b>ngày giao</b> ở bảng chứng từ chờ tiếp nhận và theo <b>ngày nhận</b> ở bảng phiếu tiếp nhận gần đây.</p>
</form>

<div class="card">
    <div class="card-head">
        <h2>Danh sách chứng từ chờ tiếp nhận</h2>
        <span class="muted">Tổng: <b><?= count($ds) ?></b> chứng từ</span>
    </div>

    <?php if (!$ds): ?>
        <div class="alert alert-error" role="alert">
            <?= e(UC06_MSG_KHONG_CO_CHUNG_TU) ?>.
            <?php if ($coChungTuHopLe && $loc['dangLoc']): ?>Vui lòng điều chỉnh bộ lọc để tìm kiếm lại.<?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th class="w-stt">STT</th>
                    <th>Số chứng từ</th>
                    <th>Loại chứng từ</th>
                    <th>Nhà cung cấp / Nguồn giao</th>
                    <th>Ngày giao</th>
                    <th>Tình trạng</th>
                    <th class="w-actions">Thao tác</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ds as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><b><?= e($r['soChungTu']) ?></b></td>
                        <td><?= e($r['loaiChungTu']) ?></td>
                        <td class="cell-nguon"><?= e($r['nguonGiao']) ?></td>
                        <td><?= e(fmt_date($r['ngayGiao'])) ?></td>
                        <td><?= status_badge($r['tinhTrang']) ?></td>
                        <td><a class="btn btn-outline btn-sm" href="<?= e(url(uc06_url('form.php', ['soChungTu' => $r['soChungTu']]))) ?>">Tiếp nhận</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-head">
        <h2>Phiếu tiếp nhận gần đây</h2>
        <span class="muted">
            <?php if ($gd['tong'] > count($ganDay)): ?>
                Hiển thị <b><?= count($ganDay) ?></b> phiếu mới nhất trong tổng <b><?= $gd['tong'] ?></b> phiếu<?= $loc['dangLoc'] ? ' phù hợp' : '' ?>
            <?php else: ?>
                Tổng: <b><?= $gd['tong'] ?></b> phiếu
            <?php endif; ?>
            · Nhấn vào một dòng để xem chi tiết
        </span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Mã phiếu tiếp nhận</th><th>Ngày nhận</th><th>Giờ nhận</th><th>Số chứng từ</th><th>Nhà cung cấp / Nguồn giao</th><th>Người nhận</th><th>Vị trí lưu trữ</th><th>Tình trạng</th></tr></thead>
            <tbody>
            <?php foreach ($ganDay as $r): ?>
                <tr class="row-link js-row-detail" data-url="<?= e(url(uc06_url('chi_tiet.php', ['ma' => $r['maTiepNhan'], 'modal' => 1]))) ?>">
                    <td><a class="js-detail-link" href="<?= e(url(uc06_url('chi_tiet.php', ['ma' => $r['maTiepNhan']]))) ?>" title="Xem chi tiết phiếu <?= e($r['maTiepNhan']) ?>"><b><?= e($r['maTiepNhan']) ?></b></a></td>
                    <td><?= e(fmt_date($r['thoiGianTiepNhan'])) ?></td>
                    <td><?= e(fmt_time($r['thoiGianTiepNhan'])) ?></td>
                    <td><?= e($r['soChungTu']) ?></td>
                    <td class="cell-nguon"><?= e($r['nguonGiao']) ?></td>
                    <td><?= e($tenNV[$r['maNhanVien']] ?? $r['maNhanVien']) ?></td>
                    <td><?= e($r['tenViTri']) ?></td>
                    <td><?= status_badge($r['trangThaiTiepNhan']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$ganDay): ?>
                <tr><td colspan="8" class="empty-row"><?= $loc['dangLoc'] ? 'Không có phiếu tiếp nhận nào phù hợp với bộ lọc.' : 'Chưa có phiếu tiếp nhận nào.' ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Popup chi tiết phiếu tiếp nhận (nội dung nạp từ chi_tiet.php?modal=1) -->
<div class="modal" id="detailModal" hidden>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="detailModalTitle">
        <div class="modal-head">
            <h3 id="detailModalTitle">Chi tiết phiếu tiếp nhận</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Đóng">&times;</button>
        </div>
        <div class="modal-body" id="detailModalBody"></div>
        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" data-modal-close>Đóng</button>
        </div>
    </div>
</div>
<?php
include ROOT_PATH . '/includes/footer.php';
include ROOT_PATH . '/includes/scripts.php';
