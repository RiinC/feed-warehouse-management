<?php
/** Chi tiết vị trí. Biến: $pos,$used,$lots,$allocations,$history. */
$can = $pos['sucChuaToiDa'] - $used['daDung']; if ($can < 0) { $can = 0; }
?>
<?php
$vtActionsHtml = vi_tri_badge($pos['trangThai']);
if (can('vi-tri-luu-tru', 'edit')) {
    $vtActionsHtml .= '<a class="btn btn-sm" href="' . e(route('vi-tri-luu-tru/edit', ['id' => $pos['maViTri']])) . '">Sửa</a>';
}
if (can('vi-tri-luu-tru', 'stop') && $pos['trangThai'] !== 'Đã khóa') {
    $vtActionsHtml .= '<a class="btn btn-sm btn-danger" href="' . e(route('vi-tri-luu-tru/stop-using', ['id' => $pos['maViTri']])) . '">Ngừng sử dụng</a>';
}

render_kv_pair_cards(
    [
        'title' => 'Thông tin vị trí',
        'rows'  => [
            'Mã vị trí'               => (string)$pos['maViTri'],
            'Tên vị trí'              => (string)$pos['tenViTri'],
            'Kho'                     => (string)$pos['tenKho'],
            'Khu vực'                 => (string)$pos['tenKhuVuc'],
            'Tọa độ (dãy/kệ/tầng/ô)' => $pos['day'] . '/' . $pos['ke'] . '/' . $pos['tang'] . '/' . $pos['o'],
        ],
    ],
    [
        'title'       => 'Sức chứa & Trạng thái',
        'headerRight' => $vtActionsHtml,
        'rows'        => [
            'Sức chứa tối đa' => fmt_number($pos['sucChuaToiDa'], 2) . ' kg',
            'Đang lưu trữ'    => fmt_number($used['daDung'], 2) . ' kg',
            'Còn lại'         => fmt_number($can, 2) . ' kg',
            'Trạng thái'      => ['html' => vi_tri_badge($pos['trangThai'])],
        ],
    ]
);
?>
<?php if ((float)$used['daDung'] > (float)$pos['sucChuaToiDa'] + 0.000001): ?>
    <div style="margin-bottom: 14px;">
        <?php render_alert('warning', 'Cảnh báo dữ liệu: đang lưu trữ ' . fmt_number($used['daDung'], 2) . ' kg, vượt sức chứa tối đa ' . fmt_number($pos['sucChuaToiDa'], 2) . ' kg. Vui lòng kiểm tra lại phân bổ tồn hoặc tăng sức chứa.'); ?>
    </div>
<?php endif; ?>
<div class="card">
    <h2>Tồn thực tế tại vị trí</h2>
    <?php render_data_table(
        ['maLoHang' => ['label' => 'Mã lô', 'class' => 'nowrap'], 'tenHangHoa' => ['label' => 'Hàng hóa', 'class' => 'wrap'], 'ngayHetHan' => ['label' => 'Hạn sử dụng', 'class' => 'nowrap'], 'soLuongTon' => ['label' => 'Tồn (kg)', 'class' => 'num nowrap'], 'soLuongDaGiu' => ['label' => 'Đang giữ (kg)', 'class' => 'num nowrap']],
        $lots,
        [
            'stt'     => true,
            'empty'   => 'Vị trí chưa có tồn thực tế.',
            'actions' => can('hang-bat-thuong', 'record') ? function ($r) use ($pos) {
                if ((float)($r['soLuongTon'] ?? 0) <= 0) {
                    return '';
                }
                $url = route('hang-bat-thuong/create', [
                    'maKho'    => $pos['maKho'],
                    'maKhuVuc' => $pos['maKhuVuc'],
                    'maViTri'  => $pos['maViTri'],
                    'maLoHang' => $r['maLoHang'],
                ]);
                return '<a class="btn btn-sm" href="' . e($url) . '">Báo bất thường</a>';
            } : null,
        ]
    ); ?>
</div>
<div class="card">
    <h2>Đã phân bổ tại vị trí</h2>
    <?php render_data_table(['maLoHang' => ['label' => 'Mã lô', 'class' => 'nowrap'], 'tenHangHoa' => ['label' => 'Hàng hóa', 'class' => 'wrap'], 'soLuongPhanBo' => ['label' => 'Số lượng (kg)', 'class' => 'num nowrap'], 'thoiDiemPhanBo' => ['label' => 'Thời điểm', 'class' => 'nowrap'], 'hoTen' => ['label' => 'Người thực hiện', 'class' => 'nowrap'], 'ghiChu' => ['label' => 'Ghi chú', 'class' => 'wrap']], $allocations, ['stt' => true, 'empty' => 'Chưa có phân bổ tại vị trí.']); ?>
</div>
<div class="card">
    <h2>Lịch sử biến động gần đây</h2>
    <?php render_data_table(['maBienDong' => ['label' => 'Mã bút toán', 'class' => 'nowrap'], 'loaiGiaoDich' => ['label' => 'Loại', 'class' => 'nowrap'], 'soLuongBienDong' => ['label' => 'Biến động (kg)', 'class' => 'num nowrap'], 'soLuongTonTruoc' => ['label' => 'Tồn trước', 'class' => 'num nowrap'], 'soLuongTonSau' => ['label' => 'Tồn sau', 'class' => 'num nowrap'], 'thoiGianPhatSinh' => ['label' => 'Thời điểm', 'class' => 'nowrap'], 'hoTen' => ['label' => 'Người thực hiện', 'class' => 'nowrap']], $history, ['stt' => true, 'empty' => 'Chưa có biến động.']); ?>
</div>

