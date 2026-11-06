<?php

declare(strict_types=1);

namespace Linc\Service;

use Linc\Core\BusinessException;
use Linc\Core\Database;

/**
 * 门户皮肤服务端持久化：主题中心选择写入 sys_config（sys.index.skin），
 * 门户前台据此渲染外观——这是「前台主题跟随后台」的联动核心。
 */
final class SkinService
{
    private const CONFIG_KEY = 'sys.index.skin';
    public const ALLOWED = ['linc', 'pearl'];

    public static function current(): string
    {
        $value = Database::scalar(
            "SELECT config_value FROM sys_config WHERE config_key = ? LIMIT 1",
            [self::CONFIG_KEY]
        );
        $skin = is_string($value) ? $value : '';
        return in_array($skin, self::ALLOWED, true) ? $skin : 'linc';
    }

    public static function apply(string $skin): void
    {
        if (!in_array($skin, self::ALLOWED, true)) {
            throw new BusinessException('不支持的主题标识');
        }
        $exists = Database::scalar(
            "SELECT COUNT(*) FROM sys_config WHERE config_key = ?",
            [self::CONFIG_KEY]
        );
        if ((int) $exists > 0) {
            Database::execute(
                "UPDATE sys_config SET config_value = ?, update_by = ? WHERE config_key = ?",
                [$skin, 'system', self::CONFIG_KEY]
            );
            return;
        }
        Database::execute(
            "INSERT INTO sys_config (config_name, config_key, config_value, config_type, builtin, remark, create_by, update_by)
             VALUES (?, ?, ?, 'Y', 0, ?, 'system', 'system')",
            ['门户默认皮肤', self::CONFIG_KEY, $skin, '门户前台跟随的后台主题（linc 灵枢青 / pearl 珍珠玻璃）']
        );
    }
}
