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

/**
 * Render a single two-column-layout white key-value card (`kv-card`).
 *
 * @param array{title:string, headerRight?:string, rows:array<string, mixed>} $card
 */
function render_kv_card(array $card): void
{
    $title = (string)($card['title'] ?? '');
    $headerRight = (string)($card['headerRight'] ?? '');
    $rows = (array)($card['rows'] ?? []);

    echo '<div class="card kv-card">';
    echo '<div class="kv-card-head">';
    echo '<h2>' . e($title) . '</h2>';
    if ($headerRight !== '') {
        echo '<div class="kv-card-actions">' . $headerRight . '</div>';
    }
    echo '</div>';
    echo '<table class="detail-table kv-table">';
    foreach ($rows as $label => $value) {
        $labelHtml = e((string)$label);
        $trAttr = '';
        $tdClass = '';
        if (is_array($value)) {
            if (!empty($value['labelHtml'])) {
                $labelHtml = (string)$value['labelHtml'];
            } elseif (!empty($value['required'])) {
                $labelHtml .= ' <span class="text-danger">*</span>';
            }
            if (!empty($value['trId'])) {
                $trAttr .= ' id="' . e((string)$value['trId']) . '"';
            }
            if (!empty($value['trClass'])) {
                $trAttr .= ' class="' . e((string)$value['trClass']) . '"';
            }
            if (!empty($value['tdClass'])) {
                $tdClass = ' class="' . e((string)$value['tdClass']) . '"';
            }
            $html = array_key_exists('html', $value)
                ? (string)$value['html']
                : e((string)($value['value'] ?? ''));
        } else {
            $html = e((string)$value);
        }
        echo '<tr' . $trAttr . '><th scope="row">' . $labelHtml . '</th><td' . $tdClass . '>' . $html . '</td></tr>';
    }
    echo '</table>';
    echo '</div>';
}

/**
 * Render a pair of side-by-side white key-value cards (`.kv-grid`).
 */
function render_kv_pair_cards(array $left, array $right, string $gridClass = 'kv-grid'): void
{
    echo '<div class="' . e($gridClass) . '">';
    render_kv_card($left);
    render_kv_card($right);
    echo '</div>';
}
