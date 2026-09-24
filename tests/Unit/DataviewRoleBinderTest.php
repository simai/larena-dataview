<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Larena\Dataview\Enums\DataviewViewType;
use Larena\Dataview\Runtime\DataviewDescriptorNormalizer;
use Larena\Dataview\Runtime\DataviewRoleBinder;

$binder = new DataviewRoleBinder();
$fields = ['title' => 'string', 'summary' => 'text.long', 'status' => 'choice', 'deadline' => 'date', 'price' => 'number'];

// A role is filled only by a field of an accepted type; a type family admits its variants.
assert(DataviewRoleBinder::typeAllowed('text.long', ['string', 'text']));
assert(!DataviewRoleBinder::typeAllowed('number', ['date', 'datetime']));
$kanban = $binder->bind(DataviewViewType::Kanban, $fields, ['lane' => 'status']);
assert($kanban === ['bindings' => ['lane' => 'status'], 'missing' => []]);

// A missing or ill-typed candidate leaves a required role missing; nothing else is picked instead.
assert($binder->bind(DataviewViewType::Kanban, $fields, ['lane' => 'title'])['missing'] === ['lane']);
assert($binder->bind(DataviewViewType::Calendar, $fields, ['date' => 'nope'])['missing'] === ['date']);
assert($binder->bind(DataviewViewType::Gantt, $fields, ['start' => 'deadline'])['missing'] === ['end']);
// An optional role may stay unbound without making the view unavailable.
assert($binder->bind(DataviewViewType::Table, $fields, []) === ['bindings' => [], 'missing' => []]);

// Bindings a descriptor carries are checked the same way.
assert($binder->problems(DataviewViewType::Kanban, $fields, ['lane' => 'status']) === []);
assert($binder->problems(DataviewViewType::Kanban, $fields, ['lane' => 'price']) === ['role_type:lane']);
assert($binder->problems(DataviewViewType::Table, $fields, ['colour' => 'status']) === ['unknown_role:colour']);
assert($binder->problems(DataviewViewType::Calendar, $fields, []) === ['role_missing:date']);

// The normalizer refuses a stored view whose lane is a number.
$descriptor = [
    'schema' => DataviewDescriptorNormalizer::SCHEMA, 'scope_ref' => 'scope:tenant.alpha', 'view_id' => 'workbench.projects.kanban',
    'source' => ['key' => 'storage.projects', 'owner_package' => 'larena/storage'], 'type' => 'kanban',
    'fields' => [
        ['key' => 'status', 'property_type' => 'choice', 'label_key' => 'lang:storage.status', 'hidden' => false, 'readonly' => true],
        ['key' => 'price', 'property_type' => 'number', 'label_key' => 'lang:storage.price', 'hidden' => false, 'readonly' => true],
    ],
    'options' => ['lane' => 'status'],
    'query' => ['filter_fields' => [], 'sort_fields' => [], 'default_sort_field' => null, 'default_sort_direction' => 'asc', 'per_page' => 20],
    'interaction' => ['create' => true, 'update' => true, 'delete' => true, 'restore' => true],
];
$normalizer = new DataviewDescriptorNormalizer();
assert($normalizer->normalize($descriptor)['options'] === ['lane' => 'status']);
$descriptor['options'] = ['lane' => 'price'];
try {
    $normalizer->normalize($descriptor);
    throw new RuntimeException('Expected rejection.');
} catch (InvalidArgumentException $exception) {
    assert($exception->getMessage() === 'dataview_descriptor_roles_invalid');
}

echo "DataviewRoleBinderTest passed.\n";
