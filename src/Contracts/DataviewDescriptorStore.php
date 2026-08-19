<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

use Larena\Dataview\ValueObjects\DataviewDescriptorRevision;

interface DataviewDescriptorStore
{
    /** @param array<string, mixed> $descriptor */
    public function create(array $descriptor, string $actor): DataviewDescriptorRevision;

    /** @param array<string, mixed> $descriptor */
    public function update(array $descriptor, int $expectedRevision, string $actor): DataviewDescriptorRevision;

    public function read(string $scopeRef, string $viewId): ?DataviewDescriptorRevision;

    /** @return list<DataviewDescriptorRevision> */
    public function history(string $scopeRef, string $viewId): array;

    public function rollback(
        string $scopeRef,
        string $viewId,
        int $targetRevision,
        int $expectedRevision,
        string $actor,
    ): DataviewDescriptorRevision;
}
