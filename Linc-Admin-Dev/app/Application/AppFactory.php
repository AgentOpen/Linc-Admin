<?php

declare(strict_types=1);

namespace Linc\Application;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Linc\Controller\AnnounceController;
use Linc\Controller\AuthController;
use Linc\Controller\DashboardController;
use Linc\Controller\GeneratorController;
use Linc\Controller\HealthController;
use Linc\Controller\InstallerController;
use Linc\Controller\MonitorController;
use Linc\Controller\SkinController;
use Linc\Controller\SystemManagementController;
use Linc\Middleware\AuthMiddleware;
use Linc\Middleware\OperLog;
use Linc\Middleware\RequirePermission;
use Linc\Middleware\TraceIdMiddleware;

/**
 * Slim 应用装配与路由表：路径、权限码、操作日志标注集中登记。
 * 公开路径：/api/health、/api/auth/captcha、/api/auth/login；其余 /api/** 需登录。
 *
 * 中间件执行顺序：
 *   错误处理（最外）→ TraceId → 认证 → 路由 → 路由级权限/操作日志 → 控制器。
 */
final class AppFactory
{
    /**
     * @param bool $installed 已安装时注册全部业务路由；未安装仅开放健康检查与安装接口。
     */
    public static function create(bool $installed = true): App
    {
        $responseFactory = new \Slim\Psr7\Factory\ResponseFactory();
        $container = new \DI\Container();
        $app = new App($responseFactory, $container);
        // 绑定 Slim 内部接口，供错误处理器等组件经容器解析
        $container->set(\Slim\Interfaces\CallableResolverInterface::class, $app->getCallableResolver());
        $container->set(\Psr\Http\Message\ResponseFactoryInterface::class, $responseFactory);

        $app->addRoutingMiddleware();
        $app->add(new AuthMiddleware());
        $app->add(new TraceIdMiddleware());
        $errorMiddleware = $app->addErrorMiddleware(true, true, true);
        $errorMiddleware->setDefaultErrorHandler(\Linc\Middleware\ApiErrorHandler::class);

        $app->get('/api/health', [HealthController::class, 'health']);
        $app->group('/api/install', function (RouteCollectorProxy $group): void {
            $group->get('/env', [InstallerController::class, 'env']);
            $group->post('/test', [InstallerController::class, 'test']);
            $group->post('/run', [InstallerController::class, 'run']);
            $group->get('/status', [InstallerController::class, 'status']);
        });

        if ($installed) {
            self::registerRoutes($app);
        }
        return $app;
    }

