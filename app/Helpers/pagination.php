<?php
/**
 * Phân trang. Giữ nguyên các tham số lọc trên URL, chỉ đổi ?page=.
 * $page: trang hiện tại (từ 1), $total: tổng số dòng, $perPage: dòng/trang.
 * Tính offset: ($page - 1) * $perPage
 */

/**
 * Tạo danh sách các số trang hiển thị cùng dấu "..." thông minh.
 * Khi có nhiều trang (kể cả 100 trang):
 * Trang 1:  [1, 2, 3, 4, 5, '...', 100]
 * Trang 50: [1, '...', 49, 50, 51, '...', 100]
 * Trang 99: [1, '...', 96, 97, 98, 99, 100]
 */
function pagination_items(int $current, int $total): array
{
    if ($total <= 1) {
        return [1];
    }
    if ($total <= 7) {
        return range(1, $total);
    }
    if ($current <= 4) {
        return [1, 2, 3, 4, 5, '...', $total];
    }
    if ($current >= $total - 3) {
        return [1, '...', $total - 4, $total - 3, $total - 2, $total - 1, $total];
    }
    return [1, '...', $current - 1, $current, $current + 1, '...', $total];
}

function render_pagination(int $page, int $total, int $perPage = 0, string $baseUrl = ''): void
{
    $perPage = $perPage > 0 ? $perPage : (int)config('per_page', 10);
    $pages = (int)ceil($total / $perPage);
    if ($pages <= 1) {
        return;
    }
    $page = max(1, min($page, $pages));
    $params = $_GET;
    $link = function (int $p) use ($params, $baseUrl): string {
        $params['page'] = $p;
        return e(($baseUrl !== '' ? $baseUrl : strtok($_SERVER['REQUEST_URI'] ?? '', '?')) . '?' . http_build_query($params));
    };

    echo '<nav class="pagination" aria-label="Phân trang">';
    if ($page > 1) {
        echo '<a href="' . $link($page - 1) . '">&laquo;</a>';
    }
    foreach (pagination_items($page, $pages) as $item) {
        if ($item === '...') {
            echo '<span class="ellipsis" style="padding: 0 6px; color: #94a3b8; user-select: none;">...</span>';
        } elseif ($item === $page) {
            echo '<span class="current">' . $item . '</span>';
        } else {
            echo '<a href="' . $link((int)$item) . '">' . $item . '</a>';
        }
    }
    if ($page < $pages) {
        echo '<a href="' . $link($page + 1) . '">&raquo;</a>';
    }
    echo '</nav>';
}
