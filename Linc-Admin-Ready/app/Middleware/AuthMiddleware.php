<?php

declare(strict_types=1);

namespace Linc\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Linc\Core\AuthContext;
use Linc\Core\Config;
use Linc\Core\JwtCodec;
use Linc\Core\TokenStore;

/**
 * 认证中间件：
 * 1) 解析 Bearer token → 验签 → TokenStore 校验未吊销且用户名一致 → 写入会话上下文；
 * 2) 强制鉴权：/api/** 除公开路径外必须携带有效 token，否则 401「未认证或登录已过期」。
 * 公开路径：/api/health、/api/auth/captcha、/api/auth/login，以及 /api/install/**。
 */
final class AuthMiddleware implements MiddlewareInterface
{
    private const PUBLIC_PATHS = [
        '/api/health',
        '/api/auth/captcha',
        '/api/auth/login',
    ];

    public function process(Request $request, Handler $handler): Response
    {
        $path = $request->getUri()->getPath();
        $authorization = $request->getHeaderLine('Authorization');
        if ($authorization !== '' && str_starts_with($authorization, 'Bearer ')) {
            $token = substr($authorization, 7);
            $claims = JwtCodec::decode(Config::jwtSecret(), $token);
            $username = is_array($claims) ? ($claims['sub'] ?? null) : null;
            if (is_string($username) && $username !== '' && TokenStore::usernameOf($token) === $username) {
                AuthContext::set($username, $token);
                $request = $request->withAttribute('auth.username', $username);
            }
        }

        if ($this->isPublic($path) || AuthContext::username() !== null) {
            return $handler->handle($request);
        }

        // 未认证访问受保护接口，
        $response = (new \Slim\Psr7\Factory\ResponseFactory())->createResponse(401);
        $response->getBody()->write(
            (string) json_encode(
                \Linc\Core\R::error(401, '未认证或登录已过期'),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
        return $response->withHeader('Content-Type', 'application/json;charset=utf-8');
    }

    private function isPublic(string $path): bool
    {
        if (in_array($path, self::PUBLIC_PATHS, true)) {
            return true;
        }
        // 安装接口：仅未安装时开放；已安装时由控制器守卫返回 403
        if (str_starts_with($path, '/api/install/')) {
            return true;
        }
        return false;
    }
}
