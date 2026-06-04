<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

final readonly class DataviewViewProjection
{
    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string> $explain
     */
    public function __construct(
        public DataviewViewDescriptor $descriptor,
        public array $rows,
        public DataviewActionPolicy $interactionPolicy,
        public array $explain,
        public bool $ownsSourceData = false,
    ) {
    }

    public function isSafeForRender(): bool
    {
        return $this->descriptor->isValid()
            && $this->explain !== []
            && !$this->ownsSourceData;
    }
}
