<?php

declare(strict_types=1);

namespace Larena\Dataview\Providers;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use Larena\Dataview\Contracts\DataviewDescriptorStore;
use Larena\Dataview\Persistence\DataviewMigrations;
use Larena\Dataview\Persistence\PdoDataviewDescriptorStore;
use Larena\Dataview\Runtime\DataviewDescriptorNormalizer;

/**
 * Dataview's own composition: the descriptor tables (its migration) and the descriptor store over the
 * application's database connection. A host may bind its own DataviewDescriptorStore instead.
 */
final class DataviewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(DataviewDescriptorNormalizer::class);
        $this->app->scoped(PdoDataviewDescriptorStore::class, static fn (Container $app): PdoDataviewDescriptorStore => new PdoDataviewDescriptorStore(
            $app->make(DatabaseManager::class)->connection()->getPdo(),
            $app->make(DataviewDescriptorNormalizer::class),
        ));
        $this->app->scopedIf(DataviewDescriptorStore::class, static fn (Container $app): DataviewDescriptorStore => $app->make(PdoDataviewDescriptorStore::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(DataviewMigrations::path());
    }
}
