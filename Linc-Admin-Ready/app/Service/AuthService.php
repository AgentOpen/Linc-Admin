<?php

declare(strict_types=1);

namespace Linc\Service;

use Linc\Core\AuthContext;
use Linc\Core\BusinessException;
use Linc\Core\Config;
use Linc\Core\JwtCodec;
use Linc\Core\TokenStore;
use Linc\Repository\AuthRepository;

/**
 * 认证业务，
 * 登录校验顺序（验证码 → 账号密码 → 停用）、登录日志、routes 树构建、改密码校验。
 */
final class AuthService
{
    /** @return array{token: string, expiresIn: int} */
    public static function login(array $request): array
    {
        $username = (string) ($request['username'] ?? '');
        $password = (string) ($request['password'] ?? '');
        $user = AuthRepository::selectUserByUsername($username);
        if ($user === null || !password_verify($password, (string) $user['password'])) {
            AuthRepository::insertLoginLog($username, 0, '账号或密码错误');
            throw new BusinessException('账号或密码错误');
        }
        if ((int) ($user['status'] ?? 0) !== 1) {
            AuthRepository::insertLoginLog($username, 0, '账号已停用');
            throw new BusinessException('账号已停用');
        }
        $expires = Config::jwtExpiresSeconds();
        $token = JwtCodec::encode(Config::jwtSecret(), (int) $user['id'], (string) $user['username'], $expires);
        TokenStore::store($token, (string) $user['username'], $expires);
        AuthRepository::insertLoginLog((string) $user['username'], 1, '登录成功');
        AuthRepository::updateLastLoginTime((int) $user['id']);
        return ['token' => $token, 'expiresIn' => $expires];
    }

    public static function logout(): void
    {
        $token = AuthContext::token();
        $username = AuthContext::username();
        if ($token !== null) {
            TokenStore::revoke($token);
        }
        if ($username !== null) {
            AuthRepository::insertLoginLog($username, 1, '退出登录');
        }
        AuthContext::clear();
    }

    /** @return array<string,mixed> */
    public static function currentUser(): array
    {
        $user = AuthContext::currentUserRow();
        $userId = (int) $user['id'];
        $roleCodes = AuthRepository::selectRoleCodes($userId);
        return [
            'userId' => $userId,
            'username' => (string) $user['username'],
            'nickname' => (string) $user['nickname'],
            'deptId' => $user['dept_id'] !== null ? (int) $user['dept_id'] : null,
            'deptName' => $user['dept_name'],
            'roles' => $roleCodes,
            'permissions' => in_array('super_admin', $roleCodes, true)
                ? AuthRepository::selectAllPermissions()
                : AuthRepository::selectPermissions($userId),
        ];
    }

    /** @return array{routes: list<array<string,mixed>>} */
    public static function routes(): array
    {
        $user = AuthContext::currentUserRow();
        $userId = (int) $user['id'];
        // 超级管理员不受角色菜单分配限制，新增菜单无需再授权即可见
        $menus = in_array('super_admin', AuthRepository::selectRoleCodes($userId), true)
            ? AuthRepository::selectAllRouteMenus()
            : AuthRepository::selectRouteMenus($userId);
        return ['routes' => self::buildRoutes($menus)];
    }

    public static function changePassword(array $request): void
    {
        $user = AuthContext::currentUserRow();
        $oldPassword = (string) ($request['oldPassword'] ?? '');
        $newPassword = (string) ($request['newPassword'] ?? '');
        if ($oldPassword === '' || !password_verify($oldPassword, (string) $user['password'])) {
            throw new BusinessException('旧密码不正确');
        }
        if (password_verify($newPassword, (string) $user['password'])) {
            throw new BusinessException('新密码不能与旧密码一致');
        }
        $updated = AuthRepository::updatePassword(
            (int) $user['id'],
            (string) $user['username'],
            password_hash($newPassword, PASSWORD_BCRYPT)
        );
        if ($updated === 0) {
            throw new BusinessException('密码修改失败');
        }
    }

    /** @param list<array<string,mixed>> $menus */
    private static function buildRoutes(array $menus): array
    {
        // parent_id, sort, id 排序
        usort($menus, static function (array $a, array $b): int {
            return [(int) $a['parent_id'], (int) $a['sort'], (int) $a['id']]
                <=> [(int) $b['parent_id'], (int) $b['sort'], (int) $b['id']];
        });
        $byId = [];
        foreach ($menus as $menu) {
            $byId[(int) $menu['id']] = self::toRoute($menu);
        }
        $roots = [];
        foreach (array_keys($byId) as $id) {
            $parentId = (int) $byId[$id]['parentId'];
            if ($parentId === $id || !isset($byId[$parentId])) {
                $roots[] = &$byId[$id];
            } else {
                $byId[$parentId]['children'][] = &$byId[$id];
            }
        }
        unset($id);
        return $roots;
    }

    /** @return array<string,mixed> */
    private static function toRoute(array $menu): array
    {
        return [
            'id' => (int) $menu['id'],
            'parentId' => (int) $menu['parent_id'],
            'name' => (string) $menu['menu_name'],
            'path' => $menu['path'],
            'component' => $menu['component'],
            'icon' => $menu['icon'],
            'permission' => $menu['permission'],
            'children' => [],
        ];
    }
}
