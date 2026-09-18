<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

/**
 * Product/domain bridge for one allowlisted Dataview source.
 *
 * The adapter owner keeps authorization, canonical records and query execution.
 * Dataview validates the registration, request and returned snapshot.
 */
interface RegisteredDataviewSourceAdapter
{
    public function descriptor(): DataviewSourceDescriptor;

    /** @return array<string,array{type:string,operators:list<string>,sortable:bool}> */
    public function queryFields(): array;

    /** @return list<string> */
    public function searchFields(): array;

    public function dataset(DataviewQuery $query, string $principalId): DataviewDatasetSnapshot;
}
