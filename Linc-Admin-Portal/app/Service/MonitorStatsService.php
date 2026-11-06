<?php

declare(strict_types=1);

namespace Linc\Service;

use Linc\Repository\MonitorStatsRepository;

/**
 * 监控日志只读统计，
 * 概览读数、按时间桶趋势、当日按模块泳道（15 分钟合并块）、登录热力与失败聚合告警。
 */
final class MonitorStatsService
{
    /** 同一模块两次操作间隔不超过 15 分钟时合并为一个时间块 */
    public const MERGE_GAP_SECONDS = 15 * 60;
    /** 每个时间块最多回传的日志 ID 数，供前端定位明细 */
    public const BLOCK_ID_LIMIT = 50;
    /** 登录失败达到该次数即列为异常，可由参数 sys.login.alertThreshold 覆盖 */
    public const DEFAULT_ALERT_THRESHOLD = 5;

    /** @return array<string,mixed> */
    public static function operStats(string $range): array
    {
        $window = self::window($range);
        $overview = MonitorStatsRepository::selectOperOverview($window['since']);
        $total = (int) ($overview['total'] ?? 0);
        $failed = (int) ($overview['failed'] ?? 0);
        $costCount = (int) ($overview['costCount'] ?? 0);

        $p95Cost = 0;
        if ($costCount > 0) {
            $offset = (int) ceil($costCount * 0.95) - 1;
            $p95Cost = MonitorStatsRepository::selectOperCostAt($window['since'], $offset) ?? 0;
        }

        return [
            'range' => $window['range'],
            'total' => $total,
            'failed' => $failed,
            'successRate' => self::successRate($total, $failed),
            'avgCost' => (int) ($overview['avgCost'] ?? 0),
            'p95Cost' => $p95Cost,
            'activeUsers' => (int) ($overview['activeUsers'] ?? 0),
            'series' => self::series(
                $window,
                $window['hourly']
                    ? MonitorStatsRepository::selectOperHourly($window['since'], $window['now'])
                    : MonitorStatsRepository::selectOperDaily($window['since'], $window['now'])
            ),
        ];
    }

    /** @return array<string,mixed> */
    public static function loginStats(string $range): array
    {
        $window = self::window($range);
        $overview = MonitorStatsRepository::selectLoginOverview($window['since']);
        $total = (int) ($overview['total'] ?? 0);
        $failed = (int) ($overview['failed'] ?? 0);

        return [
            'range' => $window['range'],
            'total' => $total,
            'failed' => $failed,
            'successRate' => self::successRate($total, $failed),
            'users' => (int) ($overview['users'] ?? 0),
            'ips' => (int) ($overview['ips'] ?? 0),
            'series' => self::series(
                $window,
                $window['hourly']
                    ? MonitorStatsRepository::selectLoginHourly($window['since'], $window['now'])
                    : MonitorStatsRepository::selectLoginDaily($window['since'], $window['now'])
            ),
        ];
    }

    /**
     * 登录热力：最近 days 天按「星期 × 小时」聚合，cells 只回传有数据的格子；
     * anomalies 为同一 IP / 同一账号失败次数达到阈值的聚合告警。
     * @return array<string,mixed>
     */
    public static function loginHeatmap(int $days): array
    {
        $span = $days >= 30 ? 30 : 7;
        $dbNow = MonitorStatsRepository::selectDbNow();
        $since = date('Y-m-d 00:00:00', strtotime($dbNow) - ($span - 1) * 86400);
        $threshold = self::DEFAULT_ALERT_THRESHOLD;
        $configured = MonitorStatsRepository::selectLoginAlertThreshold();
        if ($configured !== null && trim($configured) !== '' && is_numeric(trim($configured))) {
            $threshold = max(1, (int) trim($configured));
        }

        $cells = [];
        foreach (MonitorStatsRepository::selectLoginHeatmap($since) as $row) {
            $cells[] = [
                'dow' => (int) ($row['dow'] ?? 0),
                'hour' => (int) ($row['hour'] ?? 0),
                'total' => (int) ($row['total'] ?? 0),
                'failed' => (int) ($row['failed'] ?? 0),
            ];
        }
        $anomalies = [];
        foreach (MonitorStatsRepository::selectLoginIpAnomalies($since, $threshold) as $row) {
            $anomalies[] = self::anomaly('ip', $row);
        }
        foreach (MonitorStatsRepository::selectLoginUserAnomalies($since, $threshold) as $row) {
            $anomalies[] = self::anomaly('user', $row);
        }
        usort($anomalies, static fn (array $a, array $b): int => $b['failed'] <=> $a['failed']);

        return [
            'days' => $span,
            'since' => $since,
            'threshold' => $threshold,
            'cells' => $cells,
            'anomalies' => $anomalies,
        ];
    }

