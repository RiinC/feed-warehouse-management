<?php
/** Hộp thông báo. $type: success | error | warning | info */
function render_alert(string $type, string $message): void
{
    $type = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
    echo '<div class="alert alert-' . $type . '" role="alert">';
    echo '<span>' . e($message) . '</span>';
    echo '<button type="button" class="alert-close" data-dismiss="alert" aria-label="Đóng">&times;</button>';
    echo '</div>';
}

/** In và xóa các thông báo đã lưu bằng flash_set(). */
function render_flash(): void
{
    foreach (flash_get_all() as $f) {
        render_alert($f['type'], $f['message']);
    }
}
