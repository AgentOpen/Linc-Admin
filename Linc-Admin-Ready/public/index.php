<?php

/**
 * 前置控制器（唯一 Web 入口）：
 *  - /api/**  → Slim 路由（未安装时仅开放安装接口与健康检查）
 *  - 其余 GET → public 下的静态资源，未命中则回退 index.html（Vue history 路由）
 *  - 未安装时任何页面请求都进入安装向导
 */
declare(strict_types=1);

define('LINC_BASE', dirname(__DIR__));

require LINC_BASE . '/vendor/autoload.php';

use Linc\Application\AppFactory;
use Linc\Core\Config;

/* ── 基础环境 ─────────────────────────────────────────────── */

$config = Config::load();
$dbConf = is_array($config['database'] ?? null) ? $config['database'] : [];
date_default_timezone_set(php_timezone_for_offset((string) ($dbConf['timezone'] ?? '+08:00')));

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rawurldecode($uri);

/* ── API：交给 Slim ───────────────────────────────────────── */

if ($uri === '/api' || str_starts_with($uri, '/api/')) {
    $installed = Config::installed();
    header('Content-Type: application/json;charset=utf-8');
    try {
        $app = AppFactory::create($installed);
        $app->run();
    } catch (\Throwable $error) {
        http_response_code(500);
        echo json_encode(
            \Linc\Core\R::error(500, '系统内部错误'),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        error_log((string) $error);
    }
    exit;
}

/* ── 页面与静态资源 ───────────────────────────────────────── */

if ($method !== 'GET' && $method !== 'HEAD') {
    http_response_code(404);
    header('Content-Type: application/json;charset=utf-8');
    echo json_encode(
        \Linc\Core\R::error(404, '资源不存在'),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$publicDir = __DIR__;

// 门户版开关：安装向导生成 config.php 时写入；true 时 `/` 为门户前台、`/admin` 为管理后台
$portalEnabled = ($config['portal']['enabled'] ?? false) === true;

// 未安装：一律进入安装向导页面（门户开关此时不生效）
if (!Config::installed()) {
    serveFile($publicDir . '/install.html');
    exit;
}

/* ── 门户版：/admin 管理后台静态资源与回退 ────────────────── */

if ($portalEnabled && ($uri === '/admin' || str_starts_with($uri, '/admin/'))) {
    $rel = trim(substr($uri, 6), '/');
    if ($rel === '') {
        $rel = 'index.html';
    }
    $adminBase = $publicDir . DIRECTORY_SEPARATOR . 'admin';
    $candidate = realpath($adminBase . '/' . $rel);
    if ($candidate !== false && is_file($candidate) && str_starts_with($candidate, $adminBase . DIRECTORY_SEPARATOR)) {
        serveFile($candidate);
        exit;
    }
    // SPA history 路由回退：/admin/dashboard 等 → admin/index.html
    serveFile($adminBase . DIRECTORY_SEPARATOR . 'index.html');
    exit;
}

/* ── 门户版：`/` 与其余页面路径 → 门户前台（服务端渲染） ───── */

if ($portalEnabled && $uri !== '/') {
    // 静态文件（如 favicon、robots）优先
    $candidate = realpath($publicDir . $uri);
    if ($candidate !== false && is_file($candidate) && str_starts_with($candidate, $publicDir . DIRECTORY_SEPARATOR)) {
        serveFile($candidate);
        exit;
    }
}

if ($portalEnabled) {
    \Linc\Controller\PortalController::render();
    exit;
}

if ($uri !== '/') {
    $candidate = realpath($publicDir . $uri);
    if ($candidate !== false && is_file($candidate) && str_starts_with($candidate, $publicDir . DIRECTORY_SEPARATOR)) {
        serveFile($candidate);
        exit;
    }
}

serveFile($publicDir . '/index.html');

/* ── 输出工具 ─────────────────────────────────────────────── */

function serveFile(string $file): void
{
    if (!is_file($file)) {
        http_response_code(404);
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(
            \Linc\Core\R::error(404, '资源不存在'),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        return;
    }
    $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
        'html' => 'text/html; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'js', 'mjs' => 'application/javascript; charset=utf-8',
        'json', 'map' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'txt' => 'text/plain; charset=utf-8',
        default => 'application/octet-stream',
    };
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($file));
    header('X-Content-Type-Options: nosniff');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
        return;
    }
    readfile($file);
}
