<?php

declare(strict_types=1);

namespace Larena\Dataview\ValueObjects;

final readonly class DataviewDescriptorRevision
{
    /** @param array<string, mixed> $descriptor */
    public function __construct(
        public string $scopeRef,
        public string $viewId,
        public int $revision,
        public array $descriptor,
        public string $semanticHash,
    ) {
    }
}
