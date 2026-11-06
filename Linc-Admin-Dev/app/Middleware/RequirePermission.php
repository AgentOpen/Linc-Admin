<?php

declare(strict_types=1);

namespace Linc\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Linc\Core\AuthContext;
use Linc\Core\BusinessException;
use Linc\Service\PermissionService;

/**
 * 权限校验路由中间件。
 */
final class RequirePermission implements MiddlewareInterface
{
    public function __construct(private readonly string $permission)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $username = AuthContext::username();
        if ($username === null || !PermissionService::hasPermission($username, $this->permission)) {
            throw new BusinessException('没有访问权限', 403);
        }
        return $handler->handle($request);
    }
}