    private static function registerRoutes(App $app): void
    {
        $app->group('/api/auth', function (RouteCollectorProxy $group): void {
            $group->get('/captcha', [AuthController::class, 'captcha']);
            $group->post('/login', [AuthController::class, 'login']);
            $group->post('/logout', [AuthController::class, 'logout']);
            $group->get('/me', [AuthController::class, 'me']);
            $group->get('/routes', [AuthController::class, 'routes']);
            $group->put('/password', [AuthController::class, 'changePassword'])
                ->add(new OperLog('账号安全', '修改密码'));
        });

        $app->get('/api/dashboard/summary', [DashboardController::class, 'summary']);

        // 门户联动：公告管理（前台内容源）与主题皮肤持久化
        $app->group('/api/biz/announce', function (RouteCollectorProxy $group): void {
            $group->get('', [AnnounceController::class, 'index']);
            $group->post('', [AnnounceController::class, 'create'])
                ->add(new OperLog('公告管理', '新增'))
                ->add(new RequirePermission('biz:announce:add'));
            $group->put('/{id}', [AnnounceController::class, 'update'])
                ->add(new OperLog('公告管理', '修改'))
                ->add(new RequirePermission('biz:announce:edit'));
            $group->delete('/{id}', [AnnounceController::class, 'remove'])
                ->add(new OperLog('公告管理', '删除'))
                ->add(new RequirePermission('biz:announce:remove'));
        })->add(new RequirePermission('biz:announce:list'));

        $app->get('/api/theme/skin', [SkinController::class, 'get']);
        $app->put('/api/theme/skin', [SkinController::class, 'applyFromRoute'])
            ->add(new OperLog('主题中心', '应用门户主题'))
            ->add(new RequirePermission('system:theme:edit'));

        $app->group('/api/system', function (RouteCollectorProxy $group): void {
            self::users($group);
            self::roles($group);
            self::menus($group);
            self::depts($group);
            self::posts($group);
            self::dict($group);
            self::configs($group);
        });

        $app->group('/api/monitor', function (RouteCollectorProxy $group): void {
            $group->get('/oper-logs/stats', [MonitorController::class, 'operLogStats'])
                ->add(new RequirePermission('monitor:operlog:list'));
            $group->get('/oper-logs/timeline', [MonitorController::class, 'operLogTimeline'])
                ->add(new RequirePermission('monitor:operlog:list'));
            $group->get('/login-logs/stats', [MonitorController::class, 'loginLogStats'])
                ->add(new RequirePermission('monitor:loginlog:list'));
            $group->get('/login-logs/heatmap', [MonitorController::class, 'loginLogHeatmap'])
                ->add(new RequirePermission('monitor:loginlog:list'));
            $group->get('/oper-logs', [MonitorController::class, 'operLogs'])
                ->add(new RequirePermission('monitor:operlog:list'));
            $group->get('/oper-logs/export', [MonitorController::class, 'exportOperLogs'])
                ->add(new RequirePermission('monitor:operlog:list'));
            $group->delete('/oper-logs', [MonitorController::class, 'clearOperLogs'])
                ->add(new RequirePermission('monitor:operlog:remove'));
            $group->delete('/oper-logs/{id}', [MonitorController::class, 'deleteOperLog'])
                ->add(new RequirePermission('monitor:operlog:remove'));
            $group->get('/login-logs', [MonitorController::class, 'loginLogs'])
                ->add(new RequirePermission('monitor:loginlog:list'));
            $group->get('/login-logs/export', [MonitorController::class, 'exportLoginLogs'])
                ->add(new RequirePermission('monitor:loginlog:list'));
            $group->delete('/login-logs', [MonitorController::class, 'clearLoginLogs'])
                ->add(new RequirePermission('monitor:loginlog:remove'));
            $group->delete('/login-logs/{id}', [MonitorController::class, 'deleteLoginLog'])
                ->add(new RequirePermission('monitor:loginlog:remove'));
        });

        $app->group('/api/tool/generator', function (RouteCollectorProxy $group): void {
            $group->get('/tables', [GeneratorController::class, 'tables']);
            $group->get('/importable-tables', [GeneratorController::class, 'importableTables']);
            $group->post('/import', [GeneratorController::class, 'import'])
                ->add(new OperLog('代码生成', '导入表'));
            $group->get('/tables/{id}/columns', [GeneratorController::class, 'columns']);
            $group->get('/tables/{id}/preview', [GeneratorController::class, 'preview']);
            $group->put('/tables/{id}', [GeneratorController::class, 'update'])
                ->add(new OperLog('代码生成', '修改配置'));
            $group->post('/tables/{id}/generate', [GeneratorController::class, 'generate'])
                ->add(new OperLog('代码生成', '生成代码'));
            $group->get('/tables/{id}/download', [GeneratorController::class, 'download'])
                ->add(new OperLog('代码生成', '下载代码'));
        })->add(new RequirePermission('tool:gen:list'));
    }

