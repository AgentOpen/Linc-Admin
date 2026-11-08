<?php
/**
 * 门户前台视图：参照 DevUI 官网结构（导航 / Hero / 亮点 / 快速开始 / 皮肤 / 公告 / 页脚）。
 * 由 PortalController::render() 提供变量：$skin, $primary, $primaryHover, $primarySoft, $primaryRgb,
 * $seo, $jsonLd, $faqJsonLd, $origin, $announces, $total, $version, $year, $h。
 * 全页深空玻璃风格，动效：粒子星域、滚动渐显、数字滚动、导航收缩、代码面板切换。
 */
?>
<!DOCTYPE html>
<html lang="zh-CN" data-skin="<?= $h($skin) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $h($seo['title']) ?></title>
<meta name="description" content="<?= $h($seo['description']) ?>">
<meta name="keywords" content="<?= $h($seo['keywords']) ?>">
<meta name="author" content="灵枢中控 Linc-Admin">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<link rel="canonical" href="<?= $h($origin) ?>/">
<meta property="og:type" content="website">
<meta property="og:site_name" content="灵枢中控 Linc-Admin">
<meta property="og:title" content="<?= $h($seo['title']) ?>">
<meta property="og:description" content="<?= $h($seo['description']) ?>">
<meta property="og:url" content="<?= $h($origin) ?>/">
<meta property="og:locale" content="zh_CN">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= $h($seo['title']) ?>">
<meta name="twitter:description" content="<?= $h($seo['description']) ?>">
<link rel="canonical" href="<?= $h($origin) ?>/">
<link rel="alternate" type="text/plain" href="<?= $h($origin) ?>/llms.txt">
<script type="application/ld+json">
<?= $jsonLd ?>
</script>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Ccircle cx='16' cy='16' r='14' fill='%23101418'/%3E%3Ccircle cx='16' cy='16' r='7' fill='%2382dd2d'/%3E%3C/svg%3E">
<?php if ($primary !== null) : ?>
<style>:root { --blue: <?= $h($primary) ?>; --blue-hover: <?= $h($primaryHover) ?>; --blue-soft: <?= $h($primarySoft) ?>; }</style>
<?php endif; ?>
<style>
:root {
  font-family: "SF Pro Display", "SF Pro Text", "PingFang SC", -apple-system, BlinkMacSystemFont, "Noto Sans SC", "Microsoft YaHei", sans-serif;
  --ink: #eef4f2; --ink-muted: rgba(238,244,242,.66); --ink-soft: rgba(238,244,242,.44); --ink-faint: rgba(238,244,242,.28);
  --blue: #5e7ce0; --blue-hover: #7693f5; --blue-soft: rgba(94,124,224,.16);
  --lime: #82dd2d; --lime-soft: rgba(130,221,45,.14);
  --r-xs: 8px; --r-sm: 10px; --r-md: 14px; --r-lg: 18px; --r-xl: 24px;
  --line: rgba(255,255,255,.08); --line-strong: rgba(255,255,255,.13);
  --glass: rgba(22,27,34,.66);
  --e-accent: 0 2px 14px rgba(52,72,153,.4), 0 4px 18px rgba(94,124,224,.22);
  --mono: ui-monospace, "SF Mono", "Cascadia Code", Consolas, monospace;
  --ease: cubic-bezier(.16, 1, .3, 1);
  --stage: #0c0f14; --panel: rgba(255,255,255,.035);
}
:root[data-skin="pearl"] {
  --blue: #236de9; --blue-hover: #4c8dff; --blue-soft: rgba(35,109,233,.18);
  --lime: #9be35a;
  --e-accent: 0 2px 14px rgba(20,66,148,.45), 0 4px 18px rgba(35,109,233,.25);
}
@media (prefers-reduced-motion: reduce) {
  * { animation: none !important; transition: none !important; }
  .reveal { opacity: 1 !important; transform: none !important; }
}
* { box-sizing: border-box; margin: 0; }
html { scroll-behavior: smooth; }
body { color: var(--ink); background: var(--stage); overflow-x: hidden; }
::selection { background: var(--blue-soft); color: #fff; }
a { color: inherit; text-decoration: none; }
.wrap { max-width: 1120px; margin: 0 auto; padding: 0 24px; }

.nav { position: fixed; top: 0; left: 0; right: 0; z-index: 50; backdrop-filter: blur(18px); background: rgba(12,15,20,.55); border-bottom: 1px solid transparent; transition: background .3s ease, border-color .3s ease; }
.nav.scrolled { background: rgba(12,15,20,.86); border-bottom-color: var(--line); }
.nav-inner { max-width: 1120px; margin: 0 auto; padding: 0 24px; height: 64px; display: flex; align-items: center; gap: 26px; }
.nav-brand { display: flex; align-items: center; gap: 10px; margin-right: auto; }
.brand-mark { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: rgba(255,255,255,.07); box-shadow: inset 0 1px 0 rgba(255,255,255,.14), 0 0 0 1px rgba(255,255,255,.08); }
.brand-mark i { width: 11px; height: 11px; border-radius: 50%; background: var(--lime); box-shadow: 0 0 12px var(--lime); display: block; animation: pulse 2.6s ease-in-out infinite; }
@keyframes pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(.7); opacity: .7; } }
.brand-txt b { display: block; font-size: 14.5px; font-weight: 680; }
.brand-txt span { display: block; font-size: 11px; color: var(--ink-soft); }
.nav-links { display: flex; gap: 4px; }
.nav-links a { font-size: 13.5px; color: var(--ink-muted); padding: 8px 13px; border-radius: var(--r-xs); transition: color .2s, background .2s; }
.nav-links a:hover { color: var(--ink); background: rgba(255,255,255,.06); }
.nav-cta { display: inline-flex; align-items: center; gap: 7px; height: 38px; padding: 0 18px; border-radius: 10px; background: var(--blue); color: #fff; font-size: 13.5px; font-weight: 620; box-shadow: var(--e-accent); transition: transform .2s var(--ease), background .2s, box-shadow .2s; }
.nav-cta:hover { background: var(--blue-hover); transform: translateY(-1px); }
@media (max-width: 800px) { .nav-links { display: none; } }

.hero { position: relative; overflow: hidden; padding: 150px 24px 120px; }
#space { position: absolute; inset: 0; }
.hero::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 180px; background: linear-gradient(to bottom, transparent, var(--stage)); pointer-events: none; }
.hero-inner { position: relative; max-width: 1120px; margin: 0 auto; }
.eyebrow { display: inline-flex; align-items: center; gap: 10px; font-family: var(--mono); font-size: 12.5px; letter-spacing: .16em; color: var(--ink-soft); text-transform: uppercase; }
.eyebrow::before { content: ""; width: 24px; height: 1px; background: var(--lime); }
h1.hero-title { font-size: clamp(36px, 5.6vw, 62px); font-weight: 720; line-height: 1.16; margin: 20px 0 22px; letter-spacing: .01em; }
h1.hero-title .grad { background: linear-gradient(94deg, var(--lime) 10%, #d9f5a8 45%, var(--blue) 100%); background-size: 200% 100%; -webkit-background-clip: text; background-clip: text; color: transparent; animation: gradShift 6s ease-in-out infinite alternate; }
@keyframes gradShift { from { background-position: 0% 0; } to { background-position: 100% 0; } }
.hero-lead { max-width: 620px; font-size: 15.5px; line-height: 1.9; color: var(--ink-muted); }
.hero-cta { display: flex; gap: 14px; margin-top: 36px; flex-wrap: wrap; }
.btn-primary { display: inline-flex; align-items: center; gap: 8px; height: 46px; padding: 0 28px; border-radius: var(--r-md); background: var(--blue); color: #fff; font-weight: 640; font-size: 14.5px; box-shadow: var(--e-accent); transition: transform .2s var(--ease), background .2s, box-shadow .2s; }
.btn-primary:hover { background: var(--blue-hover); transform: translateY(-2px); box-shadow: 0 8px 30px rgba(52,72,153,.5); }
.btn-ghost { display: inline-flex; align-items: center; gap: 8px; height: 46px; padding: 0 24px; border-radius: var(--r-md); color: var(--ink-muted); font-size: 14.5px; font-weight: 560; background: rgba(255,255,255,.06); border: 1px solid var(--line-strong); transition: background .2s, transform .2s var(--ease), color .2s; }
.btn-ghost:hover { background: rgba(255,255,255,.11); color: var(--ink); transform: translateY(-2px); }
.hero-stats { display: flex; gap: 46px; margin-top: 56px; flex-wrap: wrap; }
.stat b { display: flex; align-items: baseline; font-family: var(--mono); font-size: 30px; font-weight: 720; color: #fff; }
.stat b em { font-style: normal; font-size: 16px; margin-left: 2px; color: var(--ink-soft); }
.stat span { font-size: 12.5px; color: var(--ink-soft); }

section.sec { padding: 86px 0 30px; }
.sec-head { margin-bottom: 34px; }
.sec-head .eyebrow { color: var(--ink-soft); }
.sec-head .eyebrow::before { background: var(--blue); }
.sec-head h2 { font-size: clamp(24px, 3vw, 32px); font-weight: 720; margin: 14px 0 10px; }
.sec-head p { max-width: 640px; font-size: 14px; line-height: 1.85; color: var(--ink-muted); }

.feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
@media (max-width: 900px) { .feature-grid { grid-template-columns: 1fr; } }
.feature { position: relative; padding: 26px 26px 28px; border-radius: var(--r-lg); background: var(--panel); border: 1px solid var(--line); transition: transform .3s var(--ease), border-color .3s, box-shadow .3s; overflow: hidden; }
.feature::before { content: ""; position: absolute; top: 0; left: 24px; right: 24px; height: 1px; background: linear-gradient(90deg, transparent, var(--blue), transparent); opacity: 0; transition: opacity .3s; }
.feature:hover { transform: translateY(-6px); border-color: var(--line-strong); box-shadow: 0 18px 50px rgba(0,0,0,.4); }
.feature:hover::before { opacity: 1; }
.feature-ico { width: 44px; height: 44px; border-radius: var(--r-sm); display: grid; place-items: center; background: var(--blue-soft); color: #a9bdf5; margin-bottom: 16px; font-family: var(--mono); font-size: 13px; font-weight: 680; }
.feature b { display: block; font-size: 15px; font-weight: 660; margin-bottom: 8px; }
.feature span { font-size: 13px; line-height: 1.75; color: var(--ink-muted); }

.start { display: grid; grid-template-columns: 300px 1fr; gap: 26px; align-items: start; }
@media (max-width: 860px) { .start { grid-template-columns: 1fr; } }
.start-steps { display: flex; flex-direction: column; gap: 8px; }
.start-step { display: flex; gap: 14px; align-items: flex-start; text-align: left; padding: 16px 18px; border-radius: var(--r-md); cursor: pointer; background: transparent; border: 1px solid transparent; color: inherit; font-family: inherit; transition: background .2s, border-color .2s; }
.start-step:hover { background: rgba(255,255,255,.04); }
.start-step.active { background: var(--panel); border-color: var(--line-strong); }
.start-step .n { width: 30px; height: 30px; border-radius: 50%; flex: none; display: grid; place-items: center; font-family: var(--mono); font-size: 12.5px; font-weight: 680; background: rgba(255,255,255,.06); border: 1px solid var(--line-strong); color: var(--ink-soft); transition: all .25s var(--ease); }
.start-step.active .n { background: var(--blue); border-color: transparent; color: #fff; box-shadow: var(--e-accent); }
.start-step b { display: block; font-size: 14px; font-weight: 640; margin: 2px 0 4px; }
.start-step span { display: block; font-size: 12.5px; color: var(--ink-soft); line-height: 1.6; }
.start-code { border-radius: var(--r-lg); border: 1px solid var(--line-strong); background: #0a0d12; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.45); }
.code-bar { display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-bottom: 1px solid var(--line); }
.code-bar i { width: 11px; height: 11px; border-radius: 50%; display: block; }
.code-bar .r { background: #ff5f57; } .code-bar .y { background: #febc2e; } .code-bar .g { background: #28c840; }
.code-bar span { margin-left: 10px; font-family: var(--mono); font-size: 12px; color: var(--ink-faint); }
.code-bar .copy { margin-left: auto; font-family: inherit; font-size: 12px; color: var(--ink-soft); background: rgba(255,255,255,.06); border: 1px solid var(--line); border-radius: 6px; padding: 4px 12px; cursor: pointer; transition: color .2s, background .2s; }
.code-bar .copy:hover { color: var(--ink); background: rgba(255,255,255,.1); }
.code-body { padding: 20px 22px; font-family: var(--mono); font-size: 13px; line-height: 2; color: #c8d6e5; white-space: pre-wrap; min-height: 230px; }
.code-body .cm { color: #5a6a7d; }
.code-body .kw { color: var(--lime); }
.code-body .st { color: #a9bdf5; }
.start-pane { display: none; }
.start-pane.active { display: block; animation: paneIn .45s var(--ease); }
@keyframes paneIn { from { opacity: 0; transform: translateY(10px); } }

.skin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 760px) { .skin-grid { grid-template-columns: 1fr; } }
.skin-card { border-radius: var(--r-lg); border: 1px solid var(--line-strong); overflow: hidden; background: var(--panel); transition: transform .3s var(--ease), box-shadow .3s; }
.skin-card:hover { transform: translateY(-5px); box-shadow: 0 20px 60px rgba(0,0,0,.45); }
.skin-view { aspect-ratio: 21/9; position: relative; padding: 12px; display: flex; gap: 10px; }
.skin-view .mini { flex: 1; display: flex; flex-direction: column; gap: 7px; border-radius: var(--r-xs); border: 1px solid rgba(255,255,255,.08); padding: 10px; }
.skin-view .mini-rail { width: 48px; }
.skin-view .mini i { display: block; border-radius: 999px; }
.skin-view .mini .d { width: 12px; height: 12px; border-radius: 5px; }
.skin-view .mini .l { height: 4px; }
.skin-view .mini .row { display: flex; gap: 6px; align-items: center; }
.skin-view .mini .p { width: 22px; height: 6px; }
.skin-view .mini .l2 { flex: 1; }
.skin-meta { padding: 18px 22px 20px; }
.skin-meta b { font-size: 15px; }
.skin-meta .sw { display: inline-flex; gap: 5px; margin-left: 10px; vertical-align: 2px; }
.skin-meta .sw i { width: 13px; height: 13px; border-radius: 4px; display: inline-block; border: 1px solid rgba(255,255,255,.15); }
.skin-meta p { font-size: 12.5px; color: var(--ink-muted); margin: 8px 0 0; line-height: 1.7; }

.ann-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
@media (max-width: 900px) { .ann-grid { grid-template-columns: 1fr; } }
.ann-card { padding: 22px 24px; border-radius: var(--r-lg); background: var(--panel); border: 1px solid var(--line); transition: transform .28s var(--ease), border-color .28s, box-shadow .28s; }
.ann-card:hover { transform: translateY(-4px); border-color: var(--line-strong); box-shadow: 0 16px 44px rgba(0,0,0,.4); }
.ann-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
.ann-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--lime); box-shadow: 0 0 0 3px var(--lime-soft); animation: pulse 2.4s ease-in-out infinite; }
.ann-meta time { font-family: var(--mono); font-size: 12px; color: var(--ink-faint); }
.ann-card h3 { font-size: 14.5px; font-weight: 650; margin-bottom: 9px; }
.ann-card p { font-size: 12.8px; line-height: 1.75; color: var(--ink-muted); }
.ann-empty { padding: 44px; text-align: center; color: var(--ink-soft); font-size: 13.5px; border: 1px dashed var(--line-strong); border-radius: var(--r-lg); background: var(--panel); }
.ann-empty-ico { display: block; width: 34px; height: 34px; margin: 0 auto 12px; border-radius: 50%; background: rgba(255,255,255,.05); box-shadow: inset 0 0 0 1px var(--line-strong); position: relative; }
.ann-empty-ico::after { content: ""; position: absolute; inset: 12px; border-radius: 50%; background: var(--lime-soft); }

footer { border-top: 1px solid var(--line); margin-top: 60px; padding: 40px 24px 48px; }
.foot-inner { max-width: 1120px; margin: 0 auto; display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
.foot-inner .l b { font-size: 14px; font-weight: 660; }
.foot-inner .l span { display: block; font-size: 12px; color: var(--ink-faint); margin-top: 6px; }
.foot-links { display: flex; gap: 22px; font-size: 13px; color: var(--ink-muted); }
.foot-links a:hover { color: var(--ink); }
.copyright { max-width: 1120px; margin: 26px auto 0; padding-top: 20px; border-top: 1px solid var(--line); font-size: 12px; color: var(--ink-faint); }

.reveal { opacity: 0; transform: translateY(22px); transition: opacity .8s var(--ease), transform .8s var(--ease); }
.reveal.in { opacity: 1; transform: none; }
</style>
</head>
<body>

<nav class="nav" id="nav">
  <div class="nav-inner">
    <a class="nav-brand" href="/">
      <span class="brand-mark"><i></i></span>
      <span class="brand-txt"><b>灵枢中控</b><span>Linc-Admin</span></span>
    </a>
    <div class="nav-links">
      <a href="#features">系统亮点</a>
      <a href="#start">快速开始</a>
      <a href="#skins">主题皮肤</a>
      <a href="#announce">公告动态</a>
    </div>
    <a class="nav-cta" href="/admin">进入管理后台</a>
  </div>
</nav>

<section class="hero">
  <canvas id="space"></canvas>
  <div class="hero-inner">
    <span class="eyebrow reveal">Linc-Admin · 统一管理后台 · <?= $h($version) ?></span>
    <h1 class="hero-title reveal" style="transition-delay:.08s">统一管理后台，<br>兼顾<span class="grad">设计美学</span>与<span class="grad">高效开发</span>。</h1>
    <p class="hero-lead reveal" style="transition-delay:.16s">灵枢中控是一套开箱即用的 PHP 中后台模板：Web 安装向导三步完成部署，账号权限、组织架构、审计日志与多主题体系开箱即用；门户外观与后台主题实时联动。</p>
    <div class="hero-cta reveal" style="transition-delay:.24s">
      <a class="btn-primary" href="/admin">进入管理后台 →</a>
      <a class="btn-ghost" href="#start">查看快速开始</a>
    </div>
    <div class="hero-stats reveal" style="transition-delay:.32s">
      <div class="stat"><b><span class="cnt" data-count="<?= (int) $total ?>">0</span><em>条</em></b><span>已发布公告</span></div>
      <div class="stat"><b><span class="cnt" data-count="2">0</span><em>套</em></b><span>内置主题皮肤</span></div>
      <div class="stat"><b><span class="cnt" data-count="17">0</span><em>+</em></b><span>系统数据表</span></div>
      <div class="stat"><b>MIT</b><span>开源许可</span></div>
    </div>
  </div>
</section>

<div class="wrap">
  <section class="sec" id="features">
    <div class="sec-head reveal">
      <span class="eyebrow">Features</span>
      <h2>系统亮点</h2>
      <p>把中后台系统里「每个项目都要写一遍」的部分做成底座能力，新业务只需专注字段与流程。</p>
    </div>
    <div class="feature-grid">
      <div class="feature reveal"><span class="feature-ico">权限</span><b>认证与权限体系</b><span>JWT 会话 + 菜单按钮两级权限码，前后端同一套权限标识；超级管理员直通，低权限接口真实拒绝。</span></div>
      <div class="feature reveal" style="transition-delay:.06s"><span class="feature-ico">数据</span><b>组织与数据权限</b><span>部门 / 岗位 / 角色，数据范围五种粒度：全部、本部门及下级、本部门、自定义、仅本人。</span></div>
      <div class="feature reveal" style="transition-delay:.12s"><span class="feature-ico">审计</span><b>全程操作审计</b><span>操作日志与登录日志自动记录（敏感参数脱敏），趋势、泳道、热力与异常聚合一屏尽览。</span></div>
      <div class="feature reveal" style="transition-delay:.18s"><span class="feature-ico">主题</span><b>多主题体系</b><span>令牌级主题包：灵枢青（DevUI 风格）与珍珠玻璃，后台一键切换，门户前台实时跟随。</span></div>
      <div class="feature reveal" style="transition-delay:.24s"><span class="feature-ico">生成</span><b>代码生成器</b><span>导入数据表、配置字段，预览并下载业务模块四层骨架 + 前端页面 + 菜单 SQL。</span></div>
      <div class="feature reveal" style="transition-delay:.3s"><span class="feature-ico">安装</span><b>Web 安装向导</b><span>六步引导：协议、环境检查、数据库、管理员、执行安装、完成交付——零命令行完成部署。</span></div>
    </div>
  </section>

  <section class="sec" id="start">
    <div class="sec-head reveal">
      <span class="eyebrow">Quick Start</span>
      <h2>快速开始</h2>
      <p>三步完成部署：环境准备 → 上传安装 → 登录使用。完整图文教程见项目 docs/12-安装教程。</p>
    </div>
    <div class="start reveal">
      <div class="start-steps">
        <button class="start-step active" data-pane="p1"><span class="n">01</span><span><b>环境准备</b><span>准备 PHP 8.0+ 与 MySQL 5.7+ 的服务器，推荐使用宝塔面板</span></span></button>
        <button class="start-step" data-pane="p2"><span class="n">02</span><span><b>上传与安装</b><span>设置运行目录 public，访问域名进入安装向导</span></span></button>
        <button class="start-step" data-pane="p3"><span class="n">03</span><span><b>登录使用</b><span>用管理员账号登录后台，发布内容、切换主题</span></span></button>
      </div>
      <div class="start-code">
        <div class="code-bar"><i class="r"></i><i class="y"></i><i class="g"></i><span id="code-title">环境准备</span><button class="copy" data-copy="p1">复制</button></div>
        <div class="start-pane active" id="p1"><div class="code-body"><span class="cm"># 服务器要求（宝塔面板图形化安装最省心）</span>
          PHP     ≥ 8.0    扩展 pdo_mysql / zlib
          MySQL   ≥ 5.7    字符集 utf8mb4
          Web     Nginx / Apache

          <span class="cm"># 本地快速体验（可选）</span>
          <span class="kw">php</span> <span class="st">-S 127.0.0.1:8090 -t public public/index.php</span></div></div>
        <div class="start-pane" id="p2"><div class="code-body"><span class="cm"># 1. 上传项目到站点目录，运行目录设为 public/</span>

          <span class="cm"># 2. 伪静态（宝塔选 laravel5 模板）</span>
          <span class="kw">try_files</span> <span class="st">$uri $uri/ /index.php$is_args$args</span>;

          <span class="cm"># 3. 浏览器访问域名 → 六步安装向导</span>
          <span class="cm">#    环境检查 → 数据库 → 管理员 → 完成</span></div></div>
        <div class="start-pane" id="p3"><div class="code-body"><span class="cm"># 管理后台（默认 admin / admin123，装完即改）</span>
          https://你的域名/admin

          <span class="cm"># 门户前台（本页，跟随后台主题换肤）</span>
          https://你的域名/

          <span class="cm"># 主题中心一键换肤，前台实时跟随</span>
          后台 → 主题中心 → 使用此主题</div></div>
      </div>
    </div>
  </section>

  <section class="sec" id="skins">
    <div class="sec-head reveal">
      <span class="eyebrow">Themes</span>
      <h2>主题皮肤</h2>
      <p>后台「主题中心」一键切换，门户前台实时跟随；浅色 / 深色与表格密度对所有主题通用。</p>
    </div>
    <div class="skin-grid">
      <div class="skin-card reveal">
        <div class="skin-view" style="background:#e4e7ee">
          <div class="mini mini-rail" style="background:#fff"><i class="d" style="background:#5e7ce0"></i><i class="l" style="background:#5e7ce0;opacity:.55;width:62%"></i><i class="l" style="background:#5e7ce0;opacity:.28;width:46%"></i></div>
          <div class="mini" style="background:#fff"><div class="row"><i class="p" style="background:#5e7ce0"></i><i class="l l2" style="background:#5e7ce0;opacity:.3"></i></div><i class="l" style="background:#e8ebf2;height:26px;border-radius:4px"></i><i class="l" style="background:#e8ebf2;height:26px;border-radius:4px"></i></div>
        </div>
        <div class="skin-meta"><b>灵枢青</b><span class="sw"><i style="background:#5e7ce0"></i><i style="background:#3dcc7e"></i><i style="background:#e4e7ee"></i></span><p>出厂默认主题，复刻 DevUI 设计语言：靛蓝主色、冷灰画布、纯白面板、小圆角与轻阴影。</p></div>
      </div>
      <div class="skin-card reveal" style="transition-delay:.08s">
        <div class="skin-view" style="background:#dfe3e2">
          <div class="mini mini-rail" style="background:#fafbfa"><i class="d" style="background:#236de9"></i><i class="l" style="background:#236de9;opacity:.55;width:62%"></i><i class="l" style="background:#236de9;opacity:.28;width:46%"></i></div>
          <div class="mini" style="background:#fbfcfc"><div class="row"><i class="p" style="background:#236de9"></i><i class="l l2" style="background:#236de9;opacity:.3"></i></div><i class="l" style="background:#eef1f0;height:26px;border-radius:8px"></i><i class="l" style="background:#eef1f0;height:26px;border-radius:8px"></i></div>
        </div>
        <div class="skin-meta"><b>珍珠玻璃</b><span class="sw"><i style="background:#236de9"></i><i style="background:#82dd2d"></i><i style="background:#dfe3e2"></i></span><p>经典主题：柔和灰绿画布、玻璃浮层与仪器化语汇，青柠点缀、层次柔和。</p></div>
      </div>
    </div>
  </section>

  <section class="sec" id="announce">
    <div class="sec-head reveal">
      <span class="eyebrow">Announcements</span>
      <h2>公告动态</h2>
      <p>来自后台「公告管理」——发布后实时展示在这里。</p>
    </div>
    <?php if ($announces === []) : ?>
    <div class="ann-empty reveal"><span class="ann-empty-ico"></span>公告即将发布，敬请期待。在后台「公告管理」发布后，这里会实时更新。</div>
    <?php else : ?>
    <div class="ann-grid">
      <?php $i = 0; foreach ($announces as $a) :
        $content = (string) ($a['content'] ?? '');
        $excerpt = mb_substr($content, 0, 90) . (mb_strlen($content) > 90 ? '…' : '');
        $delay = sprintf('transition-delay:%.2fs', $i * 0.07); $i++;
        if ($i > 6) break; ?>
      <article class="ann-card reveal" style="<?= $delay ?>">
        <div class="ann-meta"><span class="ann-dot"></span><time><?= $h($a['publishTime']) ?></time></div>
        <h3><?= $h($a['title']) ?></h3>
        <p><?= $h($excerpt) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</div>

<script type="application/ld+json">
<?= $faqJsonLd ?>
</script>
<footer>
  <div class="foot-inner">
    <div class="l"><b>灵枢中控 Linc-Admin</b><span>统一管理后台 · 兼具设计美学与高效开发</span></div>
    <div class="foot-links">
      <a href="/admin">管理后台</a>
      <a href="#features">系统亮点</a>
      <a href="#start">快速开始</a>
      <a href="#announce">公告动态</a>
    </div>
  </div>
  <div class="copyright">© <?= $h($year) ?> 灵枢中控 Linc-Admin · 基于 MIT License 开源 · v<?= $h($version) ?></div>
</footer>

<script>
(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var nav = document.getElementById('nav');
  function onScroll() {
    if (window.scrollY > 24) nav.classList.add('scrolled');
    else nav.classList.remove('scrolled');
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  var revealEls = document.querySelectorAll('.reveal');
  if (reduce || !('IntersectionObserver' in window)) {
    revealEls.forEach(function (el) { el.classList.add('in'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.12 });
    revealEls.forEach(function (el) { io.observe(el); });
  }

  function countUp(el) {
    var target = parseInt(el.getAttribute('data-count'), 10) || 0;
    if (reduce || target === 0) { el.textContent = String(target); return; }
    var t0 = null, DUR = 1400;
    function tick(ts) {
      if (!t0) t0 = ts;
      var p = Math.min((ts - t0) / DUR, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = String(Math.round(target * eased));
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
  var cntEls = document.querySelectorAll('.cnt');
  if ('IntersectionObserver' in window && !reduce) {
    var io2 = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { countUp(en.target); io2.unobserve(en.target); }
      });
    }, { threshold: 0.4 });
    cntEls.forEach(function (el) { io2.observe(el); });
  } else {
    cntEls.forEach(countUp);
  }

  var steps = document.querySelectorAll('.start-step');
  var titles = { p1: '环境准备', p2: '上传与安装', p3: '登录使用' };
  steps.forEach(function (btn) {
    btn.addEventListener('click', function () {
      steps.forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      var pane = btn.getAttribute('data-pane');
      document.querySelectorAll('.start-pane').forEach(function (p) { p.classList.remove('active'); });
      document.getElementById(pane).classList.add('active');
      document.getElementById('code-title').textContent = titles[pane] || '';
      document.querySelector('.copy').setAttribute('data-copy', pane);
    });
  });
  document.querySelector('.copy').addEventListener('click', function () {
    var pane = this.getAttribute('data-copy');
    var text = document.getElementById(pane).innerText;
    var self = this;
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(function () {
        self.textContent = '已复制';
        setTimeout(function () { self.textContent = '复制'; }, 1600);
      });
    }
  });

  var canvas = document.getElementById('space');
  if (canvas && canvas.getContext && !reduce) {
    var ctx = canvas.getContext('2d');
    var dots = [];
    var COLORS = ['94,124,224', '130,221,45', '238,244,242'];
    function resize() { canvas.width = canvas.offsetWidth; canvas.height = canvas.offsetHeight; }
    window.addEventListener('resize', resize);
    resize();
    var COUNT = Math.min(70, Math.max(34, Math.floor(canvas.width / 24)));
    for (var i = 0; i < COUNT; i++) {
      dots.push({
        x: Math.random() * canvas.width, y: Math.random() * canvas.height,
        vx: (Math.random() - .5) * .3, vy: (Math.random() - .5) * .3,
        r: Math.random() * 1.6 + .7, c: COLORS[Math.floor(Math.random() * COLORS.length)]
      });
    }
    function frame() {
      if (canvas.offsetParent === null) { requestAnimationFrame(frame); return; }
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      for (var i = 0; i < dots.length; i++) {
        var d = dots[i];
        d.x += d.vx; d.y += d.vy;
        if (d.x < 0 || d.x > canvas.width) d.vx *= -1;
        if (d.y < 0 || d.y > canvas.height) d.vy *= -1;
        ctx.beginPath();
        ctx.arc(d.x, d.y, d.r, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(' + d.c + ',.5)';
        ctx.fill();
        for (var j = i + 1; j < dots.length; j++) {
          var o = dots[j], dx = d.x - o.x, dy = d.y - o.y, ds = dx * dx + dy * dy;
          if (ds < 140 * 140) {
            ctx.beginPath();
            ctx.moveTo(d.x, d.y); ctx.lineTo(o.x, o.y);
            ctx.strokeStyle = 'rgba(94,124,224,' + (0.15 * (1 - ds / (140 * 140))) + ')';
            ctx.lineWidth = 1; ctx.stroke();
          }
        }
      }
      requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }
})();
</script>
</body>
</html>
