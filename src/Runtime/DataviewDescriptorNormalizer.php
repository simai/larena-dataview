<?php

declare(strict_types=1);

namespace Larena\Dataview\Runtime;

use InvalidArgumentException;
use JsonException;
use Larena\Dataview\Contracts\DataviewFieldDescriptor;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\DataviewViewDescriptor;
use Larena\Dataview\Enums\DataviewViewType;

final readonly class DataviewDescriptorNormalizer
{
    public const SCHEMA = 'larena.dataview.descriptor.v1';
    public const MAX_BYTES = 131_072;
    public const MAX_FIELDS = 100;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function normalize(array $input): array
    {
        $this->exactKeys($input, ['fields', 'interaction', 'options', 'query', 'schema', 'scope_ref', 'source', 'type', 'view_id']);
        if (($input['schema'] ?? null) !== self::SCHEMA) {
            throw $this->reject('dataview_descriptor_schema_invalid');
        }
        $scope = $input['scope_ref'] ?? null;
        if (!is_string($scope) || preg_match('/^scope:[a-z][a-z0-9_.:-]{1,119}$/', $scope) !== 1) {
            throw $this->reject('dataview_descriptor_scope_invalid');
        }
        $viewId = $this->stableKey($input['view_id'] ?? null, 'dataview_descriptor_view_id_invalid');
        $source = $this->source($input['source'] ?? null);
        $typeValue = $input['type'] ?? null;
        if (!is_string($typeValue) || ($type = DataviewViewType::tryFrom($typeValue)) === null) {
            throw $this->reject('dataview_descriptor_type_invalid');
        }
        $fields = $this->fields($input['fields'] ?? null);
        $fieldKeys = array_column($fields, 'key');
        $options = $this->options($input['options'] ?? null, $fieldKeys);
        $query = $this->query($input['query'] ?? null, $fieldKeys);
        $interaction = $this->interaction($input['interaction'] ?? null);

        $descriptor = new DataviewViewDescriptor(
            $viewId,
            new DataviewSourceDescriptor($source['key'], $source['owner_package'], true),
            $type,
            array_map(static fn (array $field): DataviewFieldDescriptor => new DataviewFieldDescriptor(
                $field['key'], $field['property_type'], $field['label_key'], $field['hidden'], $field['readonly'],
            ), $fields),
            options: $options,
        );
        if (!$descriptor->isValid()) {
            throw $this->reject('dataview_descriptor_contract_invalid');
        }

        $normalized = $this->canonicalize([
            'schema' => self::SCHEMA,
            'scope_ref' => $scope,
            'view_id' => $viewId,
            'source' => $source,
            'type' => $type->value,
            'fields' => $fields,
            'options' => $options,
            'query' => $query,
            'interaction' => $interaction,
        ]);
        if (strlen($this->encodeNormalized($normalized)) > self::MAX_BYTES) {
            throw $this->reject('dataview_descriptor_size_limit_exceeded');
        }
        return $normalized;
    }

    /** @param array<string, mixed> $descriptor */
    public function hash(array $descriptor): string
    {
        return hash('sha256', $this->encodeNormalized($this->normalize($descriptor)));
    }

    /** @param array<string, mixed> $descriptor */
    public function encode(array $descriptor): string
    {
        return $this->encodeNormalized($this->normalize($descriptor));
    }

    /** @param array<string, mixed> $descriptor */
    public function toViewDescriptor(array $descriptor): DataviewViewDescriptor
    {
        $descriptor = $this->normalize($descriptor);
        return new DataviewViewDescriptor(
            $descriptor['view_id'],
            new DataviewSourceDescriptor($descriptor['source']['key'], $descriptor['source']['owner_package'], true),
            DataviewViewType::from($descriptor['type']),
            array_map(static fn (array $field): DataviewFieldDescriptor => new DataviewFieldDescriptor(
                $field['key'], $field['property_type'], $field['label_key'], $field['hidden'], $field['readonly'],
            ), $descriptor['fields']),
            options: $descriptor['options'],
        );
    }

    /** @return array{key:string,owner_package:string} */
    private function source(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw $this->reject('dataview_descriptor_source_invalid');
        }
        $this->exactKeys($value, ['key', 'owner_package']);
        $key = $this->stableKey($value['key'] ?? null, 'dataview_descriptor_source_invalid');
        $owner = $value['owner_package'] ?? null;
        if (!is_string($owner) || preg_match('#^larena/[a-z][a-z0-9-]{1,60}$#', $owner) !== 1) {
            throw $this->reject('dataview_descriptor_source_invalid');
        }
        return ['key' => $key, 'owner_package' => $owner];
    }

    /** @return list<array{key:string,property_type:string,label_key:string,hidden:bool,readonly:bool}> */
    private function fields(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value) || $value === [] || count($value) > self::MAX_FIELDS) {
            throw $this->reject('dataview_descriptor_fields_invalid');
        }
        $seen = [];
        $fields = [];
        foreach ($value as $field) {
            if (!is_array($field) || array_is_list($field)) {
                throw $this->reject('dataview_descriptor_field_invalid');
            }
            $this->exactKeys($field, ['hidden', 'key', 'label_key', 'property_type', 'readonly']);
            $key = $this->stableKey($field['key'] ?? null, 'dataview_descriptor_field_invalid');
            $property = $this->stableKey($field['property_type'] ?? null, 'dataview_descriptor_field_invalid');
            $label = $field['label_key'] ?? null;
            if (isset($seen[$key]) || !is_string($label) || preg_match('/^lang:[a-z][a-z0-9_.:-]{1,190}$/', $label) !== 1
                || !is_bool($field['hidden'] ?? null) || !is_bool($field['readonly'] ?? null)) {
                throw $this->reject('dataview_descriptor_field_invalid');
            }
            $seen[$key] = true;
            $fields[] = ['key' => $key, 'property_type' => $property, 'label_key' => $label, 'hidden' => $field['hidden'], 'readonly' => $field['readonly']];
        }
        return $fields;
    }

    /** @param list<string> $fieldKeys @return array<string,string> */
    private function options(mixed $value, array $fieldKeys): array
    {
        if (!is_array($value) || ($value !== [] && array_is_list($value)) || count($value) > 20) {
            throw $this->reject('dataview_descriptor_options_invalid');
        }
        $options = [];
        foreach ($value as $role => $field) {
            if (!is_string($role) || $this->stableKey($role, 'dataview_descriptor_options_invalid') !== $role
                || !is_string($field) || !in_array($field, $fieldKeys, true)) {
                throw $this->reject('dataview_descriptor_options_invalid');
            }
            $options[$role] = $field;
        }
        ksort($options, SORT_STRING);
        return $options;
    }

    /** @param list<string> $fieldKeys @return array<string,mixed> */
    private function query(mixed $value, array $fieldKeys): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw $this->reject('dataview_descriptor_query_invalid');
        }
        $this->exactKeys($value, ['default_sort_direction', 'default_sort_field', 'filter_fields', 'per_page', 'sort_fields']);
        $filters = $this->fieldList($value['filter_fields'] ?? null, $fieldKeys);
        $sorts = $this->fieldList($value['sort_fields'] ?? null, $fieldKeys);
        $default = $value['default_sort_field'] ?? null;
        if ($default !== null && (!is_string($default) || !in_array($default, $sorts, true))) {
            throw $this->reject('dataview_descriptor_query_invalid');
        }
        $direction = $value['default_sort_direction'] ?? null;
        if (!in_array($direction, ['asc', 'desc'], true) || ($default === null && $direction !== 'asc')) {
            throw $this->reject('dataview_descriptor_query_invalid');
        }
        $perPage = $value['per_page'] ?? null;
        if (!is_int($perPage) || !in_array($perPage, [10, 20, 50, 100], true)) {
            throw $this->reject('dataview_descriptor_query_invalid');
        }
        return ['filter_fields' => $filters, 'sort_fields' => $sorts, 'default_sort_field' => $default, 'default_sort_direction' => $direction, 'per_page' => $perPage];
    }

    /** @param list<string> $fieldKeys @return list<string> */
    private function fieldList(mixed $value, array $fieldKeys): array
    {
        if (!is_array($value) || !array_is_list($value) || count($value) > self::MAX_FIELDS) {
            throw $this->reject('dataview_descriptor_query_invalid');
        }
        foreach ($value as $field) {
            if (!is_string($field) || !in_array($field, $fieldKeys, true)) {
                throw $this->reject('dataview_descriptor_query_invalid');
            }
        }
        if (count($value) !== count(array_unique($value))) {
            throw $this->reject('dataview_descriptor_query_invalid');
        }
        return $value;
    }

    /** @return array{create:bool,update:bool,delete:bool,restore:bool} */
    private function interaction(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw $this->reject('dataview_descriptor_interaction_invalid');
        }
        $this->exactKeys($value, ['create', 'delete', 'restore', 'update']);
        foreach ($value as $allowed) {
            if (!is_bool($allowed)) {
                throw $this->reject('dataview_descriptor_interaction_invalid');
            }
        }
        return ['create' => $value['create'], 'update' => $value['update'], 'delete' => $value['delete'], 'restore' => $value['restore']];
    }

    private function stableKey(mixed $value, string $reason): string
    {
        if (!is_string($value) || !DataviewSourceDescriptor::isStableKey($value)) {
            throw $this->reject($reason);
        }
        return $value;
    }

    /** @param array<string,mixed> $value @param list<string> $expected */
    private function exactKeys(array $value, array $expected): void
    {
        $keys = array_keys($value);
        sort($keys, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($keys !== $expected) {
            throw $this->reject('dataview_descriptor_unknown_key');
        }
    }

    private function canonicalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (!array_is_list($value)) {
            ksort($value, SORT_STRING);
        }
        foreach ($value as $key => $child) {
            $value[$key] = $this->canonicalize($child);
        }
        return $value;
    }

    /** @param array<string,mixed> $value */
    private function encodeNormalized(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            throw $this->reject('dataview_descriptor_json_invalid');
        }
    }

    private function reject(string $reason): InvalidArgumentException
    {
        return new InvalidArgumentException($reason);
    }
}
