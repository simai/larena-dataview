<?php

declare(strict_types=1);

namespace Larena\Dataview\Persistence;

/**
 * The package-owned schema of the descriptor store (larena_dataview_descriptors and their versions).
 *
 * Dataview has no framework provider: the host that binds PdoDataviewDescriptorStore registers this path
 * with its migrator (in Laravel, loadMigrationsFrom(DataviewMigrations::path())). The migration keeps the
 * filename it had in Root, so installations that already ran it see it as done.
 */
final class DataviewMigrations
{
    public static function path(): string
    {
        return dirname(__DIR__, 2) . '/database/migrations';
    }
}
