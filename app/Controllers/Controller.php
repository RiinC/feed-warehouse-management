<?php
namespace App\Controllers;

use App\Models\ThongBao;
use Core\Auth;
use Core\Menu;

/** Controller cơ sở của ứng dụng: bổ sung dữ liệu chung cho layout (menu theo vai trò, chuông thông báo). */
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
}
