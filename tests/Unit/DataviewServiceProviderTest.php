<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\DatabaseManager;
use Larena\Dataview\Contracts\DataviewDescriptorStore;
use Larena\Dataview\Persistence\PdoDataviewDescriptorStore;
use Larena\Dataview\Providers\DataviewServiceProvider;

$composer = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true, 512, JSON_THROW_ON_ERROR);
assert(($composer['extra']['laravel']['providers'] ?? null) === [DataviewServiceProvider::class]);
assert(isset($composer['require']['ext-pdo'], $composer['require']['illuminate/database'], $composer['require']['illuminate/support']));

$capsule = new Capsule();
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);

// The package composes its descriptor store over the application's connection.
$app = new Container();
$app->instance(DatabaseManager::class, $capsule->getDatabaseManager());
(new DataviewServiceProvider($app))->register(); // @phpstan-ignore argument.type
assert($app->make(DataviewDescriptorStore::class) instanceof PdoDataviewDescriptorStore);

// A host that bound its own store first keeps it.
$own = new class implements DataviewDescriptorStore {
    public function create(array $descriptor, string $actor): never { throw new LogicException(); }
    public function update(array $descriptor, int $expectedRevision, string $actor): never { throw new LogicException(); }
    public function read(string $scopeRef, string $viewId): never { throw new LogicException(); }
    public function history(string $scopeRef, string $viewId): never { throw new LogicException(); }
    public function rollback(string $scopeRef, string $viewId, int $targetRevision, int $expectedRevision, string $actor): never { throw new LogicException(); }
};
$app = new Container();
$app->instance(DatabaseManager::class, $capsule->getDatabaseManager());
$app->instance(DataviewDescriptorStore::class, $own);
(new DataviewServiceProvider($app))->register(); // @phpstan-ignore argument.type
assert($app->make(DataviewDescriptorStore::class) === $own);

echo "DataviewServiceProviderTest passed.\n";
