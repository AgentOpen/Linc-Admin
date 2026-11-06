<?php

declare(strict_types=1);

namespace Linc\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Linc\Service\GeneratorService;

/**
 * 代码生成接口。
 */
final class GeneratorController
{
    public static function tables(Request $request, Response $response): Response
    {
        $keyword = $request->getQueryParams()['keyword'] ?? null;
        return AuthController::ok($response, GeneratorService::tables(
            AuthController::pageQuery($request),
            is_string($keyword) && $keyword !== '' ? $keyword : null
        ));
    }

    public static function importableTables(Request $request, Response $response): Response
    {
        $keyword = $request->getQueryParams()['keyword'] ?? null;
        return AuthController::ok($response, GeneratorService::importableTables(
            is_string($keyword) && $keyword !== '' ? $keyword : null
        ));
    }

    public static function import(Request $request, Response $response): Response
    {
        $body = json_body((string) $request->getBody());
        return AuthController::ok($response, GeneratorService::importTables(
            isset($body['tableNames']) && is_array($body['tableNames']) ? $body['tableNames'] : null
        ));
    }

    public static function columns(Request $request, Response $response, array $args): Response
    {
        return AuthController::ok($response, GeneratorService::columns(AuthController::pathId($args)));
    }

    public static function preview(Request $request, Response $response, array $args): Response
    {
        return AuthController::ok($response, GeneratorService::preview(AuthController::pathId($args)));
    }

    public static function update(Request $request, Response $response, array $args): Response
    {
        GeneratorService::updateTable(AuthController::pathId($args), json_body((string) $request->getBody()));
        return AuthController::ok($response);
    }

    public static function generate(Request $request, Response $response, array $args): Response
    {
        return AuthController::ok($response, GeneratorService::generate(AuthController::pathId($args)));
    }

    public static function download(Request $request, Response $response, array $args): Response
    {
        $id = AuthController::pathId($args);
        $preview = GeneratorService::preview($id);
        $activeFile = (string) $preview['activeFile'];
        $className = str_ends_with($activeFile, 'Controller.php')
            ? substr($activeFile, 0, strlen($activeFile) - strlen('Controller.php'))
            : 'code';
        $response->getBody()->write(GeneratorService::download($id));
        return $response
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $className . '.zip"');
    }
}
