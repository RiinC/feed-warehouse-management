<?php
/**
 * Chuông thông báo ở thanh trên cùng. Biến: $unread (số chưa đọc), $items (các thông báo mới nhất).
 * Bấm một mục -> thong-bao/open (đánh dấu đã đọc rồi tới trang đích). Tự cập nhật mỗi 30 giây bằng
 * public/assets/js/notifications.js (gọi route thong-bao/poll).
 */
?>
<div class="notif" id="notifRoot" data-poll-url="<?= e(route('thong-bao/poll')) ?>">
    <button type="button" class="icon-btn notif-btn" id="notifToggle" aria-label="Thông báo" aria-haspopup="true" aria-expanded="false">
        <?= icon('bell', 20) ?>
        <span class="notif-count" id="notifCount"<?= $unread > 0 ? '' : ' hidden' ?>><?= $unread > 99 ? '99+' : (int)$unread ?></span>
    </button>
    <div class="notif-panel" id="notifPanel" hidden>
        <div class="notif-head">
            <b>Thông báo</b>
            <form method="post" action="<?= e(route('thong-bao/read-all')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="back" value="<?= e(current_route()) ?>">
                <button type="submit" class="link-btn">Đánh dấu đã đọc tất cả</button>
            </form>
        </div>
        <ul class="notif-list" id="notifList">
            <?php foreach ($items as $n): ?>
                <li>
                    <a class="notif-item <?= $n['daDoc'] ? 'read' : 'unread' ?> type-<?= e($n['loai']) ?>" href="<?= e(route('thong-bao/open', ['id' => (int)$n['maThongBao']])) ?>">
                        <span class="notif-dot"></span>
                        <span class="notif-body">
                            <b><?= e($n['tieuDe']) ?></b>
                            <?php if ($n['noiDung']): ?><span class="notif-msg"><?= e($n['noiDung']) ?></span><?php endif; ?>
                            <small><?= e(time_ago($n['thoiDiemTao'])) ?></small>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
            <?php if (!$items): ?><li class="notif-empty">Chưa có thông báo nào.</li><?php endif; ?>
        </ul>
        <a class="notif-foot" href="<?= e(route('thong-bao')) ?>">Xem tất cả thông báo</a>
    </div>
</div>
