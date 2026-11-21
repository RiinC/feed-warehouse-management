<?php
namespace App\Controllers;

use Core\Auth;
use Core\Menu;

/** Tổng quan: lối tắt tới các chức năng mà vai trò hiện tại được phép (config/menu.php lọc theo quyền của route). */
class DashboardController extends Controller
{
    /** GET dashboard */
    public function index(): void
    {
        $user = Auth::user();
        $this->render('dashboard/index', [
            'sections' => Menu::visibleSections('dash'),
        ], [
            'title'      => 'Tổng quan',
            'subtitle'   => 'Xin chào ' . $user['hoTen'] . ' (' . ROLE_LABELS[$user['role']] . ')',
            'module'     => 'dashboard',
            'breadcrumb' => [['Tổng quan', null]],
        ]);
    }
}
