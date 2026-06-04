<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

use Larena\Dataview\Enums\DataviewSavedViewScope;

final readonly class DataviewSavedView
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public string $savedViewKey,
        public DataviewSavedViewScope $scope,
        public DataviewViewDescriptor $descriptor,
        public array $filters = [],
        public bool $containsPrivateFilters = false,
    ) {
    }

    public function isValid(): bool
    {
        return DataviewSourceDescriptor::isStableKey($this->savedViewKey)
            && $this->descriptor->isValid()
            && (!$this->containsPrivateFilters || $this->scope->canContainPrivateFilters());
    }
}
