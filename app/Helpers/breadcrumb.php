<?php
/**
 * Đường dẫn trang. $items: [[nhãn, route|null], ...]; "Tổng quan" được thêm tự động.
 */
function render_breadcrumb(array $items): void
{
    echo '<nav class="breadcrumb" aria-label="breadcrumb">';
    echo '<a href="' . e(route('dashboard')) . '">Tổng quan</a>';
    foreach ($items as [$label, $href]) {
        if ($label === 'Tổng quan') {
            continue;
        }
        echo '<span class="sep">/</span>';
        // Cấp cha chỉ là liên kết khi vai trò hiện tại được mở trang đó; nếu không thì hiện chữ thường (tránh 403/404).
        echo ($href && \Core\Permission::canRoute($href))
            ? '<a href="' . e(route($href)) . '">' . e($label) . '</a>'
            : '<span class="current">' . e($label) . '</span>';
    }
    echo '</nav>';
}
