<?php

declare(strict_types=1);

namespace Larena\Dataview\Runtime;

use InvalidArgumentException;
use Larena\Dataview\Contracts\DataviewDatasetSnapshot;
use Larena\Dataview\Contracts\DataviewQuery;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\RegisteredDataviewSourceAdapter;

/** A fail-closed registry; request JSON can select a key but cannot register executable behavior. */
final class RegisteredDataviewSourceRegistry
{
    /**
     * @var array<string,array{
     *   adapter:RegisteredDataviewSourceAdapter,
     *   descriptor:DataviewSourceDescriptor,
     *   fields:array<string,array{type:string,operators:list<string>,sortable:bool}>,
     *   search_fields:list<string>
     * }>
     */
    private array $entries = [];

    /** @param iterable<mixed> $adapters Runtime validation protects container/tag integrations. */
    public function __construct(iterable $adapters)
    {
        $validator = new RegisteredQueryValidator();
        foreach ($adapters as $adapter) {
            if (!$adapter instanceof RegisteredDataviewSourceAdapter) {
                throw new InvalidArgumentException('dataview_source_adapter_invalid');
            }
            $descriptor = $adapter->descriptor();
            $fields = $adapter->queryFields();
            $searchFields = $adapter->searchFields();
            if (!$descriptor->isValid()) {
                throw new InvalidArgumentException('dataview_source_registration_invalid');
            }
            $validator->assertAllowed(new DataviewQuery(), $fields, $searchFields);
            if (isset($this->entries[$descriptor->sourceKey])) {
                throw new InvalidArgumentException('dataview_source_registration_duplicate');
            }
            $this->entries[$descriptor->sourceKey] = [
                'adapter' => $adapter,
                'descriptor' => $descriptor,
                'fields' => $fields,
                'search_fields' => $searchFields,
            ];
        }
    }

    /** @return list<string> */
    public function keys(): array
    {
        $keys = array_keys($this->entries);
        sort($keys, SORT_STRING);

        return $keys;
    }

    /** @return array<string,array{type:string,operators:list<string>,sortable:bool}> */
    public function queryFields(string $sourceKey): array
    {
        return $this->entry($sourceKey)['fields'];
    }

    /** @return list<string> */
    public function searchFields(string $sourceKey): array
    {
        return $this->entry($sourceKey)['search_fields'];
    }

    public function dataset(string $sourceKey, DataviewQuery $query, string $principalId): DataviewDatasetSnapshot
    {
        $entry = $this->entry($sourceKey);
        $this->assertPrincipal($principalId);
        (new RegisteredQueryValidator())->assertAllowed($query, $entry['fields'], $entry['search_fields']);

        $result = $entry['adapter']->dataset($query, $principalId);
        $pagination = $result->pagination;
        $expectedRows = min(
            $pagination->perPage,
            max(0, $pagination->total - ($pagination->page - 1) * $pagination->perPage),
        );
        if (!$result->isValid()
            || $result->source != $entry['descriptor']
            || $result->query != $query
            || $pagination->perPage !== $query->perPage
            || $pagination->page > $query->page
            || count($result->rows) !== $expectedRows) {
            throw new InvalidArgumentException('dataview_source_result_invalid');
        }
        foreach ($result->rows as $row) {
            if (array_diff(array_keys($row), array_keys($entry['fields'])) !== []) {
                throw new InvalidArgumentException('dataview_source_projection_invalid');
            }
        }

        return $result;
    }

    /**
     * @return array{
     *   adapter:RegisteredDataviewSourceAdapter,
     *   descriptor:DataviewSourceDescriptor,
     *   fields:array<string,array{type:string,operators:list<string>,sortable:bool}>,
     *   search_fields:list<string>
     * }
     */
    private function entry(string $sourceKey): array
    {
        if (!DataviewSourceDescriptor::isStableKey($sourceKey) || !isset($this->entries[$sourceKey])) {
            throw new InvalidArgumentException('dataview_source_unknown');
        }

        return $this->entries[$sourceKey];
    }

    private function assertPrincipal(string $principalId): void
    {
        if ($principalId === '' || trim($principalId) === '' || strlen($principalId) > 512
            || preg_match('//u', $principalId) !== 1
            || preg_match('/[\x00-\x1F\x7F]/', $principalId) === 1) {
            throw new InvalidArgumentException('dataview_source_principal_invalid');
        }
    }
}
