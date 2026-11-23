<?php
/**
 * Form tìm kiếm / lọc (method GET).
 * $fields: ['tên_tham_số' => ['label' => 'Nhãn', 'type' => 'text|date|select', 'options' => ['giá trị' => 'nhãn'], 'placeholder' => '', 'attrs' => [...]]]
 * $values: giá trị hiện tại (thường là $_GET)
 */
function render_search_form(array $fields, array $values = [], string $action = ''): void
{
    $actionUrl = $action !== '' ? $action : route(current_route());
    echo '<form class="card filter-card" method="get" action="' . e($actionUrl) . '"><div class="filter-grid">';
    if (!config('url_rewrite', true) && isset($_GET['r'])) {
        echo '<input type="hidden" name="r" value="' . e((string)$_GET['r']) . '">';
    }
    foreach ($fields as $name => $f) {
        $type = $f['type'] ?? 'text';
        $val = (string)($values[$name] ?? '' );
        echo '<div class="field"><label for="f_' . e($name) . '">' . e($f['label'] ?? $name) . '</label>';
        $attrs = '';
        foreach (($f['attrs'] ?? []) as $an => $av) {
            $an = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$an);
            $attrs .= ' ' . $an . '="' . e((string)$av) . '"';
        }
        if ($type === 'select') {
            echo '<select id="f_' . e($name) . '" name="' . e($name) . '"' . $attrs . '><option value="">Tất cả</option>';
            foreach (($f['options'] ?? []) as $k => $opt) {
                if (is_array($opt)) {
                    $optLabel = $opt['label'] ?? $k;
                    $optAttrs = $opt['attrs'] ?? [];
                } else {
                    $optLabel = $opt;
                    $optAttrs = [];
                }
                $a = '';
                foreach ($optAttrs as $an => $av) {
                    $an = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$an);
                    $a .= ' ' . $an . '="' . e((string)$av) . '"';
                }
                echo '<option value="' . e((string)$k) . '"' . $a . ((string)$k === $val ? ' selected' : '') . '>' . e($optLabel) . '</option>';
            }
            echo '</select>';
        } else {
            echo '<input type="' . e($type) . '" id="f_' . e($name) . '" name="' . e($name) . '" value="' . e($val) . '" placeholder="' . e($f['placeholder'] ?? '') . '"' . $attrs . '>';
        }
        echo '</div>';
    }
    echo '<div class="field filter-actions"><button class="btn btn-primary" type="submit">' . icon('search', 16) . ' Tìm kiếm</button>';
    echo '<a class="btn btn-ghost" href="' . e($actionUrl) . '">Đặt lại</a></div>';
    echo '</div></form>';
}

