<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * 统一 API 响应模型。
 *
 * JSON 结构：{code, message, data, traceId}；成功 code 固定 200、message 固定「操作成功」。
 */
final class R
{
    public const SUCCESS_CODE = 200;
    public const SUCCESS_MESSAGE = '操作成功';

    /**
     * @param mixed $data 业务数据
     * @return array{code: int, message: string, data: mixed, traceId: ?string}
     */
    public static function success(mixed $data = null): array
    {
        return [
            'code' => self::SUCCESS_CODE,
            'message' => self::SUCCESS_MESSAGE,
            'data' => $data,
            'traceId' => TraceId::current(),
        ];
    }

    /**
     * @return array{code: int, message: string, data: null, traceId: ?string}
     */
    public static function error(int $code, string $message): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => null,
            'traceId' => TraceId::current(),
        ];
    }
}
