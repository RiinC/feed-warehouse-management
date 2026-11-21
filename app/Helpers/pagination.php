<?php
/**
 * Phân trang. Giữ nguyên các tham số lọc trên URL, chỉ đổi ?page=.
 * $page: trang hiện tại (từ 1), $total: tổng số dòng, $perPage: dòng/trang.
 * Tính offset: ($page - 1) * $perPage
 */
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
    for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++) {
        echo $p === $page ? '<span class="current">' . $p . '</span>' : '<a href="' . $link($p) . '">' . $p . '</a>';
    }
    if ($page < $pages) {
        echo '<a href="' . $link($page + 1) . '">&raquo;</a>';
    }
    echo '</nav>';
}
