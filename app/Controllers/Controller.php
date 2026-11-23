<?php
namespace App\Controllers;

use App\Models\NhanVien;
use App\Models\ThongBao;
use Core\Auth;
use Core\Menu;

/** Controller cơ sở: dữ liệu chung cho layout + phạm vi kho của người đang đăng nhập. */
abstract class Controller extends \Core\Controller
{
    protected function sharedData(): array
    {
        if (!Auth::check()) {
            return ['menuTree' => [], 'unread' => 0, 'recentNotifications' => []];
        }
        $id = Auth::staffId();
        return [
            'menuTree'            => Menu::tree(),
            'unread'              => ThongBao::unreadCount($id),
            'recentNotifications' => ThongBao::recent($id, 8),
        ];
    }

    /**
     * Phạm vi kho của người dùng: null = Quản trị viên (không giới hạn);
     * chuỗi rỗng = tài khoản chưa được phân công kho; còn lại là mã kho phụ trách.
     */
    protected function warehouseScope(): ?string
    {
        if (!Auth::check()) {
            return '';
        }
        if (Auth::role() === ROLE_ADMIN) {
            return null;
        }
        $nv = NhanVien::find(Auth::staffId());
        return (string)($nv['maKho'] ?? '');
    }

    /** Trả trang 403 thật (không chỉ ẩn nút). */
    protected function forbidden(): void
    {
        (new ErrorController())->show(403);
    }
}

