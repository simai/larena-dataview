<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

final readonly class DataviewPagination
{
    public function __construct(public int $page, public int $perPage, public int $total) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function isValid(): bool
    {
        return $this->page >= 1 && $this->perPage >= 1 && $this->perPage <= 100
            && $this->total >= 0 && $this->page <= $this->lastPage();
    }
}
