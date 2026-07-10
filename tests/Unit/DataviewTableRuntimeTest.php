<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Larena\Dataview\Contracts\DataviewActionPolicy;
use Larena\Dataview\Contracts\DataviewFieldDescriptor;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\DataviewSourceProvider;
use Larena\Dataview\Contracts\DataviewViewDescriptor;
use Larena\Dataview\Enums\DataviewViewType;
use Larena\Dataview\Runtime\DataviewTableRuntime;

$source = new DataviewSourceDescriptor('docara.pages', 'larena/docara', true);
$provider = new class($source) implements DataviewSourceProvider {
    public function __construct(private DataviewSourceDescriptor $source) {}
    public function descriptor(): DataviewSourceDescriptor { return $this->source; }
    public function rows(): array { return [['title' => 'A'], ['title' => 'B']]; }
};
$view = new DataviewViewDescriptor('docara.pages.table', $source, DataviewViewType::Table, [
    new DataviewFieldDescriptor('title', 'text', 'lang:pages.title'),
]);
$result = (new DataviewTableRuntime())->project($provider, $view, DataviewActionPolicy::readOnly(), 1, 1);
assert($result->isSafeForRender());
assert(count($result->projection->rows) === 1);
assert($result->pagination->total === 2);
assert($result->pagination->lastPage() === 2);
echo "DataviewTableRuntimeTest: OK\n";
