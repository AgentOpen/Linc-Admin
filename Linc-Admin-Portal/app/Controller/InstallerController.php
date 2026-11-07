<?php

declare(strict_types=1);

namespace Linc\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Linc\Core\BusinessException;
use Linc\Core\Config;
use Linc\Core\Database;
use Linc\Core\SqlScript;

/**
 * 安装向导接口（PHP 版新增能力，不改变任何后台行为）。
 *
 * 三步：env 环境检查 → test 连接验证 → run 建库导表种数据并落锁。
 * 已安装后所有安装接口返回 403「系统已安装」，install.lock 落在 runtime/（Web 不可达）。
 */
final class InstallerController
{
    private const REQUIRED_EXTENSIONS = ['pdo_mysql'];

    public static function env(Request $request, Response $response): Response
    {
        self::guardInstalled();
        $items = [
            ['key' => 'php', 'label' => 'PHP 版本 ≥ 8.0', 'pass' => PHP_VERSION_ID >= 80000, 'detail' => '当前 ' . PHP_VERSION],
            ['key' => 'pdo_mysql', 'label' => 'PDO MySQL 扩展', 'pass' => extension_loaded('pdo_mysql'), 'detail' => extension_loaded('pdo_mysql') ? '已安装' : '未安装'],
            ['key' => 'json', 'label' => 'JSON 扩展', 'pass' => extension_loaded('json'), 'detail' => extension_loaded('json') ? '已安装' : '未安装'],
            ['key' => 'zlib', 'label' => 'Zlib 扩展（代码生成 ZIP）', 'pass' => extension_loaded('zlib'), 'detail' => extension_loaded('zlib') ? '已安装' : '未安装'],
            ['key' => 'runtime', 'label' => 'runtime/ 目录可写', 'pass' => self::dirWritable(self::baseDir('runtime')), 'detail' => self::dirWritable(self::baseDir('runtime')) ? '可写' : '不可写'],
            ['key' => 'config', 'label' => 'config/ 目录可写', 'pass' => self::dirWritable(self::baseDir('config')), 'detail' => self::dirWritable(self::baseDir('config')) ? '可写' : '不可写'],
            ['key' => 'redis', 'label' => 'Redis（可选，未启用时使用文件缓存）', 'pass' => true, 'detail' => extension_loaded('redis') ? '扩展可用' : '未检测到扩展，文件缓存可满足全部功能'],
        ];
        $pass = true;
        foreach ($items as $item) {
            if (in_array($item['key'], self::REQUIRED_EXTENSIONS, true) && !$item['pass']) {
                $pass = false;
            }
        }
        if (!self::dirWritable(self::baseDir('runtime')) || !self::dirWritable(self::baseDir('config'))) {
            $pass = false;
        }
        return AuthController::ok($response, ['pass' => $pass, 'items' => $items]);
    }

    public static function test(Request $request, Response $response): Response
    {
        self::guardInstalled();
        $body = json_body((string) $request->getBody());
        $conf = self::databaseConf($body);
        try {
            $pdo = Database::pdoWithoutDatabase($conf);
        } catch (\RuntimeException $error) {
            throw new BusinessException('数据库连接失败：' . $error->getMessage());
        }
        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        if (version_compare($version, '5.7', '<')) {
            throw new BusinessException('MySQL 版本过低：' . $version . '，需要 5.7 及以上');
        }
        $dbName = $conf['name'];
        $exists = false;
        $statement = $pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
        $statement->execute([$dbName]);
        $exists = $statement->fetchColumn() !== false;

        $hasTables = false;
        if ($exists) {
            try {
                $statement = $pdo->prepare(
                    'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?'
                );
                $statement->execute([$dbName, 'sys_user']);
                $hasTables = (int) $statement->fetchColumn() > 0;
            } catch (\PDOException) {
                $hasTables = false;
            }
        }
        return AuthController::ok($response, [
            'version' => $version,
            'databaseExists' => $exists,
            'alreadyHasTables' => $hasTables,
            'canCreate' => $conf['createDatabase'] === true,
        ]);
    }

