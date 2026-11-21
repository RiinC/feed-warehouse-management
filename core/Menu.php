<?php
namespace Core;

/**
 * Menu và lối tắt theo vai trò.
 *   config/menu.php         : danh mục chức năng (nhãn, icon, route, nơi hiển thị). Quyền lấy từ routes/web.php.
 *   config/menu-layout.php  : bố cục menu (nhóm, thứ tự) của từng vai trò.
 * Mục nào vai trò hiện tại không có quyền thì tự ẩn; nhóm rỗng tự ẩn; nhóm còn đúng 1 mục thì thành mục đơn.
 */
final class Menu
{
    public static function catalog(): array
    {
        static $c = null;
        return $c ??= require ROOT_PATH . '/config/menu.php';
    }

    /** Tìm một mục trong danh mục theo route; null nếu không có. */
    public static function item(string $route): ?array
    {
        foreach (self::catalog() as $section) {
            foreach ($section['items'] as $it) {
                if ($it['route'] === $route) {
                    return $it;
                }
            }
        }
        return null;
    }

    /**
     * Các nhóm chức năng vai trò hiện tại được phép dùng (dùng cho trang Tổng quan).
     * $surface: 'menu' (chỉ mục show=menu) | 'dash' (mục show=menu và show=dash).
     */
    public static function visibleSections(string $surface = 'dash'): array
    {
        $allowed = $surface === 'dash' ? ['menu', 'dash'] : ['menu'];
        $out = [];
        foreach (self::catalog() as $section) {
            $items = [];
            foreach ($section['items'] as $it) {
                if (in_array($it['show'] ?? 'menu', $allowed, true) && Permission::canRoute($it['route'])) {
                    $items[] = $it;
                }
            }
            if ($items) {
                $out[] = ['title' => $section['title'], 'items' => $items];
            }
        }
        return $out;
    }

    /**
     * Cây menu sidebar của vai trò hiện tại: mục đơn ['label','icon','route','module']
     * hoặc nhóm ['label','icon','items' => [['label','route','module'], ...]].
     */
    public static function tree(): array
    {
        static $layouts = null;
        $layouts ??= require ROOT_PATH . '/config/menu-layout.php';

        $tree = [];
        foreach ($layouts[Auth::role()] ?? [] as $node) {
            if (isset($node['items'])) {
                $kids = [];
                foreach ($node['items'] as [$label, $route]) {
                    if (Permission::canRoute($route)) {
                        $kids[] = ['label' => $label, 'route' => $route, 'module' => self::moduleOf($route)];
                    }
                }
                if (count($kids) === 1) {
                    // Nhóm chỉ còn một mục vai trò này được dùng -> hiện như mục đơn, khỏi phải mở nhóm.
                    $tree[] = ['label' => $node['label'], 'icon' => $node['icon'], 'route' => $kids[0]['route'], 'module' => $kids[0]['module']];
                } elseif ($kids) {
                    $tree[] = ['label' => $node['label'], 'icon' => $node['icon'], 'items' => $kids];
                }
            } elseif (Permission::canRoute($node['route'])) {
                $node['module'] = self::moduleOf($node['route']);
                $tree[] = $node;
            }
        }
        return $tree;
    }

    private static function moduleOf(string $route): string
    {
        return (string)(Router::instance()->permissionFor($route)['module'] ?? '');
    }
}
