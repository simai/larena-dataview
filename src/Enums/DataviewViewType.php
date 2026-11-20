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

    /**
     * The layout a view of this type opens in, in the words of simai.view-config/1 (`table`, `board`, `cards`,
     * `calendar`, `list`, `tree`): a kanban is a `board`; a gantt has no layout of its own in version 1 (`timeline`
     * is a reserved name there), so it answers null and its view keeps its type beside the layout.
     */
    public function layout(): ?string
    {
        return match ($this) {
            self::Kanban => 'board',
            self::Gantt => null,
            default => $this->value,
        };
    }

    /** The type of a simai.view-config/1 layout: null for `list` and for a name the view does not know. */
    public static function fromLayout(string $layout): ?self
    {
        return $layout === 'board' ? self::Kanban : (in_array($layout, ['table', 'cards', 'calendar', 'tree'], true) ? self::from($layout) : null);
    }

    public function isAdvanced(): bool
    {
        return in_array($this, [self::Gantt], true);
    }

    /**
     * The field roles this view needs and the field types that may fill each of them. A type may be
     * named by its family: "text" admits "text.short" and "text.long".
     *
     * Cards (C306, CV-4) may bind a `cover`: a file field whose picture tops the card. A calendar (C307, CL-4) may
     * bind an `end` of the start's kind (a delivery window, a stay of several days), an `all_day` yes/no field (without
     * it a date field is all-day and a date-time field is timed) and a `color`: a choice field whose option colour the
     * event takes. The layout options of these views (cover fit and ratio, card fields, size; modes, hours, rules) are
     * the view's `layouts.cards` and `layouts.calendar` in simai.view-config/1, kept by the source.
     *
     * @return array<string, array{required: bool, types: list<string>}>
     */
    public function roles(): array
    {
        $text = ['string', 'text'];
        $dates = ['date', 'datetime'];

        return match ($this) {
            self::Table => ['title' => ['required' => false, 'types' => $text]],
            self::Cards => ['title' => ['required' => true, 'types' => $text], 'cover' => ['required' => false, 'types' => ['file']]],
            self::Kanban => ['lane' => ['required' => true, 'types' => ['choice', 'user', 'boolean']]],
            self::Calendar => [
                'date' => ['required' => true, 'types' => $dates],
                'end' => ['required' => false, 'types' => $dates],
                'all_day' => ['required' => false, 'types' => ['boolean']],
                'color' => ['required' => false, 'types' => ['choice']],
            ],
            self::Gantt => ['start' => ['required' => true, 'types' => $dates], 'end' => ['required' => true, 'types' => $dates]],
            self::Tree => [
                'id' => ['required' => true, 'types' => ['string', 'integer', 'identifier']],
                'parent' => ['required' => true, 'types' => ['relation', 'string', 'integer', 'identifier']],
            ],
        };
    }

    /**
     * What part of the shared query this view can honour. A list pages through records (cards: 24 a
     * page); a calendar or a timeline asks for a range over its date roles instead (the days on screen,
     * up to 1000 records, the undated ones asked apart). Filtering a tree would cut
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
