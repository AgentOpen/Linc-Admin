<?php

declare(strict_types=1);

namespace Linc\Service;

use Linc\Core\AuthContext;
use Linc\Core\BusinessException;
use Linc\Core\Csv;
use Linc\Core\DataScopeCriteria;
use Linc\Core\PageQuery;
use Linc\Core\PageResult;
use Linc\Repository\SystemManagementRepository;

/**
 * 系统管理业务，
 * 校验顺序与文案、COALESCE 部分更新、树构建、布尔行转换、CSV 表头与行序、数据范围接入点。
 */
final class SystemManagementService
{
    /* ── 引用选项与字典选项 ─────────────────────────────────── */

    /** 用户页所需的引用选项，避免为只读用户申请角色、岗位和参数管理权限。 */
    public static function userOptions(): array
    {
        $username = AuthContext::username();
        $canEdit = PermissionService::hasPermission($username, 'system:user:add')
            || PermissionService::hasPermission($username, 'system:user:edit');
        $canSetPassword = PermissionService::hasPermission($username, 'system:user:add')
            || PermissionService::hasPermission($username, 'system:user:resetPwd');
        $depts = array_map(
            static fn (array $row): array => [
                'id' => $row['id'],
                'parentId' => $row['parentId'],
                'name' => $row['name'],
                'status' => $row['status'],
            ],
            SystemManagementRepository::selectDepts(null, null)
        );
        $roles = $canEdit
            ? array_map(
                static fn (array $row): array => ['id' => $row['id'], 'name' => $row['name']],
                SystemManagementRepository::selectRoles(null, 1, 0, 5000)
            )
            : [];
        $posts = $canEdit
            ? array_map(
                static fn (array $row): array => ['id' => $row['id'], 'name' => $row['postName']],
                SystemManagementRepository::selectPosts(null, 1, 0, 5000)
            )
            : [];
        $initialPassword = '';
        if ($canSetPassword) {
            foreach (SystemManagementRepository::selectConfigs('sys.user.initPassword', 0, 10) as $row) {
                if (($row['key'] ?? '') === 'sys.user.initPassword') {
                    $initialPassword = (string) ($row['value'] ?? '');
                    break;
                }
            }
        }
        return [
            'depts' => self::treeOf($depts),
            'roles' => $roles,
            'posts' => $posts,
            'initPassword' => $initialPassword,
        ];
    }

    public static function dictOptions(string $dictType): array
    {
        return SystemManagementRepository::selectDictOptions($dictType);
    }

    /* ── 用户 ───────────────────────────────────────────── */

    public static function users(PageQuery $query, ?string $keyword, ?int $status, ?int $deptId, ?int $postId): array
    {
        $scope = DataScopeService::current();
        [$pageNum, $pageSize, $offset] = self::pageOf($query);
        $total = SystemManagementRepository::countUsers($keyword, $status, $deptId, $postId, $scope);
        $rows = SystemManagementRepository::selectUsers($keyword, $status, $deptId, $postId, $scope, $offset, $pageSize);
        return PageResult::of($rows, $total, $pageNum, $pageSize);
    }

    public static function exportUsers(?string $keyword, ?int $status, ?int $deptId, ?int $postId): string
    {
        $rows = SystemManagementRepository::selectUsers(
            $keyword, $status, $deptId, $postId, DataScopeService::current(), 0, 5000
        );
        $data = array_map(static fn (array $row): array => [
            $row['username'] ?? null,
            $row['nickname'] ?? null,
            $row['phone'] ?? null,
            $row['email'] ?? null,
            $row['deptName'] ?? null,
            $row['postName'] ?? null,
            $row['roleName'] ?? null,
            self::userStatusText($row['status'] ?? null),
            $row['createTime'] ?? null,
            $row['lastLogin'] ?? null,
        ], $rows);
        return Csv::build(
            ['登录账号', '用户昵称', '手机号', '邮箱', '部门', '岗位', '角色', '状态', '创建时间', '最近登录'],
            $data
        );
    }

