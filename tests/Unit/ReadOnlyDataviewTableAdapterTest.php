<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Larena\Dataview\Contracts\DataviewFieldDescriptor;
use Larena\Dataview\Contracts\DataviewReadOnlyTableDefinition;
use Larena\Dataview\Runtime\ReadOnlyDataviewTableAdapter;

$adapter = new ReadOnlyDataviewTableAdapter();
$result = $adapter->adapt(new DataviewReadOnlyTableDefinition(
    'articles.read_only_table',
    'storage.articles',
    'larena/storage',
    'storage.articles.table',
    [
        new DataviewFieldDescriptor('title', 'text.short', 'lang:dataview.title'),
        new DataviewFieldDescriptor('status', 'status.badge', 'lang:dataview.status'),
    ],
    [
        ['title' => 'Example', 'status' => 'published'],
    ],
    [
        'source:storage.articles',
        'owner:larena/dataview',
        'consumer:larena/admin',
    ],
    'larena/admin',
    'read_only_table_adapter_contract_test',
));

assert($result->adapter['runtime'] === ReadOnlyDataviewTableAdapter::ADAPTER_RUNTIME);
assert($result->projection->isSafeForRender());
assert($result->serialized['adapter_runtime'] === ReadOnlyDataviewTableAdapter::ADAPTER_RUNTIME);
assert($result->serialized['source_descriptor']['source_key'] === 'storage.articles');
assert($result->serialized['view_descriptor']['type'] === 'table');
assert($result->serialized['projection']['rows_count'] === 1);
assert($result->serialized['interaction_policy']['mode'] === 'read_only');
assert($result->diagnostics['dataview_adapter_runtime'] === ReadOnlyDataviewTableAdapter::ADAPTER_RUNTIME);
assert($result->diagnostics['dataview_projection_rows_match_source_rows'] === true);
assert($result->diagnostics['dataview_negative_guardrails_passed'] === true);
assert($result->negativeGuardrails['unsafe_source_rejected'] === true);
assert($result->negativeGuardrails['invalid_field_key_rejected'] === true);
assert($result->negativeGuardrails['write_capable_action_without_approval_rejected'] === true);
assert($result->negativeGuardrails['unsupported_view_mode_rejected'] === true);
assert($result->negativeGuardrails['owner_mismatch_rejected'] === true);
assert($result->negativeGuardrails['rows_mismatch_rejected'] === true);
assert($result->negativeGuardrails['direct_blade_or_raw_html_bypass_rejected'] === true);
assert($result->negativeGuardrails['root_asset_copy_or_cdn_rejected'] === true);
assert($result->guardrailsPassed());
assert($result->guardrailSummary() === '8/8 passed');
