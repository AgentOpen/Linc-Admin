<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * 登录 token 存储，
 * 键 auth:token:{jwt} = username，TTL 为 token 有效期；退出登录删除当前 token。
 */
final class TokenStore
{
    private const TOKEN_KEY_PREFIX = 'auth:token:';

    public static function store(string $token, string $username, int $expiresSeconds): void
    {
        Cache::set(self::key($token), $username, $expiresSeconds);
    }

    public static function usernameOf(string $token): ?string
    {
        return Cache::get(self::key($token));
    }

    public static function revoke(string $token): void
    {
        Cache::delete(self::key($token));
    }

    private static function key(string $token): string
    {
        return self::TOKEN_KEY_PREFIX . $token;
    }
}
