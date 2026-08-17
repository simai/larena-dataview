<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

final readonly class DataviewDatasetSnapshot
{
    /** @param list<array<string, mixed>> $rows */
    public function __construct(
        public string $snapshotId,
        public DataviewSourceDescriptor $source,
        public DataviewQuery $query,
        public array $rows,
        public DataviewPagination $pagination,
        public bool $accessFiltered,
        public bool $ownsCanonicalRecords = false,
    ) {
    }

    public function isValid(): bool
    {
        return preg_match('/^sha256:[a-f0-9]{64}$/', $this->snapshotId) === 1
            && $this->source->isValid()
            && $this->query->isValid()
            && $this->pagination->isValid()
            && $this->accessFiltered
            && !$this->ownsCanonicalRecords;
    }
}
