<?php
partial('components/transfer-steps', ['current' => 3]);
?>
<div class="card">
    <h2>Vị trí nguồn đã chọn</h2>
    <?php render_detail_table([
        'Mã lô' => $draft['maLoHang'],
        'Kho (cố định)' => $lot['tenKho'] ?? $draft['maKho'],
        'Vị trí nguồn' => $src['tenViTri'] ?? $draft['maViTriNguon'],
        'Khu vực nguồn' => $src['tenKhuVuc'] ?? '—',
    ]); ?>
</div>

<form method="get" action="<?= e(route('dieu-chuyen/create/destination')) ?>" class="card filter-card">
    <div class="filter-grid">
        <div class="field">
            <label for="destArea">Khu vực đích</label>
            <select id="destArea" name="khuVuc" onchange="this.form.submit()">
                <option value="">Tất cả khu vực</option>
                <?php foreach ($areas as $ma => $ten): ?>
                    <option value="<?= e($ma) ?>" <?= (string)$khuVuc === (string)$ma ? 'selected' : '' ?>><?= e($ten) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field filter-actions"><button type="submit" class="btn btn-primary">Lọc</button></div>
    </div>
</form>

<form method="post" action="<?= e(route('dieu-chuyen/create/destination')) ?>" class="card" data-validate novalidate>
    <?= csrf_field() ?>
    <h2>Chọn vị trí đích</h2>
    <p class="muted small">Chọn khu vực để lọc bảng, sau đó nhấp vào dòng hoặc radio để chọn vị trí đích. Chỉ hiển thị vị trí còn sức chứa trong cùng kho; vị trí nguồn không được chọn.</p>
    <?php
    render_data_table([
        'chon' => ['label' => 'Chọn', 'render' => function ($p) use ($draft) {
            $checked = (string)($draft['maViTriDich'] ?? '') === (string)$p['maViTri'];
            return '<input type="radio" name="maViTriDich" value="' . e($p['maViTri']) . '" required' . ($checked ? ' checked' : '') . '>';
        }],
        'maViTri' => ['label' => 'Mã vị trí', 'class' => 'nowrap'],
        'tenViTri' => ['label' => 'Tên vị trí', 'class' => 'wrap'],
        'tenKhuVuc' => ['label' => 'Khu vực', 'class' => 'wrap'],
        'sucChuaToiDa' => ['label' => 'Sức chứa (kg)', 'class' => 'num nowrap'],
        'daDung' => ['label' => 'Đang dùng (kg)', 'class' => 'num nowrap'],
        'conLai' => ['label' => 'Còn lại (kg)', 'class' => 'num nowrap', 'render' => fn(array $p): string => e(fmt_number((float)$p['sucChuaToiDa'] - (float)$p['daDung'], 2))],
    ], $positions, [
        'stt' => true,
        'stt_start' => max(0, ($page - 1) * $perPage),
        'empty' => 'Không còn vị trí đích khả dụng trong khu vực đã chọn.',
        'row_attrs' => fn(array $p): array => ['class' => 'selectable-row'],
    ]);
    render_pagination($page, $total, $perPage);
    ?>
    <div class="page-actions">
        <button type="submit" class="btn btn-primary">Tiếp tục</button>
        <a class="btn" href="<?= e(route('dieu-chuyen/create/source')) ?>">Quay lại</a>
    </div>
</form>

