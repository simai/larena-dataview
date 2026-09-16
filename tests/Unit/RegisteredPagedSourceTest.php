<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';
$loader = new Composer\Autoload\ClassLoader(); $loader->addPsr4('Larena\\Dataview\\', dirname(__DIR__, 2).'/src'); $loader->register(true);

use Larena\Dataview\Contracts\DataviewDatasetSnapshot;
use Larena\Dataview\Contracts\DataviewPagedSourceProvider;
use Larena\Dataview\Contracts\DataviewPagination;
use Larena\Dataview\Contracts\DataviewQuery;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Runtime\DataviewDatasetRuntime;

$source = new class implements DataviewPagedSourceProvider {
    private int $calls = 0;
    public function callCount(): int { return $this->calls; }
    public string $mode = 'valid';
    public function descriptor(): DataviewSourceDescriptor { return new DataviewSourceDescriptor('content.records', 'larena/storage', true); }
    public function searchFields(): array { return []; }
    public function page(DataviewQuery $query): DataviewDatasetSnapshot {
        $this->calls++;
        $rows = [['record_id' => 'record_Привет_2', 'title' => 'Beta']];
        if ($this->mode === 'leak') $rows[0]['password_hash'] = 'forbidden';
        if ($this->mode === 'count') $rows = [];
        return new DataviewDatasetSnapshot('sha256:'.str_repeat('a', 64),
            $this->mode === 'owner' ? new DataviewSourceDescriptor('content.records', 'larena/auth', true) : $this->descriptor(),
            $this->mode === 'query' ? new DataviewQuery() : $query,
            $rows, new DataviewPagination($query->page, $query->perPage, 2), $this->mode !== 'access');
    }
};
$fields = ['record_id' => ['type' => 'identifier', 'operators' => [], 'sortable' => true], 'title' => ['type' => 'string', 'operators' => ['eq'], 'sortable' => true]];
$runtime = new DataviewDatasetRuntime(); $query = new DataviewQuery([], [], 2, 1);
$page = $runtime->loadRegisteredPage($source, $query, $fields);
assert($page->rows[0]['record_id'] === 'record_Привет_2');
assert($source->callCount() === 1);
foreach (['owner', 'query', 'count', 'access', 'leak'] as $mode) {
    $source->mode = $mode;
    try { $runtime->loadRegisteredPage($source, $query, $fields); throw new RuntimeException('Invalid owner page accepted.'); }
    catch (InvalidArgumentException) {}
}
$calls = $source->callCount();
try { $runtime->loadRegisteredPage($source, new DataviewQuery([['field' => 'private', 'operator' => 'eq', 'value' => 'x']]), $fields); throw new RuntimeException('Invalid query accepted.'); }
catch (InvalidArgumentException) { assert($source->callCount() === $calls); }
echo "RegisteredPagedSourceTest passed: one owner page, no reslicing, five invalid output refusals and pre-query guard.\n";
