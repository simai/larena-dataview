<?php

declare(strict_types=1);

namespace Larena\Dataview\Enums;

enum DataviewViewType: string
{
    case Table = 'table';
    case Kanban = 'kanban';
    case Calendar = 'calendar';
    case Gantt = 'gantt';
    case Cards = 'cards';
    case Tree = 'tree';

    public function isAdvanced(): bool
    {
        return in_array($this, [self::Gantt], true);
    }
}
