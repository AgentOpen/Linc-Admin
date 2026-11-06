<?php

/**
 * API 契约回归脚本：对照 Java 版集成测试要点，验证 PHP 版行为一致性。
 *
 * 用法（需先完成安装并可访问服务）：
 *   php tests/api_contract.php [BASE_URL]
 *   默认 BASE_URL = http://127.0.0.1:8090
 *
 * 覆盖：响应包络、健康检查、验证码、登录/登出、认证失败 401、权限 403、
 * 分页结构、写操作校验文案、CSV 导出表头、监控统计结构、代码生成、安装守卫。
 */

declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8090', '/');

$passed = 0;
$failed = 0;

function request(string $base, string $method, string $path, ?string $token = null, ?array $body = null): array
{
    $headers = ['Accept: application/json'];
    if ($token !== null) {
        $headers[] = "Authorization: Bearer $token";
    }
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers) . "\r\n",
            'content' => $body === null ? null : json_encode($body, JSON_UNESCAPED_UNICODE),
            'ignore_errors' => true,
            'timeout' => 15,
        ],
    ]);
    $raw = file_get_contents($base . $path, false, $context);
    $status = 0;
    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
            $status = (int) $m[1];
        }
    }
    $decoded = json_decode((string) $raw, true);
    return [$status, is_array($decoded) ? $decoded : []];
}

function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [通过] $name\n";
    } else {
        $failed++;
        echo "  [失败] $name" . ($detail !== '' ? " —— $detail" : '') . "\n";
    }
}

echo "API 契约回归：$base\n";

/* ── 健康检查（公开） ───────────────────────────────────────────── */

[$status, $res] = request($base, 'GET', '/api/health');
check('健康检查 code=200', ($res['code'] ?? 0) === 200 && ($res['data']['status'] ?? '') === 'UP');
check('包络字段齐全', isset($res['code'], $res['message'], $res['traceId']) && ($res['message'] ?? '') === '操作成功');

/* ── 未认证访问 ─────────────────────────────────────────────────── */

[$status, $res] = request($base, 'GET', '/api/system/users');
check('未认证 401 文案', ($res['code'] ?? 0) === 401 && ($res['message'] ?? '') === '未认证或登录已过期', json_encode($res, JSON_UNESCAPED_UNICODE));

/* ── 安装守卫 ───────────────────────────────────────────────────── */

[$status, $res] = request($base, 'GET', '/api/install/env');
check('已安装守卫 403', ($res['code'] ?? 0) === 403 && str_contains((string) ($res['message'] ?? ''), '系统已安装'));

/* ── 登录链路 ───────────────────────────────────────────────────── */

[$status, $res] = request($base, 'GET', '/api/auth/captcha');
$captcha = $res['data'] ?? [];
check('验证码返回 uuid+code', isset($captcha['uuid'], $captcha['code']) && strlen((string) $captcha['code']) === 4);

[$status, $res] = request($base, 'POST', '/api/auth/login', null, [
    'username' => 'admin', 'password' => 'wrong-password',
    'captcha' => $captcha['code'], 'captchaUuid' => $captcha['uuid'],
]);
check('密码错误文案', ($res['code'] ?? 0) === 400 && ($res['message'] ?? '') === '账号或密码错误');

[$status, $res] = request($base, 'POST', '/api/auth/login', null, ['username' => '', 'password' => '']);
check('登录参数校验文案', ($res['code'] ?? 0) === 400 && ($res['message'] ?? '') === 'username 不能为空', json_encode($res, JSON_UNESCAPED_UNICODE));

[$status, $res] = request($base, 'GET', '/api/auth/captcha');
$captcha = $res['data'] ?? [];
[$status, $login] = request($base, 'POST', '/api/auth/login', null, [
    'username' => 'admin', 'password' => 'admin123',
    'captcha' => $captcha['code'], 'captchaUuid' => $captcha['uuid'],
]);
$token = $login['data']['token'] ?? '';
check('登录返回 token+expiresIn', ($login['code'] ?? 0) === 200 && $token !== '' && ($login['data']['expiresIn'] ?? 0) === 7200);

/* ── 会话信息 ───────────────────────────────────────────────────── */

[$status, $res] = request($base, 'GET', '/api/auth/me', $token);
$me = $res['data'] ?? [];
check('me 字段与超管角色', ($me['userId'] ?? 0) === 1
    && ($me['username'] ?? '') === 'admin'
    && in_array('super_admin', $me['roles'] ?? [], true)
    && in_array('system:user:list', $me['permissions'] ?? [], true));