    public static function run(Request $request, Response $response): Response
    {
        self::guardInstalled();
        $body = json_body((string) $request->getBody());
        $conf = self::databaseConf($body);

        // 管理员账号（不填使用默认 admin / admin123，与原版种子一致）
        $adminUsername = trim((string) ($body['adminUsername'] ?? 'admin'));
        $adminUsername = $adminUsername !== '' ? $adminUsername : 'admin';
        if (!preg_match('/^[A-Za-z0-9_]{2,64}$/', $adminUsername)) {
            throw new BusinessException('管理员账号只能包含字母、数字和下划线，长度 2-64');
        }
        $adminPassword = (string) ($body['adminPassword'] ?? 'admin123');
        if (strlen($adminPassword) < 6 || strlen($adminPassword) > 64) {
            throw new BusinessException('管理员密码长度必须在6到64位之间');
        }

        try {
            $pdo = Database::pdoWithoutDatabase($conf);
        } catch (\RuntimeException $error) {
            throw new BusinessException('数据库连接失败：' . $error->getMessage());
        }

        // 1. 建库（不存在且允许时）
        $dbName = $conf['name'];
        $statement = $pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
        $statement->execute([$dbName]);
        $exists = $statement->fetchColumn() !== false;
        if (!$exists) {
            if ($conf['createDatabase'] !== true) {
                throw new BusinessException('数据库 ' . $dbName . ' 不存在，且未勾选自动创建');
            }
            $pdo->exec('CREATE DATABASE ' . self::quoteIdent($dbName) . " DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_general_ci");
        }

        // 2. 按 版本号 顺序导入 database/ 下全部 V*__*.sql（建表、种子数据与后续版本一并执行）
        try {
            Database::useOverride($conf);
            foreach (self::sqlFiles() as $file) {
                foreach (SqlScript::statements((string) file_get_contents($file)) as $sql) {
                    Database::execute($sql);
                }
            }
        } catch (\Throwable $error) {
            Database::useOverride(null);
            throw new BusinessException('初始化表结构失败：' . $error->getMessage());
        }

        // 3. 写入自定义管理员（覆盖种子中的 admin 记录）
        $hash = password_hash($adminPassword, PASSWORD_BCRYPT);
        Database::execute(
            'UPDATE sys_user SET username = ?, password = ?, nickname = ?, update_by = ? WHERE id = 1',
            [$adminUsername, $hash, '管理员', 'installer']
        );

        // 4. 生成配置（数据库 + 随机 JWT 密钥 + 缓存驱动）
        $jwtSecret = bin2hex(random_bytes(32));
        $cacheDriver = ($body['cacheDriver'] ?? 'file') === 'redis' ? 'redis' : 'file';
        $configExport = self::renderConfig($conf, $jwtSecret, $cacheDriver);
        $configFile = self::baseDir('config/config.php');
        if (@file_put_contents($configFile, $configExport) === false) {
            Database::useOverride(null);
            throw new BusinessException('配置文件写入失败：' . $configFile);
        }
        @chmod($configFile, 0640);
        Database::useOverride(null);

        // 5. 落锁：安装完成
        if (@file_put_contents(
            self::baseDir('runtime/install.lock'),
            json_encode([
                'installedAt' => date('Y-m-d H:i:s'),
                'adminUsername' => $adminUsername,
                'version' => self::version(),
            ], JSON_UNESCAPED_UNICODE) . "\n"
        ) === false) {
            throw new BusinessException('安装锁写入失败：' . self::baseDir('runtime/install.lock'));
        }

        $portalEnabled = (bool) Config::get('portal.enabled', false);
        return AuthController::ok($response, [
            'adminUsername' => $adminUsername,
            'loginPath' => $portalEnabled ? '/admin' : '/login',
            'portal' => $portalEnabled,
        ]);
    }

