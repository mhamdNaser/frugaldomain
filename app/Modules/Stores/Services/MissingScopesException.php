<?php

namespace App\Modules\Stores\Services;

use RuntimeException;

/** The token is valid but lacks scopes the import cannot work without. */
class MissingScopesException extends RuntimeException
{
    public function __construct(public readonly array $check)
    {
        parent::__construct('The access token is missing permissions the import needs.');
    }
}
