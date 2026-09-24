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

    /**
     * The field roles this view needs and the field types that may fill each of them. A type may be
     * named by its family: "text" admits "text.short" and "text.long".
     *
     * @return array<string, array{required: bool, types: list<string>}>
     */
    public function roles(): array
    {
        $text = ['string', 'text'];
        $dates = ['date', 'datetime'];

        return match ($this) {
            self::Table => ['title' => ['required' => false, 'types' => $text]],
            self::Cards => ['title' => ['required' => true, 'types' => $text]],
            self::Kanban => ['lane' => ['required' => true, 'types' => ['choice', 'user', 'boolean']]],
            self::Calendar => ['date' => ['required' => true, 'types' => $dates]],
            self::Gantt => ['start' => ['required' => true, 'types' => $dates], 'end' => ['required' => true, 'types' => $dates]],
            self::Tree => [
                'id' => ['required' => true, 'types' => ['string', 'integer', 'identifier']],
                'parent' => ['required' => true, 'types' => ['relation', 'string', 'integer', 'identifier']],
            ],
        };
    }

    /**
     * What part of the shared query this view can honour. A list pages through records; a calendar
     * or a timeline asks for a range over its date roles instead. Filtering a tree would cut
     * branches from their parents, so a tree searches but does not filter.
     *
     * @return array{filters: bool, search: bool, sort: bool, window: 'page'|'range'}
     */
    public function queryCapabilities(): array
    {
        return match ($this) {
            self::Table, self::Cards, self::Kanban => ['filters' => true, 'search' => true, 'sort' => true, 'window' => 'page'],
            self::Calendar => ['filters' => true, 'search' => true, 'sort' => false, 'window' => 'range'],
            self::Gantt => ['filters' => true, 'search' => true, 'sort' => true, 'window' => 'range'],
            self::Tree => ['filters' => false, 'search' => true, 'sort' => true, 'window' => 'page'],
        };
    }
}
