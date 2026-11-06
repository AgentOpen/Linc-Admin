<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * CSV 导出，
 * 首行中文表头，换行分隔；含逗号/引号/换行的单元格用双引号包裹并转义引号；无 BOM、UTF-8。
 */
final class Csv
{
    /**
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     */
    public static function build(array $headers, array $rows): string
    {
        $lines = [implode(',', $headers)];
        foreach ($rows as $row) {
            $cells = [];
            foreach ($row as $value) {
                $cells[] = self::cell($value);
            }
            $lines[] = implode(',', $cells);
        }
        return implode("\n", $lines) . "\n";
    }

    private static function cell(mixed $value): string
    {
        $text = $value === null ? '' : self::stringify($value);
        if (str_contains($text, '"')) {
            $text = str_replace('"', '""', $text);
        }
        if (str_contains($text, ',') || str_contains($text, '"') || str_contains($text, "\n") || str_contains($text, "\r")) {
            return '"' . $text . '"';
        }
        return $text;
    }

    private static function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_float($value)) {
            return self::floatText($value);
        }
        return (string) $value;
    }

    private static function floatText(float $value): string
    {
        $text = json_encode($value);
        return is_string($text) ? str_replace('.0', '', $text) : (string) $value;
    }
}
