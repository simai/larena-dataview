<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

use Larena\Dataview\Enums\DataviewViewType;

final readonly class DataviewViewDescriptor
{
    /**
     * @param list<DataviewFieldDescriptor> $fields
     * @param array<string, string> $options
     */
    public function __construct(
        public string $viewKey,
        public DataviewSourceDescriptor $source,
        public DataviewViewType $type,
        public array $fields,
        public bool $capabilityUnlocked = true,
        public array $options = [],
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

        $fieldKeys = array_map(static fn (DataviewFieldDescriptor $field): string => $field->fieldKey, $this->fields);
        foreach ($this->options as $role => $fieldKey) {
            if (!DataviewSourceDescriptor::isStableKey($role)
                || !DataviewSourceDescriptor::isStableKey($fieldKey)
                || !in_array($fieldKey, $fieldKeys, true)) {
                return false;
            }
        }

        foreach ($this->requiredOptionRoles() as $role) {
            if (!isset($this->options[$role])) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    private function requiredOptionRoles(): array
    {
        return match ($this->type) {
            DataviewViewType::Table => [],
            DataviewViewType::Cards => ['title'],
            DataviewViewType::Calendar => ['date'],
            DataviewViewType::Kanban => ['lane'],
            DataviewViewType::Gantt => ['start', 'end'],
            DataviewViewType::Tree => ['id', 'parent'],
        };
    }
}
