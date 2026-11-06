<?php

declare(strict_types=1);

use Linc\Core\StringUtil;

if (!function_exists('trim_to_null')) {
    /** 去除首尾空白；空白串返回 null。 */
    function trim_to_null(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }
}

if (!function_exists('required')) {
    /** 必填校验，为空抛出业务异常。 */
    function required(?string $value, string $message): string
    {
        $trimmed = trim_to_null($value);
        if ($trimmed === null) {
            throw new Linc\Core\BusinessException($message);
        }
        return $trimmed;
    }
}

if (!function_exists('default_string')) {
    /** 空串回退默认值。 */
    function default_string(?string $value, string $defaultValue): string
    {
        $trimmed = trim_to_null($value);
        return $trimmed === null ? $defaultValue : $trimmed;
    }
}

if (!function_exists('default_int')) {
    /** 空值回退默认整数。 */
    function default_int(mixed $value, int $defaultValue): int
    {
        return $value === null ? $defaultValue : (int) $value;
    }
}

if (!function_exists('bool_as_int')) {
    /** 布尔转 0/1；null 视为 false。 */
    function bool_as_int(?bool $value): int
    {
        return $value === true ? 1 : 0;
    }
}

if (!function_exists('number_as_long')) {
    /** 数值归一为整数。 */
    function number_as_long(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }
        return 0;
    }
}

if (!function_exists('json_body')) {
    /** 解析 JSON 请求体；格式错误抛出 400「请求体格式错误」。 */
    function json_body(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new Linc\Core\BusinessException('请求体格式错误');
        }
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('client_ip')) {
    /** 客户端 IP：X-Forwarded-For 首段 > X-Real-IP > REMOTE_ADDR。 */
    function client_ip(): ?string
    {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        if (is_string($forwarded) && trim($forwarded) !== '') {
            $first = trim(explode(',', $forwarded)[0]);
            if ($first !== '') {
                return $first;
            }
        }
        $realIp = $_SERVER['HTTP_X_REAL_IP'] ?? null;
        if (is_string($realIp) && trim($realIp) !== '') {
            return trim($realIp);
        }
        return isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : null;
    }
}

if (!function_exists('location_of_ip')) {
    /** IP 归属地文案。 */
    function location_of_ip(?string $ip): ?string
    {
        if ($ip === null || trim($ip) === '') {
            return null;
        }
        if ($ip === '127.0.0.1' || $ip === '0:0:0:0:0:0:0:1' || $ip === '::1') {
            return '本机';
        }
        return '内网';
    }
}

if (!function_exists('php_timezone_for_offset')) {
    /**
     * MySQL 时区偏移（如 +08:00）→ PHP 时区标识。
     * Etc/GMT 系列符号与惯用方向相反：UTC+8 对应 Etc/GMT-8；
     * 非整小时偏移退回 UTC（此时所有时间标注仍以数据库时钟字符串为准）。
     */
    function php_timezone_for_offset(string $mysqlOffset): string
    {
        if (preg_match('/^([+-])(\d{1,2}):?(\d{2})?$/', trim($mysqlOffset), $m) === 1) {
            $minutes = ((int) $m[2]) * 60 + (int) ($m[3] ?? '0');
            if ($m[1] === '-') {
                $minutes = -$minutes;
            }
            if ($minutes % 60 === 0) {
                $hours = intdiv($minutes, 60);
                if ($hours === 0) {
                    return 'UTC';
                }
                return $hours > 0 ? 'Etc/GMT-' . $hours : 'Etc/GMT+' . (-$hours);
            }
        }
        return 'UTC';
    }
}
