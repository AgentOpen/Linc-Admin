<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * 字符串与命名工具。
 */
final class StringUtil
{
    public static function decapitalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }
        return mb_strtolower(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }

    public static function upperCamel(?string $value): ?string
    {
        $lower = self::lowerCamel($value);
        if ($lower === null || $lower === '') {
            return $lower;
        }
        return mb_strtoupper(mb_substr($lower, 0, 1)) . mb_substr($lower, 1);
    }

    public static function lowerCamel(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }
        $builder = '';
        foreach (explode('_', strtolower($value)) as $part) {
            if (trim($part) === '') {
                continue;
            }
            $builder = $builder === ''
                ? $part
                : $builder . mb_strtoupper(mb_substr($part, 0, 1)) . mb_substr($part, 1);
        }
        return $builder;
    }
}
