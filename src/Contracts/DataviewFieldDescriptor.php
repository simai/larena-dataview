<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

final readonly class DataviewFieldDescriptor
{
    public function __construct(
        public string $fieldKey,
        public string $propertyTypeKey,
        public string $labelKey,
        public bool $hidden = false,
        public bool $readonly = true,
    ) {
    }

    public function isValid(): bool
    {
        return DataviewSourceDescriptor::isStableKey($this->fieldKey)
            && DataviewSourceDescriptor::isStableKey($this->propertyTypeKey)
            && str_starts_with($this->labelKey, 'lang:');
    }
}
