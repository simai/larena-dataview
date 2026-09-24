<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

use InvalidArgumentException;

final readonly class DataviewQuery
{
    /**
     * The shared operator vocabulary of every view. Which of them a field offers is decided by its
     * type and by the source that evaluates it; relative ranges stay relative here and are resolved
     * by the host at query time.
     */
    public const OPERATORS = ['eq', 'in', 'contains', 'starts_with', 'gt', 'gte', 'lt', 'lte', 'between', 'today', 'week', 'month'];

    public const RELATIVE_OPERATORS = ['today', 'week', 'month'];

    /**
     * @param list<array<string, mixed>> $filters
     * @param list<array<string, mixed>> $sort
     */
    public function __construct(
        public array $filters = [],
        public array $sort = [],
        public int $page = 1,
        public int $perPage = 20,
        public ?string $search = null,
    ) {
    }

    public function isValid(): bool
    {
        if ($this->page < 1 || $this->perPage < 1 || $this->perPage > 100) {
            return false;
        }

        if ($this->search !== null && (trim($this->search) === '' || strlen($this->search) > 100
            || preg_match('//u', $this->search) !== 1
            || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $this->search) === 1)) return false;

        foreach ($this->filters as $filter) {
            if (!isset($filter['field'], $filter['operator'])
                || !is_string($filter['field'])
                || !is_string($filter['operator'])
                || !DataviewSourceDescriptor::isStableKey($filter['field'])
                || !in_array($filter['operator'], self::OPERATORS, true)
                || !array_key_exists('value', $filter)
                || !$this->isFilterValue($filter['value'])
                || !$this->valueFitsOperator($filter['operator'], $filter['value'])) {
                return false;
            }
        }
        foreach ($this->sort as $sort) {
            if (!isset($sort['field'], $sort['direction'])
                || !is_string($sort['field'])
                || !is_string($sort['direction'])
                || !DataviewSourceDescriptor::isStableKey($sort['field'])
                || !in_array($sort['direction'], ['asc', 'desc'], true)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<array{field: string, operator: string, value: mixed}> */
    public function normalizedFilters(): array
    {
        if (!$this->isValid()) {
            throw new InvalidArgumentException('dataview_query_invalid');
        }
        $normalized = [];
        foreach ($this->filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? null;
            if (!is_string($field) || !is_string($operator)) {
                throw new InvalidArgumentException('dataview_query_filter_invalid');
            }
            $normalized[] = ['field' => $field, 'operator' => $operator, 'value' => $filter['value'] ?? null];
        }

        return $normalized;
    }

    /** @return list<array{field: string, direction: string}> */
    public function normalizedSort(): array
    {
        if (!$this->isValid()) {
            throw new InvalidArgumentException('dataview_query_invalid');
        }
        $normalized = [];
        foreach ($this->sort as $sort) {
            $field = $sort['field'] ?? null;
            $direction = $sort['direction'] ?? null;
            if (!is_string($field) || !is_string($direction)) {
                throw new InvalidArgumentException('dataview_query_sort_invalid');
            }
            $normalized[] = ['field' => $field, 'direction' => $direction];
        }

        return $normalized;
    }

    private function valueFitsOperator(string $operator, mixed $value): bool
    {
        return match (true) {
            in_array($operator, self::RELATIVE_OPERATORS, true) => $value === null,
            $operator === 'between' => is_array($value) && count($value) === 2,
            $operator === 'in' => is_array($value) && $value !== [],
            default => !is_array($value),
        };
    }

    private function isFilterValue(mixed $value): bool
    {
        if ($value === null || is_scalar($value)) {
            return true;
        }
        if (!is_array($value) || !array_is_list($value)) {
            return false;
        }
        foreach ($value as $item) {
            if ($item !== null && !is_scalar($item)) {
                return false;
            }
        }

        return true;
    }
}
