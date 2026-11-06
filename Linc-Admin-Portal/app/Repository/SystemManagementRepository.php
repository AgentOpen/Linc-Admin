<?php

declare(strict_types=1);

namespace Linc\Repository;

use Linc\Core\Database;
use Linc\Core\DataScopeCriteria;

/**
 * 系统管理数据访问。
 * 说明：MyBatis 动态 SQL 中的 scope 展开为位置参数；COALESCE 部分更新语义与原实现一致。
 */
final class SystemManagementRepository
{
    /* ── 用户 ───────────────────────────────────────────── */

    /** @return array{0: list<string>, 1: list<mixed>} 数据范围 SQL 片段与绑定值 */
    private static function scopeClause(DataScopeCriteria $scope): array
    {
        if ($scope->isAllScope()) {
            return ['(TRUE)', []];
        }
        $clauses = ['u.id = ?'];
        $params = [$scope->getUserId()];
        $deptIds = $scope->getDeptIds();
        if ($deptIds !== []) {
            $clauses[] = 'u.dept_id IN (' . implode(',', array_fill(0, count($deptIds), '?')) . ')';
            $params = array_merge($params, $deptIds);
        }
        return ['(' . implode(' OR ', $clauses) . ')', $params];
    }

    /**
     * 通用用户过滤条件：keyword/status/deptId/postId/数据范围。
     * @return array{0: string, 1: list<mixed>}
     */
    private static function userWhere(?string $keyword, ?int $status, ?int $deptId, ?int $postId, DataScopeCriteria $scope): array
    {
        [$scopeSql, $scopeParams] = self::scopeClause($scope);
        $sql = "u.deleted = 0
              AND (? IS NULL OR ? = ''
                   OR u.username LIKE CONCAT('%', ?, '%')
                   OR u.nickname LIKE CONCAT('%', ?, '%')
                   OR u.phone LIKE CONCAT('%', ?, '%'))
              AND (? IS NULL OR u.status = ?)
              AND (? IS NULL OR u.dept_id IN (SELECT sd.id FROM sys_dept sd WHERE sd.id = ? OR FIND_IN_SET(?, sd.ancestors)))
              AND (? IS NULL OR EXISTS (SELECT 1 FROM sys_user_post fup WHERE fup.user_id = u.id AND fup.post_id = ?))
              AND " . $scopeSql;
        $params = [$keyword, $keyword, $keyword, $keyword, $keyword, $status, $status, $deptId, $deptId, $deptId, $postId, $postId];
        return [$sql, array_merge($params, $scopeParams)];
    }

