<?php
/**
 * Bảng chi tiết 2 cột Nhãn / Giá trị, dùng thống nhất cho trang xem chi tiết và xem lại.
 * $rows: ['Nhãn' => 'Giá trị'] hoặc ['Nhãn' => ['html' => '<span class="badge">...</span>']]
 */
function render_detail_table(array $rows): void
{
    echo '<table class="review-table detail-table">';
    foreach ($rows as $label => $value) {
        if (is_array($value)) {
            $html = (string)($value['html'] ?? e((string)($value['value'] ?? '')));
        } else {
            $html = e((string)$value);
        }
        echo '<tr><th scope="row">' . e((string)$label) . '</th><td>' . $html . '</td></tr>';
    }
    echo '</table>';
}
