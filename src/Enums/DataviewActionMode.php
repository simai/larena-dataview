<?php

declare(strict_types=1);

namespace Larena\Dataview\Enums;

enum DataviewActionMode: string
{
    case ReadOnly = 'read_only';
    case InlineEdit = 'inline_edit';
    case DragDrop = 'drag_drop';
    case BulkAction = 'bulk_action';

    public function hasSideEffects(): bool
    {
        return $this !== self::ReadOnly;
    }
}
