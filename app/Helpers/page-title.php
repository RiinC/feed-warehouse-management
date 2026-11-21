<?php
/**
 * Tiêu đề trang. $actionsHtml: HTML các nút (đã escape sẵn), VD
 *   '<a class="btn btn-primary" href="...">Thêm mới</a>'
 */
function render_page_title(string $title, string $subtitle = '', string $actionsHtml = ''): void
{
    if ($title === '') {
        return;
    }
    echo '<div class="page-title"><div><h1>' . e($title) . '</h1>';
    if ($subtitle !== '') {
        echo '<p class="muted">' . e($subtitle) . '</p>';
    }
    echo '</div>';
    if ($actionsHtml !== '') {
        echo '<div class="page-actions">' . $actionsHtml . '</div>';
    }
    echo '</div>';
}
