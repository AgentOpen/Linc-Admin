<?php

declare(strict_types=1);

namespace Linc\Repository;

use Linc\Core\Database;

/**
 * 认证数据访问。
 */
final class AuthRepository
{
    /** @return array<string,mixed>|null */
    public static function selectUserByUsername(string $username): ?array
    {
        return Database::first(
            "SELECT u.id, u.username, u.password, u.nickname, u.status, u.dept_id, d.dept_name
             FROM sys_user u
             LEFT JOIN sys_dept d ON d.id = u.dept_id
             WHERE u.username = ? AND u.deleted = 0
             LIMIT 1",
            [$username]
        );
    }

    /** @return list<string> */
    public static function selectRoleCodes(int $userId): array
    {
        $rows = Database::all(
            "SELECT r.role_code
             FROM sys_user_role ur
             JOIN sys_role r ON r.id = ur.role_id
             WHERE ur.user_id = ? AND r.status = 1 AND r.deleted = 0
             ORDER BY r.sort, r.id",
            [$userId]
        );
        return array_map(static fn (array $row): string => (string) $row['role_code'], $rows);
    }

    /** @return list<string> */
    public static function selectPermissions(int $userId): array
    {
        $rows = Database::all(
            "SELECT DISTINCT m.permission
             FROM sys_user_role ur
             JOIN sys_role r ON r.id = ur.role_id AND r.status = 1 AND r.deleted = 0
             JOIN sys_role_menu rm ON rm.role_id = ur.role_id
             JOIN sys_menu m ON m.id = rm.menu_id
             WHERE ur.user_id = ?
               AND m.status = 1
               AND m.permission IS NOT NULL
               AND m.permission <> ''
             ORDER BY m.permission",
            [$userId]
        );
        return array_map(static fn (array $row): string => (string) $row['permission'], $rows);
    }

    /** @return list<array<string,mixed>> */
    public static function selectRouteMenus(int $userId): array
    {
        return Database::all(
            "SELECT DISTINCT m.id, m.parent_id, m.menu_name, m.menu_type, m.icon, m.path,
                    m.component, m.permission, m.sort
             FROM sys_user_role ur
             JOIN sys_role r ON r.id = ur.role_id AND r.status = 1 AND r.deleted = 0
             JOIN sys_role_menu rm ON rm.role_id = ur.role_id
             JOIN sys_menu m ON m.id = rm.menu_id
             WHERE ur.user_id = ?
               AND m.status = 1
               AND m.visible = 1
               AND m.menu_type IN ('dir', 'menu')
             ORDER BY m.parent_id, m.sort, m.id",
            [$userId]
        );
    }

    /** @return list<string> 超级管理员：全部启用的权限码 */
    public static function selectAllPermissions(): array
    {
        $rows = Database::all(
            "SELECT DISTINCT permission
             FROM sys_menu
             WHERE status = 1 AND permission IS NOT NULL AND permission <> ''
             ORDER BY permission"
        );
        return array_map(static fn (array $row): string => (string) $row['permission'], $rows);
    }

    /** @return list<array<string,mixed>> 超级管理员：全部启用且可见的目录与菜单 */
    public static function selectAllRouteMenus(): array
    {
        return Database::all(
            "SELECT id, parent_id, menu_name, menu_type, icon, path, component, permission, sort
             FROM sys_menu
             WHERE status = 1 AND visible = 1 AND menu_type IN ('dir', 'menu')
             ORDER BY parent_id, sort, id"
        );
    }

    public static function insertLoginLog(string $username, int $status, string $msg): void
    {
        Database::execute(
            "INSERT INTO sys_login_log (username, status, msg, login_time)
             VALUES (?, ?, ?, NOW())",
            [$username, $status, $msg]
        );
    }

    /** 登录成功后记录最近登录时间（sys_user.last_login_time），用户列表与看板据此展示 */
    public static function updateLastLoginTime(int $userId): void
    {
        Database::execute(
            "UPDATE sys_user SET last_login_time = NOW() WHERE id = ? AND deleted = 0",
            [$userId]
        );
    }

    public static function updatePassword(int $userId, string $username, string $password): int
    {
        return Database::execute(
            "UPDATE sys_user
             SET password = ?, update_by = ?, update_time = NOW()
             WHERE id = ? AND deleted = 0",
            [$password, $username, $userId]
        );
    }
}
