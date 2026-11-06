<?php

declare(strict_types=1);

namespace Linc\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Linc\Service\DashboardService;

/**
 * 工作台接口。
 */
final class DashboardController
{
    public static function summary(Request $request, Response $response): Response
    {
        $range = $request->getQueryParams()['range'] ?? '24h';
        if (!is_string($range) || $range === '') {
            $range = '24h';
        }
        return AuthController::ok($response, DashboardService::summary($range));
    }
}
