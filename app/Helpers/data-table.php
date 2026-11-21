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
    $actions = $options['actions'] ?? null;
    $colspan = count($columns) + ($stt ? 1 : 0) + ($actions ? 1 : 0);

    echo '<div class="table-wrap"><table class="table"><thead><tr>';
    if ($stt) {
        echo '<th class="w-stt">STT</th>';
    }
    foreach ($columns as $def) {
        $label = is_array($def) ? ($def['label'] ?? '') : $def;
        $class = is_array($def) ? ($def['class'] ?? '') : '';
        echo '<th' . ($class ? ' class="' . e($class) . '"' : '') . '>' . e($label) . '</th>';
    }
    if ($actions) {
        echo '<th class="w-actions">Thao tác</th>';
    }
    echo '</tr></thead><tbody>';

    if (!$rows) {
        echo '<tr><td class="empty-row" colspan="' . $colspan . '">' . e($options['empty'] ?? 'Không có dữ liệu.') . '</td></tr>';
    }
    foreach (array_values($rows) as $i => $row) {
        echo '<tr>';
        if ($stt) {
            echo '<td>' . ($i + 1) . '</td>';
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
