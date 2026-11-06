<?php

declare(strict_types=1);

namespace Linc\Repository;

use Linc\Core\Database;

/**
 * 代码生成数据访问。
 */
final class GeneratorRepository
{
    public static function countTables(?string $keyword): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*)
             FROM gen_table
             WHERE ? IS NULL OR ? = ''
                OR table_name LIKE CONCAT('%', ?, '%')
                OR table_comment LIKE CONCAT('%', ?, '%')
                OR class_name LIKE CONCAT('%', ?, '%')",
            [$keyword, $keyword, $keyword, $keyword, $keyword]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectTables(?string $keyword, int $offset, int $pageSize): array
    {
        return Database::all(
            "SELECT id,
                    table_name AS tableName,
                    table_comment AS tableComment,
                    class_name AS className,
                    module_name AS module,
                    business_name AS businessName,
                    synced,
                    DATE_FORMAT(create_time, '%Y-%m-%d %H:%i') AS createTime
             FROM gen_table
             WHERE ? IS NULL OR ? = ''
                OR table_name LIKE CONCAT('%', ?, '%')
                OR table_comment LIKE CONCAT('%', ?, '%')
                OR class_name LIKE CONCAT('%', ?, '%')
             ORDER BY update_time DESC, id DESC
             LIMIT ?, ?",
            [$keyword, $keyword, $keyword, $keyword, $keyword, $offset, $pageSize]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectImportableTables(?string $keyword): array
    {
        return Database::all(
            "SELECT t.table_name AS tableName,
                    t.table_comment AS tableComment,
                    CASE WHEN g.id IS NULL THEN 0 ELSE 1 END AS imported
             FROM information_schema.tables t
             LEFT JOIN gen_table g ON g.table_name = t.table_name
             WHERE t.table_schema = DATABASE()
               AND t.table_type = 'BASE TABLE'
               AND (? IS NULL OR ? = ''
                    OR t.table_name LIKE CONCAT('%', ?, '%')
                    OR t.table_comment LIKE CONCAT('%', ?, '%'))
             ORDER BY t.table_name",
            [$keyword, $keyword, $keyword, $keyword]
        );
    }

    /** @return array<string,mixed>|null */
    public static function selectDatabaseTable(string $tableName): ?array
    {
        return Database::first(
            "SELECT table_name AS tableName,
                    table_comment AS tableComment
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = ?",
            [$tableName]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectDatabaseColumns(string $tableName): array
    {
        return Database::all(
            "SELECT column_name AS columnName,
                    data_type AS dataType,
                    column_type AS columnType,
                    column_comment AS columnComment,
                    is_nullable AS nullable,
                    ordinal_position AS sort
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = ?
             ORDER BY ordinal_position",
            [$tableName]
        );
    }

    /** @return array<string,mixed>|null */
    public static function selectTableById(int $id): ?array
    {
        return Database::first(
            "SELECT id,
                    table_name AS tableName,
                    table_comment AS tableComment,
                    class_name AS className,
                    module_name AS module,
                    business_name AS businessName,
                    synced
             FROM gen_table
             WHERE id = ?",
            [$id]
        );
    }

    public static function selectTableIdByName(string $tableName): ?int
    {
        $value = Database::scalar("SELECT id FROM gen_table WHERE table_name = ?", [$tableName]);
        return $value === false || $value === null ? null : (int) $value;
    }

    public static function insertTable(array $row, string $username): int
    {
        return Database::insert(
            "INSERT INTO gen_table (table_name, table_comment, class_name, module_name, business_name, synced, create_by, update_by)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?)",
            [
                $row['tableName'], $row['tableComment'], $row['className'],
                $row['module'], $row['businessName'], $username, $username,
            ]
        );
    }

    public static function insertColumn(array $row): void
    {
        Database::execute(
            "INSERT INTO gen_table_column (table_id, column_name, field_name, field_type, data_type, column_comment,
                is_insert, is_edit, is_list, is_query, query_type, form_type, required, sort)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $row['tableId'], $row['columnName'], $row['fieldName'], $row['fieldType'], $row['dataType'],
                $row['columnComment'], $row['insert'], $row['edit'], $row['list'], $row['query'],
                $row['queryType'], $row['formType'], $row['required'], $row['sort'],
            ]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function selectColumns(int $tableId): array
    {
        return Database::all(
            "SELECT id,
                    column_name AS col,
                    field_name AS fieldName,
                    field_type AS fieldType,
                    data_type AS dataType,
                    column_comment AS comment,
                    is_insert AS `insert`,
                    is_edit AS edit,
                    is_list AS list,
                    is_query AS query,
                    query_type AS queryType,
                    form_type AS formType,
                    required
             FROM gen_table_column
             WHERE table_id = ?
             ORDER BY sort, id",
            [$tableId]
        );
    }

    public static function updateTable(int $id, ?string $tableComment, ?int $synced, string $username): int
    {
        return Database::execute(
            "UPDATE gen_table
             SET table_comment = COALESCE(?, table_comment),
                 synced = COALESCE(?, synced),
                 update_by = ?
             WHERE id = ?",
            [$tableComment, $synced, $username, $id]
        );
    }
}
