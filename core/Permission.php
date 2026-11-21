<?php
namespace Core;

/**
 * Phân quyền theo vai trò. Ma trận quyền: config/permissions.php (module -> hành động -> các vai trò được phép).
 * Quản trị viên luôn được phép mọi thao tác. Quyền của từng route khai báo ở routes/web.php.
 */
final class Permission
{
    public static function matrix(): array
    {
        static $matrix = null;
        return $matrix ??= require ROOT_PATH . '/config/permissions.php';
    }

    public static function can(string $module, string $action = 'view'): bool
    {
        $role = Auth::role();
        if ($role === '') {
            return false;
        }
        if ($role === ROLE_ADMIN) {
            return true;
        }
        return in_array($role, self::matrix()[$module][$action] ?? [], true);
    }

    /** Vai trò hiện tại có được mở route (GET) này không? Route không tồn tại -> false; route công khai -> true. */
    public static function canRoute(string $path): bool
    {
        $perm = Router::instance()->permissionFor($path);
        if ($perm === null) {
            return false;
        }
        return $perm['module'] === null ? true : self::can($perm['module'], $perm['action']);
    }
}
