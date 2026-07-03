<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

final readonly class DataviewReadOnlyTableAdapterResult
{
    /**
     * @param array<string, mixed> $serialized
     * @param array<string, bool> $negativeGuardrails
     * @param array<string, bool|string|int> $diagnostics
     * @param array<string, string|bool> $adapter
     */
    public function __construct(
        public DataviewViewProjection $projection,
        public array $serialized,
        public array $negativeGuardrails,
        public array $diagnostics,
        public array $adapter,
    ) {
    }

    public function guardrailSummary(): string
    {
        $passed = 0;
        foreach ($this->negativeGuardrails as $guardrail) {
            if ($guardrail === true) {
                ++$passed;
            }
        }

        return $passed . '/' . count($this->negativeGuardrails) . ' passed';
    }

    public function guardrailsPassed(): bool
    {
        if ($this->negativeGuardrails === []) {
            return false;
        }

        foreach ($this->negativeGuardrails as $guardrail) {
            if ($guardrail !== true) {
                return false;
            }
        }

        return true;
    }
}
