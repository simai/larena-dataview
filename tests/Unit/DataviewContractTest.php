<?php

declare(strict_types=1);

use Larena\Dataview\Contracts\DataviewActionPolicy;
use Larena\Dataview\Contracts\DataviewFieldDescriptor;
use Larena\Dataview\Contracts\DataviewSavedView;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\DataviewViewDescriptor;
use Larena\Dataview\Contracts\DataviewViewProjection;
use Larena\Dataview\Enums\DataviewActionMode;
use Larena\Dataview\Enums\DataviewSavedViewScope;
use Larena\Dataview\Enums\DataviewViewType;

$source = new DataviewSourceDescriptor('storage.articles', 'larena/storage', true);
assert($source->isValid());

$field = new DataviewFieldDescriptor('title', 'text.short', 'lang:dataview.title');
assert($field->isValid());

$view = new DataviewViewDescriptor('articles.table', $source, DataviewViewType::Table, [$field]);
assert($view->isValid());

$policy = new DataviewActionPolicy(DataviewActionMode::InlineEdit, true, true, true);
assert($policy->allowsExecution());

$projection = new DataviewViewProjection($view, [['title' => 'Example']], DataviewActionPolicy::readOnly(), ['source:storage.articles']);
assert($projection->isSafeForRender());

$savedView = new DataviewSavedView('my.articles', DataviewSavedViewScope::User, $view, ['title' => 'Example'], true);
assert($savedView->isValid());
