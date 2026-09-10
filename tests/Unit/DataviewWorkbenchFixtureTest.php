<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Larena\Dataview\Developer\DataviewWorkbenchFixture;

$fixture = new DataviewWorkbenchFixture();
assert(count($fixture->rows()) === 48);
assert(count($fixture->columns()) === 9);
assert(($fixture->columns()[1]['width'] ?? null) === '320px');
assert(array_column($fixture->filterFields(), 'filter')[0]['control'] === 'text');
$controls = array_column(array_column($fixture->filterFields(), 'filter'), 'control');
foreach (['text', 'range', 'date-range', 'multi-select', 'single-select', 'entity-multi-select', 'entity-single-select'] as $control) {
    assert(in_array($control, $controls, true));
}
$page = $fixture->query(['page' => 1, 'page_size' => 5, 'filters' => ['status' => ['operator' => 'in', 'value' => ['review']]]]);
assert(count($page['rows']) === 5);
assert($page['pagination']['total'] === 12);
assert(array_reduce($page['rows'], static fn (bool $match, array $row): bool => $match && $row['status_key'] === 'review', true));
$range = $fixture->query(['page_size' => 40, 'filters' => ['score' => ['operator' => 'between', 'value' => [80, 90]]]]);
assert($range['pagination']['total'] > 0);
assert($range['pagination']['total'] < 48);

$rejected = false;
try { $fixture->query(['filters' => ['unknown' => ['value' => 'x']]]); } catch (InvalidArgumentException) { $rejected = true; }
assert($rejected);

echo "DataviewWorkbenchFixtureTest passed.\n";
