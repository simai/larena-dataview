<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../Unit/DataviewDescriptorNormalizerTest.php';

use Larena\Dataview\Persistence\PdoDataviewDescriptorStore;

$path = tempnam(sys_get_temp_dir(), 'larena-dataview-');
assert(is_string($path));
$pdo = new PDO('sqlite:'.$path);
$store = new PdoDataviewDescriptorStore($pdo);
$store->install();
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
