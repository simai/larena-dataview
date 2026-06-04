<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

use Larena\Dataview\Enums\DataviewViewType;

final readonly class DataviewViewDescriptor
{
    /**
     * @param list<DataviewFieldDescriptor> $fields
     */
    public function __construct(
        public string $viewKey,
        public DataviewSourceDescriptor $source,
        public DataviewViewType $type,
        public array $fields,
        public bool $capabilityUnlocked = true,
    ) {
    }

    public function isValid(): bool
    {
        if (!DataviewSourceDescriptor::isStableKey($this->viewKey) || !$this->source->isValid() || $this->fields === []) {
            return false;
        }

        if ($this->type->isAdvanced() && !$this->capabilityUnlocked) {
            return false;
        }

        foreach ($this->fields as $field) {
            if (!$field->isValid()) {
                return false;
            }
        }

        return true;
    }
}
