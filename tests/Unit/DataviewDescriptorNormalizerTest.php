<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use Larena\Dataview\Runtime\DataviewDescriptorNormalizer;

$normalizer = new DataviewDescriptorNormalizer();
$descriptor = dataviewDescriptor();
$normalized = $normalizer->normalize($descriptor);
assert($normalized['schema'] === DataviewDescriptorNormalizer::SCHEMA);
assert($normalizer->hash($descriptor) === $normalizer->hash($normalized));
assert($normalizer->toViewDescriptor($descriptor)->isValid());

$unknown = $descriptor;
$unknown['html'] = '<script>x</script>';
rejectDescriptor(static fn () => $normalizer->normalize($unknown), 'dataview_descriptor_unknown_key');
$invalidField = $descriptor;
$invalidField['query']['filter_fields'] = ['secret'];
rejectDescriptor(static fn () => $normalizer->normalize($invalidField), 'dataview_descriptor_query_invalid');
$missingRole = $descriptor;
$missingRole['type'] = 'calendar';
rejectDescriptor(static fn () => $normalizer->normalize($missingRole), 'dataview_descriptor_contract_invalid');

echo "DataviewDescriptorNormalizerTest passed.\n";

/** @return array<string,mixed> */
function dataviewDescriptor(): array
{
    return [
        'schema' => 'larena.dataview.descriptor.v1',
        'scope_ref' => 'scope:tenant.alpha',
        'view_id' => 'workbench.projects.table',
        'source' => ['key' => 'storage.projects', 'owner_package' => 'larena/storage'],
        'type' => 'table',
        'fields' => [
            ['key' => 'title', 'property_type' => 'string', 'label_key' => 'lang:storage.title', 'hidden' => false, 'readonly' => true],
            ['key' => 'status', 'property_type' => 'string', 'label_key' => 'lang:storage.status', 'hidden' => false, 'readonly' => true],
        ],
        'options' => [],
        'query' => ['filter_fields' => ['status'], 'sort_fields' => ['title', 'status'], 'default_sort_field' => 'title', 'default_sort_direction' => 'asc', 'per_page' => 20],
        'interaction' => ['create' => true, 'update' => true, 'delete' => true, 'restore' => true],
    ];
}

function rejectDescriptor(callable $operation, string $reason): void
{
    try {
        $operation();
        throw new RuntimeException('Expected rejection.');
    } catch (InvalidArgumentException $exception) {
        assert($exception->getMessage() === $reason);
    }
}
