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

    /** Composer package identity of the owning package; independent vendors are allowed here. */
    public static function isOwnerPackage(string $package): bool
    {
        return strlen($package) <= 120
            && preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*\\/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$/', $package) === 1;
    }

    /** Owner trust is decided by the host registry, not by this identity. */
    public function isFirstParty(): bool
    {
        return str_starts_with($this->ownerPackage, 'larena/');
    }

    public function isValid(): bool
    {
        return self::isStableKey($this->sourceKey)
            && self::isOwnerPackage($this->ownerPackage)
            && $this->accessScoped
            && !$this->ownsCanonicalRecords;
    }
}
