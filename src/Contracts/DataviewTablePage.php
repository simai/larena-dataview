<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

final readonly class DataviewTablePage
{
    public function __construct(public DataviewViewProjection $projection, public DataviewPagination $pagination) {}

    public function isSafeForRender(): bool
    {
        return $this->projection->isSafeForRender() && $this->pagination->isValid();
    }
}
