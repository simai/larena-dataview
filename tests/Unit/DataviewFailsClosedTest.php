<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Larena\Dataview\Contracts\DataviewActionPolicy;
use Larena\Dataview\Contracts\DataviewFieldDescriptor;
use Larena\Dataview\Contracts\DataviewSavedView;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\DataviewViewDescriptor;
use Larena\Dataview\Contracts\DataviewViewProjection;
use Larena\Dataview\Enums\DataviewActionMode;
use Larena\Dataview\Enums\DataviewSavedViewScope;
use Larena\Dataview\Enums\DataviewViewType;

$unscopedSource = new DataviewSourceDescriptor('storage.articles', 'larena/storage', false);
assert(!$unscopedSource->isValid());

$owningSource = new DataviewSourceDescriptor('storage.articles', 'larena/storage', true, true);
assert(!$owningSource->isValid());

$badField = new DataviewFieldDescriptor('title', 'text.short', 'Title');
assert(!$badField->isValid());

$validSource = new DataviewSourceDescriptor('storage.articles', 'larena/storage', true);
$field = new DataviewFieldDescriptor('title', 'text.short', 'lang:dataview.title');
$lockedGantt = new DataviewViewDescriptor('articles.gantt', $validSource, DataviewViewType::Gantt, [$field], false);
assert(!$lockedGantt->isValid());

$unsafeAction = new DataviewActionPolicy(DataviewActionMode::DragDrop, true, false, true);
assert(!$unsafeAction->allowsExecution());

$validView = new DataviewViewDescriptor('articles.table', $validSource, DataviewViewType::Table, [$field]);
$owningProjection = new DataviewViewProjection($validView, [], DataviewActionPolicy::readOnly(), ['source'], true);
assert(!$owningProjection->isSafeForRender());

$publicPrivateSavedView = new DataviewSavedView('public.private_filters', DataviewSavedViewScope::Public, $validView, ['owner_id' => 1], true);
assert(!$publicPrivateSavedView->isValid());
