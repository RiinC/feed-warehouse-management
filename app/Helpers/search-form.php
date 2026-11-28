<?php
/**
 * Form tìm kiếm / lọc (method GET).
 * $fields: ['tên_tham_số' => ['label' => 'Nhãn', 'type' => 'text|date|select|readonly', 'options' => ['giá trị' => 'nhãn'], 'placeholder' => '',
 *            'value' => (readonly) giá trị hiển thị, 'parent' => (select) tên ô cha, 'groups' => (select) [giá trị => giá trị ô cha]]]
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
        if ($type === 'readonly') {
            // Giá trị cố định theo phân quyền: chỉ hiển thị, không gửi kèm khi tìm kiếm
            echo '<input type="text" id="f_' . e($name) . '" value="' . e((string)($f['value'] ?? '')) . '" readonly class="input-readonly">';
        } elseif ($type === 'select') {
            $parentAttr = isset($f['parent']) ? ' data-parent="f_' . e($f['parent']) . '"' : '';
            echo '<select id="f_' . e($name) . '" name="' . e($name) . '"' . $parentAttr . '><option value="">Tất cả</option>';
            foreach (($f['options'] ?? []) as $k => $label) {
                // Ô chọn phụ thuộc (vd. khu vực theo kho): $f['groups'][$k] = giá trị ô cha mà lựa chọn này thuộc về
                $g = isset($f['groups'][$k]) ? ' data-group="' . e((string)$f['groups'][$k]) . '"' : '';
                echo '<option value="' . e($k) . '"' . $g . ((string)$k === $val ? ' selected' : '') . '>' . e($label) . '</option>';
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
