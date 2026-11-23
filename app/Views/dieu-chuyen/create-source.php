<?php
partial('components/transfer-steps', ['current' => 2]);
?>
<div class="card">
    <h2>Lô đã chọn</h2>
    <?php render_detail_table([
        'Mã lô' => $draft['maLoHang'],
        'Kho (cố định)' => $lot['tenKho'] ?? $draft['maKho'],
        'Hàng hóa' => $lot['tenHangHoa'] ?? '—',
        'Hạn sử dụng' => $lot['ngayHetHan'] ?? '—',
    ]); ?>
</div>

<form method="get" action="<?= e(route('dieu-chuyen/create/source')) ?>" class="card filter-card">
    <div class="filter-grid">
        <div class="field">
            <label for="sourceArea">Khu vực nguồn</label>
            <select id="sourceArea" name="khuVuc" onchange="this.form.submit()">
                <option value="">Tất cả khu vực</option>
                <?php foreach ($areas as $ma => $ten): ?>
                    <option value="<?= e($ma) ?>" <?= (string)$khuVuc === (string)$ma ? 'selected' : '' ?>><?= e($ten) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field filter-actions"><button type="submit" class="btn btn-primary">Lọc</button></div>
    </div>
</form>

<form method="post" action="<?= e(route('dieu-chuyen/create/source')) ?>" class="card" data-validate novalidate>
    <?= csrf_field() ?>
    <h2>Chọn vị trí nguồn</h2>
    <p class="muted small">Chọn khu vực để lọc bảng, sau đó nhấp vào dòng hoặc radio để chọn vị trí nguồn.</p>
    <?php
    render_data_table([
        'chon' => ['label' => 'Chọn', 'render' => function ($p) use ($draft) {
            $checked = (string)($draft['maViTriNguon'] ?? '') === (string)$p['maViTri'];
            return '<input type="radio" name="maViTriNguon" value="' . e($p['maViTri']) . '" required' . ($checked ? ' checked' : '') . '>';
        }],
        'maViTri' => ['label' => 'Mã vị trí', 'class' => 'nowrap'],
        'tenViTri' => ['label' => 'Tên vị trí', 'class' => 'wrap'],
        'tenKhuVuc' => ['label' => 'Khu vực', 'class' => 'wrap'],
        'khaDung' => ['label' => 'Khả dụng (kg)', 'class' => 'num nowrap'],
        'sucChuaToiDa' => ['label' => 'Sức chứa (kg)', 'class' => 'num nowrap'],
    ], $positions, [
        'stt' => true,
        'stt_start' => max(0, ($page - 1) * $perPage),
        'empty' => 'Lô không còn vị trí khả dụng trong khu vực đã chọn.',
        'row_attrs' => fn(array $p): array => ['class' => 'selectable-row'],
    ]);
    render_pagination($page, $total, $perPage);
    ?>
    <div class="page-actions">
        <button type="submit" class="btn btn-primary">Tiếp tục</button>
        <a class="btn" href="<?= e(route('dieu-chuyen/create')) ?>">Quay lại</a>
    </div>
</form>

