<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use Larena\Dataview\Contracts\DataviewActionPolicy;
use Larena\Dataview\Contracts\DataviewFieldDescriptor;
use Larena\Dataview\Contracts\DataviewQuery;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\DataviewSourceProvider;
use Larena\Dataview\Contracts\DataviewViewDescriptor;
use Larena\Dataview\Enums\DataviewViewType;
use Larena\Dataview\Runtime\DataviewDatasetRuntime;
use Larena\Dataview\Runtime\DataviewSixViewRuntime;

$source = new DataviewSourceDescriptor('storage.project_items', 'larena/storage', true);
$rows = [
    ['id' => '1', 'parent_id' => null, 'title' => 'Alpha', 'status' => 'open', 'date' => '2026-08-18', 'start' => '2026-08-18', 'end' => '2026-08-19'],
    ['id' => '2', 'parent_id' => '1', 'title' => 'Beta', 'status' => 'closed', 'date' => '2026-08-19', 'start' => '2026-08-19', 'end' => '2026-08-20'],
    ['id' => '3', 'parent_id' => '1', 'title' => 'Gamma', 'status' => 'open', 'date' => '2026-08-20', 'start' => '2026-08-20', 'end' => '2026-08-21'],
];
$provider = new class($source, $rows) implements DataviewSourceProvider {
    /** @param list<array<string, mixed>> $rows */
    public function __construct(private DataviewSourceDescriptor $source, private array $rows) {}
    public function descriptor(): DataviewSourceDescriptor { return $this->source; }
    public function rows(): array { return $this->rows; }
};
$query = new DataviewQuery(
    filters: [['field' => 'status', 'operator' => 'eq', 'value' => 'open']],
    sort: [['field' => 'title', 'direction' => 'desc']],
    page: 1,
    perPage: 100,
);
$snapshot = (new DataviewDatasetRuntime())->load($provider, $query);
assert($snapshot->isValid());
assert(array_column($snapshot->rows, 'title') === ['Gamma', 'Alpha']);
assert($snapshot->pagination->total === 2);

$fields = array_map(
    static fn (string $field): DataviewFieldDescriptor => new DataviewFieldDescriptor($field, 'string', 'lang:dataview.'.$field),
    ['id', 'parent_id', 'title', 'status', 'date', 'start', 'end'],
);
$views = [
    new DataviewViewDescriptor('project.table', $source, DataviewViewType::Table, $fields),
    new DataviewViewDescriptor('project.cards', $source, DataviewViewType::Cards, $fields, options: ['title' => 'title']),
    new DataviewViewDescriptor('project.calendar', $source, DataviewViewType::Calendar, $fields, options: ['date' => 'date']),
    new DataviewViewDescriptor('project.kanban', $source, DataviewViewType::Kanban, $fields, options: ['lane' => 'status']),
    new DataviewViewDescriptor('project.gantt', $source, DataviewViewType::Gantt, $fields, options: ['start' => 'start', 'end' => 'end']),
    new DataviewViewDescriptor('project.tree', $source, DataviewViewType::Tree, $fields, options: ['id' => 'id', 'parent' => 'parent_id']),
];
$set = (new DataviewSixViewRuntime())->project($snapshot, $views, DataviewActionPolicy::readOnly());
assert($set->isValid());
assert(array_keys($set->projections) === ['table', 'cards', 'calendar', 'kanban', 'gantt', 'tree']);
foreach ($set->projections as $projection) {
    assert($projection->rows === $snapshot->rows);
    assert($projection->sourceSnapshotId === $snapshot->snapshotId);
    assert(!$projection->ownsSourceData);
}

$incompleteRejected = false;
try {
    (new DataviewSixViewRuntime())->project($snapshot, array_slice($views, 0, 5), DataviewActionPolicy::readOnly());
} catch (InvalidArgumentException $exception) {
    $incompleteRejected = $exception->getMessage() === 'dataview_six_view_set_incomplete';
}
assert($incompleteRejected);
assert(!is_dir(dirname(__DIR__, 2).'/database'));

echo "DataviewSixViewRuntimeTest: OK\n";
