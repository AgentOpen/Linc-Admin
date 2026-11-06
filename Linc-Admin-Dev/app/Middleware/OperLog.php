<?php

declare(strict_types=1);

namespace Linc\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Linc\Core\AuthContext;
use Linc\Repository\OperationLogRepository;

/**
 * 操作日志路由中间件。
 */
final class OperLog implements MiddlewareInterface
{
    private const MAX_PARAMS_LENGTH = 4000;
    private const MAX_ERROR_LENGTH = 4000;

    public function __construct(
        private readonly string $module,
        private readonly string $action,
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $start = microtime(true);
        $succeeded = true;
        $errorMessage = null;
        try {
            return $handler->handle($request);
        } catch (\Throwable $error) {
            $succeeded = false;
            $errorMessage = $error->getMessage();
            throw $error;
        } finally {
            $cost = (int) round((microtime(true) - $start) * 1000);
            try {
                OperationLogRepository::record(
                    module: $this->module,
                    action: $this->action,
                    requestMethod: $request->getMethod(),
                    requestUrl: $request->getUri()->getPath() . (
                        $request->getUri()->getQuery() !== '' ? '?' . $request->getUri()->getQuery() : ''
                    ),
                    requestParams: self::maskParams($request),
                    operator: AuthContext::username(),
                    ip: client_ip(),
                    cost: $cost,
                    result: $succeeded ? 1 : 0,
                    errorMessage: $errorMessage !== null
                        ? mb_substr($errorMessage, 0, self::MAX_ERROR_LENGTH)
                        : null
                );
            } catch (\Throwable) {
                // 日志写入失败不影响业务响应
            }
        }
    }

    /**
     * 参数数组：路径参数在前、JSON 请求体在后，如 [1,{…}]。
     */
    private static function maskParams(Request $request): ?string
    {
        $args = [];
        $route = $request->getAttribute('__route__');
        if ($route instanceof \Slim\Interfaces\RouteInterface) {
            foreach ($route->getArguments() as $value) {
                $args[] = is_numeric($value) ? (int) $value : $value;
            }
        }
        $raw = (string) $request->getBody();
        if (trim($raw) !== '') {
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                $args[] = $decoded;
            } catch (\JsonException) {
                $args[] = null;
            }
        }
        self::maskNode($args);
        $encoded = json_encode($args, JSON_UNESCAPED_UNICODE);
        if ($encoded === false || mb_strlen($encoded) > self::MAX_PARAMS_LENGTH) {
            $encoded = $encoded === false ? '[]' : mb_substr($encoded, 0, self::MAX_PARAMS_LENGTH);
        }
        return $encoded;
    }

    private static function maskNode(array &$node): void
    {
        foreach ($node as $key => &$value) {
            if (is_array($value)) {
                self::maskNode($value);
                continue;
            }
            if (is_string($key) && self::isSensitive($key)) {
                $value = '******';
            }
        }
        unset($value);
    }

    private static function isSensitive(string $field): bool
    {
        $lower = strtolower($field);
        return str_contains($lower, 'password')
            || str_contains($lower, 'token')
            || str_contains($lower, 'secret')
            || str_contains($lower, 'authorization');
    }
}