    public static function createUser(array $request): array
    {
        $username = required(isset($request['username']) && is_string($request['username']) ? $request['username'] : null, '登录账号不能为空');
        $nickname = required(isset($request['nickname']) && is_string($request['nickname']) ? $request['nickname'] : null, '用户昵称不能为空');
        if (SystemManagementRepository::countUsername($username, null) > 0) {
            throw new BusinessException('登录账号已存在');
        }
        $password = default_string(
            isset($request['password']) && is_string($request['password']) ? $request['password'] : null,
            'admin123'
        );
        $userId = SystemManagementRepository::insertUser([
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'nickname' => $nickname,
            'phone' => self::stringOrNull($request['phone'] ?? null),
            'email' => self::stringOrNull($request['email'] ?? null),
            'deptId' => self::intOrNull($request['deptId'] ?? null),
            'status' => default_int(self::intOrNull($request['status'] ?? null), 1),
        ], AuthContext::operator());
        self::replaceUserRoles($userId, self::longListOrNull($request['roleIds'] ?? null));
        self::replaceUserPosts($userId, self::longListOrNull($request['postIds'] ?? null));
        return ['id' => $userId];
    }

    public static function updateUser(int $id, array $request): void
    {
        $updated = SystemManagementRepository::updateUser($id, [
            'nickname' => trim_to_null(self::stringOrNull($request['nickname'] ?? null)),
            'phone' => self::stringOrNull($request['phone'] ?? null),
            'email' => self::stringOrNull($request['email'] ?? null),
            'deptId' => self::intOrNull($request['deptId'] ?? null),
            'status' => self::intOrNull($request['status'] ?? null),
        ], AuthContext::operator());
        if ($updated === 0) {
            throw new BusinessException('用户不存在或已删除');
        }
        if (array_key_exists('roleIds', $request)) {
            self::replaceUserRoles($id, self::longListOrNull($request['roleIds']));
        }
        if (array_key_exists('postIds', $request)) {
            self::replaceUserPosts($id, self::longListOrNull($request['postIds']));
        }
    }

    public static function updateUserStatus(int $id, array $request): void
    {
        $status = self::intOrNull($request['status'] ?? null);
        if ($status === null || ($status !== 0 && $status !== 1)) {
            throw new BusinessException('用户状态只能为正常或停用');
        }
        if ($id === 1 && $status === 0) {
            throw new BusinessException('内置管理员不允许停用');
        }
        if (SystemManagementRepository::updateUserStatus($id, $status, AuthContext::operator()) === 0) {
            throw new BusinessException('用户不存在或已删除');
        }
    }

    public static function resetUserPassword(int $id, array $request): void
    {
        $password = default_string(
            isset($request['password']) && is_string($request['password']) ? $request['password'] : null,
            'Yy@123456'
        );
        if (strlen($password) < 6 || strlen($password) > 64) {
            throw new BusinessException('密码长度必须在6到64位之间');
        }
        if (SystemManagementRepository::resetUserPassword($id, password_hash($password, PASSWORD_BCRYPT), AuthContext::operator()) === 0) {
            throw new BusinessException('用户不存在或已删除');
        }
    }

    public static function deleteUser(int $id): void
    {
        if ($id === 1) {
            throw new BusinessException('内置管理员不允许删除');
        }
        if (SystemManagementRepository::softDeleteUser($id, AuthContext::operator()) === 0) {
            throw new BusinessException('用户不存在或已删除');
        }
    }

    /* ── 角色 ───────────────────────────────────────────── */

    public static function roles(PageQuery $query, ?string $keyword, ?int $status): array
    {
        [$pageNum, $pageSize, $offset] = self::pageOf($query);
        $total = SystemManagementRepository::countRoles($keyword, $status);
        $rows = self::roleRows(SystemManagementRepository::selectRoles($keyword, $status, $offset, $pageSize));
        return PageResult::of($rows, $total, $pageNum, $pageSize);
    }

    /** 权限关系图：角色、菜单与「用户-角色」关联一次返回；用户节点按数据权限与 user:list 权限过滤。 */
    public static function permissionGraph(): array
    {
        $scope = DataScopeService::current();
        $roles = self::roleRows(SystemManagementRepository::selectRoles(null, null, 0, 5000));
        $roleNodes = array_map(static fn (array $row): array => [
            'id' => $row['id'],
            'name' => $row['name'],
            'code' => $row['code'],
            'dataScope' => $row['dataScope'],
            'status' => $row['status'],
            'builtin' => $row['builtin'],
            'userCount' => $row['userCount'],
            'menuIds' => $row['menuIds'],
        ], $roles);
        $menuNodes = array_map(static fn (array $row): array => [
            'id' => $row['id'],
            'parentId' => $row['parentId'],
            'name' => $row['name'],
            'type' => $row['type'],
            'icon' => $row['icon'],
            'path' => $row['path'],
            'permission' => $row['permission'],
            'sort' => $row['sort'],
            'visible' => $row['visible'],
            'status' => $row['status'],
        ], SystemManagementRepository::selectMenus(null, null));
        $canSeeUsers = PermissionService::hasPermission(AuthContext::username(), 'system:user:list');
        $users = $canSeeUsers ? SystemManagementRepository::selectGraphUsers($scope) : [];
        $userIds = array_map(static fn (array $row): int => (int) $row['id'], $users);
        $userRoles = [];
        if ($canSeeUsers) {
            foreach (SystemManagementRepository::selectUserRoleLinks() as $link) {
                if (in_array((int) $link['userId'], $userIds, true)) {
                    $userRoles[] = [(int) $link['userId'], (int) $link['roleId']];
                }
            }
        }
        return [
            'users' => $users,
            'roles' => $roleNodes,
            'menus' => $menuNodes,
            'userRoles' => $userRoles,
            'usersVisible' => $canSeeUsers,
        ];
    }

