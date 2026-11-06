<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * 请求追踪 ID：
 * 优先取请求头 X-Trace-Id，否则生成 32 位无横线 UUID；响应时回写同值到 X-Trace-Id 头。
 */
final class TraceId
{
    private static ?string $current = null;

    public static function resolve(?string $headerValue): string
    {
        $traceId = $headerValue !== null && trim($headerValue) !== ''
            ? trim($headerValue)
            : str_replace('-', '', self::uuid4());
        self::$current = $traceId;
        return $traceId;
    }

    public static function current(): ?string
    {
        return self::$current;
    }

    public static function clear(): void
    {
        self::$current = null;
    }

    public static function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
