<?php

declare(strict_types=1);

namespace Larena\Dataview\Runtime;

use Larena\Dataview\Contracts\DataviewActionPolicy;
use Larena\Dataview\Contracts\DataviewFieldDescriptor;
use Larena\Dataview\Contracts\DataviewReadOnlyTableAdapterResult;
use Larena\Dataview\Contracts\DataviewReadOnlyTableDefinition;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\DataviewViewDescriptor;
use Larena\Dataview\Contracts\DataviewViewProjection;
use Larena\Dataview\Enums\DataviewActionMode;
use Larena\Dataview\Enums\DataviewViewType;

final class ReadOnlyDataviewTableAdapter
{
    public const ADAPTER_RUNTIME = 'larena/dataview:read_only_table_adapter.v1';

    public function adapt(DataviewReadOnlyTableDefinition $definition): DataviewReadOnlyTableAdapterResult
    {
        $source = $definition->source();
        $view = new DataviewViewDescriptor(
            $definition->viewKey,
            $source,
            DataviewViewType::Table,
            $definition->fields,
        );
        $projection = new DataviewViewProjection(
            $view,
            $definition->rows,
            DataviewActionPolicy::readOnly(),
            $definition->explain,
        );
        $negativeGuardrails = $this->negativeGuardrails($definition, $projection);
        $diagnostics = $this->diagnostics($definition, $projection, $negativeGuardrails);

        return new DataviewReadOnlyTableAdapterResult(
            $projection,
            $this->serializeProjection($definition, $projection),
            $negativeGuardrails,
            $diagnostics,
            [
                'runtime' => self::ADAPTER_RUNTIME,
                'adapter_key' => $definition->adapterKey,
                'owner_package' => 'larena/dataview',
                'consumer_package' => $definition->consumerPackage,
                'read_only_table_adapter' => true,
            ],
        );
    }

    /**
     * @return array<string, bool>
     */
    private function negativeGuardrails(DataviewReadOnlyTableDefinition $definition, DataviewViewProjection $projection): array
    {
        $unsafeSource = new DataviewSourceDescriptor($this->humanizedKey($definition->sourceKey), $definition->sourceOwnerPackage, true);
        $invalidField = new DataviewFieldDescriptor(($projection->descriptor->fields[0]->fieldKey ?? 'field') . ' label', 'text.short', 'lang:dataview.invalid');
        $writeAction = new DataviewActionPolicy(DataviewActionMode::InlineEdit, true, false, true);
        $unsupportedView = new DataviewViewDescriptor(
            $definition->viewKey . '.gantt',
            $projection->descriptor->source,
            DataviewViewType::Gantt,
            $projection->descriptor->fields,
            false,
        );
        $ownerMismatch = new DataviewSourceDescriptor($definition->sourceKey, 'simai/larena', true);

        return [
            'unsafe_source_rejected' => !$unsafeSource->isValid(),
            'invalid_field_key_rejected' => !$invalidField->isValid(),
            'write_capable_action_without_approval_rejected' => !$writeAction->allowsExecution(),
            'unsupported_view_mode_rejected' => !$unsupportedView->isValid(),
            'owner_mismatch_rejected' => !$ownerMismatch->isValid(),
            'rows_mismatch_rejected' => count($projection->rows) !== count($definition->rows) + 1,
            'direct_blade_or_raw_html_bypass_rejected' => !$definition->directHtmlBypassPresent,
            'root_asset_copy_or_cdn_rejected' => !$definition->rootAssetCopyOrCdnPresent,
        ];
    }

    /**
     * @param array<string, bool> $negativeGuardrails
     * @return array<string, bool|string|int>
     */
    private function diagnostics(
        DataviewReadOnlyTableDefinition $definition,
        DataviewViewProjection $projection,
        array $negativeGuardrails,
    ): array {
        return [
            'dataview_adapter_runtime' => self::ADAPTER_RUNTIME,
            'dataview_adapter_key' => $definition->adapterKey,
            'dataview_adapter_owner_package' => 'larena/dataview',
            'dataview_adapter_consumer_package' => $definition->consumerPackage,
            'dataview_source_descriptor_valid' => $projection->descriptor->source->isValid(),
            'dataview_view_descriptor_valid' => $projection->descriptor->isValid(),
            'dataview_projection_safe_for_render' => $projection->isSafeForRender(),
            'dataview_projection_rows_match_source_rows' => count($projection->rows) === count($definition->rows),
            'dataview_table_view_type' => $projection->descriptor->type === DataviewViewType::Table,
            'dataview_interaction_read_only' => $projection->interactionPolicy->mode === DataviewActionMode::ReadOnly,
            'dataview_negative_guardrails_passed' => $this->guardrailsPassed($negativeGuardrails),
            'dataview_row_count' => count($projection->rows),
            'full_dataview_rollout_claim' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProjection(DataviewReadOnlyTableDefinition $definition, DataviewViewProjection $projection): array
    {
        return [
            'owner_package' => 'larena/dataview',
            'consumer_package' => $definition->consumerPackage,
            'adapter_runtime' => self::ADAPTER_RUNTIME,
            'adapter_key' => $definition->adapterKey,
            'accepted_scope' => $definition->acceptedScope,
            'source_descriptor' => [
                'source_key' => $projection->descriptor->source->sourceKey,
                'owner_package' => $projection->descriptor->source->ownerPackage,
                'access_scoped' => $projection->descriptor->source->accessScoped,
                'owns_canonical_records' => $projection->descriptor->source->ownsCanonicalRecords,
                'valid' => $projection->descriptor->source->isValid(),
            ],
            'view_descriptor' => [
                'view_key' => $projection->descriptor->viewKey,
                'type' => $projection->descriptor->type->value,
                'valid' => $projection->descriptor->isValid(),
                'fields' => array_map(static fn (DataviewFieldDescriptor $field): array => [
                    'field_key' => $field->fieldKey,
                    'property_type_key' => $field->propertyTypeKey,
                    'label_key' => $field->labelKey,
                    'hidden' => $field->hidden,
                    'readonly' => $field->readonly,
                    'valid' => $field->isValid(),
                ], $projection->descriptor->fields),
            ],
            'projection' => [
                'safe_for_render' => $projection->isSafeForRender(),
                'rows_count' => count($projection->rows),
                'owns_source_data' => $projection->ownsSourceData,
                'explain' => $projection->explain,
            ],
            'interaction_policy' => [
                'mode' => $projection->interactionPolicy->mode->value,
                'access_allowed' => $projection->interactionPolicy->accessAllowed,
                'audit_required' => $projection->interactionPolicy->auditRequired,
                'source_validation_required' => $projection->interactionPolicy->sourceValidationRequired,
                'allows_execution' => $projection->interactionPolicy->allowsExecution(),
            ],
            'full_dataview_rollout_claim' => false,
        ];
    }

    /**
     * @param array<string, bool> $guardrails
     */
    private function guardrailsPassed(array $guardrails): bool
    {
        if ($guardrails === []) {
            return false;
        }

        foreach ($guardrails as $passed) {
            if ($passed !== true) {
                return false;
            }
        }

        return true;
    }

    private function humanizedKey(string $key): string
    {
        return str_replace(['.', '_'], ' ', $key);
    }
}