    public static function roleMenus(int $roleId): array
    {
        return ['menuIds' => SystemManagementRepository::selectRoleMenuIds($roleId)];
    }

    public static function exportRoles(?string $keyword, ?int $status): string
    {
        $rows = SystemManagementRepository::selectRoles($keyword, $status, 0, 5000);
        $data = array_map(static fn (array $row): array => [
            $row['name'] ?? null,
            $row['code'] ?? null,
            $row['dataScope'] ?? null,
            self::userStatusText($row['status'] ?? null),
            $row['createTime'] ?? null,
            $row['remark'] ?? null,
        ], $rows);
        return Csv::build(['角色名称', '权限字符', '数据权限', '状态', '创建时间', '备注'], $data);
    }

    public static function createRole(array $request): array
    {
        $name = required(self::stringOrNull($request['name'] ?? null), '角色名称不能为空');
        $code = required(self::stringOrNull($request['code'] ?? null), '角色编码不能为空');
        if (SystemManagementRepository::countRoleCode($code, null) > 0) {
            throw new BusinessException('角色编码已存在');
        }
        $roleId = SystemManagementRepository::insertRole([
            'name' => $name,
            'code' => $code,
            'dataScope' => default_string(self::stringOrNull($request['dataScope'] ?? null), 'SELF'),
            'sort' => default_int(self::intOrNull($request['sort'] ?? null), 0),
            'status' => default_int(self::intOrNull($request['status'] ?? null), 1),
            'remark' => self::stringOrNull($request['remark'] ?? null),
        ], AuthContext::operator());
        self::replaceRoleMenus($roleId, self::longListOrNull($request['menuIds'] ?? null));
        return ['id' => $roleId];
    }

    public static function updateRole(int $id, array $request): void
    {
        $updated = SystemManagementRepository::updateRole($id, [
            'name' => trim_to_null(self::stringOrNull($request['name'] ?? null)),
            'dataScope' => trim_to_null(self::stringOrNull($request['dataScope'] ?? null)),
            'sort' => self::intOrNull($request['sort'] ?? null),
            'status' => self::intOrNull($request['status'] ?? null),
            'remark' => self::stringOrNull($request['remark'] ?? null),
        ], AuthContext::operator());
        if ($updated === 0) {
            throw new BusinessException('角色不存在、已删除或为内置角色');
        }
        if (array_key_exists('menuIds', $request)) {
            self::replaceRoleMenus($id, self::longListOrNull($request['menuIds']));
        }
    }

    public static function deleteRole(int $id): void
    {
        if (SystemManagementRepository::countRoleUsers($id) > 0) {
            throw new BusinessException('角色已分配用户，不能删除');
        }
        SystemManagementRepository::deleteRoleMenus($id);
        if (SystemManagementRepository::softDeleteRole($id, AuthContext::operator()) === 0) {
            throw new BusinessException('角色不存在、已删除或为内置角色');
        }
    }

    /* ── 菜单 ───────────────────────────────────────────── */

    public static function menus(?string $keyword, ?int $status): array
    {
        return self::treeOf(SystemManagementRepository::selectMenus($keyword, $status));
    }

    public static function exportMenus(?string $keyword, ?int $status): string
    {
        $rows = SystemManagementRepository::selectMenus($keyword, $status);
        $data = array_map(static fn (array $row): array => [
            $row['name'] ?? null,
            $row['type'] ?? null,
            $row['icon'] ?? null,
            $row['path'] ?? null,
            $row['component'] ?? null,
            $row['permission'] ?? null,
            $row['sort'] ?? null,
            self::userStatusText($row['status'] ?? null),
        ], $rows);
        return Csv::build(['菜单名称', '类型', '图标', '路由地址', '组件路径', '权限标识', '排序', '状态'], $data);
    }

