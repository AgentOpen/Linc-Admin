<?php
/**
 * 门户前台视图：DevUI 风格浅色侧栏布局（侧栏 / Hero / 白卡 / 推荐 / 价值观 / 公告 / 页脚）。
 * PortalController::render() 提供：$skin, $primary, $primaryHover, $primarySoft, $primaryRgb,
 * $seo(title/description/keywords), $origin, $announces, $total, $version, $year, $h。
 */
$brand = '灵枢中控';
$brandEn = 'Linc-Admin';
?>
<!DOCTYPE html>
<html lang="zh-CN" data-skin="<?= $h($skin) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $h($seo['title']) ?></title>
<meta name="description" content="<?= $h($seo['description']) ?>">
<meta name="keywords" content="<?= $h($seo['keywords']) ?>">
<meta name="author" content="<?= $h($brand) ?> <?= $h($brandEn) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<link rel="canonical" href="<?= $h($origin) ?>/">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= $h($brand) ?> <?= $h($brandEn) ?>">
<meta property="og:title" content="<?= $h($seo['title']) ?>">
<meta property="og:description" content="<?= $h($seo['description']) ?>">
<meta property="og:url" content="<?= $h($origin) ?>/">
<meta property="og:locale" content="zh_CN">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= $h($seo['title']) ?>">
<meta name="twitter:description" content="<?= $h($seo['description']) ?>">
<script type="application/ld+json">
<?= $jsonLd ?>
</script>
<script type="application/ld+json">
<?= $faqJsonLd ?>
</script>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cpath d='M6 3 L26 10 L14 15 Z' fill='%233D5AFE'/%3E%3Cpath d='M14 15 L26 10 L20 29 Z' fill='%235E7CE0'/%3E%3Cpath d='M6 3 L14 15 L20 29 L11 20 Z' fill='%237B9BF5' opacity='.85'/%3E%3C/svg%3E">
<style>
/* ═══ 灵枢青 · DevUI 风格 · 浅色侧栏布局 ═══ */
:root {
  font-family: "PingFang SC", "Helvetica Neue", "Microsoft YaHei", Arial, sans-serif;
  --primary: #5E7CE0;
  --primary-light: #7B9BF5;
  --primary-dark: #3D5AFE;
  --text: #252B3A;
  --text-2: #575D6C;
  --text-3: #8A8E99;
  --bg: #F5F7FD;
  --card: #FFFFFF;
  --border: #E3E5E9;
  --lime: #82DD2D;
  --red: #D9312C;
  --sidebar-w: 220px;
  --ease: cubic-bezier(.25, .8, .25, 1);
  --shadow-card: 0 2px 12px rgba(94,124,224,.08);
  --shadow-card-hover: 0 8px 30px rgba(94,124,224,.16);
}
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation: none !important; transition: none !important; }
  .reveal, .reveal.in { opacity: 1 !important; transform: none !important; }
}
* { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { color: var(--text); background: var(--bg); font-size: 14px; line-height: 1.6; }
a { text-decoration: none; color: inherit; }
img, svg { display: block; }
button { font-family: inherit; cursor: pointer; }

.sidebar {
  position: fixed; top: 0; left: 0; bottom: 0; width: var(--sidebar-w); z-index: 100;
  background: #fff; border-right: 1px solid var(--border);
  display: flex; flex-direction: column;
  transition: transform .3s var(--ease);
}
.sidebar-logo { display: flex; align-items: center; gap: 10px; padding: 24px 20px 28px; }
.logo-mark { width: 32px; height: 32px; flex: none; }
.logo-text { font-size: 18px; font-weight: 700; color: var(--primary); letter-spacing: .5px; }
.logo-sub { font-size: 10px; color: var(--text-3); display: block; }
.sidebar-nav { flex: 1; padding: 0 12px; display: flex; flex-direction: column; gap: 2px; }
.nav-item {
  display: flex; align-items: center; gap: 12px; padding: 11px 14px;
  border-radius: 8px; font-size: 14px; color: var(--text-2);
  transition: background .2s, color .2s;
}
.nav-item:hover, .nav-item.active { background: #F0F3FF; color: var(--primary); }
.nav-item .nav-icon { width: 20px; height: 20px; flex: none; }
.nav-item .eco-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--lime); position: absolute; margin-left: 14px; margin-top: -14px; }
.sidebar-bottom { padding: 16px 20px; display: flex; gap: 12px; align-items: center; border-top: 1px solid var(--border); }
.side-emoji { font-size: 18px; }
.side-lang, .side-g { font-size: 11px; font-weight: 600; color: var(--text-3); border: 1px solid var(--border); border-radius: 4px; padding: 2px 6px; }
.side-headset { width: 18px; height: 18px; color: var(--text-3); }

