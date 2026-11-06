<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * SQL 脚本拆分：将整份初始化脚本按语句拆开逐条执行（PDO 原生预处理不支持多语句）。
 * 支持 `--` 行注释、块注释与单双引号字符串内的分号保护。
 */
final class SqlScript
{
    /** @return list<string> */
    public static function statements(string $script): array
    {
        $statements = [];
        $current = '';
        $length = strlen($script);
        $inSingle = false;
        $inDouble = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $script[$i];
            $next = $i + 1 < $length ? $script[$i + 1] : '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                    $current .= $char;
                }
                continue;
            }
            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }
            if ($inSingle) {
                if ($char === '\\' && $next !== '') {
                    $current .= $char . $next;
                    $i++;
                    continue;
                }
                if ($char === "'") {
                    // MySQL 的单引号转义为 ''
                    if ($next === "'") {
                        $current .= "''";
                        $i++;
                        continue;
                    }
                    $inSingle = false;
                }
                $current .= $char;
                continue;
            }
            if ($inDouble) {
                if ($char === '\\' && $next !== '') {
                    $current .= $char . $next;
                    $i++;
                    continue;
                }
                if ($char === '"') {
                    $inDouble = false;
                }
                $current .= $char;
                continue;
            }

            if ($char === '-' && $next === '-') {
                $inLineComment = true;
                $i++;
                continue;
            }
            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }
            if ($char === "'") {
                $inSingle = true;
                $current .= $char;
                continue;
            }
            if ($char === '"') {
                $inDouble = true;
                $current .= $char;
                continue;
            }
            if ($char === ';') {
                $trimmed = trim($current);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $current = '';
                continue;
            }
            $current .= $char;
        }

        $trimmed = trim($current);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }
        return $statements;
    }
}