    public static function createMenu(array $request): array
    {
        $name = required(self::stringOrNull($request['name'] ?? null), '菜单名称不能为空');
        $menuId = SystemManagementRepository::nextMenuId();
        SystemManagementRepository::insertMenu([
            'id' => $menuId,
            'parentId' => self::intOrNull($request['parentId'] ?? null) ?? 0,
            'name' => $name,
            'type' => default_string(self::stringOrNull($request['type'] ?? null), 'menu'),
            'icon' => self::stringOrNull($request['icon'] ?? null),
            'path' => self::stringOrNull($request['path'] ?? null),
            'component' => self::stringOrNull($request['component'] ?? null),
            'permission' => self::stringOrNull($request['permission'] ?? null),
            'sort' => default_int(self::intOrNull($request['sort'] ?? null), 0),
            'visible' => default_int(self::intOrNull($request['visible'] ?? null), 1),
            'status' => default_int(self::intOrNull($request['status'] ?? null), 1),
        ], AuthContext::operator());
        return ['id' => $menuId];
    }

    public static function updateMenu(int $id, array $request): void
    {
        $updated = SystemManagementRepository::updateMenu($id, [
            'parentId' => self::intOrNull($request['parentId'] ?? null),
            'name' => trim_to_null(self::stringOrNull($request['name'] ?? null)),
            'type' => trim_to_null(self::stringOrNull($request['type'] ?? null)),
            'icon' => self::stringOrNull($request['icon'] ?? null),
            'path' => self::stringOrNull($request['path'] ?? null),
            'component' => self::stringOrNull($request['component'] ?? null),
            'permission' => self::stringOrNull($request['permission'] ?? null),
            'sort' => self::intOrNull($request['sort'] ?? null),
            'visible' => self::intOrNull($request['visible'] ?? null),
            'status' => self::intOrNull($request['status'] ?? null),
        ], AuthContext::operator());
        if ($updated === 0) {
            throw new BusinessException('菜单不存在');
        }
    }

    public static function deleteMenu(int $id): void
    {
        if (SystemManagementRepository::countMenuChildren($id) > 0) {
            throw new BusinessException('存在子菜单，不能删除');
        }
        if (SystemManagementRepository::countMenuRoles($id) > 0) {
            throw new BusinessException('菜单已分配角色，不能删除');
        }
        if (SystemManagementRepository::deleteMenu($id) === 0) {
            throw new BusinessException('菜单不存在');
        }
    }

    /* ── 部门 ───────────────────────────────────────────── */

    public static function depts(?string $keyword, ?int $status): array
    {
        return self::treeOf(SystemManagementRepository::selectDepts($keyword, $status));
    }

    public static function exportDepts(?string $keyword, ?int $status): string
    {
        $rows = SystemManagementRepository::selectDepts($keyword, $status);
        $data = array_map(static fn (array $row): array => [
            $row['name'] ?? null,
            $row['leader'] ?? null,
            $row['phone'] ?? null,
            $row['email'] ?? null,
            $row['sort'] ?? null,
            self::userStatusText($row['status'] ?? null),
        ], $rows);
        return Csv::build(['部门名称', '负责人', '联系电话', '邮箱', '排序', '状态'], $data);
    }

    public static function createDept(array $request): array
    {
        $name = required(self::stringOrNull($request['name'] ?? null), '部门名称不能为空');
        $parentId = self::intOrNull($request['parentId'] ?? null) ?? 0;
        $deptId = SystemManagementRepository::insertDept([
            'parentId' => $parentId,
            'ancestors' => self::ancestorsFor($parentId),
            'name' => $name,
            'leader' => self::stringOrNull($request['leader'] ?? null),
            'phone' => self::stringOrNull($request['phone'] ?? null),
            'email' => self::stringOrNull($request['email'] ?? null),
            'sort' => default_int(self::intOrNull($request['sort'] ?? null), 0),
            'status' => default_int(self::intOrNull($request['status'] ?? null), 1),
        ], AuthContext::operator());
        return ['id' => $deptId];
    }

    public static function updateDept(int $id, array $request): void
    {
        $parentId = self::intOrNull($request['parentId'] ?? null);
        $updated = SystemManagementRepository::updateDept($id, [
            'parentId' => $parentId,
            'ancestors' => $parentId === null ? null : self::ancestorsFor($parentId),
            'name' => trim_to_null(self::stringOrNull($request['name'] ?? null)),
            'leader' => self::stringOrNull($request['leader'] ?? null),
            'phone' => self::stringOrNull($request['phone'] ?? null),
            'email' => self::stringOrNull($request['email'] ?? null),
            'sort' => self::intOrNull($request['sort'] ?? null),
            'status' => self::intOrNull($request['status'] ?? null),
        ], AuthContext::operator());
        if ($updated === 0) {
            throw new BusinessException('部门不存在或已删除');
        }
    }

