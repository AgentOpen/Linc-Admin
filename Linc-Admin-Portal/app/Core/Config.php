<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * 配置装载：安装器生成的 config/config.php 返回数组；未安装时为空配置。
 *
 * 支持环境变量覆盖（DB_HOST、REDIS_HOST、JWT_SECRET 等），便于容器化部署。
 */
final class Config
{
    private static ?array $values = null;

    public static function load(): array
    {
        if (self::$values !== null) {
            return self::$values;
        }
        $file = self::configFile();
        $values = is_file($file) ? (require $file) : [];
        if (!is_array($values)) {
            $values = [];
        }
        self::$values = $values;
        return self::$values;
    }

    public static function configFile(): string
    {
        return dirname(__DIR__, 2) . '/config/config.php';
    }

    public static function installed(): bool
    {
        return is_file(dirname(__DIR__, 2) . '/runtime/install.lock');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $values = self::load();
        $current = $values;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }
        return $current;
    }

    public static function database(): array
    {
        $configured = self::get('database', []);
        return [
            'host' => (string) ($configured['host'] ?? getenv('DB_HOST') ?: 'localhost'),
            'port' => (int) ($configured['port'] ?? getenv('DB_PORT') ?: 3306),
            'name' => (string) ($configured['name'] ?? getenv('DB_NAME') ?: 'linc_admin'),
            'username' => (string) ($configured['username'] ?? getenv('DB_USERNAME') ?: 'root'),
            'password' => (string) ($configured['password'] ?? getenv('DB_PASSWORD') ?: ''),
            'timezone' => (string) ($configured['timezone'] ?? getenv('DB_TIMEZONE') ?: '+08:00'),
        ];
    }

    public static function jwtSecret(): string
    {
        $secret = self::get('jwt.secret');
        if (is_string($secret) && $secret !== '' && strlen($secret) >= 32) {
            return $secret;
        }
        $secret = getenv('JWT_SECRET');
        if (is_string($secret) && $secret !== '' && strlen($secret) >= 32) {
            return $secret;
        }
        throw new BusinessException('JWT_SECRET 必须配置为至少 32 字节的非空白密钥');
    }

    public static function jwtExpiresSeconds(): int
    {
        return (int) (self::get('jwt.expires_seconds') ?? getenv('JWT_EXPIRES_SECONDS') ?: 7200);
    }

    public static function cacheDriver(): string
    {
        $driver = self::get('cache.driver');
        if (!is_string($driver) || $driver === '') {
            $driver = getenv('CACHE_DRIVER');
        }
        return $driver === 'redis' ? 'redis' : 'file';
    }
}
