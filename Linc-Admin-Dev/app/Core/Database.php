<?php

declare(strict_types=1);

namespace Linc\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * PDO MySQL 连接工厂。
 *
 * 连接时设置会话时区（timezone，默认 +08:00），
 * 保证 NOW() 与业务时间口径一致；错误统一包装为「数据库连接失败」便于安装向导提示。
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static ?array $override = null;

    /** @param array{host?:string,port?:int,name?:string,username?:string,password?:string,timezone?:string} $override 安装器测试连接时临时覆盖 */
    public static function useOverride(?array $override): void
    {
        self::$override = $override;
        self::$pdo = null;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $conf = self::$override ?? Config::database();
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $conf['host'],
            (int) $conf['port'],
            $conf['name']
        );
        try {
            $pdo = new PDO($dsn, $conf['username'], $conf['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        } catch (PDOException $error) {
            throw new RuntimeException('数据库连接失败: ' . $error->getMessage(), 0, $error);
        }
        $timezone = (string) ($conf['timezone'] ?? '+08:00');
        $quoted = $pdo->quote($timezone);
        $pdo->exec('SET time_zone = ' . ($quoted !== false ? $quoted : "'" . str_replace("'", "''", $timezone) . "'"));
        $pdo->exec('SET NAMES utf8mb4');
        self::$pdo = $pdo;
        return $pdo;
    }

    /**
     * 供安装器/生成器使用的无库名连接（连接到服务器但不选择库）。
     */
    public static function pdoWithoutDatabase(array $conf): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $conf['host'], (int) $conf['port']);
        try {
            return new PDO($dsn, $conf['username'], $conf['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $error) {
            throw new RuntimeException('数据库连接失败: ' . $error->getMessage(), 0, $error);
        }
    }

    /** @return list<array<string,mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll() ?: [];
    }

    /** @return array<string,mixed>|null */
    public static function first(string $sql, array $params = []): ?array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public static function scalar(string $sql, array $params = []): mixed
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchColumn();
    }

    public static function execute(string $sql, array $params = []): int
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement->rowCount();
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::execute($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    public static function beginTransaction(): void
    {
        $pdo = self::pdo();
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }
    }

    public static function commit(): void
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    }

    public static function rollBack(): void
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}
