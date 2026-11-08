<?php

declare(strict_types=1);

namespace Linc\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Linc\Service\SkinService;

/**
 * 门户站点设置接口：皮肤、站点主色、SEO 文案（写入 sys_config，前台与 SEO 输出实时跟随）。
 */
final class SkinController
{
    public static function get(Request $request, Response $response): Response
    {
        return AuthController::ok($response, [
            'skin' => SkinService::current(),
            'primary' => SkinService::primary(),
            'seo' => SkinService::seo(),
        ]);
    }

    /** @param array<string,mixed> $args 路由占位（无） */
    public static function applyFromRoute(Request $request, Response $response, array $args): Response
    {
        $body = json_body((string) $request->getBody());
        $result = SkinService::applyAll($body);
        return AuthController::ok($response, $result);
    }
}
