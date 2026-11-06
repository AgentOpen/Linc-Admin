<?php

declare(strict_types=1);

namespace Linc\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use Linc\Core\BusinessException;
use Linc\Core\R;
use Throwable;

/**
 * 统一异常 → R 包络，
 * BusinessException 恒为 HTTP 400（body.code 为异常自带码）；404/405/500 文案一致；
 * 所有错误响应都带 traceId。
 */
final class ApiErrorHandler extends SlimErrorHandler
{
    protected function respond(): Response
    {
        $error = $this->exception;
        $httpStatus = 500;
        $bodyCode = 500;
        $message = '系统内部错误';

        if ($error instanceof BusinessException) {
           
            $httpStatus = 400;
            $bodyCode = $error->code();
            $message = $error->getMessage();
        } elseif ($error instanceof HttpNotFoundException) {
            $httpStatus = 404;
            $bodyCode = 404;
            $message = '资源不存在';
        } elseif ($error instanceof HttpMethodNotAllowedException) {
            $httpStatus = 405;
            $bodyCode = 405;
            $message = '不支持的请求方法: ' . ($this->request?->getMethod() ?? '-');
        } elseif ($error instanceof \JsonException) {
            $httpStatus = 400;
            $bodyCode = 400;
            $message = '请求体格式错误';
        }

        if ($httpStatus === 500) {
            self::writeErrorLog($error);
        }

        $payload = json_encode(
            R::error($bodyCode, $message),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $response = (new \Slim\Psr7\Factory\ResponseFactory())->createResponse($httpStatus);
        $response->getBody()->write((string) $payload);
        return $response->withHeader('Content-Type', 'application/json;charset=utf-8');
    }

    /** 未处理异常仅记录文件日志、不影响响应结构。 */
    private static function writeErrorLog(?Throwable $exception): void
    {
        $logFile = dirname(__DIR__, 2) . '/runtime/logs';
        if (!is_dir($logFile)) {
            @mkdir($logFile, 0775, true);
        }
        @file_put_contents(
            $logFile . '/error-' . date('Ymd') . '.log',
            sprintf(
                "[%s] %s %s\n%s\n\n",
                date('Y-m-d H:i:s'),
                $_SERVER['REQUEST_METHOD'] ?? '-',
                $_SERVER['REQUEST_URI'] ?? '-',
                (string) $exception
            ),
            FILE_APPEND
        );
    }
}
