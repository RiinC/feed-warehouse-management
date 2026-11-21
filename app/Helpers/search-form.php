<?php
/**
 * Form tìm kiếm / lọc (method GET).
 * $fields: ['tên_tham_số' => ['label' => 'Nhãn', 'type' => 'text|date|select', 'options' => ['giá trị' => 'nhãn'], 'placeholder' => '']]
 * $values: giá trị hiện tại (thường là $_GET)
 */
function render_search_form(array $fields, array $values = [], string $action = ''): void
{
    echo '<form class="card filter-card" method="get" action="' . e($action !== '' ? $action : route(current_route())) . '"><div class="filter-grid">';
    if (!config('url_rewrite', true) && isset($_GET['r'])) {
        echo '<input type="hidden" name="r" value="' . e((string)$_GET['r']) . '">';   // giữ route khi tắt rewrite
    }
    foreach ($fields as $name => $f) {
        $type = $f['type'] ?? 'text';
        $val = (string)($values[$name] ?? '');
        echo '<div class="field"><label for="f_' . e($name) . '">' . e($f['label'] ?? $name) . '</label>';
        if ($type === 'select') {
            echo '<select id="f_' . e($name) . '" name="' . e($name) . '"><option value="">Tất cả</option>';
            foreach (($f['options'] ?? []) as $k => $label) {
                echo '<option value="' . e($k) . '"' . ((string)$k === $val ? ' selected' : '') . '>' . e($label) . '</option>';
            }
            echo '</select>';
        } else {
            echo '<input type="' . e($type) . '" id="f_' . e($name) . '" name="' . e($name) . '" value="' . e($val) . '" placeholder="' . e($f['placeholder'] ?? '') . '">';
        }
        echo '</div>';
    }
    echo '<div class="field filter-actions"><button class="btn btn-primary" type="submit">' . icon('search', 16) . ' Tìm kiếm</button>';
    echo '<a class="btn btn-ghost" href="' . e($action !== '' ? $action : route(current_route())) . '">Đặt lại</a></div>';
    echo '</div></form>';
}
