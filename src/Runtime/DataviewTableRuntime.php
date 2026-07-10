<?php

declare(strict_types=1);

namespace Larena\Dataview\Runtime;

use InvalidArgumentException;
use Larena\Dataview\Contracts\DataviewActionPolicy;
use Larena\Dataview\Contracts\DataviewPagination;
use Larena\Dataview\Contracts\DataviewSourceProvider;
use Larena\Dataview\Contracts\DataviewTablePage;
use Larena\Dataview\Contracts\DataviewViewDescriptor;
use Larena\Dataview\Contracts\DataviewViewProjection;
use Larena\Dataview\Enums\DataviewViewType;

final class DataviewTableRuntime
{
    public function project(DataviewSourceProvider $provider, DataviewViewDescriptor $view, DataviewActionPolicy $policy, int $page = 1, int $perPage = 20): DataviewTablePage
    {
        if (!$view->isValid() || $view->type !== DataviewViewType::Table) {
            throw new InvalidArgumentException('dataview_table_view_required');
        }
        $source = $provider->descriptor();
        if (!$source->isValid() || $source != $view->source) {
            throw new InvalidArgumentException('dataview_source_descriptor_mismatch');
        }
        if (!$policy->allowsExecution()) {
            throw new InvalidArgumentException('dataview_action_policy_denied');
        }
        $rows = $provider->rows();
        $perPage = max(1, min(100, $perPage));
        $candidate = new DataviewPagination(max(1, $page), $perPage, count($rows));
        $page = min($candidate->page, $candidate->lastPage());
        $pagination = new DataviewPagination($page, $perPage, count($rows));
        $slice = array_slice($rows, ($page - 1) * $perPage, $perPage);

        return new DataviewTablePage(
            new DataviewViewProjection($view, $slice, $policy, ['source-provider:' . $source->sourceKey, 'table-projection:read-only-safe']),
            $pagination,
        );
    }
}
