<?php

declare(strict_types=1);

namespace Linc\Service;

use Linc\Core\AuthContext;
use Linc\Core\BusinessException;
use Linc\Core\PageQuery;
use Linc\Core\PageResult;
use Linc\Repository\AnnounceRepository;

/**
 * 公告业务：门户前台的内容源，后台 CRUD 与校验集中在这一层。
 */
final class AnnounceService
{
    public static function page(PageQuery $query, ?string $keyword, ?int $status): array
    {
        $pageNum = $query->normalizedPageNum();
        $pageSize = $query->normalizedPageSize();
        return PageResult::of(
            AnnounceRepository::select($keyword, $status, ($pageNum - 1) * $pageSize, $pageSize),
            AnnounceRepository::count($keyword, $status),
            $pageNum,
            $pageSize
        );
    }

    /** @param array<string,mixed> $request */
    public static function create(array $request): array
    {
        $title = required(self::stringOrNull($request['title'] ?? null), '公告标题不能为空');
        $id = AnnounceRepository::insert([
            'title' => $title,
            'content' => self::stringOrNull($request['content'] ?? null),
            'status' => self::statusOf($request['status'] ?? null),
            'publishTime' => self::stringOrNull($request['publishTime'] ?? null),
        ], AuthContext::operator());
        return ['id' => $id];
    }

    /** @param array<string,mixed> $request */
    public static function update(int $id, array $request): void
    {
        $updated = AnnounceRepository::update($id, [
            'title' => trim_to_null(self::stringOrNull($request['title'] ?? null)),
            'content' => self::stringOrNull($request['content'] ?? null),
            'status' => self::statusOf($request['status'] ?? null),
            'publishTime' => self::stringOrNull($request['publishTime'] ?? null),
        ], AuthContext::operator());
        if ($updated === 0) {
            throw new BusinessException('公告不存在或已删除');
        }
    }

    public static function remove(int $id): void
    {
        if (AnnounceRepository::softDelete($id, AuthContext::operator()) === 0) {
            throw new BusinessException('公告不存在或已删除');
        }
    }

    private static function statusOf(mixed $value): int
    {
        $status = is_numeric($value) ? (int) $value : 1;
        return $status === 0 ? 0 : 1;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return is_scalar($value) ? (string) $value : null;
    }
}
