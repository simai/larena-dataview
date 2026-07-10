<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

interface DataviewSourceProvider
{
    public function descriptor(): DataviewSourceDescriptor;

    /** @return list<array<string, mixed>> */
    public function rows(): array;
}
