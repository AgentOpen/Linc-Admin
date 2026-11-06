<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * JWT HS256 编解码，行为一致：
 * header {alg: HS256, typ: JWT}；claims {jti: uuid, sub: username, userId, iat, exp}。
 * 密钥为 UTF-8 字节、至少 32 字节；验签失败或过期返回 null。
 */
final class JwtCodec
{
    public static function encode(string $secret, int $userId, string $username, int $expiresSeconds): string
    {
        self::assertSecret($secret);
        $now = time();
        $payload = [
            'jti' => TraceId::uuid4(),
            'sub' => $username,
            'userId' => $userId,
            'iat' => $now,
            'exp' => $now + $expiresSeconds,
        ];
        $segments = [
            self::base64Url(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_UNESCAPED_SLASHES)),
            self::base64Url(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];
        $signature = self::sign($secret, implode('.', $segments));
        $segments[] = self::base64Url($signature);
        return implode('.', $segments);
    }

    /** 验签并返回 claims；无效返回 null（对应 parseUsername 的静默失败）。 */
    public static function decode(string $secret, string $token): ?array
    {
        self::assertSecret($secret);
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$head64, $body64, $sig64] = $parts;
        $expected = self::sign($secret, $head64 . '.' . $body64);
        $given = self::base64UrlDecode($sig64);
        if (!is_string($given) || !hash_equals($expected, $given)) {
            return null;
        }
        $header = json_decode(self::base64UrlDecode($head64) ?? '', true);
        if (!is_array($header) || (($header['alg'] ?? null) !== 'HS256')) {
            return null;
        }
        $claims = json_decode(self::base64UrlDecode($body64) ?? '', true);
        if (!is_array($claims)) {
            return null;
        }
        if (!isset($claims['exp']) || !is_numeric($claims['exp']) || (int) $claims['exp'] < time()) {
            return null;
        }
        return $claims;
    }

    private static function sign(string $secret, string $data): string
    {
        return hash_hmac('sha256', $data, $secret, true);
    }

    private static function assertSecret(string $secret): void
    {
        $bytes = strlen($secret);
        if ($secret === '' || trim($secret) === '' || $bytes < 32) {
            throw new BusinessException('JWT_SECRET 必须配置为至少 32 字节的非空白密钥');
        }
    }

    private static function base64Url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): ?string
    {
        $remainder = strlen($data) % 4;
        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }
}
