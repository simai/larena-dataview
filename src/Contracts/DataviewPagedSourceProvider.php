<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

/** Domain owner performs authorized querying and pagination; Dataview does not reslice. */
interface DataviewPagedSourceProvider
{
    public function descriptor(): DataviewSourceDescriptor;

    /** @return list<string> Exact trusted native search field set; [] means search unsupported. */
    public function searchFields(): array;

    public function page(DataviewQuery $query): DataviewDatasetSnapshot;
}