    public static function deleteDept(int $id): void
    {
        if (SystemManagementRepository::countDeptChildren($id) > 0) {
            throw new BusinessException('存在下级部门，不能删除');
        }
        if (SystemManagementRepository::countDeptUsers($id) > 0) {
            throw new BusinessException('部门已关联用户，不能删除');
        }
        if (SystemManagementRepository::softDeleteDept($id, AuthContext::operator()) === 0) {
            throw new BusinessException('部门不存在或已删除');
        }
    }

    /* ── 岗位 ───────────────────────────────────────────── */

    public static function posts(PageQuery $query, ?string $keyword, ?int $status): array
    {
        [$pageNum, $pageSize, $offset] = self::pageOf($query);
        $total = SystemManagementRepository::countPosts($keyword, $status);
        $rows = SystemManagementRepository::selectPosts($keyword, $status, $offset, $pageSize);
        return PageResult::of($rows, $total, $pageNum, $pageSize);
    }

    public static function exportPosts(?string $keyword, ?int $status): string
    {
        $rows = SystemManagementRepository::selectPosts($keyword, $status, 0, 5000);
        $data = array_map(static fn (array $row): array => [
            $row['postCode'] ?? null,
            $row['postName'] ?? null,
            $row['sort'] ?? null,
            self::userStatusText($row['status'] ?? null),
            $row['createTime'] ?? null,
            $row['remark'] ?? null,
        ], $rows);
        return Csv::build(['岗位编码', '岗位名称', '排序', '状态', '创建时间', '备注'], $data);
    }

    public static function createPost(array $request): array
    {
        $postCode = required(self::stringOrNull($request['postCode'] ?? null), '岗位编码不能为空');
        $postName = required(self::stringOrNull($request['postName'] ?? null), '岗位名称不能为空');
        if (SystemManagementRepository::countPostCode($postCode, null) > 0) {
            throw new BusinessException('岗位编码已存在');
        }
        $postId = SystemManagementRepository::insertPost([
            'postCode' => $postCode,
            'postName' => $postName,
            'sort' => default_int(self::intOrNull($request['sort'] ?? null), 0),
            'status' => default_int(self::intOrNull($request['status'] ?? null), 1),
            'remark' => self::stringOrNull($request['remark'] ?? null),
        ], AuthContext::operator());
        return ['id' => $postId];
    }

    public static function updatePost(int $id, array $request): void
    {
        $updated = SystemManagementRepository::updatePost($id, [
            'postName' => trim_to_null(self::stringOrNull($request['postName'] ?? null)),
            'sort' => self::intOrNull($request['sort'] ?? null),
            'status' => self::intOrNull($request['status'] ?? null),
            'remark' => self::stringOrNull($request['remark'] ?? null),
        ], AuthContext::operator());
        if ($updated === 0) {
            throw new BusinessException('岗位不存在或已删除');
        }
    }

    public static function deletePost(int $id): void
    {
        if (SystemManagementRepository::softDeletePost($id, AuthContext::operator()) === 0) {
            throw new BusinessException('岗位不存在或已删除');
        }
    }

    /* ── 字典 ───────────────────────────────────────────── */

    public static function dictTypes(PageQuery $query, ?string $keyword, ?int $status): array
    {
        [$pageNum, $pageSize, $offset] = self::pageOf($query);
        $total = SystemManagementRepository::countDictTypes($keyword, $status);
        $rows = SystemManagementRepository::selectDictTypes($keyword, $status, $offset, $pageSize);
        return PageResult::of($rows, $total, $pageNum, $pageSize);
    }

    public static function exportDictTypes(?string $keyword, ?int $status): string
    {
        $rows = SystemManagementRepository::selectDictTypes($keyword, $status, 0, 5000);
        $data = array_map(static fn (array $row): array => [
            $row['dictName'] ?? null,
            $row['dictType'] ?? null,
            self::userStatusText($row['status'] ?? null),
            $row['createTime'] ?? null,
            $row['remark'] ?? null,
        ], $rows);
        return Csv::build(['字典名称', '字典类型', '状态', '创建时间', '备注'], $data);
    }

    public static function dictData(string $dictType): array
    {
        return self::booleanRows(SystemManagementRepository::selectDictData($dictType), 'def');
    }

