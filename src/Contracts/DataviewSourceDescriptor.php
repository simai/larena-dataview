<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

final readonly class DataviewSourceDescriptor
{
    public function __construct(
        public string $sourceKey,
        public string $ownerPackage,
        public bool $accessScoped,
        public bool $ownsCanonicalRecords = false,
    ) {
    }

    public static function isStableKey(string $key): bool
    {
        return preg_match('/^[a-z][a-z0-9_]*(\\.[a-z][a-z0-9_]*)*$/', $key) === 1;
    }

    public function isValid(): bool
    {
        return self::isStableKey($this->sourceKey)
            && str_starts_with($this->ownerPackage, 'larena/')
            && $this->accessScoped
            && !$this->ownsCanonicalRecords;
    }
}
