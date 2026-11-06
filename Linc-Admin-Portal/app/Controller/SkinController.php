<?php

declare(strict_types=1);

namespace Linc\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Linc\Service\SkinService;

/**
 * 门户皮肤接口：主题中心把选择持久化到服务端，前台即时跟随。
 */
final class SkinController
{
    public static function get(Request $request, Response $response): Response
    {
        return AuthController::ok($response, ['skin' => SkinService::current()]);
    }

    public static function apply(Request $request, Response $response): Response
    {
        $body = json_body((string) $request->getBody());
        $skin = isset($body['skin']) && is_string($body['skin']) ? $body['skin'] : '';
        SkinService::apply($skin);
        return AuthController::ok($response, ['skin' => SkinService::current()]);
    }
}