    private static function users(RouteCollectorProxy $group): void
    {
        $group->get('/users', [SystemManagementController::class, 'users'])
            ->add(new RequirePermission('system:user:list'));
        $group->get('/users/options', [SystemManagementController::class, 'userOptions'])
            ->add(new RequirePermission('system:user:list'));
        $group->get('/users/export', [SystemManagementController::class, 'exportUsers'])
            ->add(new RequirePermission('system:user:list'));
        $group->post('/users', [SystemManagementController::class, 'createUser'])
            ->add(new OperLog('用户管理', '新增'))
            ->add(new RequirePermission('system:user:add'));
        $group->put('/users/{id}', [SystemManagementController::class, 'updateUser'])
            ->add(new OperLog('用户管理', '修改'))
            ->add(new RequirePermission('system:user:edit'));
        $group->put('/users/{id}/status', [SystemManagementController::class, 'updateUserStatus'])
            ->add(new OperLog('用户管理', '状态变更'))
            ->add(new RequirePermission('system:user:edit'));
        $group->put('/users/{id}/password', [SystemManagementController::class, 'resetUserPassword'])
            ->add(new OperLog('用户管理', '重置密码'))
            ->add(new RequirePermission('system:user:resetPwd'));
        $group->delete('/users/{id}', [SystemManagementController::class, 'deleteUser'])
            ->add(new OperLog('用户管理', '删除'))
            ->add(new RequirePermission('system:user:remove'));
    }

    private static function roles(RouteCollectorProxy $group): void
    {
        $group->get('/roles/export', [SystemManagementController::class, 'exportRoles'])
            ->add(new RequirePermission('system:role:list'));
        $group->get('/roles', [SystemManagementController::class, 'roles'])
            ->add(new RequirePermission('system:role:list'));
        $group->get('/permissions/graph', [SystemManagementController::class, 'permissionGraph'])
            ->add(new RequirePermission('system:role:list'));
        $group->get('/roles/{id}/menus', [SystemManagementController::class, 'roleMenus'])
            ->add(new RequirePermission('system:role:list'));
        $group->post('/roles', [SystemManagementController::class, 'createRole'])
            ->add(new OperLog('角色管理', '新增'))
            ->add(new RequirePermission('system:role:add'));
        $group->put('/roles/{id}', [SystemManagementController::class, 'updateRole'])
            ->add(new OperLog('角色管理', '修改'))
            ->add(new RequirePermission('system:role:edit'));
        $group->delete('/roles/{id}', [SystemManagementController::class, 'deleteRole'])
            ->add(new OperLog('角色管理', '删除'))
            ->add(new RequirePermission('system:role:remove'));
    }

    private static function menus(RouteCollectorProxy $group): void
    {
        $group->get('/menus/export', [SystemManagementController::class, 'exportMenus'])
            ->add(new RequirePermission('system:menu:list'));
        $group->get('/menus/tree', [SystemManagementController::class, 'menus'])
            ->add(new RequirePermission('system:menu:list'));
        $group->post('/menus', [SystemManagementController::class, 'createMenu'])
            ->add(new OperLog('菜单管理', '新增'))
            ->add(new RequirePermission('system:menu:add'));
        $group->put('/menus/{id}', [SystemManagementController::class, 'updateMenu'])
            ->add(new OperLog('菜单管理', '修改'))
            ->add(new RequirePermission('system:menu:edit'));
        $group->delete('/menus/{id}', [SystemManagementController::class, 'deleteMenu'])
            ->add(new OperLog('菜单管理', '删除'))
            ->add(new RequirePermission('system:menu:remove'));
    }

    private static function depts(RouteCollectorProxy $group): void
    {
        $group->get('/depts/export', [SystemManagementController::class, 'exportDepts'])
            ->add(new RequirePermission('system:dept:list'));
        $group->get('/depts/tree', [SystemManagementController::class, 'depts'])
            ->add(new RequirePermission('system:dept:list'));
        $group->post('/depts', [SystemManagementController::class, 'createDept'])
            ->add(new OperLog('部门管理', '新增'))
            ->add(new RequirePermission('system:dept:add'));
        $group->put('/depts/{id}', [SystemManagementController::class, 'updateDept'])
            ->add(new OperLog('部门管理', '修改'))
            ->add(new RequirePermission('system:dept:edit'));
        $group->delete('/depts/{id}', [SystemManagementController::class, 'deleteDept'])
            ->add(new OperLog('部门管理', '删除'))
            ->add(new RequirePermission('system:dept:remove'));
    }

