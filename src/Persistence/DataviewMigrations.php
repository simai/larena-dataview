<?php

declare(strict_types=1);

namespace Larena\Dataview\Persistence;

/**
 * The package-owned schema of the descriptor store (larena_dataview_descriptors and their versions).
 *
 * DataviewServiceProvider registers this path with the migrator; a host without Laravel runs the same
 * migration itself. The migration keeps the filename it had in Root, so installations that already ran it
 * see it as done.
 */
final class DataviewMigrations
{
    public static function path(): string
    {
        return dirname(__DIR__, 2) . '/database/migrations';
    }
}