.main { margin-left: var(--sidebar-w); min-height: 100vh; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 32px; }

.hero { padding: 48px 0 20px; }
.banner-card {
  display: flex; align-items: center; justify-content: space-between; gap: 30px;
  background: linear-gradient(135deg, #EBF1FF 0%, #F5F8FF 50%, #EFF4FF 100%);
  border-radius: 20px; padding: 48px 52px; overflow: hidden;
  position: relative;
}
.banner-card::before {
  content: ""; position: absolute; top: -60px; right: -60px; width: 280px; height: 280px;
  border-radius: 50%; background: radial-gradient(circle, rgba(94,124,224,.08), transparent 70%);
  animation: float 8s ease-in-out infinite;
}
@keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-16px); } }
.design-title { font-size: clamp(32px, 4.5vw, 48px); font-weight: 720; line-height: 1.15; color: var(--text); margin-bottom: 14px; }
.design-title .accent { color: var(--primary); }
.banner-sub { font-size: 15.5px; color: var(--text-2); line-height: 1.8; max-width: 480px; margin-bottom: 28px; }
.banner-btns { display: flex; gap: 14px; flex-wrap: wrap; }
.btn {
  display: inline-flex; align-items: center; gap: 8px; height: 44px; padding: 0 28px;
  border-radius: 8px; font-size: 14.5px; font-weight: 600; border: none;
  transition: transform .2s var(--ease), box-shadow .2s;
}
.btn-primary { background: var(--primary); color: #fff; box-shadow: 0 4px 16px rgba(94,124,224,.3); }
.btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(94,124,224,.4); }
.btn-ghost { background: #fff; color: var(--primary); border: 1px solid var(--primary); }
.btn-ghost:hover { background: #F0F3FF; transform: translateY(-2px); }
.banner-art { flex: none; animation: float 6s ease-in-out infinite; }
.banner-art svg { width: 420px; height: auto; }

.cards-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 24px; }
.white-card {
  background: var(--card); border-radius: 16px; padding: 28px;
  display: flex; align-items: center; gap: 22px;
  border: 1px solid var(--border); box-shadow: var(--shadow-card);
  transition: transform .25s var(--ease), box-shadow .25s;
}
.white-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-card-hover); }
.card-illus { width: 88px; height: 88px; flex: none; }
.card-title { font-size: 16px; font-weight: 680; margin-bottom: 6px; }
.card-desc { font-size: 13px; color: var(--text-2); line-height: 1.7; }

.section { padding: 56px 0 20px; }
.content-title { font-size: clamp(22px, 3vw, 30px); font-weight: 720; margin-bottom: 28px; text-align: center; }

