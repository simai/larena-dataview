<?php

declare(strict_types=1);

namespace Larena\Dataview\Contracts;

final readonly class DataviewReadOnlyTableDefinition
{
    /**
     * @param list<DataviewFieldDescriptor> $fields
     * @param list<array<string, mixed>> $rows
     * @param list<string> $explain
     */
    public function __construct(
        public string $adapterKey,
        public string $sourceKey,
        public string $sourceOwnerPackage,
        public string $viewKey,
        public array $fields,
        public array $rows,
        public array $explain,
        public string $consumerPackage,
        public string $acceptedScope,
        public bool $directHtmlBypassPresent = false,
        public bool $rootAssetCopyOrCdnPresent = false,
    ) {
    }

    public function source(): DataviewSourceDescriptor
    {
        return new DataviewSourceDescriptor($this->sourceKey, $this->sourceOwnerPackage, true);
    }
}