    public static function createDictType(array $request): array
    {
        $dictName = required(self::stringOrNull($request['dictName'] ?? null), '字典名称不能为空');
        $dictType = required(self::stringOrNull($request['dictType'] ?? null), '字典类型不能为空');
        if (SystemManagementRepository::countDictType($dictType, null) > 0) {
            throw new BusinessException('字典类型已存在');
        }
        $id = SystemManagementRepository::insertDictType([
            'dictName' => $dictName,
            'dictType' => $dictType,
            'status' => default_int(self::intOrNull($request['status'] ?? null), 1),
            'remark' => self::stringOrNull($request['remark'] ?? null),
        ], AuthContext::operator());
        return ['id' => $id];
    }

    public static function updateDictType(int $id, array $request): void
    {
        $updated = SystemManagementRepository::updateDictType($id, [
            'dictName' => trim_to_null(self::stringOrNull($request['dictName'] ?? null)),
            'status' => self::intOrNull($request['status'] ?? null),
            'remark' => self::stringOrNull($request['remark'] ?? null),
        ], AuthContext::operator());
        if ($updated === 0) {
            throw new BusinessException('字典类型不存在');
        }
    }

    public static function deleteDictType(int $id): void
    {
        $dictType = SystemManagementRepository::selectDictTypeCode($id);
        if ($dictType === null) {
            throw new BusinessException('字典类型不存在');
        }
        if (SystemManagementRepository::countDictDataByType($dictType) > 0) {
            throw new BusinessException('字典类型存在字典数据，不能删除');
        }
        SystemManagementRepository::deleteDictType($id);
    }

    public static function createDictData(array $request): array
    {
        $dictType = required(self::stringOrNull($request['dictType'] ?? null), '字典类型不能为空');
        $label = required(self::stringOrNull($request['label'] ?? null), '字典标签不能为空');
        $value = required(self::stringOrNull($request['value'] ?? null), '字典值不能为空');
        $id = SystemManagementRepository::insertDictData([
            'dictType' => $dictType,
            'label' => $label,
            'value' => $value,
            'sort' => default_int(self::intOrNull($request['sort'] ?? null), 0),
            'status' => default_int(self::intOrNull($request['status'] ?? null), 1),
            'tone' => self::stringOrNull($request['tone'] ?? null),
            'def' => bool_as_int(self::boolOrNull($request['def'] ?? null)),
            'remark' => self::stringOrNull($request['remark'] ?? null),
        ], AuthContext::operator());
        return ['id' => $id];
    }

    public static function updateDictData(int $id, array $request): void
    {
        $def = self::boolOrNull($request['def'] ?? null);
        $updated = SystemManagementRepository::updateDictData($id, [
            'dictType' => trim_to_null(self::stringOrNull($request['dictType'] ?? null)),
            'label' => trim_to_null(self::stringOrNull($request['label'] ?? null)),
            'value' => trim_to_null(self::stringOrNull($request['value'] ?? null)),
            'sort' => self::intOrNull($request['sort'] ?? null),
            'status' => self::intOrNull($request['status'] ?? null),
            'tone' => self::stringOrNull($request['tone'] ?? null),
            'def' => $def === null ? null : bool_as_int($def),
            'remark' => self::stringOrNull($request['remark'] ?? null),
        ], AuthContext::operator());
        if ($updated === 0) {
            throw new BusinessException('字典数据不存在');
        }
    }

    public static function deleteDictData(int $id): void
    {
        if (SystemManagementRepository::deleteDictData($id) === 0) {
            throw new BusinessException('字典数据不存在');
        }
    }

    /* ── 参数配置 ────────────────────────────────────────── */

    public static function configs(PageQuery $query, ?string $keyword): array
    {
        [$pageNum, $pageSize, $offset] = self::pageOf($query);
        $total = SystemManagementRepository::countConfigs($keyword);
        $rows = self::booleanRows(SystemManagementRepository::selectConfigs($keyword, $offset, $pageSize), 'builtin');
        return PageResult::of($rows, $total, $pageNum, $pageSize);
    }

    public static function exportConfigs(?string $keyword): string
    {
        $rows = SystemManagementRepository::selectConfigs($keyword, 0, 5000);
        $data = array_map(static fn (array $row): array => [
            $row['name'] ?? null,
            $row['key'] ?? null,
            $row['value'] ?? null,
            $row['type'] ?? null,
            $row['createTime'] ?? null,
            $row['remark'] ?? null,
        ], $rows);
        return Csv::build(['参数名称', '参数键名', '参数键值', '系统内置', '创建时间', '备注'], $data);
    }

