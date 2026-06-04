<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

use Larena\Dataview\Enums\DataviewActionMode;

final readonly class DataviewActionPolicy
{
    public function __construct(
        public DataviewActionMode $mode,
        public bool $accessAllowed,
        public bool $auditRequired,
        public bool $sourceValidationRequired,
    ) {
    }

    public static function readOnly(): self
    {
        return new self(DataviewActionMode::ReadOnly, true, false, false);
    }

    public function allowsExecution(): bool
    {
        if (!$this->accessAllowed) {
            return false;
        }

        if (!$this->mode->hasSideEffects()) {
            return true;
        }

        return $this->auditRequired && $this->sourceValidationRequired;
    }
}
