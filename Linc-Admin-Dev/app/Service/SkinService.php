<?php

declare(strict_types=1);

namespace Linc\Service;

use Linc\Core\BusinessException;
use Linc\Core\Database;

/**
 * 门户站点设置：皮肤、站点主色、SEO 文案——持久化到 sys_config，门户前台与 SEO 输出据此渲染。
 *
 * 参数键：
 * - sys.portal.skin      皮肤（linc / pearl）
 * - sys.portal.primary   站点主色（#RGB / #RRGGBB，覆盖皮肤默认主色）
 * - sys.seo.title        SEO 标题（缺省用「灵枢中控 Linc-Admin · 统一管理后台」）
 * - sys.seo.description  SEO 描述
 * - sys.seo.keywords     SEO 关键词
 */
final class SkinService
{
    private const SKIN_KEY = 'sys.portal.skin';
    private const PRIMARY_KEY = 'sys.portal.primary';
    private const SEO_KEY = 'sys.seo.';
    public const ALLOWED = ['linc', 'pearl'];

    /** 当前皮肤。 */
    public static function current(): string
    {
        $value = Database::scalar(
            "SELECT config_value FROM sys_config WHERE config_key = ? LIMIT 1",
            [self::SKIN_KEY]
        );
        $skin = is_string($value) ? $value : '';
        return in_array($skin, self::ALLOWED, true) ? $skin : 'linc';
    }

    /** 当前站点主色（未设置返回 null，用皮肤默认）。 */
    public static function primary(): ?string
    {
        $value = Database::scalar(
            "SELECT config_value FROM sys_config WHERE config_key = ? LIMIT 1",
            [self::PRIMARY_KEY]
        );
        $color = is_string($value) ? trim($value) : '';
        return self::isHexColor($color) ? $color : null;
    }

    /** SEO 文案：config 值优先，缺省回退品牌默认。 */
    public static function seo(): array
    {
        $rows = Database::all(
            "SELECT config_key, config_value FROM sys_config WHERE config_key LIKE ?",
            [self::SEO_KEY . '%']
        );
        $seo = [
            'title' => '灵枢中控 Linc-Admin · 统一管理后台',
            'description' => '灵枢中控 Linc-Admin：兼具设计美学与高效开发的 PHP 中后台模板。Web 安装向导三步部署，权限、审计、多主题开箱即用。',
            'keywords' => 'Linc-Admin,灵枢中控,PHP中后台,管理后台模板,权限管理,开源后台',
        ];
        foreach ($rows as $row) {
            $key = (string) ($row['config_key'] ?? '');
            $value = (string) ($row['config_value'] ?? '');
            if ($value === '') {
                continue;
            }
            if ($key === self::SEO_KEY . 'title') {
                $seo['title'] = $value;
            } elseif ($key === self::SEO_KEY . 'description') {
                $seo['description'] = $value;
            } elseif ($key === self::SEO_KEY . 'keywords') {
                $seo['keywords'] = $value;
            }
        }
        return $seo;
    }

    /**
     * 应用门户设置（主题中心调用）：皮肤 + 可选站点主色。
     * @param array<string,mixed> $request
     */
    public static function applyAll(array $request): array
    {
        $skin = isset($request['skin']) && is_string($request['skin']) ? $request['skin'] : '';
        if (!in_array($skin, self::ALLOWED, true)) {
            throw new BusinessException('不支持的主题标识');
        }
        self::upsert(self::SKIN_KEY, $skin, '门户默认皮肤', '门户前台跟随后台主题（linc 灵枢青 / pearl 珍珠玻璃）');

        $primary = isset($request['primary']) && is_string($request['primary']) ? trim($request['primary']) : '';
        if ($primary !== '') {
            if (!self::isHexColor($primary)) {
                throw new BusinessException('站点主色格式不正确，应为 #RRGGBB');
            }
            self::upsert(self::PRIMARY_KEY, $primary, '门户站点主色', '门户前台的主色（覆盖皮肤默认主色）');
        } else {
            Database::execute("DELETE FROM sys_config WHERE config_key = ?", [self::PRIMARY_KEY]);
        }

        return ['skin' => self::current(), 'primary' => self::primary()];
    }

    private static function upsert(string $key, string $value, string $name, string $remark): void
    {
        $exists = Database::scalar(
            "SELECT COUNT(*) FROM sys_config WHERE config_key = ?",
            [$key]
        );
        if ((int) $exists > 0) {
            Database::execute(
                "UPDATE sys_config SET config_value = ?, update_by = 'system' WHERE config_key = ?",
                [$value, $key]
            );
            return;
        }
        Database::execute(
            "INSERT INTO sys_config (config_name, config_key, config_value, config_type, builtin, remark, create_by, update_by)
             VALUES (?, ?, ?, 'Y', 0, ?, 'system', 'system')",
            [$name, $key, $value, $remark]
        );
    }

    private static function isHexColor(string $value): bool
    {
        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1;
    }
}
