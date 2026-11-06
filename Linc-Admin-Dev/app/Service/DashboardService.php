<?php

declare(strict_types=1);

namespace Linc\Service;

use Linc\Repository\DashboardRepository;

/**
 * 工作台聚合。
 * 时间桶标签统一取数据库时钟。
 */
final class DashboardService
{
    /** @return array<string,mixed> */
    public static function summary(string $range): array
    {
        $hours = match ($range) {
            '7d' => 168,
            '30d' => 720,
            default => 24,
        };
        $data = [];
        $data['stats'] = self::stats();
        $data['trend'] = self::trend($hours);
        $data['trendPrev'] = $hours <= 24
            ? DashboardRepository::countLoginsPrev24h()
            : DashboardRepository::countLoginsPrevDays(intdiv($hours, 24));
        $data['dailyOps'] = self::dailyOps();
        $data['activities'] = DashboardRepository::selectActivities();
        $data['roleDist'] = DashboardRepository::selectRoleDistribution();

        $totalCalls = DashboardRepository::countApiCallsInHours($hours);
        $successCalls = DashboardRepository::countApiCallsSuccessInHours($hours);
        $data['trendTotal'] = $totalCalls;
        $data['successRate'] = $totalCalls > 0
            ? round($successCalls * 1000.0 / $totalCalls) / 10.0
            : 100.0;

        return $data;
    }

    /** 近 7 天按日操作量，缺失的日期补 0；日期取数据库当天，避免与会话时区错位 */
    private static function dailyOps(): array
    {
        $byDay = [];
        foreach (DashboardRepository::selectDailyOps() as $row) {
            if (isset($row['dayIndex']) && is_numeric($row['dayIndex'])) {
                $byDay[(int) $row['dayIndex']] = $row;
            }
        }
        $today = DashboardRepository::selectToday();
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $row = $byDay[$i] ?? null;
            $date = date('Y-m-d', strtotime($today) - (6 - $i) * 86400);
            $days[] = [
                'date' => $date,
                'total' => $row === null ? 0 : (int) $row['total'],
                'failed' => $row === null ? 0 : (int) $row['failed'],
            ];
        }
        return $days;
    }

    /** @return list<array<string,mixed>> */
    private static function stats(): array
    {
        $users = DashboardRepository::countUsers();
        $online = DashboardRepository::countOnlineUsers();
        $roles = DashboardRepository::countRoles();
        $apiCalls = DashboardRepository::countApiCalls();

        $usersNew = DashboardRepository::countUsersCreatedSince1d();
        $online2h = DashboardRepository::countOnlineUsers2h();
        $rolesNew = DashboardRepository::countRolesCreatedSince1d();
        $apiPrev = DashboardRepository::countApiCallsPrev24h();

        return [
            self::stat('users', '用户总数', $users, 'users', 'ok', ...self::delta($users, $usersNew)),
            self::stat('online', '在线用户', $online, 'monitor', 'info', ...self::delta($online, $online2h)),
            self::stat('roles', '角色数量', $roles, 'shield', 'warn', ...self::delta($roles, $rolesNew)),
            self::stat('api', '24h 接口调用', $apiCalls, 'command', 'purple', ...self::deltaCompare($apiCalls, $apiPrev)),
            self::stat('menus', '菜单项', DashboardRepository::countMenus(), 'menu', 'info', ...self::delta(1, 0)),
            self::stat('depts', '部门数', DashboardRepository::countDepts(), 'dept', 'ok', ...self::delta(1, 0)),
        ];
    }

    /** @return array<string,mixed> */
    private static function stat(string $key, string $label, int $value, string $icon, string $tone, float $delta, float $up, float $fresh = 0.0): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'icon' => $icon,
            'tone' => $tone,
            'delta' => $delta,
            'up' => $up > 0,
            'fresh' => $fresh > 0,
            'sub' => self::subText($key),
        ];
    }

    /** @return array{0: float, 1: float} */
    private static function delta(int $total, int $recent): array
    {
        if ($total === 0) {
            return [0.0, 0.0];
        }
        $pct = round($recent * 1000.0 / max($total, 1)) / 10.0;
        return [$pct, 1.0];
    }

    /** @return array{0: float, 1: float, 2: float} */
    private static function deltaCompare(int $current, int $previous): array
    {
        // 上一周期为 0 时无法计算环比，标记为「新增」（第三位），前端不再显示误导的 +100%
        if ($previous === 0) {
            return [0.0, $current > 0 ? 1.0 : 0.0, $current > 0 ? 1.0 : 0.0];
        }
        $pct = round(($current - $previous) * 1000.0 / $previous) / 10.0;
        return [abs($pct), $pct >= 0 ? 1.0 : -1.0];
    }

    private static function subText(string $key): string
    {
        return match ($key) {
            'users' => '当前启用账号',
            'online' => '最近 1 小时登录',
            'roles' => '已启用角色',
            'api' => '最近 24 小时操作',
            'menus' => '目录 / 菜单 / 按钮',
            'depts' => '未删除部门',
            default => '',
        };
    }

    /** @return list<int> */
    private static function trend(int $hours): array
    {
        $buckets = min($hours, 24);
        $totalsByBucket = [];

        if ($hours <= 24) {
            foreach (DashboardRepository::selectLoginTrend() as $row) {
                if (isset($row['hourValue'], $row['total']) && is_numeric($row['hourValue'])) {
                    $totalsByBucket[(int) $row['hourValue']] = (int) $row['total'];
                }
            }
            $values = [];
            // 小时标签取数据库当前时刻
            $nowHour = (int) date('G', strtotime((string) \Linc\Repository\MonitorStatsRepository::selectDbNow()));
            for ($i = 23; $i >= 0; $i--) {
                $hour = ($nowHour - $i + 24) % 24;
                $values[] = $totalsByBucket[$hour] ?? 0;
            }
            return $values;
        }

        $days = intdiv($hours, 24);
        foreach (DashboardRepository::selectLoginTrendByDay($days) as $row) {
            if (isset($row['dayIndex'], $row['total']) && is_numeric($row['dayIndex'])) {
                $totalsByBucket[(int) $row['dayIndex']] = (int) $row['total'];
            }
        }
        $values = [];
        for ($i = 0; $i < $days; $i++) {
            $values[] = $totalsByBucket[$i] ?? 0;
        }
        return $values;
    }
}
