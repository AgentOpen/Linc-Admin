<?php

declare(strict_types=1);

namespace Linc\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Linc\Core\TraceId;

/**
 * 请求追踪中间件：
 * 生成/接收 X-Trace-Id，写回响应头，并供 R 包络携带 traceId。
 */
final class TraceIdMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        $traceId = TraceId::resolve($request->getHeaderLine('X-Trace-Id') ?: null);
        $response = $handler->handle($request);
        return $response->withHeader('X-Trace-Id', $traceId);
    }
}
