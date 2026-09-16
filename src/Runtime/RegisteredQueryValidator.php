<?php

declare(strict_types=1);

namespace Larena\Dataview\Runtime;

use InvalidArgumentException;
use Larena\Dataview\Contracts\DataviewQuery;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;

/** Field contracts come from trusted source registrations, never request JSON. */
final class RegisteredQueryValidator
{
    /** @param array<string,mixed> $fields Raw server-owned registrations; validate their structure before use. */
    public function assertAllowed(DataviewQuery $query, array $fields): void
    {
        if (!$query->isValid() || count($query->filters) > 20 || count($query->sort) > 4 || $fields === []) {
            throw new InvalidArgumentException('dataview_registered_query_invalid');
        }
        foreach ($fields as $name => $field) {
            if (!is_array($field) || !DataviewSourceDescriptor::isStableKey($name)
                || array_diff(array_keys($field), ['type', 'operators', 'sortable']) !== []
                || !in_array($field['type'] ?? null, ['string', 'integer', 'number', 'boolean', 'identifier'], true)
                || !is_bool($field['sortable'] ?? null) || !is_array($field['operators'] ?? null)
                || !array_is_list($field['operators'])) {
                throw new InvalidArgumentException('dataview_field_registration_invalid');
            }
            foreach ($field['operators'] as $operator) {
                if (!in_array($operator, ['eq', 'in', 'contains', 'gte', 'lte'], true)
                    || ($operator === 'contains' && $field['type'] !== 'string')
                    || (in_array($operator, ['gte', 'lte'], true) && !in_array($field['type'], ['integer', 'number'], true))) {
                    throw new InvalidArgumentException('dataview_field_registration_invalid');
                }
            }
            if (count(array_unique($field['operators'])) !== count($field['operators'])) throw new InvalidArgumentException('dataview_field_registration_invalid');
        }
        foreach ($query->filters as $filter) {
            if (array_diff(array_keys($filter), ['field', 'operator', 'value']) !== []) throw new InvalidArgumentException('dataview_filter_fields_invalid');
            $field = $fields[$filter['field']] ?? null;
            if ($field === null || !in_array($filter['operator'], $field['operators'], true)) throw new InvalidArgumentException('dataview_filter_not_registered');
            $values = $filter['operator'] === 'in' ? $filter['value'] : [$filter['value']];
            if (!is_array($values) || !array_is_list($values) || count($values) < 1 || count($values) > 100) throw new InvalidArgumentException('dataview_filter_value_invalid');
            foreach ($values as $value) if (!$this->valueAllowed($value, $field['type'])) throw new InvalidArgumentException('dataview_filter_value_invalid');
        }
        $seen = [];
        foreach ($query->sort as $sort) {
            if (array_diff(array_keys($sort), ['field', 'direction']) !== []) throw new InvalidArgumentException('dataview_sort_fields_invalid');
            if (!isset($fields[$sort['field']]) || !$fields[$sort['field']]['sortable'] || isset($seen[$sort['field']])) throw new InvalidArgumentException('dataview_sort_not_registered');
            $seen[$sort['field']] = true;
        }
    }

    private function valueAllowed(mixed $value, string $type): bool
    {
        $safeString = is_string($value) && strlen($value) <= 4096 && preg_match('//u', $value) === 1;
        return match ($type) {
            'string' => $safeString,
            'integer' => is_int($value),
            'number' => is_int($value) || (is_float($value) && is_finite($value)),
            'boolean' => is_bool($value),
            'identifier' => is_int($value) || ($safeString && $value !== ''),
            default => false,
        };
    }
}
