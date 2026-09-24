<?php

declare(strict_types=1);

namespace Larena\Dataview\Runtime;

use Larena\Dataview\Contracts\DataviewQuery;
use Larena\Dataview\Enums\DataviewViewType;

/**
 * Switching the view keeps the part of the query the target view can honour and names what it had
 * to drop, so the person is told instead of silently losing a filter. The page position never
 * carries over: a new view starts at its beginning.
 */
final class DataviewQueryCarryOver
{
    /** @return array{query: DataviewQuery, dropped: list<'filters'|'search'|'sort'>} */
    public function carry(DataviewQuery $query, DataviewViewType $target): array
    {
        $supports = $target->queryCapabilities();
        $dropped = [];
        if ($query->filters !== [] && !$supports['filters']) {
            $dropped[] = 'filters';
        }
        if ($query->search !== null && !$supports['search']) {
            $dropped[] = 'search';
        }
        if ($query->sort !== [] && !$supports['sort']) {
            $dropped[] = 'sort';
        }

        return [
            'query' => new DataviewQuery(
                filters: $supports['filters'] ? $query->filters : [],
                sort: $supports['sort'] ? $query->sort : [],
                page: 1,
                perPage: $query->perPage,
                search: $supports['search'] ? $query->search : null,
            ),
            'dropped' => $dropped,
        ];
    }
}
