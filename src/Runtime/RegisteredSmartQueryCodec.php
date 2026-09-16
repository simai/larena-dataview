<?php

declare(strict_types=1);

namespace Larena\Dataview\Runtime;

use InvalidArgumentException;
use Larena\Dataview\Contracts\DataviewQuery;

/** Converts the public Smart host query envelope, never executable bindings. */
final class RegisteredSmartQueryCodec
{
    /**
     * @param array<array-key,mixed> $wire Untrusted decoded JSON request.
     * @param array<string,array{type:string,operators:list<string>,sortable:bool}> $fields Trusted registration.
     * @param list<string> $searchFields Trusted search projection.
     */
    public function decode(array $wire, array $fields, array $searchFields = []): DataviewQuery
    {
        $validator = new RegisteredQueryValidator();
        $validator->assertAllowed(new DataviewQuery(), $fields, $searchFields);
        if (array_diff(array_keys($wire), ['search', 'filters', 'sort', 'page', 'page_size']) !== []) $this->reject();
        $page = array_key_exists('page', $wire) ? $wire['page'] : 1;
        $size = array_key_exists('page_size', $wire) ? $wire['page_size'] : 20;
        $search = array_key_exists('search', $wire) ? $wire['search'] : '';
        $rawFilters = array_key_exists('filters', $wire) ? $wire['filters'] : [];
        $sort = array_key_exists('sort', $wire) ? $wire['sort'] : [];
        if (!is_int($page) || !is_int($size) || !is_string($search) || !is_array($rawFilters)
            || ($rawFilters !== [] && array_is_list($rawFilters)) || !is_array($sort) || !array_is_list($sort)
            || count($rawFilters) > 20) $this->reject();
        $filters = [];
        foreach ($rawFilters as $field => $raw) {
            if (!is_string($field) || !isset($fields[$field])) $this->reject();
            if (!is_array($raw) || array_diff(array_keys($raw), ['operator', 'value', 'values']) !== []
                || !isset($raw['operator']) || !is_string($raw['operator'])
                || (!array_key_exists('value', $raw) && !array_key_exists('values', $raw))
                || (array_key_exists('value', $raw) && array_key_exists('values', $raw))
                || !in_array($raw['operator'], $fields[$field]['operators'], true)) $this->reject();
            $value = $raw['value'] ?? $raw['values'] ?? null;
            // The Smart filter form explicitly represents an inactive control with these values.
            if ($value === null || $value === '' || $value === []) continue;
            if (array_key_exists('values', $raw) && $raw['operator'] !== 'in') $this->reject();
            if ($fields[$field]['type'] === 'integer') {
                if ($raw['operator'] === 'in' && is_array($value)) $value = array_map($this->integer(...), $value);
                else $value = $this->integer($value);
            }
            $filters[] = ['field' => $field, 'operator' => $raw['operator'], 'value' => $value];
        }
        foreach ($sort as $item) if (!is_array($item)) $this->reject();
        $query = new DataviewQuery($filters, $sort, $page, $size, $search === '' ? null : $search);
        $validator->assertAllowed($query, $fields, $searchFields);
        return $query;
    }

    private function integer(mixed $value): int
    {
        if (is_int($value)) return $value;
        if (!is_string($value) || preg_match('/^-?(?:0|[1-9][0-9]*)$/D', $value) !== 1) $this->reject();
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        if (!is_int($parsed)) $this->reject();
        return $parsed;
    }

    private function reject(): never
    {
        throw new InvalidArgumentException('dataview_smart_query_invalid');
    }
}
