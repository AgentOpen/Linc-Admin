<?php

declare(strict_types=1);

namespace Linc\Controller;

use Linc\Repository\AnnounceRepository;
use Linc\Service\SkinService;

/**
 * 门户前台：服务端渲染（前后台不分离），外观跟随后台主题中心选择的皮肤。
 * 数据源：公告管理（biz_announce，已发布）+ 品牌 APP 常量。
 */
final class PortalController
{
    /** 渲染完整前台 HTML 并输出。 */
    public static function render(): void
    {
        $skin = SkinService::current();
        $announces = AnnounceRepository::selectPublished(10);
        $total = AnnounceRepository::countPublished();
        header('Content-Type: text/html; charset=utf-8');
        echo self::html($skin, $announces, $total);
    }

    /**
     * 主题令牌：基线（珍珠玻璃语义）+ 灵枢青覆盖，随 $skin 切换 data-skin。
     * @param list<array<string,mixed>> $announces
     */
    private static function html(string $skin, array $announces, int $total): string
    {
        $h = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $year = date('Y');
        $items = '';
        foreach ($announces as $i => $a) {
            $content = (string) ($a['content'] ?? '');
            $excerpt = mb_substr($content, 0, 120) . (mb_strlen($content) > 120 ? '…' : '');
            $delay = sprintf('transition-delay:%.2fs', $i * 0.06);
            $items .= <<<HTML
                <article class="ann-card reveal" style="{$delay}">
                  <div class="ann-meta"><span class="ann-dot"></span><time>{$h($a['publishTime'])}</time></div>
                  <h3>{$h($a['title'])}</h3>
                  <p>{$h($excerpt)}</p>
                </article>

                HTML;
        }
        $annSection = $announces === []
            ? '<div class="ann-empty reveal">公告即将发布，敬请期待。</div>'
            : '<div class="ann-grid">' . $items . '</div>';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="zh-CN" data-skin="{$skin}">
        <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>灵枢中控 · 统一管理后台</title>
        <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Ccircle cx='16' cy='16' r='14' fill='%23101418'/%3E%3Ccircle cx='16' cy='16' r='7' fill='%2382dd2d'/%3E%3C/svg%3E">
        <style>
        /* ── 主题令牌：基线 + 双皮肤（与后台 themes/*.css 同源） ── */
        :root {
          font-family: "SF Pro Display", "SF Pro Text", "PingFang SC", -apple-system, BlinkMacSystemFont, "Noto Sans SC", "Microsoft YaHei", sans-serif;
          color-scheme: light;
          --ink: #101418; --ink-muted: #596368; --ink-soft: #7e888c; --ink-faint: #99a2a5;
          --blue: #236de9; --blue-hover: #1d61d5; --blue-soft: rgba(35, 109, 233, .1);
          --lime: #82dd2d; --lime-soft: rgba(130, 221, 45, .14);
          --r-xs: 8px; --r-sm: 12px; --r-md: 16px; --r-lg: 20px; --r-xl: 24px;
          --shade: 46, 58, 62; --line: rgba(var(--shade), .085); --line-strong: rgba(var(--shade), .11);
          --e1: 0 1px 2px rgba(var(--shade), .05), 0 5px 14px rgba(var(--shade), .04);
          --e2: 0 1px 3px rgba(var(--shade), .06), 0 12px 30px rgba(var(--shade), .055);
          --edge: inset 0 1px 0 rgba(255, 255, 255, .92);
          --stage: #dfe3e2; --canvas: #f6f8f7; --surface: linear-gradient(145deg, rgba(255,255,255,.97), rgba(249,252,251,.93));
          --pill-bg: linear-gradient(145deg, rgba(255,255,255,.93), rgba(246,251,249,.62));
          --hero-ink: #eef4f2;
        }
        :root[data-skin="linc"] {
          --ink: #252b3a; --ink-muted: #575d6c; --ink-soft: #71757e; --ink-faint: #9498a3;
          --blue: #5e7ce0; --blue-hover: #7693f5; --blue-soft: rgba(94, 124, 224, .1);
          --lime: #3dcc7e; --lime-soft: rgba(61, 220, 126, .14);
          --r-xs: 4px; --r-sm: 6px; --r-md: 8px; --r-lg: 10px; --r-xl: 12px;
          --shade: 31, 35, 41; --line: rgba(31, 35, 41, .09); --line-strong: rgba(31, 35, 41, .13);
          --e1: 0 1px 3px rgba(31, 35, 41, .08); --e2: 0 2px 8px rgba(31, 35, 41, .1);
          --edge: inset 0 0 0 rgba(0, 0, 0, 0);
          --stage: #e4e7ee; --canvas: #f5f6f8; --surface: linear-gradient(145deg, #ffffff, #fdfdfe);
          --pill-bg: linear-gradient(145deg, #ffffff, #f4f7fe);
        }
        @media (prefers-reduced-motion: reduce) {
          * { animation: none !important; transition: none !important; }
          .reveal { opacity: 1 !important; transform: none !important; }
        }
        * { box-sizing: border-box; margin: 0; }
        html, body { min-height: 100%; }
        body {
          color: var(--ink);
          background:
            radial-gradient(1100px 520px at 82% -10%, var(--lime-soft), transparent 60%),
            radial-gradient(900px 480px at 8% 108%, var(--blue-soft), transparent 60%),
            var(--stage);
        }
        /* ── Hero ── */
        .hero {
          position: relative; overflow: hidden;
          background: var(--frame, #17191d); background: linear-gradient(160deg, #10141b, #171d26 55%, #10141b);
          color: var(--hero-ink);
          padding: 96px 24px 110px;
        }
        .hero::before {
          content: ""; position: absolute; inset: 0;
          background:
            radial-gradient(560px 260px at 78% 18%, rgba(94, 124, 224, .22), transparent 65%),
            radial-gradient(480px 240px at 16% 88%, rgba(130, 221, 45, .13), transparent 60%),
            linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px) 0 0 / 100% 44px,
            linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px) 0 0 / 44px 100%;
          animation: heroGlow 9s ease-in-out infinite alternate;
        }
        @keyframes heroGlow { from { opacity: .75; } to { opacity: 1; } }
        .hero-inner { position: relative; max-width: 1080px; margin: 0 auto; }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 40px; }
        .brand-mark {
          width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center;
          background: rgba(255,255,255,.08); box-shadow: inset 0 1px 0 rgba(255,255,255,.14), 0 0 0 1px rgba(255,255,255,.08);
        }
        .brand-mark i { width: 12px; height: 12px; border-radius: 50%; background: var(--lime); box-shadow: 0 0 12px var(--lime); display: block; animation: pulse 2.6s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(.72); opacity: .72; } }
        .brand-name { font-size: 15px; font-weight: 650; letter-spacing: .02em; }
        .brand-sub { font-size: 12px; color: rgba(255,255,255,.5); }
        .eyebrow {
          display: inline-flex; align-items: center; gap: 8px;
          font-family: ui-monospace, "SF Mono", Consolas, monospace; font-size: 12.5px;
          letter-spacing: .14em; color: rgba(255,255,255,.55); text-transform: uppercase;
        }
        .eyebrow::before { content: ""; width: 22px; height: 1px; background: var(--lime); }
        h1.hero-title { font-size: clamp(34px, 5.4vw, 58px); font-weight: 700; line-height: 1.18; margin: 18px 0 20px; letter-spacing: .01em; }
        h1.hero-title b { background: linear-gradient(92deg, var(--lime), #c7f07a); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .hero-lead { max-width: 560px; font-size: 15.5px; line-height: 1.85; color: rgba(255,255,255,.62); }
        .hero-cta { display: flex; gap: 14px; margin-top: 34px; flex-wrap: wrap; }
        .btn-primary {
          display: inline-flex; align-items: center; gap: 8px;
          height: 44px; padding: 0 26px; border-radius: var(--r-md);
          background: var(--blue); color: #fff; text-decoration: none; font-weight: 600; font-size: 14.5px;
          box-shadow: 0 2px 10px rgba(52, 72, 153, .35); transition: transform .2s var(--ease, ease), box-shadow .2s ease, background .2s ease;
        }
        .btn-primary:hover { background: var(--blue-hover); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(52, 72, 153, .42); }
        .btn-ghost {
          display: inline-flex; align-items: center; gap: 8px;
          height: 44px; padding: 0 24px; border-radius: var(--r-md);
          color: rgba(255,255,255,.86); text-decoration: none; font-size: 14.5px; font-weight: 550;
          background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.14);
          transition: background .2s ease, transform .2s ease;
        }
        .btn-ghost:hover { background: rgba(255,255,255,.13); transform: translateY(-2px); }
        .hero-stats { display: flex; gap: 38px; margin-top: 52px; flex-wrap: wrap; }
        .stat b { display: block; font-family: ui-monospace, "SF Mono", Consolas, monospace; font-size: 26px; font-weight: 700; color: #fff; }
        .stat span { font-size: 12.5px; color: rgba(255,255,255,.48); }
        /* ── 主体 ── */
        main { max-width: 1080px; margin: -64px auto 0; padding: 0 24px 80px; position: relative; }
        .section { margin-top: 54px; }
        .section-label { display: flex; align-items: baseline; gap: 12px; margin-bottom: 18px; }
        .section-label h2 { font-size: 19px; font-weight: 680; letter-spacing: .01em; }
        .section-label span { font-size: 12.5px; color: var(--ink-faint); }
        .card { background: var(--surface); border: 1px solid var(--line-strong); border-radius: var(--r-lg); box-shadow: var(--e2), var(--edge); backdrop-filter: blur(14px); }
        /* 公告 */
        .ann-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
        .ann-card { padding: 20px 22px; border-radius: var(--r-lg); background: var(--surface); border: 1px solid var(--line-strong); box-shadow: var(--e1); transition: transform .25s var(--ease, ease), box-shadow .25s ease; }
        .ann-card:hover { transform: translateY(-4px); box-shadow: var(--e2); }
        .ann-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .ann-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--lime); box-shadow: 0 0 0 3px var(--lime-soft); }
        .ann-meta time { font-family: ui-monospace, Consolas, monospace; font-size: 12px; color: var(--ink-faint); }
        .ann-card h3 { font-size: 15px; font-weight: 650; margin-bottom: 8px; }
        .ann-card p { font-size: 13px; line-height: 1.7; color: var(--ink-muted); }
        .ann-empty { padding: 34px; text-align: center; color: var(--ink-soft); font-size: 13.5px; border: 1px dashed var(--line-strong); border-radius: var(--r-lg); }
        /* 特性 */
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
        .feature { padding: 18px 20px; border-radius: var(--r-md); background: var(--surface); border: 1px solid var(--line); transition: transform .22s ease, border-color .22s ease; }
        .feature:hover { transform: translateY(-3px); border-color: var(--blue); }
        .feature b { display: block; font-size: 14px; margin-bottom: 6px; }
        .feature span { font-size: 12.5px; line-height: 1.6; color: var(--ink-soft); }
        /* 入场动画 */
        .reveal { opacity: 0; transform: translateY(16px); animation: rise .7s cubic-bezier(.16, 1, .3, 1) forwards; }
        @keyframes rise { to { opacity: 1; transform: none; } }
        .reveal[style] { --d: 0s; animation-delay: var(--d, 0s); }
        footer { text-align: center; padding: 34px 24px 44px; font-size: 12.5px; color: var(--ink-faint); }
        footer a { color: var(--ink-soft); }
        </style>
        </head>
        <body>
        <section class="hero">
          <div class="hero-inner">
            <div class="brand reveal">
              <span class="brand-mark"><i></i></span>
              <div><div class="brand-name">灵枢中控</div><div class="brand-sub">统一管理后台</div></div>
            </div>
            <span class="eyebrow reveal" style="transition-delay:.05s">Linc-Admin Portal</span>
            <h1 class="hero-title reveal" style="transition-delay:.12s">统一管理后台，<br>兼顾<b>设计美学</b>与<b>高效开发</b>。</h1>
            <p class="hero-lead reveal" style="transition-delay:.2s">账号、权限、组织、字典与审计开箱即用；门户外观与后台主题实时联动——在后台「主题中心」切换皮肤，这里随之改变。</p>
            <div class="hero-cta reveal" style="transition-delay:.28s">
              <a class="btn-primary" href="/admin">进入管理后台 →</a>
              <a class="btn-ghost" href="#announce">查看公告</a>
            </div>
            <div class="hero-stats reveal" style="transition-delay:.36s">
              <div class="stat"><b>{$total}</b><span>已发布公告</span></div>
              <div class="stat"><b>2</b><span>内置主题皮肤</span></div>
              <div class="stat"><b>17+</b><span>系统数据表</span></div>
              <div class="stat"><b>MIT</b><span>开源许可</span></div>
            </div>
          </div>
        </section>
        <main>
          <section class="section" id="announce">
            <div class="section-label"><h2>公告动态</h2><span>来自后台「公告管理」</span></div>
            {$annSection}
          </section>
          <section class="section">
            <div class="section-label"><h2>系统能力</h2><span>开箱即用的中后台底座</span></div>
            <div class="feature-grid">
              <div class="feature reveal"><b>认证与权限</b><span>JWT 会话 + 菜单按钮两级权限码，超级管理员直通</span></div>
              <div class="feature reveal" style="transition-delay:.06s"><b>组织与数据权限</b><span>部门 / 岗位 / 角色，数据范围五种粒度</span></div>
              <div class="feature reveal" style="transition-delay:.12s"><b>全程审计</b><span>操作日志与登录日志，趋势、泳道、热力与异常聚合</span></div>
              <div class="feature reveal" style="transition-delay:.18s"><b>多主题联动</b><span>后台切换皮肤，门户前台实时跟随</span></div>
            </div>
          </section>
        </main>
        <footer>© {$year} 灵枢中控 Linc-Admin · <a href="/admin">管理后台</a></footer>
        </body>
        </html>
        HTML;
    }
}
