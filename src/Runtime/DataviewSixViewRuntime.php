<?php

declare(strict_types=1);

namespace Larena\Dataview\Runtime;

use InvalidArgumentException;
use Larena\Dataview\Contracts\DataviewActionPolicy;
use Larena\Dataview\Contracts\DataviewDatasetSnapshot;
use Larena\Dataview\Contracts\DataviewProjectionSet;
use Larena\Dataview\Contracts\DataviewViewDescriptor;
use Larena\Dataview\Contracts\DataviewViewProjection;

final class DataviewSixViewRuntime
{
    /** @param list<DataviewViewDescriptor> $views */
    public function project(DataviewDatasetSnapshot $snapshot, array $views, DataviewActionPolicy $policy): DataviewProjectionSet
    {
        if (!$snapshot->isValid() || !$policy->allowsExecution()) {
            throw new InvalidArgumentException('dataview_projection_request_invalid');
        }

        $projections = [];
        foreach ($views as $view) {
            if (!$view->isValid() || $view->source != $snapshot->source || isset($projections[$view->type->value])) {
                throw new InvalidArgumentException('dataview_projection_descriptor_invalid');
            }
            $projections[$view->type->value] = new DataviewViewProjection(
                $view,
                $snapshot->rows,
                $policy,
                ['source-snapshot:'.$snapshot->snapshotId, 'projection:read-only'],
                false,
                $snapshot->snapshotId,
            );
        }

        $set = new DataviewProjectionSet($snapshot, $projections);
        if (!$set->isValid()) {
            throw new InvalidArgumentException('dataview_six_view_set_incomplete');
        }

        return $set;
    }
}
