<?php
namespace App\Models;

use Core\Model;
use Core\Router;

/**
 * Thông báo (chuông ở thanh trên cùng). Bảng ThongBao: mỗi dòng gửi cho MỘT nhân viên.
 *
 * GỬI THÔNG BÁO từ Controller nghiệp vụ:
 *   ThongBao::notifyUser('NV003', 'Yêu cầu xuất kho đã được duyệt', 'Phiếu YC-001', 'xuat-kho/yeu-cau', 'success');
 *   ThongBao::notifyRole(ROLE_MANAGER, 'Có lô hàng chờ xác nhận', 'Lô LH-001', 'chat-luong', 'warning');
 *   ThongBao::notifyRole([ROLE_MANAGER, ROLE_WAREHOUSE], '...', '...', 'ton-kho', 'info', Auth::staffId());
 * Đường dẫn đích là route (có thể kèm tham số): 'lo-hang/detail?id=LH-001'.
 * Loại thông báo (màu): info | success | warning | danger.
 */
class ThongBao extends Model
{
    public const TYPES = ['info', 'success', 'warning', 'danger'];

    /**
     * Chỉ chấp nhận route nội bộ có thật (kèm ?tham_số). Đường dẫn kiểu cũ "modules/x/y.php" được đổi sang route mới.
     * Trả về route đã chuẩn hóa hoặc null nếu không hợp lệ (chặn chuyển hướng ra ngoài).
     */
    public static function safeLink(?string $link): ?string
    {
        if ($link === null || trim($link) === '') {
            return null;
        }
        $link = ltrim($link, '/');
        if (preg_match('#^modules/(.+?)\.php(\?.*)?$#', $link, $m)) {
            $path = preg_replace('#/index$#', '', $m[1]);
            $link = $path . ($m[2] ?? '');
        }
        if (!preg_match('#^[A-Za-z0-9_\-]+(/[A-Za-z0-9_\-]+)*(\?[A-Za-z0-9_=&%.\-]*)?$#', $link)) {
            return null;
        }
        return Router::instance()->has($link) ? $link : null;
    }

    /* ------------------------------ Gửi ------------------------------ */
    public static function notifyUser(string $maNhanVien, string $title, string $message = '', ?string $link = null, string $type = 'info'): void
    {
        if (!in_array($type, self::TYPES, true)) {
            $type = 'info';
        }
        static::exec(
            'INSERT INTO ThongBao (maNhanVien, tieuDe, noiDung, duongDan, loai) VALUES (?, ?, ?, ?, ?)',
            [
                $maNhanVien,
                mb_substr($title, 0, 150),
                $message !== '' ? mb_substr($message, 0, 500) : null,
                self::safeLink($link),
                $type,
            ]
        );
    }

    /** Gửi cho mọi nhân viên đang hoạt động thuộc (các) vai trò. Trả về số người đã nhận. */
    public static function notifyRole($roles, string $title, string $message = '', ?string $link = null, string $type = 'info', ?string $exceptMaNhanVien = null): int
    {
        $ids = NhanVien::activeIdsByRoles($roles, $exceptMaNhanVien);
        foreach ($ids as $id) {
            self::notifyUser($id, $title, $message, $link, $type);
        }
        return count($ids);
    }

    /* ------------------------------ Đọc (của một nhân viên) ------------------------------ */

    /** Số thông báo chưa đọc. Trả về 0 nếu bảng ThongBao chưa được tạo (không làm hỏng trang). */
    public static function unreadCount(string $maNhanVien): int
    {
        try {
            $r = static::one('SELECT COUNT(*) AS c FROM ThongBao WHERE maNhanVien = ? AND daDoc = 0', [$maNhanVien]);
            return (int)($r['c'] ?? 0);
        } catch (\Throwable $ex) {
            return 0;
        }
    }

    public static function recent(string $maNhanVien, int $limit = 8): array
    {
        try {
            return static::all(
                'SELECT maThongBao, tieuDe, noiDung, duongDan, loai, daDoc, thoiDiemTao
                 FROM ThongBao WHERE maNhanVien = ?
                 ORDER BY thoiDiemTao DESC, maThongBao DESC LIMIT ?',
                [$maNhanVien, $limit]
            );
        } catch (\Throwable $ex) {
            return [];
        }
    }

    /** Danh sách có phân trang. $filter: '' (tất cả) | 'chua-doc'. Trả về [các dòng, tổng số]. */
    public static function page(string $maNhanVien, int $page, int $perPage, string $filter = ''): array
    {
        $where = 'maNhanVien = ?' . ($filter === 'chua-doc' ? ' AND daDoc = 0' : '');
        $total = (int)(static::one("SELECT COUNT(*) AS c FROM ThongBao WHERE $where", [$maNhanVien])['c'] ?? 0);
        $rows = static::all(
            "SELECT maThongBao, tieuDe, noiDung, duongDan, loai, daDoc, thoiDiemTao
             FROM ThongBao WHERE $where
             ORDER BY thoiDiemTao DESC, maThongBao DESC LIMIT ? OFFSET ?",
            [$maNhanVien, $perPage, max(0, ($page - 1) * $perPage)]
        );
        return [$rows, $total];
    }

    /** Lấy một thông báo, chỉ khi thuộc về nhân viên này. */
    public static function find(int $id, string $maNhanVien): ?array
    {
        return static::one('SELECT * FROM ThongBao WHERE maThongBao = ? AND maNhanVien = ?', [$id, $maNhanVien]);
    }

    public static function markRead(int $id, string $maNhanVien): void
    {
        static::exec(
            'UPDATE ThongBao SET daDoc = 1, thoiDiemDoc = NOW() WHERE maThongBao = ? AND maNhanVien = ? AND daDoc = 0',
            [$id, $maNhanVien]
        );
    }

    public static function markAllRead(string $maNhanVien): int
    {
        return static::exec('UPDATE ThongBao SET daDoc = 1, thoiDiemDoc = NOW() WHERE maNhanVien = ? AND daDoc = 0', [$maNhanVien]);
    }
}
