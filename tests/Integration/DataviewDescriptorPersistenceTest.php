<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../Unit/DataviewDescriptorNormalizerTest.php';

use Larena\Dataview\Persistence\PdoDataviewDescriptorStore;

$path = tempnam(sys_get_temp_dir(), 'larena-dataview-');
assert(is_string($path));
$pdo = new PDO('sqlite:'.$path);
$store = new PdoDataviewDescriptorStore($pdo);
// The tables come from the package migration; the store needs them ready and never creates them.
$missing = new PdoDataviewDescriptorStore(new PDO('sqlite::memory:'));
try {
    $missing->read('scope:tenant.alpha', 'workbench.projects.table');
    throw new RuntimeException('Missing schema accepted.');
} catch (InvalidArgumentException $exception) {
    assert($exception->getMessage() === 'dataview_descriptor_persistence_failed');
}
$pdo->exec('CREATE TABLE larena_dataview_descriptors (scope_ref VARCHAR(128) NOT NULL, view_id VARCHAR(120) NOT NULL, current_revision INTEGER NOT NULL, current_json TEXT NOT NULL, semantic_hash CHAR(64) NOT NULL, updated_by VARCHAR(160) NOT NULL, PRIMARY KEY (scope_ref, view_id))');
$pdo->exec('CREATE TABLE larena_dataview_descriptor_versions (scope_ref VARCHAR(128) NOT NULL, view_id VARCHAR(120) NOT NULL, revision INTEGER NOT NULL, document_json TEXT NOT NULL, semantic_hash CHAR(64) NOT NULL, changed_by VARCHAR(160) NOT NULL, PRIMARY KEY (scope_ref, view_id, revision))');
$created = $store->create(dataviewDescriptor(), 'actor:alpha');
assert($created->revision === 1);
$updated = $created->descriptor;
$updated['query']['per_page'] = 50;
assert($store->update($updated, 1, 'actor:alpha')->revision === 2);
assert(array_map(static fn ($item): int => $item->revision, $store->history('scope:tenant.alpha', 'workbench.projects.table')) === [2, 1]);
$rolledBack = $store->rollback('scope:tenant.alpha', 'workbench.projects.table', 1, 2, 'actor:alpha');
assert($rolledBack->revision === 3);
assert($rolledBack->descriptor['query']['per_page'] === 20);
try {
    $store->update($updated, 2, 'actor:alpha');
    throw new RuntimeException('Stale update accepted.');
} catch (InvalidArgumentException $exception) {
    assert($exception->getMessage() === 'dataview_descriptor_revision_conflict');
}
$pdo->exec("UPDATE larena_dataview_descriptors SET semantic_hash = '".str_repeat('0', 64)."'");
try {
    $store->read('scope:tenant.alpha', 'workbench.projects.table');
    throw new RuntimeException('Tampered descriptor accepted.');
} catch (InvalidArgumentException $exception) {
    assert($exception->getMessage() === 'dataview_descriptor_persisted_integrity_failed');
}
@unlink($path);
echo "DataviewDescriptorPersistenceTest passed.\n";
