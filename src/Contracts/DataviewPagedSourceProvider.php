<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

/** Domain owner performs authorized querying and pagination; Dataview does not reslice. */
interface DataviewPagedSourceProvider
{
    public function descriptor(): DataviewSourceDescriptor;

    public function page(DataviewQuery $query): DataviewDatasetSnapshot;
}
