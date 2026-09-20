<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';
$loader = new Composer\Autoload\ClassLoader();
$loader->addPsr4('Larena\\Dataview\\', dirname(__DIR__, 2).'/src');
$loader->register(true);

use Larena\Dataview\Contracts\DataviewDatasetSnapshot;
use Larena\Dataview\Contracts\DataviewPagination;
use Larena\Dataview\Contracts\DataviewQuery;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\RegisteredDataviewSourceAdapter;
use Larena\Dataview\Runtime\RegisteredDataviewSourceRegistry;

$adapter = new class implements RegisteredDataviewSourceAdapter {
    public int $calls = 0;
    public string $mode = 'valid';
    public bool $mustNotRun = false;
    public function descriptor(): DataviewSourceDescriptor
    {
        return new DataviewSourceDescriptor('example.records', 'larena/storage', true);
    }
    public function queryFields(): array
    {
        return [
            'record_id' => ['type' => 'identifier', 'operators' => [], 'sortable' => true],
            'title' => ['type' => 'string', 'operators' => ['eq', 'contains'], 'sortable' => true],
        ];
    }
    public function searchFields(): array { return ['title']; }
    public function dataset(DataviewQuery $query, string $principalId): DataviewDatasetSnapshot
    {
        if ($this->mustNotRun) throw new RuntimeException('adapter_called_before_boundary_validation');
        $this->calls++;
        $source = $this->mode === 'source'
            ? new DataviewSourceDescriptor('other.records', 'larena/storage', true)
            : $this->descriptor();
        $rows = [['record_id' => 'record_Привет', 'title' => 'Alpha']];
        if ($this->mode === 'projection') $rows[0]['secret'] = 'no';
        if ($this->mode === 'count') $rows = [];
        return new DataviewDatasetSnapshot(
            'sha256:'.str_repeat('a', 64),
            $source,
            $this->mode === 'query' ? new DataviewQuery() : $query,
            $rows,
            new DataviewPagination($query->page, $query->perPage, 1),
            $this->mode !== 'access',
        );
    }
};

$registry = new RegisteredDataviewSourceRegistry([$adapter]);
assert($registry->keys() === ['example.records']);
assert(array_keys($registry->queryFields('example.records')) === ['record_id', 'title']);
assert($registry->searchFields('example.records') === ['title']);
$query = new DataviewQuery(search: 'Alpha');
$result = $registry->dataset('example.records', $query, 'user:admin_identity:1');
assert($result->rows[0]['record_id'] === 'record_Привет');

$adapter->mustNotRun = true;
foreach (['', ' ', "bad\0actor", str_repeat('x', 513)] as $principal) {
    try {
        $registry->dataset('example.records', new DataviewQuery(), $principal);
        throw new RuntimeException('Invalid principal accepted.');
    } catch (InvalidArgumentException $failure) {
        assert($failure->getMessage() === 'dataview_source_principal_invalid');
    }
}

foreach (['unknown.records', 'bad key'] as $sourceKey) {
    try {
        $registry->dataset($sourceKey, new DataviewQuery(), 'actor:1');
        throw new RuntimeException('Unknown source accepted.');
    } catch (InvalidArgumentException $failure) {
        assert($failure->getMessage() === 'dataview_source_unknown');
    }
}

try {
    $registry->dataset('example.records', new DataviewQuery([['field' => 'secret', 'operator' => 'eq', 'value' => 'x']]), 'actor:1');
    throw new RuntimeException('Unregistered query reached adapter.');
} catch (InvalidArgumentException $failure) {
    assert($failure->getMessage() === 'dataview_filter_not_registered');
}
$adapter->mustNotRun = false;

foreach (['source', 'query', 'count', 'access', 'projection'] as $mode) {
    $adapter->mode = $mode;
    try {
        $registry->dataset('example.records', new DataviewQuery(sort: [['field' => 'title', 'direction' => 'asc']]), 'actor:1');
        throw new RuntimeException('Invalid adapter result accepted.');
    } catch (InvalidArgumentException $failure) {
        assert(in_array($failure->getMessage(), ['dataview_source_result_invalid', 'dataview_source_projection_invalid'], true));
    }
}

