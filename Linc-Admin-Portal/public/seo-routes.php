<?php

/**
 * SEO / GEO 收录路由：robots.txt / sitemap.xml / llms.txt（动态生成，无需数据库）。
 * 由 public/index.php 在解析 $uri / $scheme 后 require 本文件。
 */

if ($uri === '/robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nAllow: /\nDisallow: /api/\nDisallow: /install.html\n\n";
    echo "# AI 引擎收录（GEO）：明确允许主流 AI 爬虫抓取门户内容\n";
    foreach (['GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-Web', 'anthropic-ai', 'PerplexityBot', 'Google-Extended', 'Bingbot'] as $bot) {
        echo "User-agent: {$bot}\nAllow: /\nDisallow: /api/\n\n";
    }
    echo 'Sitemap: ' . $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . "/sitemap.xml\n";
    exit;
}

if ($uri === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    $host = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $today = date('Y-m-d');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    echo "  <url><loc>{$host}/</loc><lastmod>{$today}</lastmod><changefreq>weekly</changefreq><priority>1.0</priority></url>\n";
    echo "</urlset>\n";
    exit;
}

if ($uri === '/llms.txt') {
    header('Content-Type: text/markdown; charset=utf-8');
    $host = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '');
    echo "# 灵枢中控 Linc-Admin\n\n";
    echo "> 统一管理后台 · 兼具设计美学与高效开发的 PHP 中后台模板。Web 安装向导三步完成部署，账号权限、数据权限、审计日志与多主题体系开箱即用。基于 MIT License 开源。\n\n";
    echo "## 系统概览\n\n";
    echo "- 后端：PHP 8.0+ / Slim 4 / PDO MySQL，零 Redis 依赖（可选启用）\n";
    echo "- 前端：Vue 3 / TypeScript / Vite，珍珠玻璃设计体系，多主题（灵枢青 DevUI 风格 / 珍珠玻璃）\n";
    echo "- 权限：菜单按钮两级授权 + 数据权限五种粒度\n";
    echo "- 审计：操作日志与登录日志，趋势 / 泳道 / 热力 / 异常聚合\n\n";
    echo "## 页面\n\n";
    echo "- [门户首页]({$host}/)：产品介绍与公告动态\n";
    echo "- [管理后台]({$host}/admin)：管理员登录入口\n\n";
    echo "## 核心能力\n\n";
    echo "- 认证：JWT 会话 + 验证码，退出即吊销\n";
    echo "- 权限：菜单 / 按钮两级授权，数据权限五种粒度\n";
    echo "- 审计：操作日志自动脱敏记录\n";
    echo "- 多主题：令牌级主题包，门户前台实时跟随后台换肤\n\n";
    exit;
}
