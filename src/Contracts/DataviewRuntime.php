<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

interface DataviewRuntime
{
    public function validateSource(DataviewSourceDescriptor $source): bool;

    public function validateView(DataviewViewDescriptor $view): bool;

    public function project(DataviewViewDescriptor $view): DataviewViewProjection;

    public function validateAction(DataviewActionPolicy $policy): bool;

    public function validateSavedView(DataviewSavedView $savedView): bool;
}