    private static function posts(RouteCollectorProxy $group): void
    {
        $group->get('/posts/export', [SystemManagementController::class, 'exportPosts'])
            ->add(new RequirePermission('system:post:list'));
        $group->get('/posts', [SystemManagementController::class, 'posts'])
            ->add(new RequirePermission('system:post:list'));
        $group->post('/posts', [SystemManagementController::class, 'createPost'])
            ->add(new OperLog('岗位管理', '新增'))
            ->add(new RequirePermission('system:post:add'));
        $group->put('/posts/{id}', [SystemManagementController::class, 'updatePost'])
            ->add(new OperLog('岗位管理', '修改'))
            ->add(new RequirePermission('system:post:edit'));
        $group->delete('/posts/{id}', [SystemManagementController::class, 'deletePost'])
            ->add(new OperLog('岗位管理', '删除'))
            ->add(new RequirePermission('system:post:remove'));
    }

    private static function dict(RouteCollectorProxy $group): void
    {
        $group->get('/dict/types/export', [SystemManagementController::class, 'exportDictTypes'])
            ->add(new RequirePermission('system:dict:list'));
        $group->get('/dict/types', [SystemManagementController::class, 'dictTypes'])
            ->add(new RequirePermission('system:dict:list'));
        $group->get('/dict/options/{dictType}', [SystemManagementController::class, 'dictOptions']);
        $group->get('/dict/data/{dictType}', [SystemManagementController::class, 'dictData'])
            ->add(new RequirePermission('system:dict:list'));
        $group->post('/dict/types', [SystemManagementController::class, 'createDictType'])
            ->add(new OperLog('字典管理', '新增字典类型'))
            ->add(new RequirePermission('system:dict:add'));
        $group->put('/dict/types/{id}', [SystemManagementController::class, 'updateDictType'])
            ->add(new OperLog('字典管理', '修改字典类型'))
            ->add(new RequirePermission('system:dict:edit'));
        $group->delete('/dict/types/{id}', [SystemManagementController::class, 'deleteDictType'])
            ->add(new OperLog('字典管理', '删除字典类型'))
            ->add(new RequirePermission('system:dict:remove'));
        $group->post('/dict/data', [SystemManagementController::class, 'createDictData'])
            ->add(new OperLog('字典管理', '新增字典数据'))
            ->add(new RequirePermission('system:dict:add'));
        $group->put('/dict/data/{id}', [SystemManagementController::class, 'updateDictData'])
            ->add(new OperLog('字典管理', '修改字典数据'))
            ->add(new RequirePermission('system:dict:edit'));
        $group->delete('/dict/data/{id}', [SystemManagementController::class, 'deleteDictData'])
            ->add(new OperLog('字典管理', '删除字典数据'))
            ->add(new RequirePermission('system:dict:remove'));
    }

    private static function configs(RouteCollectorProxy $group): void
    {
        $group->get('/configs/export', [SystemManagementController::class, 'exportConfigs'])
            ->add(new RequirePermission('system:config:list'));
        $group->get('/configs', [SystemManagementController::class, 'configs'])
            ->add(new RequirePermission('system:config:list'));
        $group->post('/configs', [SystemManagementController::class, 'createConfig'])
            ->add(new OperLog('参数配置', '新增'))
            ->add(new RequirePermission('system:config:add'));
        $group->put('/configs/{id}', [SystemManagementController::class, 'updateConfig'])
            ->add(new OperLog('参数配置', '修改'))
            ->add(new RequirePermission('system:config:edit'));
        $group->delete('/configs/{id}', [SystemManagementController::class, 'deleteConfig'])
            ->add(new OperLog('参数配置', '删除'))
            ->add(new RequirePermission('system:config:remove'));
    }
}
