<?php

/**
 * 配置样例。正式环境由安装向导自动生成 config/config.php；
 * 也可以参照本文件手工创建（跳过安装向导：创建本文件后自行删除 runtime/install.lock 说明）。
 */
return [
    'database' => [
        'host' => 'localhost',       // MySQL 地址
        'port' => 3306,              // MySQL 端口
        'name' => 'linc_admin', // 数据库名（只能字母数字下划线）
        'username' => 'root',        // 数据库账号
        'password' => '',            // 数据库密码
        'timezone' => '+08:00',      // 会话时区，与 Java 版 DB_TIMEZONE 对应
    ],
    'jwt' => [
        'secret' => '请替换为至少 32 字节的随机密钥，安装向导会自动生成 64 位十六进制密钥',
        'expires_seconds' => 7200,   // 登录有效期（秒），与 Java 版一致
    ],
    'cache' => [
        'driver' => 'file',          // file（默认，零依赖）或 redis
        'redis' => [
            'host' => 'localhost',
            'port' => 6379,
            'password' => '',
        ],
    ],
];
