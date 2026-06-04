<?php

declare(strict_types=1);

namespace Larena\Dataview\Enums;

enum DataviewSavedViewScope: string
{
    case User = 'user';
    case Team = 'team';
    case System = 'system';
    case Public = 'public';

    public function canContainPrivateFilters(): bool
    {
        return $this !== self::Public;
    }
}