[$status, $res] = request($base, 'GET', '/api/auth/routes', $token);
$routes = $res['data']['routes'] ?? [];
check('routes 树含工作台与系统管理', count($routes) >= 3
    && ($routes[0]['name'] ?? '') === '工作台'
    && isset($routes[1]['children']));

/* ── 分页与查询 ─────────────────────────────────────────────────── */

[$status, $res] = request($base, 'GET', '/api/system/users?pageNum=1&pageSize=2', $token);
$page = $res['data'] ?? [];
check('分页结构 rows/total/pageNum/pageSize', isset($page['rows'], $page['total'], $page['pageNum'], $page['pageSize'])
    && ($page['pageNum'] ?? 0) === 1 && ($page['pageSize'] ?? 0) === 2);
check('用户行字段命名', isset($page['rows'][0]['deptName'], $page['rows'][0]['postName'], $page['rows'][0]['roleName'], $page['rows'][0]['createTime']));

[$status, $res] = request($base, 'GET', '/api/system/users?pageNum=1&pageSize=1000', $token);
check('pageSize 上限 100', ($res['data']['pageSize'] ?? 0) === 100);

// Java 版对「已注册路径但方法不匹配」同样返回 405（HttpRequestMethodNotSupportedException）；
// 参数类型错误需用已注册的 PUT 方法触发（@PathVariable Long 转换失败）。
[$status, $res] = request($base, 'PUT', '/api/system/users/abc', $token, []);
check('路径参数类型错误文案', ($res['code'] ?? 0) === 400 && ($res['message'] ?? '') === '参数类型错误: id', json_encode($res, JSON_UNESCAPED_UNICODE));

/* ── 写操作与业务规则 ────────────────────────────────────────────── */

// 用户名带时间戳保证脚本可重复运行（系统对「软删除后重建同名账号」返回 500，与 Java 版一致）
$contractUsername = 'contract-' . substr((string) time(), -8);

[$status, $res] = request($base, 'POST', '/api/system/users', $token, ['username' => $contractUsername, 'nickname' => '契约测试']);
$newUserId = $res['data']['id'] ?? 0;
check('创建用户返回 id', ($res['code'] ?? 0) === 200 && $newUserId > 0);

[$status, $res] = request($base, 'POST', '/api/system/users', $token, ['username' => $contractUsername, 'nickname' => '重复']);
check('重复账号文案', ($res['code'] ?? 0) === 400 && ($res['message'] ?? '') === '登录账号已存在');

[$status, $res] = request($base, 'DELETE', '/api/system/users/1', $token);
check('内置管理员保护', ($res['code'] ?? 0) === 400 && ($res['message'] ?? '') === '内置管理员不允许删除');

[$status, $res] = request($base, 'PUT', '/api/system/users/1/status', $token, ['status' => 2]);
check('状态校验文案', ($res['code'] ?? 0) === 400 && ($res['message'] ?? '') === '用户状态只能为正常或停用');

[$status, $res] = request($base, 'PUT', '/api/system/users/' . $newUserId . '/status', $token, ['status' => 0]);
check('停用用户成功', ($res['code'] ?? 0) === 200);

[$status, $res] = request($base, 'DELETE', '/api/system/users/' . $newUserId, $token);
check('删除用户成功', ($res['code'] ?? 0) === 200);

/* ── 字典与引用选项 ──────────────────────────────────────────────── */

[$status, $res] = request($base, 'GET', '/api/system/dict/options/sys_user_sex', $token);
check('字典选项无需管理权限', ($res['code'] ?? 0) === 200 && count($res['data'] ?? []) === 3
    && isset($res['data'][0]['label'], $res['data'][0]['value']));

[$status, $res] = request($base, 'GET', '/api/system/users/options', $token);
check('引用选项结构', ($res['code'] ?? 0) === 200
    && isset($res['data']['depts'], $res['data']['roles'], $res['data']['posts'], $res['data']['initPassword'])
    && ($res['data']['initPassword'] ?? '') === 'Yy@123456');

/* ── CSV 导出 ───────────────────────────────────────────────────── */

