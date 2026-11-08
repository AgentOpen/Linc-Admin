<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>服务暂不可用 · 灵枢中控</title>
<meta name="robots" content="noindex">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Ccircle cx='16' cy='16' r='14' fill='%23101418'/%3E%3Ccircle cx='16' cy='16' r='7' fill='%2382dd2d'/%3E%3C/svg%3E">
<style>
* { box-sizing: border-box; margin: 0; }
body {
  min-height: 100vh; display: grid; place-items: center; padding: 24px;
  font-family: "SF Pro Display", "PingFang SC", -apple-system, "Noto Sans SC", "Microsoft YaHei", sans-serif;
  color: #eef4f2; background: #0c0f14;
}
.card {
  max-width: 620px; width: 100%;
  background: rgba(22, 27, 34, .72); backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,.09); border-radius: 18px;
  box-shadow: 0 30px 90px rgba(0,0,0,.5);
  padding: 38px 42px;
}
.mark { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: rgba(255,255,255,.07); box-shadow: inset 0 1px 0 rgba(255,255,255,.14), 0 0 0 1px rgba(255,255,255,.08); margin-bottom: 22px; }
.mark i { width: 13px; height: 13px; border-radius: 50%; background: #ff7a71; display: block; box-shadow: 0 0 14px rgba(255,122,113,.8); }
h1 { font-size: 20px; font-weight: 680; margin: 0 0 14px; }
.card p { font-size: 13.5px; line-height: 1.9; color: rgba(238,244,242,.66); margin: 0 0 8px; }
.card code { font-family: ui-monospace, Consolas, monospace; font-size: 12px; color: #a9bdf5; background: rgba(94,124,224,.12); border-radius: 4px; padding: 1px 6px; }
footer { margin-top: 20px; font-size: 12px; color: rgba(238,244,242,.3); }
</style>
</head>
<body>
<div class="card">
  <div class="mark"><i></i></div>
  <h1>门户暂不可用</h1>
  <?php if (str_contains($message, '1045') || str_contains($message, 'Access denied')) : ?>
  <p>数据库账号或密码不正确（Access denied）。两种修复方式：</p>
  <p>1. 编辑 <code>config/config.php</code>，把 database.username / database.password 改为正确的数据库账号密码；</p>
  <p>2. 或删除 <code>runtime/install.lock</code> 与 <code>config/config.php</code>，访问 <code>/install.html</code> 重新安装。</p>
  <p>建议在面板（宝塔「数据库」页）创建专属数据库账号，而不是使用 root。</p>
  <?php elseif (!$installed) : ?>
  <p>系统尚未完成安装。请访问 <code>/install.html</code> 运行安装向导。</p>
  <?php else : ?>
  <p>数据库暂不可用（<?= $h(mb_substr($message, 0, 120)) ?>）：请确认 MySQL 已启动，并检查 <code>config/config.php</code> 中的连接信息。</p>
  <?php endif; ?>
  <footer>灵枢中控 Linc-Admin · 门户前台（服务端渲染）</footer>
</div>
</body>
</html>