    public static function countUsers(?string $keyword, ?int $status, ?int $deptId, ?int $postId, DataScopeCriteria $scope): int
    {
        [$where, $params] = self::userWhere($keyword, $status, $deptId, $postId, $scope);
        return (int) Database::scalar(
            "SELECT COUNT(DISTINCT u.id)
             FROM sys_user u
             LEFT JOIN sys_dept d ON d.id = u.dept_id
             WHERE $where",
            $params
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectUsers(?string $keyword, ?int $status, ?int $deptId, ?int $postId, DataScopeCriteria $scope, int $offset, int $pageSize): array
    {
        [$where, $params] = self::userWhere($keyword, $status, $deptId, $postId, $scope);
        return Database::all(
            "SELECT u.id,
                    u.username,
                    u.nickname,
                    u.phone,
                    u.email,
                    d.dept_name AS deptName,
                    COALESCE(GROUP_CONCAT(DISTINCT p.post_name ORDER BY p.sort, p.id SEPARATOR ','), '') AS postName,
                    COALESCE(GROUP_CONCAT(DISTINCT r.role_name ORDER BY r.sort, r.id SEPARATOR ','), '') AS roleName,
                    u.status,
                    DATE_FORMAT(u.create_time, '%Y-%m-%d %H:%i') AS createTime,
                    DATE_FORMAT(u.last_login_time, '%Y-%m-%d %H:%i') AS lastLogin
             FROM sys_user u
             LEFT JOIN sys_dept d ON d.id = u.dept_id
             LEFT JOIN sys_user_post up ON up.user_id = u.id
             LEFT JOIN sys_post p ON p.id = up.post_id AND p.deleted = 0
             LEFT JOIN sys_user_role ur ON ur.user_id = u.id
             LEFT JOIN sys_role r ON r.id = ur.role_id AND r.deleted = 0
             WHERE $where
             GROUP BY u.id, u.username, u.nickname, u.phone, u.email, d.dept_name, u.status, u.create_time, u.last_login_time
             ORDER BY u.id
             LIMIT ?, ?",
            array_merge($params, [$offset, $pageSize])
        );
    }

    /** @return array<string,mixed>|null */
    public static function selectUserProfileByUsername(string $username): ?array
    {
        return Database::first(
            "SELECT u.id, u.dept_id AS deptId
             FROM sys_user u
             WHERE u.username = ? AND u.deleted = 0 AND u.status = 1
             LIMIT 1",
            [$username]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectActiveRoleScopes(int $userId): array
    {
        return Database::all(
            "SELECT r.id, r.data_scope AS dataScope
             FROM sys_user_role ur
             JOIN sys_role r ON r.id = ur.role_id
             WHERE ur.user_id = ? AND r.status = 1 AND r.deleted = 0
             ORDER BY r.sort, r.id",
            [$userId]
        );
    }

    /** @return list<int> */
    public static function selectDeptAndChildIds(int $deptId): array
    {
        $rows = Database::all(
            "SELECT d.id
             FROM sys_dept d
             WHERE d.deleted = 0
               AND (d.id = ? OR FIND_IN_SET(?, d.ancestors))
             ORDER BY d.id",
            [$deptId, $deptId]
        );
        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    /** @return list<int> */
    public static function selectRoleDeptIds(int $roleId): array
    {
        $rows = Database::all(
            "SELECT dept_id FROM sys_role_dept WHERE role_id = ? ORDER BY dept_id",
            [$roleId]
        );
        return array_map(static fn (array $row): int => (int) $row['dept_id'], $rows);
    }

    /* ── 角色 ───────────────────────────────────────────── */

    private static function roleWhere(?string $keyword, ?int $status): array
    {
        return [
            "r.deleted = 0
              AND (? IS NULL OR ? = ''
                   OR r.role_name LIKE CONCAT('%', ?, '%')
                   OR r.role_code LIKE CONCAT('%', ?, '%'))
              AND (? IS NULL OR r.status = ?)",
            [$keyword, $keyword, $keyword, $keyword, $status, $status],
        ];
    }

    public static function countRoles(?string $keyword, ?int $status): int
    {
        [$where, $params] = self::roleWhere($keyword, $status);
        return (int) Database::scalar("SELECT COUNT(*) FROM sys_role r WHERE $where", $params);
    }

    /** @return list<array<string,mixed>> */
    public static function selectRoles(?string $keyword, ?int $status, int $offset, int $pageSize): array
    {
        [$where, $params] = self::roleWhere($keyword, $status);
        return Database::all(
            "SELECT r.id,
                    r.role_name AS name,
                    r.role_code AS code,
                    CASE r.data_scope
                        WHEN 'ALL' THEN '全部数据权限'
                        WHEN 'DEPT_AND_CHILD' THEN '本部门及以下'
                        WHEN 'DEPT' THEN '本部门数据权限'
                        WHEN 'CUSTOM' THEN '自定义数据权限'
                        ELSE '仅本人数据权限'
                    END AS dataScope,
                    (SELECT COUNT(*) FROM sys_user_role ur JOIN sys_user cu ON cu.id = ur.user_id AND cu.deleted = 0 WHERE ur.role_id = r.id) AS userCount,
                    r.sort,
                    r.status,
                    r.builtin,
                    DATE_FORMAT(r.create_time, '%Y-%m-%d %H:%i') AS createTime,
                    (SELECT GROUP_CONCAT(rm.menu_id ORDER BY rm.menu_id)
                     FROM sys_role_menu rm
                     WHERE rm.role_id = r.id) AS menuIdsText,
                    r.remark
             FROM sys_role r
             WHERE $where
             ORDER BY r.sort, r.id
             LIMIT ?, ?",
            array_merge($params, [$offset, $pageSize])
        );
    }

    /* ── 菜单 ───────────────────────────────────────────── */

    /** @return list<array<string,mixed>> */
    public static function selectMenus(?string $keyword, ?int $status): array
    {
        return Database::all(
            "SELECT m.id,
                    m.parent_id AS parentId,
                    m.menu_name AS name,
                    m.menu_type AS type,
                    m.icon,
                    m.path,
                    m.component,
                    m.permission,
                    m.sort,
                    m.visible,
                    m.status
             FROM sys_menu m
             WHERE (? IS NULL OR ? = ''
                    OR m.menu_name LIKE CONCAT('%', ?, '%')
                    OR m.permission LIKE CONCAT('%', ?, '%'))
               AND (? IS NULL OR m.status = ?)
             ORDER BY m.parent_id, m.sort, m.id",
            [$keyword, $keyword, $keyword, $keyword, $status, $status]
        );
    }

    /* ── 部门 ───────────────────────────────────────────── */

    /** @return list<array<string,mixed>> */
    public static function selectDepts(?string $keyword, ?int $status): array
    {
        return Database::all(
            "SELECT d.id,
                    d.parent_id AS parentId,
                    d.dept_name AS name,
                    d.leader,
                    d.phone,
                    d.email,
                    d.sort,
                    d.status,
                    (SELECT COUNT(*) FROM sys_user u WHERE u.dept_id = d.id AND u.deleted = 0) AS memberCount
             FROM sys_dept d
             WHERE d.deleted = 0
               AND (? IS NULL OR ? = ''
                    OR d.dept_name LIKE CONCAT('%', ?, '%')
                    OR d.leader LIKE CONCAT('%', ?, '%'))
               AND (? IS NULL OR d.status = ?)
             ORDER BY d.parent_id, d.sort, d.id",
            [$keyword, $keyword, $keyword, $keyword, $status, $status]
        );
    }

    public static function selectDeptAncestors(int $id): ?string
    {
        $value = Database::scalar(
            "SELECT ancestors FROM sys_dept WHERE id = ? AND deleted = 0",
            [$id]
        );
        return $value === false || $value === null ? null : (string) $value;
    }

    public static function insertDept(array $row, string $operator): int
    {
        return Database::insert(
            "INSERT INTO sys_dept (parent_id, ancestors, dept_name, leader, phone, email, sort, status, create_by, update_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $row['parentId'], $row['ancestors'], $row['name'], $row['leader'],
                $row['phone'], $row['email'], $row['sort'], $row['status'], $operator, $operator,
            ]
        );
    }

    public static function updateDept(int $id, array $row, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_dept
             SET parent_id = COALESCE(?, parent_id),
                 ancestors = COALESCE(?, ancestors),
                 dept_name = COALESCE(?, dept_name),
                 leader = COALESCE(?, leader),
                 phone = COALESCE(?, phone),
                 email = COALESCE(?, email),
                 sort = COALESCE(?, sort),
                 status = COALESCE(?, status),
                 update_by = ?
             WHERE id = ? AND deleted = 0",
            [
                $row['parentId'], $row['ancestors'], $row['name'], $row['leader'],
                $row['phone'], $row['email'], $row['sort'], $row['status'], $operator, $id,
            ]
        );
    }

    public static function softDeleteDept(int $id, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_dept SET deleted = 1, update_by = ? WHERE id = ? AND deleted = 0",
            [$operator, $id]
        );
    }

    public static function countDeptChildren(int $deptId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_dept WHERE parent_id = ? AND deleted = 0",
            [$deptId]
        );
    }

    public static function countDeptUsers(int $deptId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_user WHERE dept_id = ? AND deleted = 0",
            [$deptId]
        );
    }

