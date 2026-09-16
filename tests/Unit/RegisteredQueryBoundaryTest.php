<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';
$localLoader = new Composer\Autoload\ClassLoader();
$localLoader->addPsr4('Larena\\Dataview\\', dirname(__DIR__, 2).'/src');
$localLoader->register(true);

use Larena\Dataview\Contracts\DataviewQuery;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\DataviewSourceProvider;
use Larena\Dataview\Runtime\DataviewDatasetRuntime;

$source = new class implements DataviewSourceProvider {
    public int $reads = 0;
    public bool $includeThird = false;
    public function descriptor(): DataviewSourceDescriptor { return new DataviewSourceDescriptor('content.records', 'larena/storage', true); }
    public function rows(): array {
        $this->reads++;
        $rows = [['record_id' => 'record_Привет_1', 'title' => 'Alpha', 'score' => 20], ['record_id' => 'record_2', 'title' => 'Beta', 'score' => 10]];
        if ($this->includeThird) $rows[] = ['record_id' => 'record_3', 'title' => 'Gamma', 'score' => 30];
        return $rows;
    }
};
$fields = ['record_id' => ['type' => 'identifier', 'operators' => ['eq', 'in'], 'sortable' => false],
    'title' => ['type' => 'string', 'operators' => ['contains', 'eq', 'in'], 'sortable' => true],
    'score' => ['type' => 'integer', 'operators' => ['eq', 'gte', 'lte'], 'sortable' => true]];
$runtime = new DataviewDatasetRuntime();
$selected = $runtime->loadRegistered($source, new DataviewQuery([['field' => 'record_id', 'operator' => 'eq', 'value' => 'record_Привет_1']]), $fields);
assert(count($selected->rows) === 1);
assert($selected->rows[0]['record_id'] === 'record_Привет_1');
$sorted = $runtime->loadRegistered($source, new DataviewQuery([], [['field' => 'score', 'direction' => 'asc']], 1, 1), $fields);
assert($sorted->rows[0]['record_id'] === 'record_2');
assert($sorted->pagination->total === 2);
$source->includeThird = true;
$changedCount = $runtime->loadRegistered($source, new DataviewQuery([], [['field' => 'score', 'direction' => 'asc']], 1, 1), $fields);
assert($sorted->rows === $changedCount->rows);
assert($changedCount->pagination->total === 3);
assert($sorted->snapshotId !== $changedCount->snapshotId, 'Count changes must invalidate dataset snapshot even when visible rows stay equal.');
$queries = [new DataviewQuery([['field' => 'secret', 'operator' => 'eq', 'value' => 'x']]),
    new DataviewQuery([['field' => 'record_id', 'operator' => 'contains', 'value' => 'record']]),
    new DataviewQuery([['field' => 'score', 'operator' => 'gte', 'value' => '10']]),
    new DataviewQuery([['field' => 'title', 'operator' => 'in', 'value' => []]]),
    new DataviewQuery([['field' => 'title', 'operator' => 'eq', 'value' => 'Alpha', 'callback' => 'execute']]),
    new DataviewQuery([], [['field' => 'record_id', 'direction' => 'asc']]),
    new DataviewQuery([], [['field' => 'title', 'direction' => 'asc'], ['field' => 'title', 'direction' => 'desc']]),
    new DataviewQuery(array_fill(0, 21, ['field' => 'title', 'operator' => 'eq', 'value' => 'Alpha'])),
    new DataviewQuery([['field' => 'title', 'operator' => 'eq', 'value' => str_repeat('x', 4097)]]),
    new DataviewQuery([['field' => 'title', 'operator' => 'eq', 'value' => "\xFF"]])];
foreach ($queries as $query) {
    $reads = $source->reads;
    try { $runtime->loadRegistered($source, $query, $fields); throw new RuntimeException('Unregistered query accepted.'); }
    catch (InvalidArgumentException) { assert($source->reads === $reads, 'Rejected query must not read owner rows.'); }
}
$badFields = $fields; $badFields['title']['operators'] = [['unsafe']];
try { (new \Larena\Dataview\Runtime\RegisteredQueryValidator())->assertAllowed(new DataviewQuery(), $badFields); throw new RuntimeException('Invalid registration accepted.'); }
catch (InvalidArgumentException $exception) { assert($exception->getMessage() === 'dataview_field_registration_invalid'); }
assert((new ReflectionClass($runtime))->getFileName() === realpath(__DIR__.'/../../src/Runtime/DataviewDatasetRuntime.php'));
echo "RegisteredQueryBoundaryTest passed: exact identities, common sort/pagination, ten pre-read query refusals and malformed registration.\n";
