<?php

declare(strict_types=1);

namespace Linc\Repository;

use Linc\Core\Database;

/**
 * 公告数据访问（门户前台内容源）。
 */
final class AnnounceRepository
{
    private static function where(?string $keyword, ?int $status): array
    {
        return [
            "deleted = 0
              AND (? IS NULL OR ? = ''
                   OR title LIKE CONCAT('%', ?, '%')
                   OR content LIKE CONCAT('%', ?, '%'))
              AND (? IS NULL OR status = ?)",
            [$keyword, $keyword, $keyword, $keyword, $status, $status],
        ];
    }

    public static function count(?string $keyword, ?int $status): int
    {
        [$where, $params] = self::where($keyword, $status);
        return (int) Database::scalar("SELECT COUNT(*) FROM biz_announce WHERE $where", $params);
    }

    /** @return list<array<string,mixed>> */
    public static function select(?string $keyword, ?int $status, int $offset, int $pageSize): array
    {
        [$where, $params] = self::where($keyword, $status);
        return Database::all(
            "SELECT id,
                    title,
                    content,
                    status,
                    CASE WHEN publish_time IS NULL THEN DATE_FORMAT(create_time, '%Y-%m-%d %H:%i')
                         ELSE DATE_FORMAT(publish_time, '%Y-%m-%d %H:%i') END AS publishTime,
                    DATE_FORMAT(create_time, '%Y-%m-%d %H:%i') AS createTime
             FROM biz_announce
             WHERE $where
             ORDER BY COALESCE(publish_time, create_time) DESC, id DESC
             LIMIT ?, ?",
            array_merge($params, [$offset, $pageSize])
        );
    }

    /** 门户前台：已发布公告，按发布时间倒序。 */
    public static function selectPublished(int $limit = 10): array
    {
        return Database::all(
            "SELECT id, title, content,
                    CASE WHEN publish_time IS NULL THEN DATE_FORMAT(create_time, '%Y-%m-%d %H:%i')
                         ELSE DATE_FORMAT(publish_time, '%Y-%m-%d %H:%i') END AS publishTime
             FROM biz_announce
             WHERE deleted = 0 AND status = 1
             ORDER BY COALESCE(publish_time, create_time) DESC, id DESC
             LIMIT ?",
            [$limit]
        );
    }

    public static function countPublished(): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM biz_announce WHERE deleted = 0 AND status = 1"
        );
    }

    /** @param array<string,mixed> $row */
    public static function insert(array $row, string $operator): int
    {
        return Database::insert(
            "INSERT INTO biz_announce (title, content, status, publish_time, create_by, update_by)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$row['title'], $row['content'], $row['status'], $row['publishTime'], $operator, $operator]
        );
    }

    /** @param array<string,mixed> $row */
    public static function update(int $id, array $row, string $operator): int
    {
        return Database::execute(
            "UPDATE biz_announce
             SET title = COALESCE(?, title),
                 content = COALESCE(?, content),
                 status = COALESCE(?, status),
                 publish_time = COALESCE(?, publish_time),
                 update_by = ?
             WHERE id = ? AND deleted = 0",
            [$row['title'], $row['content'], $row['status'], $row['publishTime'], $operator, $id]
        );
    }

    public static function softDelete(int $id, string $operator): int
    {
        return Database::execute(
            "UPDATE biz_announce SET deleted = 1, update_by = ? WHERE id = ? AND deleted = 0",
            [$operator, $id]
        );
    }
}
