<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * 登录验证码，
 * 4 位字符（ABCDEFGHIJKLMNOPQRSTUVWXYZ23456789，无易混淆字符），存缓存 300 秒；
 * 校验大小写不敏感且一次性消费；接口直接返回明文 code 供页面显示。
 */
final class Captcha
{
    private const KEY_PREFIX = 'captcha:';
    private const EXPIRE_SECONDS = 300;
    private const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** @return array{uuid: string, code: string} */
    public static function generate(): array
    {
        $uuid = str_replace('-', '', TraceId::uuid4());
        $chars = self::CHARS;
        $length = strlen($chars);
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[random_int(0, $length - 1)];
        }
        Cache::set(self::KEY_PREFIX . $uuid, $code, self::EXPIRE_SECONDS);
        return ['uuid' => $uuid, 'code' => $code];
    }

    public static function verify(?string $uuid, ?string $code): bool
    {
        if ($uuid === null || $code === null) {
            return false;
        }
        $key = self::KEY_PREFIX . $uuid;
        $stored = Cache::get($key);
        if ($stored === null) {
            return false;
        }
        Cache::delete($key);
        return strcasecmp($stored, $code) === 0;
    }
}
