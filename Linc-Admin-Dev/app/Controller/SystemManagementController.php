<?php

declare(strict_types=1);

namespace Linc\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Linc\Service\SystemManagementService;

/**
 * 系统管理接口。
 * 权限校验与操作日志由路由表挂接（RequirePermission / OperLog 中间件）。
 */
final class SystemManagementController
{
    /* ── 用户 ───────────────────────────────────────────── */

    public static function users(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::ok($response, SystemManagementService::users(
            AuthController::pageQuery($request),
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status'),
            self::intParam($q, 'deptId'),
            self::intParam($q, 'postId')
        ));
    }

    public static function userOptions(Request $request, Response $response): Response
    {
        return AuthController::ok($response, SystemManagementService::userOptions());
    }

    public static function exportUsers(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::csv($response, 'users.csv', SystemManagementService::exportUsers(
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status'),
            self::intParam($q, 'deptId'),
            self::intParam($q, 'postId')
        ));
    }

    public static function createUser(Request $request, Response $response): Response
    {
        return AuthController::ok($response, SystemManagementService::createUser(json_body((string) $request->getBody())));
    }

    public static function updateUser(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::updateUser(AuthController::pathId($args), json_body((string) $request->getBody()));
        return AuthController::ok($response);
    }

    public static function updateUserStatus(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::updateUserStatus(AuthController::pathId($args), json_body((string) $request->getBody()));
        return AuthController::ok($response);
    }

    public static function resetUserPassword(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::resetUserPassword(AuthController::pathId($args), json_body((string) $request->getBody()));
        return AuthController::ok($response);
    }

    public static function deleteUser(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::deleteUser(AuthController::pathId($args));
        return AuthController::ok($response);
    }

    /* ── 角色 ───────────────────────────────────────────── */

    public static function roles(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::ok($response, SystemManagementService::roles(
            AuthController::pageQuery($request),
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status')
        ));
    }

    public static function exportRoles(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::csv($response, 'roles.csv', SystemManagementService::exportRoles(
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status')
        ));
    }

    public static function permissionGraph(Request $request, Response $response): Response
    {
        return AuthController::ok($response, SystemManagementService::permissionGraph());
    }

    public static function roleMenus(Request $request, Response $response, array $args): Response
    {
        return AuthController::ok($response, SystemManagementService::roleMenus(AuthController::pathId($args)));
    }

    public static function createRole(Request $request, Response $response): Response
    {
        return AuthController::ok($response, SystemManagementService::createRole(json_body((string) $request->getBody())));
    }

    public static function updateRole(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::updateRole(AuthController::pathId($args), json_body((string) $request->getBody()));
        return AuthController::ok($response);
    }

    public static function deleteRole(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::deleteRole(AuthController::pathId($args));
        return AuthController::ok($response);
    }

    /* ── 菜单 ───────────────────────────────────────────── */

    public static function menus(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::ok($response, SystemManagementService::menus(
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status')
        ));
    }

    public static function exportMenus(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::csv($response, 'menus.csv', SystemManagementService::exportMenus(
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status')
        ));
    }

    public static function createMenu(Request $request, Response $response): Response
    {
        return AuthController::ok($response, SystemManagementService::createMenu(json_body((string) $request->getBody())));
    }

    public static function updateMenu(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::updateMenu(AuthController::pathId($args), json_body((string) $request->getBody()));
        return AuthController::ok($response);
    }

    public static function deleteMenu(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::deleteMenu(AuthController::pathId($args));
        return AuthController::ok($response);
    }

    /* ── 部门 ───────────────────────────────────────────── */

    public static function depts(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::ok($response, SystemManagementService::depts(
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status')
        ));
    }

    public static function exportDepts(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::csv($response, 'depts.csv', SystemManagementService::exportDepts(
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status')
        ));
    }

    public static function createDept(Request $request, Response $response): Response
    {
        return AuthController::ok($response, SystemManagementService::createDept(json_body((string) $request->getBody())));
    }

