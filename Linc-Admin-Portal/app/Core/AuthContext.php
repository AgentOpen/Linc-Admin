<?php

declare(strict_types=1);

namespace Linc\Core;

use Linc\Repository\AuthRepository;

/**
 * 当前会话上下文：由 AuthMiddleware 写入，业务层读取当前用户名。
 * 
 */
final class AuthContext
{
    private static ?string $username = null;
    private static ?string $token = null;

    public static function set(string $username, string $token): void
    {
        self::$username = $username;
        self::$token = $token;
    }

    public static function username(): ?string
    {
        return self::$username;
    }

    /** 操作人兜底「system」。/ */
    public static function operator(): string
    {
        return self::$username ?? 'system';
    }

    public static function token(): ?string
    {
        return self::$token;
    }

    /** 当前用户行（含部门名）；停用或不存在抛 401 业务异常。 */
    public static function currentUserRow(): array
    {
        $username = self::$username;
        if ($username === null) {
            throw new BusinessException('未认证或登录已过期', 401);
        }
        $user = AuthRepository::selectUserByUsername($username);
        if ($user === null || (int) ($user['status'] ?? 0) !== 1) {
            throw new BusinessException('未认证或登录已过期', 401);
        }
        return $user;
    }

    public static function clear(): void
    {
        self::$username = null;
        self::$token = null;
    }
}
