<?php

declare(strict_types=1);

namespace App\Enums;

enum MappingConfidence: string
{
    /** Validated by the user or resolved from a launcher identifier. */
    case High = 'high';

    /** Title match only: always needs a user validation before being applied. */
    case Medium = 'medium';
}
