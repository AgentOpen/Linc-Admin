<?php

declare(strict_types=1);

namespace Linc\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Linc\Core\AuthContext;
use Linc\Core\BusinessException;
use Linc\Core\Captcha;
use Linc\Core\PageQuery;
use Linc\Core\R;
use Linc\Service\AuthService;

/**
 * 认证接口。
 */
final class AuthController
{
    public static function captcha(Request $request, Response $response): Response
    {
        return self::json($response, R::success(Captcha::generate()));
    }

    public static function login(Request $request, Response $response): Response
    {
        $body = json_body((string) $request->getBody());
       
        foreach (['username', 'password'] as $field) {
            $value = $body[$field] ?? null;
            if (!is_string($value) || trim($value) === '') {
                throw new BusinessException($field . ' 不能为空');
            }
        }
        if (!Captcha::verify(
            isset($body['captchaUuid']) && is_string($body['captchaUuid']) ? $body['captchaUuid'] : null,
            isset($body['captcha']) && is_string($body['captcha']) ? $body['captcha'] : null
        )) {
            throw new BusinessException('验证码错误或已过期');
        }
        return self::json($response, R::success(AuthService::login($body)));
    }

    public static function logout(Request $request, Response $response): Response
    {
        AuthService::logout();
        return self::json($response, R::success(null));
    }

    public static function me(Request $request, Response $response): Response
    {
        return self::json($response, R::success(AuthService::currentUser()));
    }

    public static function routes(Request $request, Response $response): Response
    {
        return self::json($response, R::success(AuthService::routes()));
    }

    public static function changePassword(Request $request, Response $response): Response
    {
        $body = json_body((string) $request->getBody());
        $old = $body['oldPassword'] ?? null;
        if (!is_string($old) || trim($old) === '') {
            throw new BusinessException('oldPassword 不能为空');
        }
        $new = $body['newPassword'] ?? null;
        if (!is_string($new) || trim($new) === '') {
            throw new BusinessException('newPassword 不能为空');
        }
        if (strlen($new) < 6 || strlen($new) > 64) {
            throw new BusinessException('newPassword 长度必须在6到64位之间');
        }
        AuthService::changePassword($body);
        return self::json($response, R::success(null));
    }

    /* ── 共用输出 ─────────────────────────────────────────── */

    /** @param array<string,mixed> $payload */
    public static function json(Response $response, array $payload, int $status = 200): Response
    {
        $response->getBody()->write(
            (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json;charset=utf-8');
    }

    public static function ok(Response $response, mixed $data = null): Response
    {
        return self::json($response, R::success($data));
    }

    public static function pageQuery(Request $request): PageQuery
    {
        return PageQuery::fromQuery($request->getQueryParams());
    }

    /** 路径参数转整数；非数字抛出「参数类型错误: id」。 */
    public static function pathId(array $args, string $name = 'id'): int
    {
        $value = $args[$name] ?? '';
        if (!is_string($value) || preg_match('/^-?\d+$/', $value) !== 1) {
            throw new BusinessException('参数类型错误: ' . $name);
        }
        return (int) $value;
    }

    /** 路径参数转字符串（字典类型等）。 */
    public static function pathString(array $args, string $name): string
    {
        $value = $args[$name] ?? '';
        if (!is_string($value) || $value === '') {
            throw new BusinessException('参数类型错误: ' . $name);
        }
        return $value;
    }

    /** CSV 下载响应，统一 CSV 下载头。 */
    public static function csv(Response $response, string $filename, string $content): Response
    {
        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'text/csv;charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /** 当前操作人兜底（业务服务已内置 operator()）。 */
    public static function operator(): string
    {
        return AuthContext::operator();
    }
}