    public static function createConfig(array $request): array
    {
        $name = required(self::stringOrNull($request['name'] ?? null), '参数名称不能为空');
        $key = required(self::stringOrNull($request['key'] ?? null), '参数键名不能为空');
        $value = required(self::stringOrNull($request['value'] ?? null), '参数键值不能为空');
        if (SystemManagementRepository::countConfigKey($key, null) > 0) {
            throw new BusinessException('参数键名已存在');
        }
        $id = SystemManagementRepository::insertConfig([
            'name' => $name,
            'key' => $key,
            'value' => $value,
            'type' => default_string(self::stringOrNull($request['type'] ?? null), 'N'),
            'builtin' => bool_as_int(self::boolOrNull($request['builtin'] ?? null)),
            'remark' => self::stringOrNull($request['remark'] ?? null),
        ], AuthContext::operator());
        return ['id' => $id];
    }

    public static function updateConfig(int $id, array $request): void
    {
        $builtin = self::boolOrNull($request['builtin'] ?? null);
        $updated = SystemManagementRepository::updateConfig($id, [
            'name' => trim_to_null(self::stringOrNull($request['name'] ?? null)),
            'value' => trim_to_null(self::stringOrNull($request['value'] ?? null)),
            'type' => trim_to_null(self::stringOrNull($request['type'] ?? null)),
            'builtin' => $builtin === null ? null : bool_as_int($builtin),
            'remark' => self::stringOrNull($request['remark'] ?? null),
        ], AuthContext::operator());
        if ($updated === 0) {
            throw new BusinessException('参数配置不存在或为内置参数');
        }
    }

    public static function deleteConfig(int $id): void
    {
        if (SystemManagementRepository::deleteConfig($id) === 0) {
            throw new BusinessException('参数配置不存在或为内置参数');
        }
    }

    /* ── 日志 ───────────────────────────────────────────── */

    public static function operLogs(PageQuery $query, ?string $keyword, ?int $result): array
    {
        [$pageNum, $pageSize, $offset] = self::pageOf($query);
        $total = SystemManagementRepository::countOperLogs($keyword, $result);
        $rows = SystemManagementRepository::selectOperLogs($keyword, $result, $offset, $pageSize);
        return PageResult::of($rows, $total, $pageNum, $pageSize);
    }

    public static function loginLogs(PageQuery $query, ?string $keyword, ?int $status): array
    {
        [$pageNum, $pageSize, $offset] = self::pageOf($query);
        $total = SystemManagementRepository::countLoginLogs($keyword, $status);
        $rows = SystemManagementRepository::selectLoginLogs($keyword, $status, $offset, $pageSize);
        return PageResult::of($rows, $total, $pageNum, $pageSize);
    }

    public static function exportOperLogs(?string $keyword, ?int $result): string
    {
        $rows = SystemManagementRepository::selectOperLogs($keyword, $result, 0, 5000);
        $data = array_map(static fn (array $row): array => [
            $row['module'] ?? null,
            $row['action'] ?? null,
            $row['method'] ?? null,
            $row['url'] ?? null,
            $row['params'] ?? null,
            $row['operName'] ?? null,
            $row['dept'] ?? null,
            $row['ip'] ?? null,
            $row['location'] ?? null,
            $row['cost'] ?? null,
            self::resultText($row['result'] ?? null),
            $row['operTime'] ?? null,
            $row['errorMsg'] ?? null,
        ], $rows);
        return Csv::build(
            ['模块', '类型', '请求方式', '请求地址', '请求参数', '操作人', '部门', 'IP', '地点', '耗时', '结果', '操作时间', '异常信息'],
            $data
        );
    }

    public static function exportLoginLogs(?string $keyword, ?int $status): string
    {
        $rows = SystemManagementRepository::selectLoginLogs($keyword, $status, 0, 5000);
        $data = array_map(static fn (array $row): array => [
            $row['username'] ?? null,
            $row['ip'] ?? null,
            $row['location'] ?? null,
            $row['browser'] ?? null,
            $row['os'] ?? null,
            self::resultText($row['status'] ?? null),
            $row['msg'] ?? null,
            $row['loginTime'] ?? null,
        ], $rows);
        return Csv::build(['登录账号', 'IP', '地点', '浏览器', '系统', '状态', '描述', '登录时间'], $data);
    }

    public static function deleteOperLog(int $id): void
    {
        if (SystemManagementRepository::deleteOperLog($id) === 0) {
            throw new BusinessException('操作日志不存在');
        }
    }

