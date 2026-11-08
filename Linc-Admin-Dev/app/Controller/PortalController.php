<?php

declare(strict_types=1);

namespace Linc\Controller;

use Linc\Repository\AnnounceRepository;
use Linc\Service\SkinService;

/**
 * 门户前台：服务端渲染（前后台不分离），外观跟随后台主题中心选择的皮肤与站点主色。
 * 数据源：公告管理（biz_announce，已发布）。视图：portal-view.php / portal-unavailable.php。
 * SEO/GEO：meta / OG / JSON-LD（SoftwareApplication + FAQPage）/ llms.txt / robots.txt / sitemap.xml。
 */
final class PortalController
{
    /** 渲染完整前台 HTML 并输出；数据库不可用时优雅降级（不裸抛异常）。 */
    public static function render(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        try {
            $skin = SkinService::current();
            $primary = SkinService::primary();
            $seo = SkinService::seo();
            $announces = AnnounceRepository::selectPublished(10);
            $total = AnnounceRepository::countPublished();
            $version = InstallerController::version();
            $year = date('Y');

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $origin = $scheme . '://' . $host;

            $h = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

            // 站点主色派生：hover / soft / rgb 三元组（供 CSS 与 Canvas）
            $base = $primary ?? '';
            if ($base === '') {
                $base = $skin === 'pearl' ? '#236de9' : '#5e7ce0';
            }
            $rgb = self::hexToRgb($base);
            $lighten = static function (array $c, float $amt): string {
                return sprintf('#%02x%02x%02x',
                    max(0, min(255, (int) round($c[0] + (255 - $c[0]) * $amt))),
                    max(0, min(255, (int) round($c[1] + (255 - $c[1]) * $amt))),
                    max(0, min(255, (int) round($c[2] + (255 - $c[2]) * $amt)))
                );
            };
            $primaryHover = $lighten($rgb, 0.16);
            $primarySoft = sprintf('rgba(%d, %d, %d, .16)', $rgb[0], $rgb[1], $rgb[2]);

            // JSON-LD：SoftwareApplication + Organization（搜索引擎 / AI 引擎结构化收录）
            $jsonLd = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => '灵枢中控 Linc-Admin',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Linux, Windows',
                'description' => $seo['description'],
                'softwareVersion' => $version,
                'author' => ['@type' => 'Organization', 'name' => '灵枢中控'],
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'CNY'],
                'url' => $origin . '/',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

            // FAQPage JSON-LD（GEO：AI 回答引擎可直接引用的结构化问答）
            $faqJsonLd = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => [
                    ['@type' => 'Question', 'name' => '灵枢中控是什么？', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => '灵枢中控（Linc-Admin）是一套开箱即用的 PHP 中后台管理系统：Web 安装向导三步完成部署，账号权限、数据权限、审计日志与多主题体系开箱即用。']],
                    ['@type' => 'Question', 'name' => '灵枢中控如何安装？', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => '上传源码到服务器、将 public 设为运行目录、访问域名进入六步安装向导（环境检查、数据库、管理员账号、执行安装），全程零命令行。']],
                    ['@type' => 'Question', 'name' => '灵枢中控支持多主题吗？', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => '支持。内置灵枢青（DevUI 风格）与珍珠玻璃双主题，后台主题中心一键切换，门户前台实时跟随；主题基于 CSS 令牌覆盖，新增主题约 30 分钟。']],
                    ['@type' => 'Question', 'name' => '灵枢中控是开源免费的吗？', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => '是的，灵枢中控基于 MIT License 开源，可免费商用。']],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

            include __DIR__ . '/portal-view.php';
        } catch (\Throwable $error) {
            http_response_code(503);
            $message = $error->getMessage();
            $h = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
            $installed = \Linc\Core\Config::installed();
            include __DIR__ . '/portal-unavailable.php';
        }
    }

    /** #RGB/#RRGGBB → [r, g, b]。 */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
