<?php

declare(strict_types=1);

namespace Larena\Dataview\Developer;

use InvalidArgumentException;

/** Deterministic, backend-owned dataset for exercising the complete Data View UI contract. */
final class DataviewWorkbenchFixture
{
    public const VIEW_KEY = 'larena.ui.dataview.workbench';

    /** @return list<array<string, mixed>> */
    public function columns(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'width' => '96px'],
            ['key' => 'title', 'label' => 'Название', 'width' => '320px'],
            ['key' => 'status', 'label' => 'Статус', 'width' => '140px'],
            ['key' => 'category', 'label' => 'Категория', 'width' => '150px'],
            ['key' => 'author', 'label' => 'Автор', 'width' => '170px'],
            ['key' => 'score', 'label' => 'Оценка', 'width' => '120px'],
            ['key' => 'published_at', 'label' => 'Опубликовано', 'width' => '155px'],
            ['key' => 'updated_at', 'label' => 'Обновлено', 'width' => '155px'],
            ['key' => 'language', 'label' => 'Язык', 'width' => '100px'],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function filterFields(): array
    {
        return [
            $this->field('title', 'Название', 'text', ['contains', 'eq', 'startsWith'], 'contains'),
            $this->field('score', 'Оценка', 'range', ['eq', 'gte', 'lte', 'between'], 'between'),
            $this->field('published_at', 'Дата публикации', 'date-range', ['eq', 'gte', 'lte', 'between'], 'between'),
            $this->field('status', 'Статус', 'multi-select', ['in', 'eq'], 'in', [
                ['value' => 'published', 'label' => 'Опубликовано'],
                ['value' => 'draft', 'label' => 'Черновик'],
                ['value' => 'review', 'label' => 'На проверке'],
                ['value' => 'archived', 'label' => 'Архив'],
            ]),
            $this->field('category', 'Категория', 'single-select', ['eq'], 'eq', [
                ['value' => 'news', 'label' => 'Новости'],
                ['value' => 'docs', 'label' => 'Документы'],
                ['value' => 'cases', 'label' => 'Кейсы'],
                ['value' => 'landing', 'label' => 'Лендинги'],
            ]),
            $this->field('author', 'Автор', 'entity-multi-select', ['in', 'eq'], 'in', [
                ['id' => 'alex', 'value' => 'alex', 'label' => 'Александр'],
                ['id' => 'maria', 'value' => 'maria', 'label' => 'Мария'],
                ['id' => 'irina', 'value' => 'irina', 'label' => 'Ирина'],
                ['id' => 'pavel', 'value' => 'pavel', 'label' => 'Павел'],
            ]),
            $this->field('language', 'Язык', 'entity-single-select', ['eq'], 'eq', [
                ['id' => 'ru', 'value' => 'ru', 'label' => 'Русский'],
                ['id' => 'en', 'value' => 'en', 'label' => 'English'],
                ['id' => 'tr', 'value' => 'tr', 'label' => 'Türkçe'],
            ]),
        ];
    }

    /** @return array<string, mixed> */
    public function packagePreferences(): array
    {
        return [
            'schema_version' => 1,
            'columns' => [
                'id' => ['visible' => true, 'order' => 10],
                'title' => ['visible' => true, 'order' => 20],
                'status' => ['visible' => true, 'order' => 30],
                'category' => ['visible' => true, 'order' => 40],
                'author' => ['visible' => true, 'order' => 50],
                'score' => ['visible' => true, 'order' => 60],
                'published_at' => ['visible' => true, 'order' => 70],
                'updated_at' => ['visible' => false, 'order' => 80],
                'language' => ['visible' => false, 'order' => 90],
            ],
            'filters' => [
                'fields' => array_combine(
                    array_column($this->filterFields(), 'key'),
                    array_map(static fn (int $index): array => ['visible' => true, 'order' => ($index + 1) * 10], array_keys($this->filterFields())),
                ),
                'templates' => [
                    ['key' => 'all', 'label' => 'Все записи', 'selected' => true, 'default' => true, 'pinned' => true, 'order' => 0, 'data' => ['values' => [], 'tags' => []]],
                    ['key' => 'needs-review', 'label' => 'На проверке', 'selected' => false, 'pinned' => true, 'order' => 10, 'data' => [
                        'values' => ['status' => ['operator' => 'in', 'value' => ['review']]],
                        'tags' => ['status' => ['label' => 'Статус', 'value' => 'На проверке']],
                    ]],
                ],
                'selected_template_key' => 'all',
            ],
            'pagination' => ['page_size' => 10],
            'layout' => ['density' => 'comfortable'],
        ];
    }

