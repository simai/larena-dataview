<?php

declare(strict_types=1);

namespace Larena\Dataview\Runtime;

use InvalidArgumentException;
use Larena\Dataview\Contracts\DataviewDatasetSnapshot;
use Larena\Dataview\Contracts\DataviewPagination;
use Larena\Dataview\Contracts\DataviewQuery;
use Larena\Dataview\Contracts\DataviewSourceProvider;

final class DataviewDatasetRuntime
{
    public function load(DataviewSourceProvider $provider, DataviewQuery $query): DataviewDatasetSnapshot
    {
        $source = $provider->descriptor();
        if (!$source->isValid() || !$query->isValid()) {
            throw new InvalidArgumentException('dataview_dataset_request_invalid');
        }

        $rows = $provider->rows();
        $rows = array_values(array_filter($rows, fn (array $row): bool => $this->matches($row, $query)));
        $this->sort($rows, $query);

        $total = count($rows);
        $candidate = new DataviewPagination($query->page, $query->perPage, $total);
        $page = min($query->page, $candidate->lastPage());
        $pagination = new DataviewPagination($page, $query->perPage, $total);
        $slice = array_slice($rows, ($page - 1) * $query->perPage, $query->perPage);
        $snapshotPayload = [$source->sourceKey, $query->normalizedFilters(), $query->normalizedSort(), $pagination->page, $pagination->perPage, $slice];

        return new DataviewDatasetSnapshot(
            'sha256:'.hash('sha256', json_encode($snapshotPayload, JSON_THROW_ON_ERROR)),
            $source,
            $query,
            $slice,
            $pagination,
            true,
        );
    }

    /** @param array<string, mixed> $row */
    private function matches(array $row, DataviewQuery $query): bool
    {
        foreach ($query->normalizedFilters() as $filter) {
            if (!array_key_exists($filter['field'], $row)) {
                return false;
            }
            $actual = $row[$filter['field']];
            $expected = $filter['value'];
            $matches = match ($filter['operator']) {
                'eq' => $actual === $expected,
                'in' => is_array($expected) && in_array($actual, $expected, true),
                'contains' => is_string($actual) && is_string($expected) && str_contains($actual, $expected),
                'gte' => is_scalar($actual) && is_scalar($expected) && $actual >= $expected,
                'lte' => is_scalar($actual) && is_scalar($expected) && $actual <= $expected,
                default => false,
            };
            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /** @param list<array<string, mixed>> $rows */
    private function sort(array &$rows, DataviewQuery $query): void
    {
        usort($rows, static function (array $left, array $right) use ($query): int {
            foreach ($query->normalizedSort() as $sort) {
                $comparison = ($left[$sort['field']] ?? null) <=> ($right[$sort['field']] ?? null);
                if ($comparison !== 0) {
                    return $sort['direction'] === 'desc' ? -$comparison : $comparison;
                }
            }

            return json_encode($left, JSON_THROW_ON_ERROR) <=> json_encode($right, JSON_THROW_ON_ERROR);
        });
    }
}