    /** @return array<string,mixed> */
    private static function anomaly(string $kind, array $row): array
    {
        return [
            'kind' => $kind,
            'key' => (string) ($row['key'] ?? ''),
            'failed' => (int) ($row['failed'] ?? 0),
            'total' => (int) ($row['total'] ?? 0),
            'spread' => (int) ($row['users'] ?? 0),
            'firstTime' => $row['firstTime'] ?? null,
            'lastTime' => $row['lastTime'] ?? null,
        ];
    }

    /**
     * 当日泳道：按模块分道，同模块相邻操作间隔不超过 15 分钟合为一块。
     * 时间以当日秒数表示（0–86400），前端按比例落在 00:00–24:00 横轴上。
     * @return array<string,mixed>
     */
    public static function operTimeline(?string $date): array
    {
        $dbNow = MonitorStatsRepository::selectDbNow();
        $day = $date !== null && $date !== '' ? $date : substr($dbNow, 0, 10);
        $dayStart = $day . ' 00:00:00';

        $lanes = [];
        $open = [];
        foreach (MonitorStatsRepository::selectOperPointsOfDay($dayStart) as $point) {
            $module = (string) $point['module'];
            $sec = (int) $point['sec'];
            if (!isset($open[$module]) || $sec - $open[$module]['endSec'] > self::MERGE_GAP_SECONDS) {
                $block = self::newBlock($sec);
                $lanes[$module] ??= [];
                $lanes[$module][] = &$block;
                $open[$module] = &$block;
                unset($block);
            }
            self::absorb($open[$module], $point, $sec);
        }

        $laneRows = [];
        foreach ($lanes as $module => $blocks) {
            $count = 0;
            foreach ($blocks as $block) {
                $count += (int) $block['count'];
            }
            $laneRows[] = [
                'module' => $module,
                'count' => $count,
                'blocks' => $blocks,
            ];
        }
        usort($laneRows, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return [
            'date' => $day,
            'nowSec' => $day === substr($dbNow, 0, 10)
                ? (int) (strtotime($dbNow) - strtotime($dayStart))
                : null,
            'lanes' => $laneRows,
        ];
    }

    /** @return array<string,mixed> */
    private static function newBlock(int $sec): array
    {
        return [
            'startSec' => $sec,
            'endSec' => $sec,
            'count' => 0,
            'failed' => 0,
            'maxCost' => 0,
            'ids' => [],
        ];
    }

    private static function absorb(array &$block, array $point, int $sec): void
    {
        $block['endSec'] = $sec;
        $block['count'] = (int) $block['count'] + 1;
        if ((int) $point['result'] === 0) {
            $block['failed'] = (int) $block['failed'] + 1;
        }
        $block['maxCost'] = max((int) $block['maxCost'], (int) ($point['cost'] ?? 0));
        if (count($block['ids']) < self::BLOCK_ID_LIMIT) {
            $block['ids'][] = (int) $point['id'];
        }
    }

    /** @return list<array<string,mixed>> */
    private static function series(array $window, array $rows): array
    {
        $byAgo = [];
        foreach ($rows as $row) {
            $byAgo[(int) $row['ago']] = $row;
        }
        $nowTime = strtotime($window['now']);
        $result = [];
        for ($i = 0; $i < $window['buckets']; $i++) {
            $ago = $window['buckets'] - 1 - $i;
            $row = $byAgo[$ago] ?? null;
            $bucket = $window['hourly']
                ? date('H:00', $nowTime - $ago * 3600)
                : date('m-d', $nowTime - $ago * 86400);
            $result[] = [
                'bucket' => $bucket,
                'total' => $row === null ? 0 : (int) $row['total'],
                'failed' => $row === null ? 0 : (int) $row['failed'],
            ];
        }
        return $result;
    }

    /** @return array{range: string, hourly: bool, buckets: int, now: string, since: string} */
    private static function window(string $range): array
    {
        $now = MonitorStatsRepository::selectDbNow();
        $nowTs = strtotime($now);
        switch ($range) {
            case '7d':
                return [
                    'range' => '7d',
                    'hourly' => false,
                    'buckets' => 7,
                    'now' => $now,
                    'since' => date('Y-m-d 00:00:00', $nowTs - 6 * 86400),
                ];
            case '30d':
                return [
                    'range' => '30d',
                    'hourly' => false,
                    'buckets' => 30,
                    'now' => $now,
                    'since' => date('Y-m-d 00:00:00', $nowTs - 29 * 86400),
                ];
            default:
                return [
                    'range' => '24h',
                    'hourly' => true,
                    'buckets' => 24,
                    'now' => $now,
                    'since' => date('Y-m-d H:i:s', $nowTs - 24 * 3600),
                ];
        }
    }

    private static function successRate(int $total, int $failed): float
    {
        return $total > 0 ? round(($total - $failed) * 1000.0 / $total) / 10.0 : 100.0;
    }
}
