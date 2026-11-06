<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * 统一分页请求模型。
 *
 * pageNum 默认 1；pageSize 默认 10、上限 100；sortField/sortOrder 接收但不参与排序。
 */
final class PageQuery
{
    public const DEFAULT_PAGE_NUM = 1;
    public const DEFAULT_PAGE_SIZE = 10;
    public const MAX_PAGE_SIZE = 100;

    public function __construct(
        private readonly ?int $pageNum,
        private readonly ?int $pageSize,
        private readonly ?string $sortField,
        private readonly ?string $sortOrder,
    ) {
    }

    /** 从 GET/POST 参数构造。 */
    public static function fromQuery(array $params): self
    {
        return new self(
            isset($params['pageNum']) && is_numeric($params['pageNum']) ? (int) $params['pageNum'] : null,
            isset($params['pageSize']) && is_numeric($params['pageSize']) ? (int) $params['pageSize'] : null,
            isset($params['sortField']) && is_string($params['sortField']) ? $params['sortField'] : null,
            isset($params['sortOrder']) && is_string($params['sortOrder']) ? $params['sortOrder'] : null,
        );
    }

    public function normalizedPageNum(): int
    {
        return $this->pageNum === null || $this->pageNum < 1 ? self::DEFAULT_PAGE_NUM : $this->pageNum;
    }

    public function normalizedPageSize(): int
    {
        if ($this->pageSize === null || $this->pageSize < 1) {
            return self::DEFAULT_PAGE_SIZE;
        }
        return min($this->pageSize, self::MAX_PAGE_SIZE);
    }

    public function normalizedSortOrder(): string
    {
        return $this->sortOrder !== null && strcasecmp($this->sortOrder, 'asc') === 0 ? 'asc' : 'desc';
    }
}
