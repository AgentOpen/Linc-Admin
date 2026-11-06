<?php

declare(strict_types=1);

namespace Linc\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 健康检查：{status: "UP", time: ISO 时间}。
 */
final class HealthController
{
    public static function health(Request $request, Response $response): Response
    {
        return AuthController::ok($response, [
            'status' => 'UP',
            'time' => date('Y-m-d\TH:i:s') . self::offset(),
        ]);
    }

    private static function offset(): string
    {
        $seconds = (int) date('Z');
        $sign = $seconds >= 0 ? '+' : '-';
        $abs = abs($seconds);
        return sprintf('%s%02d:%02d', $sign, intdiv($abs, 3600), intdiv($abs % 3600, 60));
    }
}