    public static function clearOperLogs(): void
    {
        SystemManagementRepository::clearOperLogs();
    }

    public static function deleteLoginLog(int $id): void
    {
        if (SystemManagementRepository::deleteLoginLog($id) === 0) {
            throw new BusinessException('登录日志不存在');
        }
    }

    public static function clearLoginLogs(): void
    {
        SystemManagementRepository::clearLoginLogs();
    }

    /* ── 私有工具 ───────────────── */

    /** @return array{0: int, 1: int, 2: int} [pageNum, pageSize, offset] */
    private static function pageOf(PageQuery $query): array
    {
        $pageNum = $query->normalizedPageNum();
        $pageSize = $query->normalizedPageSize();
        return [$pageNum, $pageSize, ($pageNum - 1) * $pageSize];
    }

    private static function treeOf(array $rows): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $node = $row;
            $node['children'] = [];
            $byId[number_as_long($node['id'])] = $node;
        }
        $roots = [];
        foreach (array_keys($byId) as $id) {
            $parentId = number_as_long($byId[$id]['parentId'] ?? null);
            $parent = ($parentId !== $id && isset($byId[$parentId])) ? $byId[$parentId] : null;
            if ($parent === null) {
                $roots[] = &$byId[$id];
            } else {
                $byId[$parentId]['children'][] = &$byId[$id];
            }
        }
        unset($id);
        return $roots;
    }

    /** @return list<array<string,mixed>> */
    private static function booleanRows(array $rows, string $key): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[] = self::booleanRow($row, $key);
        }
        return $result;
    }

    private static function booleanRow(array $row, string $key): array
    {
        $copy = $row;
        $copy[$key] = number_as_long($copy[$key] ?? null) === 1;
        return $copy;
    }

    /** @return list<array<string,mixed>> */
    private static function roleRows(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $copy = self::booleanRow($row, 'builtin');
            $idsText = (string) ($copy['menuIdsText'] ?? '');
            $menuIds = [];
            if ($idsText !== '' && $idsText !== 'null') {
                foreach (explode(',', $idsText) as $part) {
                    if (trim($part) !== '') {
                        $menuIds[] = (int) $part;
                    }
                }
            }
            unset($copy['menuIdsText']);
            $copy['menuIds'] = $menuIds;
            $result[] = $copy;
        }
        return $result;
    }

    private static function replaceUserRoles(int $userId, ?array $roleIds): void
    {
        SystemManagementRepository::deleteUserRoles($userId);
        if ($roleIds !== null) {
            foreach ($roleIds as $roleId) {
                SystemManagementRepository::insertUserRole($userId, $roleId);
            }
        }
    }

    private static function replaceUserPosts(int $userId, ?array $postIds): void
    {
        SystemManagementRepository::deleteUserPosts($userId);
        if ($postIds !== null) {
            foreach ($postIds as $postId) {
                SystemManagementRepository::insertUserPost($userId, $postId);
            }
        }
    }

    private static function replaceRoleMenus(int $roleId, ?array $menuIds): void
    {
        SystemManagementRepository::deleteRoleMenus($roleId);
        if ($menuIds !== null) {
            foreach ($menuIds as $menuId) {
                SystemManagementRepository::insertRoleMenu($roleId, $menuId);
            }
        }
    }

    private static function ancestorsFor(int $parentId): string
    {
        if ($parentId === 0) {
            return '';
        }
        $parentAncestors = SystemManagementRepository::selectDeptAncestors($parentId);
        if ($parentAncestors === null) {
            throw new BusinessException('上级部门不存在或已删除');
        }
        return trim($parentAncestors) === '' ? (string) $parentId : $parentAncestors . ',' . $parentId;
    }

    private static function userStatusText(mixed $value): string
    {
        return number_as_long($value) === 1 ? '正常' : '停用';
    }

    private static function resultText(mixed $value): string
    {
        return number_as_long($value) === 1 ? '成功' : '失败';
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return is_scalar($value) ? (string) $value : null;
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        return null;
    }

    private static function boolOrNull(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1;
        }
        if (is_string($value)) {
            return $value === 'true' || $value === '1';
        }
        return null;
    }

    /** @return list<int>|null */
    private static function longListOrNull(mixed $value): ?array
    {
        if ($value === null || !is_array($value)) {
            return null;
        }
        $result = [];
        foreach ($value as $item) {
            $int = self::intOrNull($item);
            if ($int !== null) {
                $result[] = $int;
            }
        }
        return $result;
    }
}
