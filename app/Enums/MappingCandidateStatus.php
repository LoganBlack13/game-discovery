<?php

declare(strict_types=1);

namespace App\Enums;

enum MappingCandidateStatus: string
{
    /** Waiting for an association, validated by the user unless resolved from a launcher identifier. */
    case Pending = 'pending';

    case Mapped = 'mapped';

    /** The user said it is not a game: it is never proposed again. */
    case Ignored = 'ignored';
}