    /** @return list<array<string, int|string>> */
    public function rows(): array
    {
        $statuses = ['published', 'draft', 'review', 'archived'];
        $statusLabels = ['published' => 'Опубликовано', 'draft' => 'Черновик', 'review' => 'На проверке', 'archived' => 'Архив'];
        $categories = ['news' => 'Новости', 'docs' => 'Документы', 'cases' => 'Кейсы', 'landing' => 'Лендинги'];
        $authors = ['alex' => 'Александр', 'maria' => 'Мария', 'irina' => 'Ирина', 'pavel' => 'Павел'];
        $languages = ['ru' => 'RU', 'en' => 'EN', 'tr' => 'TR'];
        $titles = ['Обзор платформы', 'Руководство администратора', 'История клиента', 'Новая возможность', 'Каталог решений', 'Инструкция по интеграции'];
        $rows = [];
        for ($index = 1; $index <= 48; $index++) {
            $status = $statuses[($index - 1) % count($statuses)];
            $categoryKey = array_keys($categories)[($index + 1) % count($categories)];
            $authorKey = array_keys($authors)[($index + 2) % count($authors)];
            $languageKey = array_keys($languages)[($index - 1) % count($languages)];
            $day = (($index - 1) % 28) + 1;
            $rows[] = [
                'id' => 12000 + $index,
                'title' => $titles[($index - 1) % count($titles)] . ' ' . str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'status' => $statusLabels[$status],
                'status_key' => $status,
                'category' => $categories[$categoryKey],
                'category_key' => $categoryKey,
                'author' => $authors[$authorKey],
                'author_key' => $authorKey,
                'score' => 35 + (($index * 7) % 66),
                'published_at' => sprintf('2026-%02d-%02d', (($index - 1) % 8) + 1, $day),
                'updated_at' => sprintf('2026-%02d-%02d', (($index + 1) % 8) + 1, (($day + 4) % 28) + 1),
                'language' => $languages[$languageKey],
                'language_key' => $languageKey,
            ];
        }
        return $rows;
    }

    /**
     * @param array<string, mixed> $query
     * @return array{rows:list<array<string,int|string>>,pagination:array{page:int,pageSize:int,total:int,pageCount:int},query:array<string,mixed>}
     */
    public function query(array $query): array
    {
        $search = trim((string) ($query['search'] ?? ''));
        $page = max(1, min(100, (int) ($query['page'] ?? 1)));
        $pageSize = (int) ($query['page_size'] ?? 10);
        if (!in_array($pageSize, [5, 10, 20, 40], true)) {
            throw new InvalidArgumentException('admin_dataview_page_size_invalid');
        }
        $filters = $query['filters'] ?? [];
        if (!is_array($filters) || ($filters !== [] && array_is_list($filters))) {
            throw new InvalidArgumentException('admin_dataview_filters_invalid');
        }
        $known = array_column($this->filterFields(), null, 'key');
        foreach (array_keys($filters) as $key) {
            if (!is_string($key) || !isset($known[$key])) {
                throw new InvalidArgumentException('admin_dataview_filter_unknown');
            }
        }

        $rows = array_values(array_filter($this->rows(), function (array $row) use ($search, $filters, $known): bool {
            if ($search !== '' && !str_contains(mb_strtolower(implode(' ', array_map('strval', $row))), mb_strtolower($search))) {
                return false;
            }
            foreach ($filters as $key => $raw) {
                if (!$this->matches($row, $key, $raw, $known[$key])) {
                    return false;
                }
            }
            return true;
        }));

        $total = count($rows);
        $pageCount = max(1, (int) ceil($total / $pageSize));
        $page = min($page, $pageCount);
        return [
            'rows' => array_slice($rows, ($page - 1) * $pageSize, $pageSize),
            'pagination' => ['page' => $page, 'pageSize' => $pageSize, 'total' => $total, 'pageCount' => $pageCount],
            'query' => ['search' => $search, 'filters' => $filters, 'page' => $page, 'page_size' => $pageSize],
        ];
    }

    /** @param list<string> $operators @param list<array<string,string>> $options */
    private function field(string $key, string $label, string $control, array $operators, string $default, array $options = []): array
    {
        return ['key' => $key, 'label' => $label, 'filter' => ['enabled' => true, 'control' => $control, 'operators' => $operators, 'defaultOperator' => $default, 'options' => $options]];
    }

    /** @param array<string,int|string> $row @param array<string,mixed> $field */
    private function matches(array $row, string $key, mixed $raw, array $field): bool
    {
        if (!is_array($raw)) {
            $raw = ['value' => $raw];
        }
        $operator = (string) ($raw['operator'] ?? $field['filter']['defaultOperator'] ?? 'eq');
        if (!in_array($operator, $field['filter']['operators'] ?? [], true)) {
            throw new InvalidArgumentException('admin_dataview_filter_operator_invalid');
        }
        $value = $raw['value'] ?? $raw['values'] ?? null;
        if ($value === null || $value === '' || $value === []) {
            return true;
        }
        $sourceKey = in_array($key, ['status', 'category', 'author', 'language'], true) ? $key . '_key' : $key;
        $actual = $row[$sourceKey] ?? null;
        $values = is_array($value) ? array_values($value) : [$value];
        return match ($operator) {
            'contains' => str_contains(mb_strtolower((string) $actual), mb_strtolower((string) $values[0])),
            'startsWith' => str_starts_with(mb_strtolower((string) $actual), mb_strtolower((string) $values[0])),
            'in' => in_array((string) $actual, array_map('strval', $values), true),
            'gte', 'gt' => $actual >= ($values[0] ?? $actual),
            'lte', 'lt' => $actual <= ($values[0] ?? $actual),
            'between' => $actual >= ($values[0] ?? $actual) && $actual <= ($values[1] ?? $actual),
            default => (string) $actual === (string) ($values[0] ?? ''),
        };
    }
}