try {
    new RegisteredDataviewSourceRegistry([$adapter, $adapter]);
    throw new RuntimeException('Duplicate source accepted.');
} catch (InvalidArgumentException $failure) {
    assert($failure->getMessage() === 'dataview_source_registration_duplicate');
}

$invalidRegistration = new class implements RegisteredDataviewSourceAdapter {
    public function descriptor(): DataviewSourceDescriptor { return new DataviewSourceDescriptor('bad.records', 'not a package', true); }
    public function queryFields(): array { return []; }
    public function searchFields(): array { return []; }
    public function dataset(DataviewQuery $query, string $principalId): DataviewDatasetSnapshot { throw new RuntimeException('must not run'); }
};
try {
    new RegisteredDataviewSourceRegistry([$invalidRegistration]);
    throw new RuntimeException('Invalid registration accepted.');
} catch (InvalidArgumentException $failure) {
    assert($failure->getMessage() === 'dataview_source_registration_invalid');
}

// An independently owned external source is valid only when the host declares its owner package.
$external = new class implements RegisteredDataviewSourceAdapter {
    public function descriptor(): DataviewSourceDescriptor
    {
        return new DataviewSourceDescriptor('examplevendor.catalog', 'example-vendor/larena-dataview-adapter', true);
    }
    public function queryFields(): array
    {
        return ['record_id' => ['type' => 'identifier', 'operators' => [], 'sortable' => true],
            'title' => ['type' => 'string', 'operators' => ['eq'], 'sortable' => true]];
    }
    public function searchFields(): array { return ['title']; }
    public function dataset(DataviewQuery $query, string $principalId): DataviewDatasetSnapshot
    {
        return new DataviewDatasetSnapshot('sha256:'.str_repeat('b', 64), $this->descriptor(), $query,
            [['record_id' => 'catalog-1', 'title' => 'Alpha']],
            new DataviewPagination($query->page, $query->perPage, 1), true);
    }
};
try {
    new RegisteredDataviewSourceRegistry([$external]);
    throw new RuntimeException('Untrusted external owner accepted.');
} catch (InvalidArgumentException $failure) {
    assert($failure->getMessage() === 'dataview_source_owner_not_trusted');
}
foreach (['larena/storage', 'not a package', 7] as $badTrust) {
    try {
        new RegisteredDataviewSourceRegistry([$external], [$badTrust]);
        throw new RuntimeException('Invalid owner trust accepted.');
    } catch (InvalidArgumentException $failure) {
        assert($failure->getMessage() === ($badTrust === 'larena/storage'
            ? 'dataview_source_owner_not_trusted' : 'dataview_source_owner_trust_invalid'));
    }
}
$externalRegistry = new RegisteredDataviewSourceRegistry([$external], ['example-vendor/larena-dataview-adapter']);
assert($externalRegistry->keys() === ['examplevendor.catalog']);
$denied = new Larena\Dataview\Exceptions\RegisteredSourceAccessDenied('examplevendor.catalog');
assert($denied->sourceKey === 'examplevendor.catalog' && $denied->getMessage() === 'dataview_source_access_denied');
$externalPage = $externalRegistry->dataset('examplevendor.catalog', new DataviewQuery(search: 'Alpha'), 'user:admin_identity:1');
assert($externalPage->rows === [['record_id' => 'catalog-1', 'title' => 'Alpha']]);
assert($externalPage->source->isFirstParty() === false);

try {
    new RegisteredDataviewSourceRegistry([new stdClass()]);
    throw new RuntimeException('Invalid adapter accepted.');
} catch (InvalidArgumentException $failure) {
    assert($failure->getMessage() === 'dataview_source_adapter_invalid');
}

echo "RegisteredDataviewSourceRegistryTest passed: validated registration, query, principal, result, projection and duplicate refusal.\n";
