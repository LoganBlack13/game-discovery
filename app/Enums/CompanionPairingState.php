<?php

declare(strict_types=1);

namespace App\Enums;

enum CompanionPairingState: string
{
    /** Waiting for the user to confirm the code on the web. */
    case Pending = 'pending';

    /** Confirmed; the Companion can claim its token once. */
    case Approved = 'approved';

    /** The token was already delivered. */
    case Consumed = 'consumed';

    case Expired = 'expired';
}
