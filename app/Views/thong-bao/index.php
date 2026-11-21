<?php
/** Danh sách thông báo. Biến: $rows, $total, $page, $perPage. */
render_search_form(
    ['loc' => ['label' => 'Hiển thị', 'type' => 'select', 'options' => ['chua-doc' => 'Chỉ thông báo chưa đọc']]],
    $_GET
);
?>
<div class="card">
    <?php
    render_data_table(
        [
            'tieuDe' => ['label' => 'Thông báo', 'render' => function ($r) {
                $href = route('thong-bao/open', ['id' => (int)$r['maThongBao']]);
                $title = '<a class="notif-link' . ($r['daDoc'] ? '' : ' unread') . '" href="' . e($href) . '">' . e($r['tieuDe']) . '</a>';
                $msg = $r['noiDung'] ? '<br><small class="muted">' . e($r['noiDung']) . '</small>' : '';
                return $title . $msg;
            }],
            'thoiDiemTao' => ['label' => 'Thời gian', 'render' => fn($r) => e(fmt_datetime($r['thoiDiemTao'])) . '<br><small class="muted">' . e(time_ago($r['thoiDiemTao'])) . '</small>'],
            'daDoc' => ['label' => 'Trạng thái', 'render' => fn($r) => $r['daDoc'] ? '<span class="badge">Đã đọc</span>' : '<span class="badge badge-info">Chưa đọc</span>'],
        ],
        $rows,
        ['empty' => 'Bạn chưa có thông báo nào.']
    );
    render_pagination($page, $total, $perPage);
    ?>
</div>
