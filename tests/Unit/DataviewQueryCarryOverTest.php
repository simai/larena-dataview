<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Larena\Dataview\Contracts\DataviewQuery;
use Larena\Dataview\Enums\DataviewViewType;
use Larena\Dataview\Runtime\DataviewQueryCarryOver;

// The shared vocabulary keeps relative ranges relative and checks each value against its operator.
assert((new DataviewQuery(filters: [['field' => 'deadline', 'operator' => 'month', 'value' => null]]))->isValid());
assert(!(new DataviewQuery(filters: [['field' => 'deadline', 'operator' => 'month', 'value' => '2026-09']]))->isValid());
assert((new DataviewQuery(filters: [['field' => 'price', 'operator' => 'between', 'value' => [1, 9]]]))->isValid());
assert(!(new DataviewQuery(filters: [['field' => 'price', 'operator' => 'between', 'value' => [1]]]))->isValid());
assert(!(new DataviewQuery(filters: [['field' => 'status', 'operator' => 'in', 'value' => []]]))->isValid());
assert(!(new DataviewQuery(filters: [['field' => 'status', 'operator' => 'eq', 'value' => ['done']]]))->isValid());
assert(!(new DataviewQuery(filters: [['field' => 'status', 'operator' => 'like', 'value' => 'x']]))->isValid());

$query = new DataviewQuery(
    filters: [['field' => 'status', 'operator' => 'in', 'value' => ['done']]],
    sort: [['field' => 'deadline', 'direction' => 'desc']],
    page: 4,
    perPage: 50,
    search: 'report',
);
$carry = new DataviewQueryCarryOver();

// A table keeps everything but starts at its first page.
$table = $carry->carry($query, DataviewViewType::Table);
assert($table['dropped'] === []);
assert($table['query']->filters === $query->filters && $table['query']->search === 'report' && $table['query']->sort === $query->sort);
assert($table['query']->page === 1 && $table['query']->perPage === 50);

// A calendar cannot order records by a field: sort is dropped and named.
$calendar = $carry->carry($query, DataviewViewType::Calendar);
assert($calendar['dropped'] === ['sort'] && $calendar['query']->sort === [] && $calendar['query']->filters === $query->filters);

// A tree searches but does not filter.
$tree = $carry->carry($query, DataviewViewType::Tree);
assert($tree['dropped'] === ['filters'] && $tree['query']->filters === [] && $tree['query']->search === 'report');

// Nothing to drop means nothing reported.
assert($carry->carry(new DataviewQuery(), DataviewViewType::Calendar)['dropped'] === []);

foreach (DataviewViewType::cases() as $type) {
    $capabilities = $type->queryCapabilities();
    assert(in_array($capabilities['window'], ['page', 'range'], true));
}

echo "DataviewQueryCarryOverTest passed.\n";