    public static function updateDept(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::updateDept(AuthController::pathId($args), json_body((string) $request->getBody()));
        return AuthController::ok($response);
    }

    public static function deleteDept(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::deleteDept(AuthController::pathId($args));
        return AuthController::ok($response);
    }

    /* ── 岗位 ───────────────────────────────────────────── */

    public static function posts(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::ok($response, SystemManagementService::posts(
            AuthController::pageQuery($request),
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status')
        ));
    }

    public static function exportPosts(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::csv($response, 'posts.csv', SystemManagementService::exportPosts(
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status')
        ));
    }

    public static function createPost(Request $request, Response $response): Response
    {
        return AuthController::ok($response, SystemManagementService::createPost(json_body((string) $request->getBody())));
    }

    public static function updatePost(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::updatePost(AuthController::pathId($args), json_body((string) $request->getBody()));
        return AuthController::ok($response);
    }

    public static function deletePost(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::deletePost(AuthController::pathId($args));
        return AuthController::ok($response);
    }

    /* ── 字典 ───────────────────────────────────────────── */

    public static function dictTypes(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::ok($response, SystemManagementService::dictTypes(
            AuthController::pageQuery($request),
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status')
        ));
    }

    public static function exportDictTypes(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::csv($response, 'dict-types.csv', SystemManagementService::exportDictTypes(
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status')
        ));
    }

    public static function dictOptions(Request $request, Response $response, array $args): Response
    {
        return AuthController::ok($response, SystemManagementService::dictOptions(
            AuthController::pathString($args, 'dictType')
        ));
    }

    public static function dictData(Request $request, Response $response, array $args): Response
    {
        return AuthController::ok($response, SystemManagementService::dictData(
            AuthController::pathString($args, 'dictType')
        ));
    }

    public static function createDictType(Request $request, Response $response): Response
    {
        return AuthController::ok($response, SystemManagementService::createDictType(json_body((string) $request->getBody())));
    }

    public static function updateDictType(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::updateDictType(AuthController::pathId($args), json_body((string) $request->getBody()));
        return AuthController::ok($response);
    }

    public static function deleteDictType(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::deleteDictType(AuthController::pathId($args));
        return AuthController::ok($response);
    }

    public static function createDictData(Request $request, Response $response): Response
    {
        return AuthController::ok($response, SystemManagementService::createDictData(json_body((string) $request->getBody())));
    }

    public static function updateDictData(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::updateDictData(AuthController::pathId($args), json_body((string) $request->getBody()));
        return AuthController::ok($response);
    }

    public static function deleteDictData(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::deleteDictData(AuthController::pathId($args));
        return AuthController::ok($response);
    }

    /* ── 参数配置 ────────────────────────────────────────── */

    public static function configs(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::ok($response, SystemManagementService::configs(
            AuthController::pageQuery($request),
            self::strParam($q, 'keyword')
        ));
    }

    public static function exportConfigs(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::csv($response, 'configs.csv', SystemManagementService::exportConfigs(
            self::strParam($q, 'keyword')
        ));
    }

    public static function createConfig(Request $request, Response $response): Response
    {
        return AuthController::ok($response, SystemManagementService::createConfig(json_body((string) $request->getBody())));
    }

    public static function updateConfig(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::updateConfig(AuthController::pathId($args), json_body((string) $request->getBody()));
        return AuthController::ok($response);
    }

    public static function deleteConfig(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::deleteConfig(AuthController::pathId($args));
        return AuthController::ok($response);
    }

    /* ── 查询参数工具 ─────────────────────────────────────── */

    private static function strParam(array $q, string $name): ?string
    {
        $value = $q[$name] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function intParam(array $q, string $name): ?int
    {
        $value = $q[$name] ?? null;
        if ($value === null || $value === '') {
            return null;
        }
        return is_numeric($value) ? (int) $value : null;
    }
}
