<?php

declare(strict_types=1);

namespace Linc\Service;

use Linc\Repository\AuthRepository;

/**
 * 权限解析，
 * 账号停用无权限；super_admin 角色直通；否则按用户菜单权限码判定。
 */
final class PermissionService
{
    private const SUPER_ADMIN = 'super_admin';

    public static function hasPermission(?string $username, string $permission): bool
    {
        if ($username === null || trim($username) === '') {
            return false;
        }
        $user = AuthRepository::selectUserByUsername($username);
        if ($user === null || (int) ($user['status'] ?? 0) !== 1) {
            return false;
        }
        $roleCodes = AuthRepository::selectRoleCodes((int) $user['id']);
        if (in_array(self::SUPER_ADMIN, $roleCodes, true)) {
            return true;
        }
        return in_array($permission, AuthRepository::selectPermissions((int) $user['id']), true);
    }

    public static function isSuperAdmin(int $userId): bool
    {
        return in_array(self::SUPER_ADMIN, AuthRepository::selectRoleCodes($userId), true);
    }
}
