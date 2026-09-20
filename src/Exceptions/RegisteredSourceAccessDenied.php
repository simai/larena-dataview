<?php

declare(strict_types=1);

namespace Larena\Dataview\Exceptions;

use RuntimeException;

/**
 * An owner adapter refused this principal. The owner keeps the reason; hosts map this
 * to a denial without learning anything about the owner's records or policy.
 */
final class RegisteredSourceAccessDenied extends RuntimeException
{
    public function __construct(public readonly string $sourceKey)
    {
        parent::__construct('dataview_source_access_denied');
    }
}