    /** 安装状态查询（供前端判断是否跳安装页；含数据库可达性，不含敏感信息）。 */
    public static function status(Request $request, Response $response): Response
    {
        $installed = Config::installed();
        $dbOk = null;
        if ($installed && is_file(Config::configFile())) {
            try {
                Database::scalar('SELECT 1');
                $dbOk = true;
            } catch (\Throwable) {
                $dbOk = false;
            }
        }
        return AuthController::ok($response, [
            'installed' => $installed,
            'lockExists' => is_file(dirname(__DIR__, 2) . '/runtime/install.lock'),
            'configExists' => is_file(Config::configFile()),
            'dbOk' => $dbOk,
        ]);
    }

    /* ── 私有工具 ─────────────────────────────────────────── */

    private static function guardInstalled(): void
    {
        if (Config::installed()) {
            throw new BusinessException('系统已安装，如需重新安装请删除 runtime/install.lock', 403);
        }
    }

    /** @return array{name:string,username:string,password:string,host:string,port:int,timezone:string,createDatabase:bool} */
    private static function databaseConf(array $body): array
    {
        $name = trim((string) ($body['database'] ?? 'linc_admin'));
        if ($name === '') {
            throw new BusinessException('数据库名不能为空');
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new BusinessException('数据库名只能包含字母、数字和下划线');
        }
        $host = trim((string) ($body['host'] ?? 'localhost'));
        if ($host === '') {
            $host = 'localhost';
        }
        $port = (int) ($body['port'] ?? 3306);
        if ($port < 1 || $port > 65535) {
            throw new BusinessException('数据库端口不正确');
        }
        $username = (string) ($body['username'] ?? '');
        if (trim($username) === '') {
            throw new BusinessException('数据库账号不能为空');
        }
        return [
            'host' => $host,
            'port' => $port,
            'name' => $name,
            'username' => $username,
            'password' => (string) ($body['password'] ?? ''),
            'timezone' => (string) ($body['timezone'] ?? '+08:00'),
            'createDatabase' => ($body['createDatabase'] ?? true) === true,
        ];
    }

    /** Database::useOverride 的连接结构（带库名，供正式 PDO 使用）。 */
    private static function baseDir(string $relative): string
    {
        return self::projectRoot() . '/' . $relative;
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    /** @return list<string> database/ 目录下全部 V*__*.sql，按版本号升序 */
    private static function sqlFiles(): array
    {
        $dir = self::baseDir('database');
        $files = glob($dir . '/V*.sql') ?: [];
        $files = array_values(array_filter($files, static fn (string $file): bool => (bool) preg_match('/V\d+__.*\.sql$/', $file)));
        usort($files, static function (string $a, string $b): int {
            $va = (int) substr(basename($a), 1, (int) strpos(basename($a), '__') - 1);
            $vb = (int) substr(basename($b), 1, (int) strpos(basename($b), '__') - 1);
            return $va <=> $vb;
        });
        if ($files === []) {
            throw new BusinessException('初始化脚本缺失：database/ 目录下没有 V*.sql');
        }
        return $files;
    }

    private static function dirWritable(string $dir): bool
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return is_dir($dir) && is_writable($dir);
    }

    private static function quoteIdent(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    private static function renderConfig(array $conf, string $jwtSecret, string $cacheDriver): string
    {
        $export = static function (mixed $value): string {
            return var_export($value, true);
        };
        return <<<PHP
        <?php

        // 由安装向导自动生成；如需修改数据库或密钥，可编辑本文件。
        // cache.driver: file（默认，零依赖）或 redis（需 Predis/Redis 可用）。

        return [
            'database' => [
                'host' => {$export($conf['host'])},
                'port' => {$export($conf['port'])},
                'name' => {$export($conf['name'])},
                'username' => {$export($conf['username'])},
                'password' => {$export($conf['password'])},
                'timezone' => {$export($conf['timezone'])},
            ],
            'jwt' => [
                'secret' => {$export($jwtSecret)},
                'expires_seconds' => 7200,
            ],
            'portal' => [
                'enabled' => true, // true：/ 为门户前台（前后台一体），/admin 为管理后台
            ],
            'cache' => [
                'driver' => {$export($cacheDriver)},
                'redis' => [
                    'host' => 'localhost',
                    'port' => 6379,
                    'password' => '',
                ],
            ],
        ];

        PHP;
    }

    public static function version(): string
    {
        return '1.2.1';
    }
}
