<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';
$loader = new Composer\Autoload\ClassLoader();
$loader->addPsr4('Larena\\Dataview\\', dirname(__DIR__, 2).'/src');
$loader->register(true);

use Larena\Dataview\Contracts\DataviewSourceProvider;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\DataviewQuery;
use Larena\Dataview\Runtime\DataviewDatasetRuntime;
use Larena\Dataview\Runtime\RegisteredQueryValidator;

$source = new class implements DataviewSourceProvider {
    private int $reads = 0;
    public function reads(): int { return $this->reads; }
    public function descriptor(): DataviewSourceDescriptor { return new DataviewSourceDescriptor('auth.users', 'larena/auth', true); }
    public function rows(): array {
        $this->reads++;
        return [['record_id' => 'user_1', 'name' => 'Alpha Привет', 'email' => 'first@example.test', 'status' => 'active'],
            ['record_id' => 'user_2', 'name' => 'Beta', 'email' => 'alpha@example.test', 'status' => 'blocked']];
    }
};
$fields = ['record_id' => ['type' => 'identifier', 'operators' => [], 'sortable' => true],
    'name' => ['type' => 'string', 'operators' => ['contains'], 'sortable' => true],
    'email' => ['type' => 'string', 'operators' => ['contains'], 'sortable' => true],
    'status' => ['type' => 'string', 'operators' => ['eq'], 'sortable' => true]];
$runtime = new DataviewDatasetRuntime();
$or = $runtime->loadRegistered($source, new DataviewQuery(search: ' ALPHA '), $fields, ['name', 'email']);
assert($or->pagination->total === 2);
$nameOnly = $runtime->loadRegistered($source, new DataviewQuery(search: 'alpha'), $fields, ['name']);
assert($nameOnly->pagination->total === 1);
assert($nameOnly->rows[0]['record_id'] === 'user_1');
$and = $runtime->loadRegistered($source, new DataviewQuery(
    [['field' => 'status', 'operator' => 'eq', 'value' => 'blocked']], search: 'alpha'), $fields, ['name', 'email']);
assert($and->rows[0]['record_id'] === 'user_2');
assert($and->pagination->total === 1);
assert($runtime->loadRegistered($source, new DataviewQuery(search: '%'), $fields, ['name'])->pagination->total === 0);
$unicode = $runtime->loadRegistered($source, new DataviewQuery(search: 'Привет'), $fields, ['name']);
assert($unicode->rows[0]['record_id'] === 'user_1');
assert($or->snapshotId !== $nameOnly->snapshotId);
foreach ([new DataviewQuery(search: ' '), new DataviewQuery(search: str_repeat('x', 101)),
    new DataviewQuery(search: "bad\0input"), new DataviewQuery(search: "\xFF")] as $query) {
    assert(!$query->isValid());
}
$before = $source->reads();
try {
    $runtime->loadRegistered($source, new DataviewQuery(search: 'alpha'), $fields);
    throw new RuntimeException('Unregistered search accepted.');
} catch (InvalidArgumentException $failure) { assert($failure->getMessage() === 'dataview_search_not_registered'); }
assert($source->reads() === $before);
foreach ([['record_id'], ['unknown'], ['name', 'name']] as $searchFields) {
    try {
        (new RegisteredQueryValidator())->assertAllowed(new DataviewQuery(search: 'x'), $fields, $searchFields);
        throw new RuntimeException('Invalid search registration accepted.');
    } catch (InvalidArgumentException $failure) { assert($failure->getMessage() === 'dataview_search_registration_invalid'); }
}
echo "RegisteredSearchTest passed: trusted OR fields, AND filters, literal text, Unicode, query identity and pre-read refusals.\n";