    /* ── 岗位 ───────────────────────────────────────────── */

    private static function postWhere(?string $keyword, ?int $status): array
    {
        return [
            "p.deleted = 0
              AND (? IS NULL OR ? = ''
                   OR p.post_name LIKE CONCAT('%', ?, '%')
                   OR p.post_code LIKE CONCAT('%', ?, '%'))
              AND (? IS NULL OR p.status = ?)",
            [$keyword, $keyword, $keyword, $keyword, $status, $status],
        ];
    }

    public static function countPosts(?string $keyword, ?int $status): int
    {
        [$where, $params] = self::postWhere($keyword, $status);
        return (int) Database::scalar("SELECT COUNT(*) FROM sys_post p WHERE $where", $params);
    }

    /** @return list<array<string,mixed>> */
    public static function selectPosts(?string $keyword, ?int $status, int $offset, int $pageSize): array
    {
        [$where, $params] = self::postWhere($keyword, $status);
        return Database::all(
            "SELECT p.id,
                    p.post_code AS postCode,
                    p.post_name AS postName,
                    p.sort,
                    p.status,
                    p.remark,
                    DATE_FORMAT(p.create_time, '%Y-%m-%d %H:%i') AS createTime,
                    (SELECT COUNT(*) FROM sys_user_post up JOIN sys_user cu ON cu.id = up.user_id AND cu.deleted = 0 WHERE up.post_id = p.id) AS userCount
             FROM sys_post p
             WHERE $where
             ORDER BY p.sort, p.id
             LIMIT ?, ?",
            array_merge($params, [$offset, $pageSize])
        );
    }

    
    public static function countPostCode(string $postCode, ?int $excludeId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*)
             FROM sys_post
             WHERE post_code = ?
               AND deleted = 0
               AND (? IS NULL OR id <> ?)",
            [$postCode, $excludeId, $excludeId]
        );
    }

    public static function insertPost(array $row, string $operator): int
    {
        return Database::insert(
            "INSERT INTO sys_post (post_code, post_name, sort, status, remark, create_by, update_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$row['postCode'], $row['postName'], $row['sort'], $row['status'], $row['remark'], $operator, $operator]
        );
    }

    public static function updatePost(int $id, array $row, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_post
             SET post_name = COALESCE(?, post_name),
                 sort = COALESCE(?, sort),
                 status = COALESCE(?, status),
                 remark = COALESCE(?, remark),
                 update_by = ?
             WHERE id = ? AND deleted = 0",
            [$row['postName'], $row['sort'], $row['status'], $row['remark'], $operator, $id]
        );
    }

    public static function softDeletePost(int $id, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_post SET deleted = 1, update_by = ? WHERE id = ? AND deleted = 0",
            [$operator, $id]
        );
    }

    /* ── 用户写入 ────────────────────────────────────────── */

    public static function countUsername(string $username, ?int $excludeId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*)
             FROM sys_user
             WHERE username = ?
               AND deleted = 0
               AND (? IS NULL OR id <> ?)",
            [$username, $excludeId, $excludeId]
        );
    }

    public static function insertUser(array $row, string $operator): int
    {
        return Database::insert(
            "INSERT INTO sys_user (username, password, nickname, phone, email, dept_id, status, create_by, update_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $row['username'], $row['password'], $row['nickname'], $row['phone'],
                $row['email'], $row['deptId'], $row['status'], $operator, $operator,
            ]
        );
    }

    public static function updateUser(int $id, array $row, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_user
             SET nickname = COALESCE(?, nickname),
                 phone = COALESCE(?, phone),
                 email = COALESCE(?, email),
                 dept_id = COALESCE(?, dept_id),
                 status = COALESCE(?, status),
                 update_by = ?
             WHERE id = ? AND deleted = 0",
            [
                $row['nickname'], $row['phone'], $row['email'], $row['deptId'],
                $row['status'], $operator, $id,
            ]
        );
    }

    public static function updateUserStatus(int $id, int $status, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_user SET status = ?, update_by = ? WHERE id = ? AND deleted = 0",
            [$status, $operator, $id]
        );
    }

    public static function resetUserPassword(int $id, string $password, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_user SET password = ?, update_by = ? WHERE id = ? AND deleted = 0",
            [$password, $operator, $id]
        );
    }

    public static function softDeleteUser(int $id, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_user SET deleted = 1, update_by = ? WHERE id = ? AND deleted = 0",
            [$operator, $id]
        );
    }

    public static function deleteUserRoles(int $userId): void
    {
        Database::execute("DELETE FROM sys_user_role WHERE user_id = ?", [$userId]);
    }

    public static function insertUserRole(int $userId, int $roleId): void
    {
        Database::execute(
            "INSERT IGNORE INTO sys_user_role (user_id, role_id) VALUES (?, ?)",
            [$userId, $roleId]
        );
    }

    public static function deleteUserPosts(int $userId): void
    {
        Database::execute("DELETE FROM sys_user_post WHERE user_id = ?", [$userId]);
    }

    public static function insertUserPost(int $userId, int $postId): void
    {
        Database::execute(
            "INSERT IGNORE INTO sys_user_post (user_id, post_id) VALUES (?, ?)",
            [$userId, $postId]
        );
    }

    /* ── 角色写入 ────────────────────────────────────────── */

    public static function countRoleCode(string $code, ?int $excludeId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*)
             FROM sys_role
             WHERE role_code = ?
               AND deleted = 0
               AND (? IS NULL OR id <> ?)",
            [$code, $excludeId, $excludeId]
        );
    }

    public static function insertRole(array $row, string $operator): int
    {
        return Database::insert(
            "INSERT INTO sys_role (role_name, role_code, data_scope, sort, status, builtin, remark, create_by, update_by)
             VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?)",
            [$row['name'], $row['code'], $row['dataScope'], $row['sort'], $row['status'], $row['remark'], $operator, $operator]
        );
    }

    public static function updateRole(int $id, array $row, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_role
             SET role_name = COALESCE(?, role_name),
                 data_scope = COALESCE(?, data_scope),
                 sort = COALESCE(?, sort),
                 status = COALESCE(?, status),
                 remark = COALESCE(?, remark),
                 update_by = ?
             WHERE id = ? AND deleted = 0 AND builtin = 0",
            [$row['name'], $row['dataScope'], $row['sort'], $row['status'], $row['remark'], $operator, $id]
        );
    }

    public static function softDeleteRole(int $id, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_role SET deleted = 1, update_by = ? WHERE id = ? AND deleted = 0 AND builtin = 0",
            [$operator, $id]
        );
    }

    public static function deleteRoleMenus(int $roleId): void
    {
        Database::execute("DELETE FROM sys_role_menu WHERE role_id = ?", [$roleId]);
    }

    public static function insertRoleMenu(int $roleId, int $menuId): void
    {
        Database::execute(
            "INSERT IGNORE INTO sys_role_menu (role_id, menu_id) VALUES (?, ?)",
            [$roleId, $menuId]
        );
    }

    /** @return list<int> */
    public static function selectRoleMenuIds(int $roleId): array
    {
        $rows = Database::all(
            "SELECT menu_id FROM sys_role_menu WHERE role_id = ? ORDER BY menu_id",
            [$roleId]
        );
        return array_map(static fn (array $row): int => (int) $row['menu_id'], $rows);
    }

    /** @return list<array<string,mixed>> 权限关系图的用户节点：遵守数据权限，只取图谱展示所需字段 */
    public static function selectGraphUsers(DataScopeCriteria $scope): array
    {
        [$scopeSql, $scopeParams] = self::scopeClause($scope);
        return Database::all(
            "SELECT u.id, u.username, u.nickname, d.dept_name AS deptName, u.status
             FROM sys_user u
             LEFT JOIN sys_dept d ON d.id = u.dept_id
             WHERE u.deleted = 0
               AND $scopeSql
             ORDER BY u.id
             LIMIT 2000",
            $scopeParams
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectUserRoleLinks(): array
    {
        return Database::all(
            "SELECT ur.user_id AS userId, ur.role_id AS roleId
             FROM sys_user_role ur
             JOIN sys_user u ON u.id = ur.user_id AND u.deleted = 0
             JOIN sys_role r ON r.id = ur.role_id AND r.deleted = 0
             ORDER BY ur.user_id, ur.role_id"
        );
    }

    public static function countRoleUsers(int $roleId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*)
             FROM sys_user_role ur
             JOIN sys_user u ON u.id = ur.user_id
             WHERE ur.role_id = ? AND u.deleted = 0",
            [$roleId]
        );
    }

    /* ── 菜单写入 ────────────────────────────────────────── */

    public static function nextMenuId(): int
    {
        return (int) Database::scalar("SELECT COALESCE(MAX(id), 0) + 1 FROM sys_menu") ?: 1;
    }

    public static function insertMenu(array $row, string $operator): void
    {
        Database::execute(
            "INSERT INTO sys_menu (id, parent_id, menu_name, menu_type, icon, path, component, permission, sort, visible, status, create_by, update_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $row['id'], $row['parentId'], $row['name'], $row['type'], $row['icon'],
                $row['path'], $row['component'], $row['permission'], $row['sort'],
                $row['visible'], $row['status'], $operator, $operator,
            ]
        );
    }

    public static function updateMenu(int $id, array $row, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_menu
             SET parent_id = COALESCE(?, parent_id),
                 menu_name = COALESCE(?, menu_name),
                 menu_type = COALESCE(?, menu_type),
                 icon = COALESCE(?, icon),
                 path = COALESCE(?, path),
                 component = COALESCE(?, component),
                 permission = COALESCE(?, permission),
                 sort = COALESCE(?, sort),
                 visible = COALESCE(?, visible),
                 status = COALESCE(?, status),
                 update_by = ?
             WHERE id = ?",
            [
                $row['parentId'], $row['name'], $row['type'], $row['icon'], $row['path'],
                $row['component'], $row['permission'], $row['sort'], $row['visible'],
                $row['status'], $operator, $id,
            ]
        );
    }

    public static function deleteMenu(int $id): int
    {
        return Database::execute("DELETE FROM sys_menu WHERE id = ?", [$id]);
    }

    public static function countMenuChildren(int $menuId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_menu WHERE parent_id = ?",
            [$menuId]
        );
    }

    public static function countMenuRoles(int $menuId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_role_menu WHERE menu_id = ?",
            [$menuId]
        );
    }

    /* ── 字典 ───────────────────────────────────────────── */

    private static function dictTypeWhere(?string $keyword, ?int $status): array
    {
        return [
            "(? IS NULL OR ? = ''
                   OR t.dict_name LIKE CONCAT('%', ?, '%')
                   OR t.dict_type LIKE CONCAT('%', ?, '%'))
              AND (? IS NULL OR t.status = ?)",
            [$keyword, $keyword, $keyword, $keyword, $status, $status],
        ];
    }

    public static function countDictTypes(?string $keyword, ?int $status): int
    {
        [$where, $params] = self::dictTypeWhere($keyword, $status);
        return (int) Database::scalar("SELECT COUNT(*) FROM sys_dict_type t WHERE $where", $params);
    }

    /** @return list<array<string,mixed>> */
    public static function selectDictTypes(?string $keyword, ?int $status, int $offset, int $pageSize): array
    {
        [$where, $params] = self::dictTypeWhere($keyword, $status);
        return Database::all(
            "SELECT t.id,
                    t.dict_name AS dictName,
                    t.dict_type AS dictType,
                    t.status,
                    DATE_FORMAT(t.create_time, '%Y-%m-%d %H:%i') AS createTime,
                    t.remark
             FROM sys_dict_type t
             WHERE $where
             ORDER BY t.id
             LIMIT ?, ?",
            array_merge($params, [$offset, $pageSize])
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectDictData(string $dictType): array
    {
        return Database::all(
            "SELECT d.id,
                    d.dict_label AS label,
                    d.dict_value AS value,
                    d.sort,
                    d.status,
                    d.tone,
                    d.is_default AS def
             FROM sys_dict_data d
             WHERE d.dict_type = ?
             ORDER BY d.sort, d.id",
            [$dictType]
        );
    }

    /** 业务下拉只提供启用字典的启用选项，管理接口仍按字典管理权限保护 */
    public static function selectDictOptions(string $dictType): array
    {
        return Database::all(
            "SELECT d.dict_label AS label, d.dict_value AS value, d.tone,
                    d.is_default AS def, d.status
             FROM sys_dict_data d
             JOIN sys_dict_type t ON t.dict_type = d.dict_type AND t.status = 1
             WHERE d.dict_type = ? AND d.status = 1
             ORDER BY d.sort, d.id",
            [$dictType]
        );
    }

    public static function countDictType(string $dictType, ?int $excludeId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*)
             FROM sys_dict_type
             WHERE dict_type = ?
               AND (? IS NULL OR id <> ?)",
            [$dictType, $excludeId, $excludeId]
        );
    }

    public static function insertDictType(array $row, string $operator): int
    {
        return Database::insert(
            "INSERT INTO sys_dict_type (dict_name, dict_type, status, remark, create_by, update_by)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$row['dictName'], $row['dictType'], $row['status'], $row['remark'], $operator, $operator]
        );
    }

    public static function updateDictType(int $id, array $row, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_dict_type
             SET dict_name = COALESCE(?, dict_name),
                 status = COALESCE(?, status),
                 remark = COALESCE(?, remark),
                 update_by = ?
             WHERE id = ?",
            [$row['dictName'], $row['status'], $row['remark'], $operator, $id]
        );
    }

    public static function deleteDictType(int $id): void
    {
        Database::execute("DELETE FROM sys_dict_type WHERE id = ?", [$id]);
    }

    public static function countDictDataByType(string $dictType): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_dict_data WHERE dict_type = ?",
            [$dictType]
        );
    }

    public static function selectDictTypeCode(int $id): ?string
    {
        $value = Database::scalar("SELECT dict_type FROM sys_dict_type WHERE id = ?", [$id]);
        return $value === false || $value === null ? null : (string) $value;
    }

    public static function insertDictData(array $row, string $operator): int
    {
        return Database::insert(
            "INSERT INTO sys_dict_data (dict_type, dict_label, dict_value, sort, status, tone, is_default, remark, create_by, update_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $row['dictType'], $row['label'], $row['value'], $row['sort'],
                $row['status'], $row['tone'], $row['def'], $row['remark'], $operator, $operator,
            ]
        );
    }

    public static function updateDictData(int $id, array $row, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_dict_data
             SET dict_type = COALESCE(?, dict_type),
                 dict_label = COALESCE(?, dict_label),
                 dict_value = COALESCE(?, dict_value),
                 sort = COALESCE(?, sort),
                 status = COALESCE(?, status),
                 tone = COALESCE(?, tone),
                 is_default = COALESCE(?, is_default),
                 remark = COALESCE(?, remark),
                 update_by = ?
             WHERE id = ?",
            [
                $row['dictType'], $row['label'], $row['value'], $row['sort'],
                $row['status'], $row['tone'], $row['def'], $row['remark'], $operator, $id,
            ]
        );
    }

    public static function deleteDictData(int $id): int
    {
        return Database::execute("DELETE FROM sys_dict_data WHERE id = ?", [$id]);
    }

    /* ── 参数配置 ────────────────────────────────────────── */

    public static function countConfigs(?string $keyword): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*)
             FROM sys_config c
             WHERE (? IS NULL OR ? = ''
                    OR c.config_name LIKE CONCAT('%', ?, '%')
                    OR c.config_key LIKE CONCAT('%', ?, '%'))",
            [$keyword, $keyword, $keyword, $keyword]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectConfigs(?string $keyword, int $offset, int $pageSize): array
    {
        return Database::all(
            "SELECT c.id,
                    c.config_name AS name,
                    c.config_key AS `key`,
                    c.config_value AS value,
                    c.config_type AS type,
                    c.builtin,
                    DATE_FORMAT(c.create_time, '%Y-%m-%d %H:%i') AS createTime,
                    c.remark
             FROM sys_config c
             WHERE (? IS NULL OR ? = ''
                    OR c.config_name LIKE CONCAT('%', ?, '%')
                    OR c.config_key LIKE CONCAT('%', ?, '%'))
             ORDER BY c.id
             LIMIT ?, ?",
            [$keyword, $keyword, $keyword, $keyword, $offset, $pageSize]
        );
    }

    
    public static function countConfigKey(string $key, ?int $excludeId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*)
             FROM sys_config
             WHERE config_key = ?
               AND (? IS NULL OR id <> ?)",
            [$key, $excludeId, $excludeId]
        );
    }

    public static function insertConfig(array $row, string $operator): int
    {
        return Database::insert(
            "INSERT INTO sys_config (config_name, config_key, config_value, config_type, builtin, remark, create_by, update_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$row['name'], $row['key'], $row['value'], $row['type'], $row['builtin'], $row['remark'], $operator, $operator]
        );
    }

    public static function updateConfig(int $id, array $row, string $operator): int
    {
        return Database::execute(
            "UPDATE sys_config
             SET config_name = COALESCE(?, config_name),
                 config_value = COALESCE(?, config_value),
                 config_type = COALESCE(?, config_type),
                 builtin = COALESCE(?, builtin),
                 remark = COALESCE(?, remark),
                 update_by = ?
             WHERE id = ? AND builtin = 0",
            [$row['name'], $row['value'], $row['type'], $row['builtin'], $row['remark'], $operator, $id]
        );
    }

    public static function deleteConfig(int $id): int
    {
        return Database::execute("DELETE FROM sys_config WHERE id = ? AND builtin = 0", [$id]);
    }

    /* ── 日志查询 ────────────────────────────────────────── */

    private static function operLogWhere(?string $keyword, ?int $result): array
    {
        return [
            "(? IS NULL OR ? = ''
                   OR l.module LIKE CONCAT('%', ?, '%')
                   OR l.action LIKE CONCAT('%', ?, '%')
                   OR l.oper_name LIKE CONCAT('%', ?, '%')
                   OR l.request_url LIKE CONCAT('%', ?, '%')
                   OR l.request_params LIKE CONCAT('%', ?, '%'))
              AND (? IS NULL OR l.result = ?)",
            [$keyword, $keyword, $keyword, $keyword, $keyword, $keyword, $keyword, $result, $result],
        ];
    }

    public static function countOperLogs(?string $keyword, ?int $result): int
    {
        [$where, $params] = self::operLogWhere($keyword, $result);
        return (int) Database::scalar("SELECT COUNT(*) FROM sys_oper_log l WHERE $where", $params);
    }

    /** @return list<array<string,mixed>> */
    public static function selectOperLogs(?string $keyword, ?int $result, int $offset, int $pageSize): array
    {
        [$where, $params] = self::operLogWhere($keyword, $result);
        return Database::all(
            "SELECT l.id,
                    l.module,
                    l.action,
                    l.request_method AS method,
                    l.request_url AS url,
                    l.request_params AS params,
                    l.oper_name AS operName,
                    l.dept_name AS dept,
                    l.ip,
                    l.location,
                    l.cost,
                    l.result,
                    l.error_msg AS errorMsg,
                    DATE_FORMAT(l.oper_time, '%Y-%m-%d %H:%i:%s') AS operTime
             FROM sys_oper_log l
             WHERE $where
             ORDER BY l.oper_time DESC, l.id DESC
             LIMIT ?, ?",
            array_merge($params, [$offset, $pageSize])
        );
    }

    
    public static function deleteOperLog(int $id): int
    {
        return Database::execute("DELETE FROM sys_oper_log WHERE id = ?", [$id]);
    }

    public static function clearOperLogs(): void
    {
        Database::execute("DELETE FROM sys_oper_log");
    }

    private static function loginLogWhere(?string $keyword, ?int $status): array
    {
        return [
            "(? IS NULL OR ? = ''
                   OR l.username LIKE CONCAT('%', ?, '%')
                   OR l.ip LIKE CONCAT('%', ?, '%')
                   OR l.msg LIKE CONCAT('%', ?, '%'))
              AND (? IS NULL OR l.status = ?)",
            [$keyword, $keyword, $keyword, $keyword, $keyword, $status, $status],
        ];
    }

    public static function countLoginLogs(?string $keyword, ?int $status): int
    {
        [$where, $params] = self::loginLogWhere($keyword, $status);
        return (int) Database::scalar("SELECT COUNT(*) FROM sys_login_log l WHERE $where", $params);
    }

    /** @return list<array<string,mixed>> */
    public static function selectLoginLogs(?string $keyword, ?int $status, int $offset, int $pageSize): array
    {
        [$where, $params] = self::loginLogWhere($keyword, $status);
        return Database::all(
            "SELECT l.id,
                    l.username,
                    l.ip,
                    l.location,
                    l.browser,
                    l.os,
                    l.status,
                    l.msg,
                    DATE_FORMAT(l.login_time, '%Y-%m-%d %H:%i:%s') AS loginTime
             FROM sys_login_log l
             WHERE $where
             ORDER BY l.login_time DESC, l.id DESC
             LIMIT ?, ?",
            array_merge($params, [$offset, $pageSize])
        );
    }

    
    public static function deleteLoginLog(int $id): int
    {
        return Database::execute("DELETE FROM sys_login_log WHERE id = ?", [$id]);
    }

    public static function clearLoginLogs(): void
    {
        Database::execute("DELETE FROM sys_login_log");
    }
}
