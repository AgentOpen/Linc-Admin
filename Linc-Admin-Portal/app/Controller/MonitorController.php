<?php

declare(strict_types=1);

namespace Linc\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Linc\Service\MonitorStatsService;
use Linc\Service\SystemManagementService;

/**
 * 监控日志接口。
 */
final class MonitorController
{
    /** 操作日志概览与趋势：range = 24h（按小时）/ 7d / 30d（按天） */
    public static function operLogStats(Request $request, Response $response): Response
    {
        return AuthController::ok($response, MonitorStatsService::operStats(self::range($request)));
    }

    /** 当日操作泳道：按模块分道聚合，date 缺省为数据库当天 */
    public static function operLogTimeline(Request $request, Response $response): Response
    {
        $date = $request->getQueryParams()['date'] ?? null;
        if (is_string($date) && $date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
           
            throw new \Linc\Core\BusinessException('参数类型错误: date');
        }
        return AuthController::ok($response, MonitorStatsService::operTimeline(
            is_string($date) && $date !== '' ? $date : null
        ));
    }

    /** 登录日志概览与趋势 */
    public static function loginLogStats(Request $request, Response $response): Response
    {
        return AuthController::ok($response, MonitorStatsService::loginStats(self::range($request)));
    }

    /** 登录热力（星期 × 小时）与失败聚合告警，days 取 7 或 30 */
    public static function loginLogHeatmap(Request $request, Response $response): Response
    {
        $days = $request->getQueryParams()['days'] ?? 7;
        if (!is_numeric($days)) {
            throw new \Linc\Core\BusinessException('参数类型错误: days');
        }
        return AuthController::ok($response, MonitorStatsService::loginHeatmap((int) $days));
    }

    public static function operLogs(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::ok($response, SystemManagementService::operLogs(
            AuthController::pageQuery($request),
            self::strParam($q, 'keyword'),
            self::intParam($q, 'result')
        ));
    }

    public static function exportOperLogs(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::csv($response, 'oper-logs.csv', SystemManagementService::exportOperLogs(
            self::strParam($q, 'keyword'),
            self::intParam($q, 'result')
        ));
    }

    public static function deleteOperLog(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::deleteOperLog(AuthController::pathId($args));
        return AuthController::ok($response);
    }

    public static function clearOperLogs(Request $request, Response $response): Response
    {
        SystemManagementService::clearOperLogs();
        return AuthController::ok($response);
    }

    public static function loginLogs(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::ok($response, SystemManagementService::loginLogs(
            AuthController::pageQuery($request),
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status')
        ));
    }

    public static function exportLoginLogs(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        return AuthController::csv($response, 'login-logs.csv', SystemManagementService::exportLoginLogs(
            self::strParam($q, 'keyword'),
            self::intParam($q, 'status')
        ));
    }

    public static function deleteLoginLog(Request $request, Response $response, array $args): Response
    {
        SystemManagementService::deleteLoginLog(AuthController::pathId($args));
        return AuthController::ok($response);
    }

    public static function clearLoginLogs(Request $request, Response $response): Response
    {
        SystemManagementService::clearLoginLogs();
        return AuthController::ok($response);
    }

    private static function range(Request $request): string
    {
        $range = $request->getQueryParams()['range'] ?? '24h';
        return is_string($range) && $range !== '' ? $range : '24h';
    }

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