.reco-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.reco-card {
  border-radius: 18px; overflow: hidden; position: relative; min-height: 280px;
  display: flex; flex-direction: column; justify-content: flex-end;
  transition: transform .3s var(--ease), box-shadow .3s;
}
.reco-card:hover { transform: translateY(-6px); box-shadow: 0 20px 60px rgba(0,0,0,.2); }
.reco-card:hover .reco-arrow { transform: translateX(6px); }
.reco-text { position: absolute; top: 24px; left: 28px; z-index: 2; }
.reco-name { font-size: 20px; font-weight: 720; color: #fff; margin-bottom: 4px; }
.reco-sub { font-size: 13px; color: rgba(255,255,255,.75); }
.reco-art { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.reco-arrow {
  position: absolute; bottom: 24px; right: 24px; z-index: 2;
  width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,.2);
  display: grid; place-items: center; transition: transform .25s var(--ease);
}
.reco-arrow svg { width: 18px; height: 18px; }

.values-card {
  display: grid; grid-template-columns: 1fr 1fr; gap: 0;
  border-radius: 18px; overflow: hidden;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
  color: #fff;
}
.values-purple { padding: 40px 44px; position: relative; overflow: hidden; }
.values-title { font-size: 20px; font-weight: 700; margin-bottom: 14px; }
.values-desc { font-size: 14px; line-height: 1.85; opacity: .85; margin-bottom: 24px; }
.values-more {
  display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600;
  color: #fff; border-bottom: 1px solid rgba(255,255,255,.4); padding-bottom: 3px;
  transition: border-color .2s;
}
.values-more:hover { border-color: #fff; }
.values-more .more-arrow { width: 16px; height: 16px; }
.values-deco { position: absolute; top: 0; right: 0; height: 100%; opacity: .5; }
.values-right { display: grid; grid-template-columns: 1fr 1fr; gap: 0; background: #fff; }
.value-item {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 32px 16px; gap: 12px; border: .5px solid var(--border);
  transition: background .25s;
}
.value-item:hover { background: #F8FAFF; }
.value-line { width: 28px; height: 2px; background: var(--primary); border-radius: 2px; }
.value-name { font-size: 16px; font-weight: 680; color: var(--text); }
.cards-row + .cards-row { margin-top: 20px; }

.ann-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
.ann-card {
  background: var(--card); border: 1px solid var(--border); border-radius: 14px;
  padding: 24px; transition: transform .28s var(--ease), box-shadow .28s;
}
.ann-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-card-hover); }
.ann-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
.ann-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--primary); box-shadow: 0 0 0 3px rgba(94,124,224,.15); }
.ann-meta time { font-family: var(--mono, monospace); font-size: 12px; color: var(--text-3); }
.ann-card h3 { font-size: 14.5px; font-weight: 650; margin-bottom: 9px; }
.ann-card p { font-size: 12.8px; line-height: 1.75; color: var(--text-2); }
.ann-empty {
  padding: 48px; text-align: center; color: var(--text-3); font-size: 13.5px;
  border: 1px dashed var(--border); border-radius: 14px; background: #FAFBFC;
}
.ann-empty-ico { display: block; width: 34px; height: 34px; margin: 0 auto 12px; border-radius: 50%; background: #F0F3FF; position: relative; }
.ann-empty-ico::after { content: ""; position: absolute; inset: 12px; border-radius: 50%; background: var(--primary); opacity: .2; }

.partners { position: relative; padding: 56px 0; overflow: hidden; }
.map-bg { position: absolute; inset: 0; width: 100%; height: 100%; opacity: .5; }
.partners-inner { position: relative; }
.partner-logos { display: flex; gap: 32px; flex-wrap: wrap; align-items: center; margin-top: 20px; }
.p-logo { font-size: 14px; font-weight: 620; color: var(--text-3); display: flex; align-items: center; gap: 8px; padding: 12px 20px; border: 1px solid var(--border); border-radius: 10px; background: #fff; transition: border-color .2s, box-shadow .2s; }
.p-logo:hover { border-color: var(--primary); box-shadow: var(--shadow-card); }
.p-logo .d-mark, .p-logo .g-mark { width: 20px; height: 20px; border-radius: 5px; background: var(--primary); color: #fff; display: grid; place-items: center; font-size: 12px; font-weight: 700; }
.ie-cloud-icon { width: 28px; height: 20px; }
.ie-txt { font-size: 11px; line-height: 1.2; }
.ie-txt em { font-style: normal; font-size: 10px; color: var(--text-3); }

.footer { background: #fff; border-top: 1px solid var(--border); padding: 40px 0 30px; }
.footer-inner { max-width: 1200px; margin: 0 auto; padding: 0 32px; }
.footer-title { font-size: 15px; font-weight: 680; margin-bottom: 18px; }
.footer-links { display: flex; gap: 28px; flex-wrap: wrap; }
.footer-links a { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-2); transition: color .2s; }
.footer-links a:hover { color: var(--primary); }
.f-ico { width: 20px; height: 20px; display: grid; place-items: center; border-radius: 4px; font-size: 11px; font-weight: 700; }
.f-gitcode { background: var(--primary); color: #fff; }
.f-team { background: #F0F3FF; }
.f-zhihu { background: #F0F3FF; color: var(--primary); }
.f-wechat { background: #E8F8E8; }
.footer-divider { border-top: 1px solid var(--border); margin: 24px 0 18px; }
.footer-bottom { display: flex; align-items: center; gap: 14px; font-size: 12px; color: var(--text-3); }
.logo-mark.small { width: 22px; height: 22px; }

.reveal { opacity: 0; transform: translateY(24px); transition: opacity .7s var(--ease), transform .7s var(--ease); }
.reveal.in { opacity: 1; transform: none; }
.reveal-d1 { transition-delay: .08s; }
.reveal-d2 { transition-delay: .16s; }
.reveal-d3 { transition-delay: .24s; }

@media (max-width: 900px) {
  .sidebar { transform: translateX(-100%); }
  .main { margin-left: 0; }
  .banner-card { flex-direction: column; padding: 32px; }
  .banner-art svg { width: 100%; max-width: 380px; }
  .cards-row, .reco-grid, .ann-grid { grid-template-columns: 1fr; }
  .values-card { grid-template-columns: 1fr; }
  .values-right { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <svg class="logo-mark" viewBox="0 0 32 32" aria-hidden="true">
      <path d="M6 3 L26 10 L14 15 Z" fill="#3D5AFE"/><path d="M14 15 L26 10 L20 29 Z" fill="#5E7CE0"/><path d="M6 3 L14 15 L20 29 L11 20 Z" fill="#7B9BF5" opacity=".85"/>
    </svg>
    <div><span class="logo-text">灵枢中控</span><span class="logo-sub">Linc-Admin · 统一管理后台</span></div>
  </div>
  <nav class="sidebar-nav">
    <a class="nav-item" href="#features"><svg viewBox="0 0 24 24" class="nav-icon"><circle cx="12" cy="6" r="2.2" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="5" y="13" width="14" height="7" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M9 10.5h6" stroke="currentColor" stroke-width="1.8"/></svg><span>系统亮点</span></a>
    <a class="nav-item" href="#start"><svg viewBox="0 0 24 24" class="nav-icon"><rect x="4" y="4" width="7" height="7" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="13" y="4" width="7" height="7" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="4" y="13" width="7" height="7" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="13" y="13" width="7" height="7" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.8"/></svg><span>快速开始</span></a>
    <a class="nav-item nav-eco" href="#skins"><i class="eco-dot"></i><svg viewBox="0 0 24 24" class="nav-icon"><path d="M12 3 L21 8 L12 13 L3 8 Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M6 10.5 V16 c0 1.5 2.7 3 6 3 s6-1.5 6-3 v-5.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg><span>主题皮肤</span></a>
    <a class="nav-item" href="#announce"><svg viewBox="0 0 24 24" class="nav-icon"><path d="M18 8 A6 6 0 0 0 6 8 c0 7-3 9-3 9 h18 s-3-2-3-9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M13.7 21 a2 2 0 0 1-3.4 0" fill="none" stroke="currentColor" stroke-width="1.8"/></svg><span>公告动态</span></a>
  </nav>
  <div class="sidebar-bottom">
    <span class="side-emoji">🤗</span>
    <span class="side-lang">CN</span>
    <span class="side-g">G</span>
    <svg viewBox="0 0 24 24" class="nav-icon side-headset"><path d="M4 13a8 8 0 0 1 16 0" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="3" y="13" width="4" height="6" rx="2" fill="currentColor"/><rect x="17" y="13" width="4" height="6" rx="2" fill="currentColor"/></svg>
  </div>
</aside>

<main class="main">

  <section class="hero container">
    <div class="banner-card reveal in">
      <div class="banner-text">
        <h1 class="design-title">Linc-Admin<br><span class="accent">灵枢中控</span></h1>
        <p class="banner-sub">兼具设计美学与高效开发的统一管理后台：账号权限、组织架构、审计日志与多主题体系开箱即用，Web 安装向导三步完成部署。</p>
        <div class="banner-btns">
          <a class="btn btn-primary" href="/admin">进入管理后台</a>
          <a class="btn btn-ghost" href="#start">快 速 开 始</a>
        </div>
      </div>
      <div class="banner-art" aria-hidden="true">
        <svg viewBox="0 0 500 340" width="500" height="340">
          <defs>
            <linearGradient id="gTile" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#AECBFF"/><stop offset="1" stop-color="#7FA8F5"/></linearGradient>
            <linearGradient id="gPanel" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#DCEBFF"/><stop offset="1" stop-color="#B9D4FB"/></linearGradient>
            <linearGradient id="gGauge" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#67E0A3"/><stop offset=".5" stop-color="#F7C948"/><stop offset="1" stop-color="#F08A5D"/></linearGradient>
            <linearGradient id="gImg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#9CC4FF"/><stop offset="1" stop-color="#6FA5F2"/></linearGradient>
          </defs>
          <rect x="120" y="18" width="330" height="240" rx="20" fill="url(#gPanel)" opacity=".92"/>
          <circle cx="142" cy="40" r="5" fill="#5AC46B"/><circle cx="160" cy="40" r="5" fill="#F7C948"/><circle cx="178" cy="40" r="5" fill="#F08A5D"/>
          <rect x="140" y="58" width="88" height="88" rx="16" fill="url(#gTile)"/>
          <text x="184" y="118" font-size="44" font-weight="700" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif">Aa</text>
          <rect x="132" y="50" width="104" height="104" rx="18" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-dasharray="6 6" opacity=".8"/>
          <rect x="248" y="58" width="150" height="26" rx="13" fill="#FFFFFF" opacity=".85"/>
          <circle cx="270" cy="71" r="6" fill="none" stroke="#8AA8E8" stroke-width="2"/><path d="M275 76 l6 6" stroke="#8AA8E8" stroke-width="2"/>
          <g><rect x="248" y="96" width="30" height="30" rx="8" fill="#FFFFFF" opacity=".9"/><rect x="286" y="96" width="30" height="30" rx="8" fill="#FFFFFF" opacity=".9"/><rect x="324" y="96" width="30" height="30" rx="8" fill="#FFFFFF" opacity=".9"/><rect x="362" y="96" width="30" height="30" rx="8" fill="#FFFFFF" opacity=".9"/><circle cx="263" cy="111" r="7" fill="none" stroke="#7FA8F5" stroke-width="2"/><rect x="294" y="104" width="14" height="14" rx="3" fill="none" stroke="#7FA8F5" stroke-width="2"/><path d="M331 118 l7-10 7 10 z" fill="#7FA8F5"/><rect x="368" y="104" width="18" height="14" rx="3" fill="none" stroke="#7FA8F5" stroke-width="2"/></g>
          <rect x="248" y="140" width="120" height="96" rx="14" fill="url(#gImg)"/>
          <path d="M262 216 l30-34 20 22 14-14 26 26 z" fill="#F5A623"/><circle cx="338" cy="168" r="10" fill="#F08A5D"/>
          <path d="M254 150 v-6 h6 M362 150 v-6 h-6 M254 226 v6 h6 M362 226 v6 h-6" stroke="#FFF" stroke-width="3" fill="none"/>
          <rect x="384" y="150" width="52" height="26" rx="13" fill="#FFFFFF" opacity=".9"/><circle cx="423" cy="163" r="9" fill="#7FA8F5"/>
          <rect x="228" y="58" width="14" height="178" rx="7" fill="#FFFFFF" opacity=".7"/><circle cx="235" cy="120" r="9" fill="#7FA8F5"/>
          <g><rect x="18" y="88" width="112" height="150" rx="14" fill="#EAF3FF" opacity=".95"/><circle cx="42" cy="102" r="4" fill="#5AC46B"/><circle cx="56" cy="102" r="4" fill="#F7C948"/><circle cx="70" cy="102" r="4" fill="#F08A5D"/><circle cx="74" cy="164" r="34" fill="none" stroke="#E2ECFB" stroke-width="9"/><circle cx="74" cy="164" r="34" fill="none" stroke="url(#gGauge)" stroke-width="9" stroke-linecap="round" stroke-dasharray="152 213.6" transform="rotate(-90 74 164)"/><text x="74" y="170" font-size="16" font-weight="700" text-anchor="middle" fill="#5E7CE0">66%</text><rect x="38" y="212" width="72" height="8" rx="4" fill="#C9D9F8"/></g>
          <rect x="150" y="248" width="92" height="28" rx="14" fill="#C7DBFB" opacity=".9"/><text x="188" y="267" font-size="14" text-anchor="middle" fill="#5E7CE0" font-family="Arial">click me</text><path d="M246 262 l16 6 -7 3 -3 7 z" fill="#5E7CE0"/>
          <g transform="translate(430 76) rotate(40)"><path d="M0 -22 C10 -12 12 4 6 16 L-6 16 C-12 4 -10 -12 0 -22 Z" fill="#E8EEFB"/><path d="M-6 16 L-12 28 L-2 22 Z" fill="#F08A5D"/><path d="M6 16 L12 28 L2 22 Z" fill="#F08A5D"/><circle cx="0" cy="-2" r="5" fill="#7FA8F5"/></g>
          <g transform="translate(398 236)"><path d="M0 10 L8 0 L48 0 L48 34 L0 34 Z" fill="#F7C948"/><path d="M-6 12 L44 12 L38 40 L-12 40 Z" fill="#FFD97A"/><circle cx="42" cy="38" r="8" fill="#5AC46B"/><path d="M38 38 l3 3 5-6" stroke="#FFF" stroke-width="2" fill="none"/></g>
          <g transform="translate(462 288)"><circle r="15" fill="#F7C948"/><circle cx="-5" cy="-3" r="2" fill="#7A5B00"/><circle cx="5" cy="-3" r="2" fill="#7A5B00"/><path d="M-6 4 Q0 11 6 4" stroke="#7A5B00" stroke-width="2" fill="none"/></g>
          <path d="M40 40 h8 M44 36 v8" stroke="#F5A623" stroke-width="2"/><path d="M470 30 h10 M475 25 v10" stroke="#7FA8F5" stroke-width="2"/><path d="M100 262 h8 M104 258 v8" stroke="#B8CCF3" stroke-width="2"/>
        </svg>
      </div>
    </div>

    <div class="cards-row">
      <a class="white-card reveal" href="/admin">
        <svg class="card-illus" viewBox="0 0 96 96" aria-hidden="true">
          <defs><linearGradient id="gPz1" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#7FA8F5"/><stop offset="1" stop-color="#4A7BE8"/></linearGradient></defs>
          <path d="M18 30 h18 a8 8 0 0 1 16 0 h18 v18 a8 8 0 0 0 0 16 v18 h-20 a8 8 0 0 0-16 0 h-16 v-18 a8 8 0 0 1 0-16 z" fill="url(#gPz1)"/>
          <path d="M52 52 h12 a7 7 0 0 1 14 0 h12 v14 a7 7 0 0 0 0 14 v12 h-38 v-14 a7 7 0 0 1 0-14 z" fill="#E85D5D" opacity=".92"/>
          <path d="M66 70 l12 5 -5 2 -2 5 z" fill="#5E7CE0"/>
        </svg>
        <div><h3 class="card-title">环境搭建</h3><p class="card-desc">六步安装向导引导新手快速完成部署，零命令行</p></div>
      </a>
      <a class="white-card reveal reveal-d1" href="/admin">
        <svg class="card-illus" viewBox="0 0 96 96" aria-hidden="true">
          <rect x="14" y="18" width="68" height="46" rx="6" fill="none" stroke="#4A7BE8" stroke-width="4"/>
          <rect x="20" y="24" width="56" height="34" rx="3" fill="#EAF2FF"/>
          <circle cx="40" cy="38" r="6" fill="#F7C948"/>
          <path d="M30 58 c2-8 18-8 20 0 z" fill="#F5A623"/>
          <rect x="52" y="28" width="20" height="3" rx="1.5" fill="#9CC4FF"/>
          <rect x="52" y="35" width="20" height="3" rx="1.5" fill="#9CC4FF"/>
          <rect x="52" y="42" width="14" height="3" rx="1.5" fill="#9CC4FF"/>
          <rect x="42" y="64" width="12" height="8" fill="#9CC4FF"/>
          <rect x="32" y="72" width="32" height="5" rx="2.5" fill="#4A7BE8"/>
        </svg>
        <div><h3 class="card-title">灵枢中控之旅</h3><p class="card-desc">完整教程带你从安装到二次开发，基于 Slim 4 + Vue 3 构建应用</p></div>
      </a>
    </div>
  </section>

  <section class="section container" id="features">
    <h2 class="content-title reveal">系统亮点</h2>
    <div class="values-card reveal">
      <div class="values-purple">
        <h3 class="values-title">设计价值观</h3>
        <p class="values-desc">灵枢中控的设计价值观源于「高效、开放、可信、乐趣」四种理念——为设计师与开发者提供标准的设计体系，令牌级多主题适配各类落地场景，开箱即用。</p>
        <a class="values-more" href="/admin">进入管理后台
          <svg viewBox="0 0 24 24" class="more-arrow" style="width:16px;height:16px"><path d="M3 12 h16 M13 6 l6 6 -6 6" fill="none" stroke="#FFF" stroke-width="2"/></svg>
        </a>
        <svg class="values-deco" viewBox="0 0 200 300" aria-hidden="true"><circle cx="180" cy="80" r="90" fill="#FFFFFF" opacity=".08"/><path d="M20 300 C60 200 140 180 200 160" stroke="#FFFFFF" opacity=".12" stroke-width="40" fill="none"/></svg>
      </div>
      <div class="values-right">
        <div class="value-item"><span class="value-name" style="color:var(--primary);font-size:20px">高效</span></div>
        <div class="value-item"><span class="value-name" style="color:var(--primary);font-size:20px">开放</span></div>
        <div class="value-item"><span class="value-name" style="color:var(--primary);font-size:20px">可信</span></div>
        <div class="value-item"><span class="value-name" style="color:var(--primary);font-size:20px">乐趣</span></div>
      </div>
    </div>
    <div class="cards-row" style="margin-top:20px">
      <a class="white-card reveal" href="/admin">
        <div><h3 class="card-title left">认证与权限体系</h3><p class="card-desc left">JWT 会话 + 菜单按钮两级权限码，前后端同一套权限标识；数据权限五种粒度：全部、本部门及下级、本部门、自定义、仅本人</p></div>
      </a>
      <a class="white-card reveal reveal-d1" href="/admin">
        <div><h3 class="card-title left">全程操作审计</h3><p class="card-desc left">操作日志与登录日志自动记录（敏感参数脱敏），趋势、泳道、热力与异常聚合一屏尽览</p></div>
      </a>
    </div>
  </section>

  <section class="section container" id="skins">
    <h2 class="content-title reveal">主题皮肤</h2>
    <div class="reco-grid">
      <div class="reco-card reveal" style="background:linear-gradient(135deg,#5E7CE0,#7B9BF5)">
        <div class="reco-text"><p class="reco-name">灵枢青</p><p class="reco-sub">出厂默认 · DevUI 风格</p></div>
        <span class="reco-arrow"><svg viewBox="0 0 24 24"><path d="M4 12 h14 M13 6 l6 6 -6 6" fill="none" stroke="#FFF" stroke-width="2"/></svg></span>
      </div>
      <div class="reco-card reveal reveal-d1" style="background:linear-gradient(135deg,#2D4A6E,#1A3A5E)">
        <div class="reco-text"><p class="reco-name">珍珠玻璃</p><p class="reco-sub">经典主题 · 玻璃拟态</p></div>
        <span class="reco-arrow"><svg viewBox="0 0 24 24"><path d="M4 12 h14 M13 6 l6 6 -6 6" fill="none" stroke="#FFF" stroke-width="2"/></svg></span>
      </div>
    </div>
  </section>

  <section class="section container" id="announce">
    <h2 class="content-title reveal">公告动态</h2>
    <?php if ($announces === []) : ?>
    <div class="ann-empty reveal"><span class="ann-empty-ico"></span>公告即将发布，敬请期待。在后台「公告管理」发布后，这里会实时更新。</div>
    <?php else : ?>
    <div class="ann-grid">
      <?php $i = 0; foreach ($announces as $a) :
        $content = (string) ($a['content'] ?? '');
        $excerpt = mb_substr($content, 0, 90) . (mb_strlen($content) > 90 ? '…' : '');
        $delay = $i > 0 ? sprintf('transition-delay:%.2fs', $i * 0.07) : ''; $i++;
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

  <section class="section container" id="start">
    <h2 class="content-title reveal">快速开始</h2>
    <div class="cards-row">
      <a class="white-card reveal" href="/admin">
        <div><h3 class="card-title left">1. 环境准备</h3><p class="card-desc left">PHP ≥ 8.0（pdo_mysql）+ MySQL ≥ 5.7；推荐使用宝塔面板图形化安装</p></div>
      </a>
      <a class="white-card reveal reveal-d1" href="/admin">
        <div><h3 class="card-title left">2. 上传与安装</h3><p class="card-desc left">运行目录设为 public/ → 访问域名 → 六步安装向导完成部署</p></div>
      </a>
      <a class="white-card reveal reveal-d2" href="/admin">
        <div><h3 class="card-title left">3. 登录使用</h3><p class="card-desc left">管理员账号登录后台，发布公告、切换主题、管理用户权限</p></div>
      </a>
    </div>
  </section>
</main>

<footer class="footer">
  <div class="footer-inner">
    <p class="footer-title">找到我们</p>
    <div class="footer-links">
      <a href="/admin"><i class="f-ico f-gitcode">A</i>管理后台</a>
      <a href="#features"><i class="f-ico f-team">✨</i>系统亮点</a>
      <a href="#start"><i class="f-ico f-zhihu">📖</i>快速开始</a>
      <a href="#skins"><i class="f-ico f-wechat">🎨</i>主题皮肤</a>
    </div>
    <div class="footer-divider"></div>
    <div class="footer-bottom">
      <svg class="logo-mark small" viewBox="0 0 32 32"><path d="M6 3 L26 10 L14 15 Z" fill="#3D5AFE"/><path d="M14 15 L26 10 L20 29 Z" fill="#5E7CE0"/></svg>
      <span>© <?= $h($year) ?> 灵枢中控 Linc-Admin · MIT License · v<?= $h($version) ?></span>
    </div>
  </div>
</footer>

<script>
(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var revealEls = document.querySelectorAll('.reveal');
  if (reduce || !('IntersectionObserver' in window)) {
    revealEls.forEach(function (el) { el.classList.add('in'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.1 });
    revealEls.forEach(function (el) { io.observe(el); });
  }
})();
</script>
</main>
</body>
</html>
