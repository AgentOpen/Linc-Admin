<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * 统一分页响应模型，{rows, total, pageNum, pageSize}。
 */
final class PageResult
{
    /**
     * @param list<array<string, mixed>> $rows
     * @return array{rows: list<array<string, mixed>>, total: int, pageNum: int, pageSize: int}
     */
    public static function of(array $rows, int $total, int $pageNum, int $pageSize): array
    {
        return [
            'rows' => $rows,
            'total' => $total,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
        ];
    }
}
