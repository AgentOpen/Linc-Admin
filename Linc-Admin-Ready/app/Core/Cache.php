<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * 键值缓存：默认文件驱动（runtime/cache，flock 保证原子性），可切换 Redis（Predis）。
 *
 * 语义：set 带 TTL 秒；get 过期即无；delete 即删。
 */
final class Cache
{
    private static ?object $redis = null;

    public static function set(string $key, string $value, int $ttlSeconds): void
    {
        if (self::driver() === 'redis') {
            self::redis()->setex($key, max(1, $ttlSeconds), $value);
            return;
        }
        $file = self::fileOf($key);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $payload = json_encode(['e' => time() + $ttlSeconds, 'v' => $value], JSON_UNESCAPED_UNICODE);
        file_put_contents($file, (string) $payload, LOCK_EX);
    }

    public static function get(string $key): ?string
    {
        if (self::driver() === 'redis') {
            $value = self::redis()->get($key);
            return is_string($value) ? $value : null;
        }
        $file = self::fileOf($key);
        if (!is_file($file)) {
            return null;
        }
        $raw = (string) file_get_contents($file);
        $payload = json_decode($raw, true);
        if (!is_array($payload) || !isset($payload['e'], $payload['v']) || !is_int($payload['e'])) {
            @unlink($file);
            return null;
        }
        if ($payload['e'] < time()) {
            @unlink($file);
            return null;
        }
        return is_string($payload['v']) ? $payload['v'] : null;
    }

    public static function delete(string $key): void
    {
        if (self::driver() === 'redis') {
            self::redis()->del($key);
            return;
        }
        @unlink(self::fileOf($key));
    }

    private static function driver(): string
    {
        return Config::cacheDriver();
    }

    private static function fileOf(string $key): string
    {
        $safe = str_replace([':', '/', '\\'], ['_', '_', '_'], $key);
        return dirname(__DIR__, 2) . '/runtime/cache/' . $safe . '.json';
    }

    private static function redis(): object
    {
        if (self::$redis !== null) {
            return self::$redis;
        }
        $conf = Config::get('cache.redis', []);
        $conf = is_array($conf) ? $conf : [];
        $client = new \Predis\Client([
            'scheme' => 'tcp',
            'host' => (string) ($conf['host'] ?? getenv('REDIS_HOST') ?: 'localhost'),
            'port' => (int) ($conf['port'] ?? getenv('REDIS_PORT') ?: 6379),
            'password' => (string) ($conf['password'] ?? getenv('REDIS_PASSWORD') ?: '') ?: null,
        ], ['exceptions' => true]);
        $client->connect();
        self::$redis = $client;
        return $client;
    }
}
