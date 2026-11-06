<?php

declare(strict_types=1);

namespace Linc\Repository;

use Linc\Core\Database;

/**
 * 工作台数据访问。
 */
final class DashboardRepository
{
    public static function countUsers(): int
    {
        return (int) Database::scalar("SELECT COUNT(*) FROM sys_user WHERE deleted = 0");
    }

    public static function countOnlineUsers(): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*)
             FROM sys_login_log
             WHERE status = 1 AND login_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
    }

    public static function countRoles(): int
    {
        return (int) Database::scalar("SELECT COUNT(*) FROM sys_role WHERE deleted = 0");
    }

    public static function countMenus(): int
    {
        return (int) Database::scalar("SELECT COUNT(*) FROM sys_menu");
    }

    public static function countDepts(): int
    {
        return (int) Database::scalar("SELECT COUNT(*) FROM sys_dept WHERE deleted = 0");
    }

    public static function countApiCalls(): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_oper_log WHERE oper_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectLoginTrend(): array
    {
        return Database::all(
            "SELECT HOUR(login_time) AS hourValue, COUNT(*) AS total
             FROM sys_login_log
             WHERE login_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY HOUR(login_time)"
        );
    }

    public static function countUsersCreatedSince1d(): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_user WHERE deleted = 0 AND create_time >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );
    }

    public static function countOnlineUsers2h(): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_login_log WHERE status = 1 AND login_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)"
        );
    }

    public static function countRolesCreatedSince1d(): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_role WHERE deleted = 0 AND create_time >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );
    }

    public static function countApiCallsPrev24h(): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_oper_log WHERE oper_time >= DATE_SUB(NOW(), INTERVAL 48 HOUR) AND oper_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectRoleDistribution(): array
    {
        return Database::all(
            "SELECT r.data_scope AS label, COUNT(u.id) AS value
             FROM sys_role r
             LEFT JOIN sys_user_role ur ON r.id = ur.role_id
             LEFT JOIN sys_user u ON u.id = ur.user_id AND u.deleted = 0
             WHERE r.deleted = 0
             GROUP BY r.data_scope
             ORDER BY value DESC"
        );
    }

    public static function countApiCallsInHours(int $hours): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_oper_log WHERE oper_time >= DATE_SUB(NOW(), INTERVAL ? HOUR)",
            [$hours]
        );
    }

    public static function countApiCallsSuccessInHours(int $hours): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_oper_log WHERE oper_time >= DATE_SUB(NOW(), INTERVAL ? HOUR) AND result = 1",
            [$hours]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectLoginTrendByDay(int $days): array
    {
        return Database::all(
            "SELECT DATEDIFF(DATE(login_time), CURDATE() - INTERVAL (? - 1) DAY) AS dayIndex, COUNT(*) AS total
             FROM sys_login_log
             WHERE login_time >= CURDATE() - INTERVAL (? - 1) DAY
             GROUP BY dayIndex
             ORDER BY dayIndex",
            [$days, $days]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectActivities(): array
    {
        return Database::all(
            "SELECT username AS who,
                    CASE status WHEN 1 THEN '登录系统' ELSE '登录失败' END AS action,
                    COALESCE(msg, '登录') AS target,
                    DATE_FORMAT(login_time, '%Y-%m-%d %H:%i') AS time,
                    CASE status WHEN 1 THEN 'ok' ELSE 'danger' END AS tint
             FROM sys_login_log
             ORDER BY login_time DESC, id DESC
             LIMIT 8"
        );
    }

    /** 上一周期登录数（24 小时档）：48 小时前到 24 小时前 */
    public static function countLoginsPrev24h(): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM sys_login_log WHERE login_time >= DATE_SUB(NOW(), INTERVAL 48 HOUR) AND login_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
    }

    /** 上一周期登录数（按天档）：与当前 days 天窗口等长的前一段 */
    public static function countLoginsPrevDays(int $days): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*)
             FROM sys_login_log
             WHERE login_time >= CURDATE() - INTERVAL (? * 2 - 1) DAY
               AND login_time < CURDATE() - INTERVAL (? - 1) DAY",
            [$days, $days]
        );
    }

    public static function selectToday(): string
    {
        return (string) Database::scalar("SELECT DATE_FORMAT(CURDATE(), '%Y-%m-%d')");
    }

    /** 近 7 天按日操作量：dayIndex 0 为 6 天前，6 为今天 */
    public static function selectDailyOps(): array
    {
        return Database::all(
            "SELECT DATEDIFF(DATE(oper_time), CURDATE() - INTERVAL 6 DAY) AS dayIndex,
                    COUNT(*) AS total,
                    COALESCE(SUM(result = 0), 0) AS failed
             FROM sys_oper_log
             WHERE oper_time >= CURDATE() - INTERVAL 6 DAY
             GROUP BY dayIndex"
        );
    }
}
