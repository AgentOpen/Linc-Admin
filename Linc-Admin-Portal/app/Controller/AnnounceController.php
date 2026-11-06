<?php

declare(strict_types=1);

namespace Linc\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Linc\Service\AnnounceService;

/**
 * 公告管理接口：门户前台的内容源（前后台联动）。
 */
final class AnnounceController
{
    public static function index(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        $keyword = isset($q['keyword']) && is_string($q['keyword']) && $q['keyword'] !== '' ? $q['keyword'] : null;
        $status = isset($q['status']) && is_numeric($q['status']) ? (int) $q['status'] : null;
        return AuthController::ok($response, AnnounceService::page(AuthController::pageQuery($request), $keyword, $status));
    }

    public static function create(Request $request, Response $response): Response
    {
        return AuthController::ok($response, AnnounceService::create(json_body((string) $request->getBody())));
    }

    public static function update(Request $request, Response $response, array $args): Response
    {
        AnnounceService::update(AuthController::pathId($args), json_body((string) $request->getBody()));
        return AuthController::ok($response);
    }

    public static function remove(Request $request, Response $response, array $args): Response
    {
        AnnounceService::remove(AuthController::pathId($args));
        return AuthController::ok($response);
    }
}
