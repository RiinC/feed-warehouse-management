<?php
if ($error !== '') { render_alert('error', $error); }
$oldArea = (string)($old['maKhuVuc'] ?? '' );
?>
<form method="post" action="<?= e(route('vi-tri-luu-tru/store')) ?>" class="card" data-validate novalidate>
    <?= csrf_field() ?>
    <div class="filter-grid">
        <?php if ($fixedKho === null): ?>
            <div class="field">
                <label for="maKho">Kho <span class="req">*</span></label>
                <select id="maKho" name="maKho" data-dependent-target="maKhuVuc" required>
                    <option value="">-- Chọn kho --</option>
                    <?php foreach ($khoOptions as $ma => $ten): ?>
                        <option value="<?= e($ma) ?>" <?= (string)($old['maKho'] ?? '') === (string)$ma ? 'selected' : '' ?>><?= e($ten) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else: ?>
            <div class="field">
                <label>Kho phụ trách</label>
                <input value="<?= e($khoOptions[$fixedKho] ?? $fixedKho) ?>" readonly>
                <input type="hidden" name="maKho" value="<?= e($fixedKho) ?>">
            </div>
        <?php endif; ?>
        <div class="field">
            <label for="maKhuVuc">Khu vực <span class="req">*</span></label>
            <select id="maKhuVuc" name="maKhuVuc" required>
                <option value="">-- Chọn khu vực --</option>
                <?php foreach ($khuOptions as $ma => $opt): ?>
                    <option value="<?= e($ma) ?>" data-kho="<?= e($opt['attrs']['data-kho']) ?>" <?= $oldArea === (string)$ma ? 'selected' : '' ?>><?= e($opt['label']) ?></option>
                <?php endforeach; ?>
                <?php if ($allowNewArea): ?>
                    <option value="__new__" <?= $oldArea === '__new__' ? 'selected' : '' ?>>Khác (tạo khu vực mới)</option>
                <?php endif; ?>
            </select>
        </div>
        <div class="field">
            <label for="tenViTri">Tên vị trí <span class="req">*</span></label>
            <input id="tenViTri" name="tenViTri" value="<?= e($old['tenViTri'] ?? '') ?>" placeholder="VD: Kệ 01 - Tầng 01 - Ô 01" maxlength="60" required>
        </div>
        <div class="field">
            <label for="sucChuaToiDa">Sức chứa tối đa (kg) <span class="req">*</span></label>
            <input id="sucChuaToiDa" name="sucChuaToiDa" type="text" inputmode="numeric" data-qty="positive" data-min="1" placeholder="VD: 8000" value="<?= e($old['sucChuaToiDa'] ?? '') ?>" required>
        </div>
    </div>
    <div class="filter-grid">
        <div class="field"><label for="day">Dãy (2 số) <span class="req">*</span></label><input id="day" name="day" maxlength="2" pattern="[0-9]{2}" placeholder="01" value="<?= e($old['day'] ?? '') ?>" required></div>
        <div class="field"><label for="ke">Kệ (2 số) <span class="req">*</span></label><input id="ke" name="ke" maxlength="2" pattern="[0-9]{2}" placeholder="01" value="<?= e($old['ke'] ?? '') ?>" required></div>
        <div class="field"><label for="tang">Tầng (2 số) <span class="req">*</span></label><input id="tang" name="tang" maxlength="2" pattern="[0-9]{2}" placeholder="01" value="<?= e($old['tang'] ?? '') ?>" required></div>
        <div class="field"><label for="o">Ô (2 số) <span class="req">*</span></label><input id="o" name="o" maxlength="2" pattern="[0-9]{2}" placeholder="01" value="<?= e($old['o'] ?? '') ?>" required></div>
    </div>
    <?php if ($allowNewArea): ?>
        <div id="newAreaBox" class="filter-grid" hidden>
            <div class="field"><label for="tenKhuVucMoi">Tên khu vực mới <span class="req">*</span></label><input id="tenKhuVucMoi" name="tenKhuVucMoi" maxlength="80" placeholder="VD: Khu lưu trữ hàng khô" value="<?= e($old['tenKhuVucMoi'] ?? '') ?>"></div>
            <div class="field"><label for="loaiKhuVucMoi">Loại khu vực <span class="req">*</span></label><select id="loaiKhuVucMoi" name="loaiKhuVucMoi"><option value="Lưu trữ chính">Lưu trữ chính</option><option value="Tiếp nhận tạm">Tiếp nhận tạm</option><option value="Cách ly">Cách ly</option><option value="Đóng gói">Đóng gói</option><option value="Xuất hàng">Xuất hàng</option></select></div>
        </div>
    <?php endif; ?>
    <p class="muted small">Mã vị trí được sinh tự động theo tọa độ và số thứ tự. Vị trí mới ở trạng thái Đang để trống (sẵn sàng sử dụng).</p>
    <div class="page-actions">
        <button type="submit" class="btn btn-primary">Lưu vị trí</button>
        <a class="btn" href="<?= e(route('vi-tri-luu-tru')) ?>">Hủy</a>
    </div>
</form>
<script>
    (function () {
        var kho = document.getElementById('maKho'), area = document.getElementById('maKhuVuc'), box = document.getElementById('newAreaBox');
        function filterAreas() {
            if (!kho || !area) { return; }
            var k = kho.value;
            Array.prototype.forEach.call(area.options, function (o, i) { if (i === 0) { return; } var hide = k && o.getAttribute('data-kho') && o.getAttribute('data-kho') !== k; o.hidden = hide; o.disabled = hide; });
            if (area.selectedOptions[0] && area.selectedOptions[0].hidden) { area.value = ''; }
            toggleNew();
        }
        function toggleNew() { if (!box || !area) { return; } box.hidden = area.value !== '__new__'; }
        if (kho) { kho.addEventListener('change', filterAreas); }
        if (area) { area.addEventListener('change', toggleNew); }
        filterAreas();
    })();
</script>