$context = stream_context_create(['http' => ['header' => "Authorization: Bearer $token\r\n", 'ignore_errors' => true]]);
$csv = (string) file_get_contents($base . '/api/system/users/export', false, $context);
check('CSV 中文表头', str_starts_with($csv, '登录账号,用户昵称,手机号,邮箱,部门,岗位,角色,状态,创建时间,最近登录'));

$context = stream_context_create(['http' => ['header' => "Authorization: Bearer $token\r\n", 'ignore_errors' => true]]);
$csv = (string) file_get_contents($base . '/api/system/roles/export', false, $context);
check('角色 CSV 表头', str_starts_with($csv, '角色名称,权限字符,数据权限,状态,创建时间,备注'));

/* ── 工作台与监控统计 ────────────────────────────────────────────── */

[$status, $res] = request($base, 'GET', '/api/dashboard/summary?range=7d', $token);
$data = $res['data'] ?? [];
check('工作台 summary 键', isset($data['stats'], $data['trend'], $data['trendPrev'], $data['dailyOps'], $data['activities'], $data['roleDist'], $data['trendTotal'], $data['successRate']));
check('stats 六项读数', count($data['stats'] ?? []) === 6 && ($data['stats'][0]['key'] ?? '') === 'users' && ($data['stats'][5]['key'] ?? '') === 'depts');

[$status, $res] = request($base, 'GET', '/api/monitor/oper-logs/stats?range=7d', $token);
$data = $res['data'] ?? [];
check('操作日志统计结构', ($data['range'] ?? '') === '7d' && isset($data['total'], $data['successRate'], $data['avgCost'], $data['p95Cost'], $data['activeUsers']) && count($data['series'] ?? []) === 7);

[$status, $res] = request($base, 'GET', '/api/monitor/oper-logs/timeline', $token);
$data = $res['data'] ?? [];
check('当日泳道结构', isset($data['date'], $data['nowSec'], $data['lanes']));

[$status, $res] = request($base, 'GET', '/api/monitor/login-logs/heatmap?days=30', $token);
$data = $res['data'] ?? [];
check('登录热力结构', ($data['days'] ?? 0) === 30 && isset($data['threshold'], $data['cells'], $data['anomalies']));

/* ── 代码生成 ───────────────────────────────────────────────────── */

[$status, $res] = request($base, 'GET', '/api/tool/generator/importable-tables', $token);
check('可导入表列表', ($res['code'] ?? 0) === 200 && is_array($res['data']));

[$status, $res] = request($base, 'POST', '/api/tool/generator/import', $token, ['tableNames' => ['sys_config']]);
$tableId = $res['data']['ids'][0] ?? 0;
check('导入表返回 ids', ($res['code'] ?? 0) === 200 && $tableId > 0);

[$status, $res] = request($base, 'GET', '/api/tool/generator/tables/' . $tableId . '/preview', $token);
$files = $res['data']['files'] ?? [];
check('预览 7 个文件', count($files) === 7 && ($res['data']['activeFile'] ?? '') === 'SysConfigController.php');

$context = stream_context_create(['http' => ['header' => "Authorization: Bearer $token\r\n", 'ignore_errors' => true]]);
$zipRaw = (string) file_get_contents($base . '/api/tool/generator/tables/' . $tableId . '/download', false, $context);
check('下载 ZIP 有效', str_starts_with($zipRaw, "PK\x03\x04") && strlen($zipRaw) > 1000);

/* ── 404 与方法不允许 ───────────────────────────────────────────── */

[$status, $res] = request($base, 'GET', '/api/not-exist', $token);
check('未知接口 404', ($res['code'] ?? 0) === 404 && ($res['message'] ?? '') === '资源不存在');

[$status, $res] = request($base, 'DELETE', '/api/dashboard/summary', $token);
check('方法不允许 405', ($res['code'] ?? 0) === 405 && str_starts_with((string) ($res['message'] ?? ''), '不支持的请求方法: '), json_encode($res, JSON_UNESCAPED_UNICODE));

/* ── 退出登录 ───────────────────────────────────────────────────── */

[$status, $res] = request($base, 'POST', '/api/auth/logout', $token);
check('退出登录成功', ($res['code'] ?? 0) === 200);

[$status, $res] = request($base, 'GET', '/api/auth/me', $token);
check('退出后 token 失效', ($res['code'] ?? 0) === 401);

/* ── 汇总 ───────────────────────────────────────────────────────── */

echo "\n结果：$passed 通过，$failed 失败\n";
exit($failed === 0 ? 0 : 1);
