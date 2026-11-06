<?php

declare(strict_types=1);

namespace Linc\Service;

use Linc\Core\AuthContext;
use Linc\Core\BusinessException;
use Linc\Core\Config;
use Linc\Core\DataScopeCriteria;
use Linc\Core\JwtCodec;
use Linc\Core\TokenStore;
use Linc\Repository\AuthRepository;
use Linc\Repository\SystemManagementRepository;

/**
 * 数据权限解析：
 * ALL → 全部；DEPT → 本部门；DEPT_AND_CHILD → 本部门及下级；CUSTOM → 角色自选部门；并集去重，兜底仅本人。
 */
final class DataScopeService
{
    public static function resolve(?string $username): DataScopeCriteria
    {
        if ($username === null || trim($username) === '') {
            return DataScopeCriteria::selfOnly(null);
        }
        $profile = SystemManagementRepository::selectUserProfileByUsername($username);
        if ($profile === null || !isset($profile['id'])) {
            return DataScopeCriteria::selfOnly(null);
        }
        $userId = (int) $profile['id'];
        $deptId = isset($profile['deptId']) && $profile['deptId'] !== null ? (int) $profile['deptId'] : null;
        $roles = SystemManagementRepository::selectActiveRoleScopes($userId);
        foreach ($roles as $role) {
            if (($role['dataScope'] ?? '') === 'ALL') {
                return DataScopeCriteria::all();
            }
        }
        $deptIds = [];
        foreach ($roles as $role) {
            $scope = (string) ($role['dataScope'] ?? '');
            $roleId = (int) $role['id'];
            if ($scope === 'DEPT' && $deptId !== null) {
                $deptIds[] = $deptId;
            } elseif ($scope === 'DEPT_AND_CHILD' && $deptId !== null) {
                array_push($deptIds, ...SystemManagementRepository::selectDeptAndChildIds($deptId));
            } elseif ($scope === 'CUSTOM') {
                array_push($deptIds, ...SystemManagementRepository::selectRoleDeptIds($roleId));
            }
        }
        return DataScopeCriteria::limited($userId, $deptIds);
    }

    /** @DataScope 等价入口：当前会话解析。 */
    public static function current(): DataScopeCriteria
    {
        return self::resolve(AuthContext::username());
    }
}
