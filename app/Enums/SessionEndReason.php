<?php

declare(strict_types=1);

namespace App\Enums;

enum SessionEndReason: string
{
    /** The game was closed. */
    case Closed = 'closed';

    /** The Companion stopped unexpectedly and closed the session at its next start. */
    case Recovered = 'recovered';

    /** The computer slept for too long. */
    case Sleep = 'sleep';

    /** Closed by the server after 15 minutes without heartbeat. */
    case Timeout = 'timeout';

    /** Closed by the server because the same device started a new session for the same game. */
    case Superseded = 'superseded';

    /**
     * Reasons the Companion may send; the others are set by the server.
     *
     * @return list<self>
     */
    public static function reportedByDevice(): array
    {
        return [self::Closed, self::Recovered, self::Sleep];
    }

    /**
     * A session closed by the server can still be completed by its device, which knows the real end.
     */
    public function isServerSide(): bool
    {
        return in_array($this, [self::Timeout, self::Superseded], true);
    }
}
