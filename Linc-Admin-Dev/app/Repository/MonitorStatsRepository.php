<?php

declare(strict_types=1);

namespace Linc\Repository;

use Linc\Core\Database;

/**
 * 监控日志只读聚合。
 * 时间边界统一由数据库时钟给出（字符串 yyyy-MM-dd HH:mm:ss），避免 PHP 与会话时区不一致。
 */
final class MonitorStatsRepository
{
    public static function selectDbNow(): string
    {
        return (string) Database::scalar("SELECT DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')");
    }

    /* ── 操作日志 ─────────────────────────────────────────── */

    /** @return array<string,mixed> */
    public static function selectOperOverview(string $since): array
    {
        $row = Database::first(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(result = 0), 0) AS failed,
                    COALESCE(ROUND(AVG(cost)), 0) AS avgCost,
                    COUNT(cost) AS costCount,
                    COUNT(DISTINCT oper_name) AS activeUsers
             FROM sys_oper_log
             WHERE oper_time >= ?",
            [$since]
        );
        return $row ?? [];
    }

    public static function selectOperCostAt(string $since, int $offset): ?int
    {
        $value = Database::scalar(
            "SELECT cost
             FROM sys_oper_log
             WHERE oper_time >= ? AND cost IS NOT NULL
             ORDER BY cost
             LIMIT 1 OFFSET ?",
            [$since, $offset]
        );
        return $value === false || $value === null ? null : (int) $value;
    }

    /** @return list<array<string,mixed>> */
    public static function selectOperHourly(string $since, string $now): array
    {
        return Database::all(
            "SELECT TIMESTAMPDIFF(HOUR, oper_time, ?) AS ago, COUNT(*) AS total, COALESCE(SUM(result = 0), 0) AS failed
             FROM sys_oper_log
             WHERE oper_time >= ?
             GROUP BY ago",
            [$now, $since]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectOperDaily(string $since, string $now): array
    {
        return Database::all(
            "SELECT DATEDIFF(DATE(?), DATE(oper_time)) AS ago, COUNT(*) AS total, COALESCE(SUM(result = 0), 0) AS failed
             FROM sys_oper_log
             WHERE oper_time >= ?
             GROUP BY ago",
            [$now, $since]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectOperPointsOfDay(string $start): array
    {
        return Database::all(
            "SELECT id,
                    module,
                    TIME_TO_SEC(TIME(oper_time)) AS sec,
                    result,
                    cost
             FROM sys_oper_log
             WHERE oper_time >= ? AND oper_time < DATE_ADD(?, INTERVAL 1 DAY)
             ORDER BY module, oper_time, id
             LIMIT 5000",
            [$start, $start]
        );
    }

    /* ── 登录日志 ─────────────────────────────────────────── */

    /** @return array<string,mixed> */
    public static function selectLoginOverview(string $since): array
    {
        $row = Database::first(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 0), 0) AS failed,
                    COUNT(DISTINCT username) AS users,
                    COUNT(DISTINCT ip) AS ips
             FROM sys_login_log
             WHERE login_time >= ?",
            [$since]
        );
        return $row ?? [];
    }

    /** @return list<array<string,mixed>> */
    public static function selectLoginHourly(string $since, string $now): array
    {
        return Database::all(
            "SELECT TIMESTAMPDIFF(HOUR, login_time, ?) AS ago, COUNT(*) AS total, COALESCE(SUM(status = 0), 0) AS failed
             FROM sys_login_log
             WHERE login_time >= ?
             GROUP BY ago",
            [$now, $since]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectLoginDaily(string $since, string $now): array
    {
        return Database::all(
            "SELECT DATEDIFF(DATE(?), DATE(login_time)) AS ago, COUNT(*) AS total, COALESCE(SUM(status = 0), 0) AS failed
             FROM sys_login_log
             WHERE login_time >= ?
             GROUP BY ago",
            [$now, $since]
        );
    }

    /* ── 登录热力与异常聚合 ─────────────────────────────────── */

    /** 星期（0 周一 … 6 周日）× 小时 的登录量与失败量 */
    public static function selectLoginHeatmap(string $since): array
    {
        return Database::all(
            "SELECT WEEKDAY(login_time) AS dow, HOUR(login_time) AS hour,
                    COUNT(*) AS total, COALESCE(SUM(status = 0), 0) AS failed
             FROM sys_login_log
             WHERE login_time >= ?
             GROUP BY dow, hour",
            [$since]
        );
    }

    /** 同一 IP 失败次数达到阈值：users 为该 IP 尝试过的不同账号数 */
    public static function selectLoginIpAnomalies(string $since, int $threshold): array
    {
        return Database::all(
            "SELECT ip AS `key`,
                    COALESCE(SUM(status = 0), 0) AS failed,
                    COUNT(*) AS total,
                    COUNT(DISTINCT username) AS users,
                    DATE_FORMAT(MIN(login_time), '%Y-%m-%d %H:%i') AS firstTime,
                    DATE_FORMAT(MAX(login_time), '%Y-%m-%d %H:%i') AS lastTime
             FROM sys_login_log
             WHERE login_time >= ? AND ip IS NOT NULL AND ip <> ''
             GROUP BY ip
             HAVING failed >= ?
             ORDER BY failed DESC, lastTime DESC
             LIMIT 12",
            [$since, $threshold]
        );
    }

    /** 同一账号失败次数达到阈值：users 位置返回来源 IP 数 */
    public static function selectLoginUserAnomalies(string $since, int $threshold): array
    {
        return Database::all(
            "SELECT username AS `key`,
                    COALESCE(SUM(status = 0), 0) AS failed,
                    COUNT(*) AS total,
                    COUNT(DISTINCT ip) AS users,
                    DATE_FORMAT(MIN(login_time), '%Y-%m-%d %H:%i') AS firstTime,
                    DATE_FORMAT(MAX(login_time), '%Y-%m-%d %H:%i') AS lastTime
             FROM sys_login_log
             WHERE login_time >= ? AND username IS NOT NULL AND username <> ''
             GROUP BY username
             HAVING failed >= ?
             ORDER BY failed DESC, lastTime DESC
             LIMIT 12",
            [$since, $threshold]
        );
    }

    public static function selectLoginAlertThreshold(): ?string
    {
        $value = Database::scalar(
            "SELECT config_value FROM sys_config WHERE config_key = 'sys.login.alertThreshold' LIMIT 1"
        );
        return $value === false || $value === null ? null : (string) $value;
    }
}
