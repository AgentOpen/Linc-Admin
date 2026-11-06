<?php

declare(strict_types=1);

namespace Linc\Core;

/**
 * 数据权限条件。
 */
final class DataScopeCriteria
{
    /**
     * @param list<int> $deptIds
     */
    private function __construct(
        private readonly bool $allScope,
        private readonly ?int $userId,
        private readonly array $deptIds,
    ) {
    }

    public static function all(): self
    {
        return new self(true, null, []);
    }

    public static function selfOnly(?int $userId): self
    {
        return new self(false, $userId, []);
    }

    /**
     * @param list<int|null> $deptIds
     */
    public static function limited(?int $userId, array $deptIds): self
    {
        $ids = [];
        foreach ($deptIds as $id) {
            if ($id !== null && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return new self(false, $userId, $ids);
    }

    public function isAllScope(): bool
    {
        return $this->allScope;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /** @return list<int> */
    public function getDeptIds(): array
    {
        return $this->deptIds;
    }
}
