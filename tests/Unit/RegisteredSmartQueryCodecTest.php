<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';
$loader = new Composer\Autoload\ClassLoader();
$loader->addPsr4('Larena\\Dataview\\', dirname(__DIR__, 2).'/src');
$loader->register(true);

use Larena\Dataview\Runtime\RegisteredSmartQueryCodec;

$fields = ['title' => ['type' => 'string', 'operators' => ['eq', 'contains', 'in'], 'sortable' => true],
    'record_id' => ['type' => 'identifier', 'operators' => ['eq'], 'sortable' => true],
    'count' => ['type' => 'integer', 'operators' => ['gte'], 'sortable' => true]];
$codec = new RegisteredSmartQueryCodec();
$q = $codec->decode(['search' => 'Привет', 'page' => 2, 'page_size' => 10,
    'filters' => ['record_id' => ['operator' => 'eq', 'value' => 'record_42_Привет'],
        'title' => ['operator' => 'in', 'values' => ['Alpha', 'Beta']]],
    'sort' => [['field' => 'title', 'direction' => 'asc']]], $fields, ['title']);
assert($q->search === 'Привет'); assert($q->page === 2); assert($q->perPage === 10);
assert($q->filters[0]['value'] === 'record_42_Привет');
assert($q->filters[1]['value'] === ['Alpha', 'Beta']);
assert($q->sort === [['field' => 'title', 'direction' => 'asc']]);
$inactive = $codec->decode(['search' => '', 'filters' => ['title' => ['operator' => 'eq', 'value' => '']]], $fields, ['title']);
assert($inactive->filters === []); assert($inactive->search === null);
$zero = $codec->decode(['filters' => ['count' => ['operator' => 'gte', 'value' => 0]]], $fields);
assert($zero->filters[0]['value'] === 0);
$textZero = $codec->decode(['filters' => ['count' => ['operator' => 'gte', 'value' => '0']]], $fields);
assert($textZero->filters[0]['value'] === 0);
$bad = [['page' => null], ['search' => null], ['filters' => null], ['sort' => null], ['endpoint' => '/unsafe'], ['page' => '2'], ['page_size' => 101], ['search' => []],
    ['sort' => ['title' => 'asc']], ['sort' => ['bad']],
    ['filters' => ['missing' => ['operator' => 'eq', 'value' => 'x']]],
    ['filters' => ['title' => ['operator' => 'eq', 'value' => 'x', 'values' => ['x']]]],
    ['filters' => ['title' => ['operator' => 'eq', 'values' => ['x']]]],
    ['filters' => ['title' => ['operator' => 'unknown', 'value' => '']]],
    ['filters' => ['title' => ['operator' => 'eq', 'value' => 'x', 'handler' => 'arbitrary']]],
    ['filters' => ['count' => ['operator' => 'gte', 'value' => '1.2']]]];
foreach ($bad as $wire) {
    try { $codec->decode($wire, $fields, ['title']); throw new RuntimeException('Malformed Smart query accepted.'); }
    catch (InvalidArgumentException) {}
}
echo "RegisteredSmartQueryCodecTest passed: typed public query, opaque IDs, inactive controls, zero and sixteen refusals.\n";
