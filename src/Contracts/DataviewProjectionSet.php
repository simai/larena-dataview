<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

use Larena\Dataview\Enums\DataviewViewType;

final readonly class DataviewProjectionSet
{
    /** @param array<string, DataviewViewProjection> $projections */
    public function __construct(
        public DataviewDatasetSnapshot $snapshot,
        public array $projections,
    ) {
    }

    public function isValid(): bool
    {
        if (!$this->snapshot->isValid() || count($this->projections) !== count(DataviewViewType::cases())) {
            return false;
        }
        foreach (DataviewViewType::cases() as $type) {
            $projection = $this->projections[$type->value] ?? null;
            if (!$projection instanceof DataviewViewProjection
                || !$projection->isSafeForRender()
                || $projection->descriptor->type !== $type
                || $projection->sourceSnapshotId !== $this->snapshot->snapshotId
                || $projection->rows !== $this->snapshot->rows) {
                return false;
            }
        }

        return true;
    }
}
