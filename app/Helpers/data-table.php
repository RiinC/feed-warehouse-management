<?php
/**
 * Bảng dữ liệu.
 * $columns: ['khóa_cột' => 'Nhãn'] hoặc ['khóa' => ['label' => 'Nhãn', 'class' => 'num', 'render' => fn($row) => 'HTML đã escape']]
 * $rows:    mảng các dòng (mảng kết hợp)
 * $options: ['empty' => 'Thông báo khi không có dữ liệu', 'stt' => true (thêm cột STT), 'actions' => fn($row) => 'HTML nút thao tác']
 */
function render_data_table(array $columns, array $rows, array $options = []): void
{
    $stt = !empty($options['stt']);
    $sttStart = max(0, (int)($options['stt_start'] ?? 0));
    $actions = $options['actions'] ?? null;
    $rowAttrsFn = is_callable($options['row_attrs'] ?? null) ? $options['row_attrs'] : null;
    $sortKey = (string)($options['sort_key'] ?? '');
    $sortDir = strtolower((string)($options['sort_dir'] ?? '')) === 'desc' ? 'desc' : 'asc';
    $sortUrlFn = is_callable($options['sort_url'] ?? null) ? $options['sort_url'] : null;
    $colspan = count($columns) + ($stt ? 1 : 0) + ($actions ? 1 : 0);

    echo '<div class="table-wrap"><table class="table"><thead><tr>';
    if ($stt) {
        echo '<th class="w-stt">STT</th>';
    }
    foreach ($columns as $key => $def) {
        $label = is_array($def) ? ($def['label'] ?? '') : $def;
        $class = is_array($def) ? ($def['class'] ?? '') : '';
        $labelHtml = e($label);
        if (is_array($def) && !empty($def['sortable'])) {
            $keySort = (string)($def['sort_key'] ?? $key);
            $nextDir = ($sortKey === $keySort && $sortDir === 'asc') ? 'desc' : 'asc';
            if ($sortUrlFn) {
                $url = (string)$sortUrlFn($keySort, $nextDir);
            } else {
                $params = $_GET;
                $params['sort'] = $keySort;
                $params['dir'] = $nextDir;
                unset($params['page']);
                $url = strtok($_SERVER['REQUEST_URI'] ?? '', '?') . '?' . http_build_query($params);
            }
            $indicator = $sortKey === $keySort ? ($sortDir === 'asc' ? ' ▲' : ' ▼') : ' ↕';
            $labelHtml = '<a class="sort-link" href="' . e($url) . '">' . e($label) . $indicator . '</a>';
            $class = trim($class . ' sortable' . ($sortKey === $keySort ? ' sorted-' . $sortDir : ''));
        }
        echo '<th' . ($class ? ' class="' . e($class) . '"' : '') . '>' . $labelHtml . '</th>';
    }
    if ($actions) {
        echo '<th class="w-actions">Thao tác</th>';
    }
    echo '</tr></thead><tbody>';

    if (!$rows) {
        echo '<tr><td class="empty-row" colspan="' . $colspan . '">' . e($options['empty'] ?? 'Không có dữ liệu.') . '</td></tr>';
    }
    foreach (array_values($rows) as $i => $row) {
        $rowAttrs = $rowAttrsFn ? (array)$rowAttrsFn($row) : [];
        $attrHtml = '';
        foreach ($rowAttrs as $an => $av) {
            $an = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$an);
            if ($av === true) {
                $attrHtml .= ' ' . $an;
            } elseif ($av !== false && $av !== null) {
                $attrHtml .= ' ' . $an . '="' . e((string)$av) . '"';
            }
        }
        echo '<tr' . $attrHtml . '>';
        if ($stt) {
            echo '<td>' . ($sttStart + $i + 1) . '</td>';
        }
        foreach ($columns as $key => $def) {
            $class = is_array($def) ? ($def['class'] ?? '') : '';
            $cell = (is_array($def) && isset($def['render'])) ? $def['render']($row) : e($row[$key] ?? '');
            echo '<td' . ($class ? ' class="' . e($class) . '"' : '') . '>' . $cell . '</td>';
        }
        if ($actions) {
            echo '<td class="cell-actions">' . $actions($row) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
