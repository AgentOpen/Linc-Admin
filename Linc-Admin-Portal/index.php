<?php

/**
 * 兼容垫片：当服务器无法把运行目录设置为 public/ 时，
 * 把域名根目录指到本项目即可工作（推荐仍优先设置 public/ 为运行目录）。
 */
declare(strict_types=1);

require __DIR__ . '/public/index.php';
