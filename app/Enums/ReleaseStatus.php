<?php

declare(strict_types=1);

namespace App\Enums;

enum ReleaseStatus: string
{
    case Announced = 'announced';

    case ComingSoon = 'coming_soon';

    case Released = 'released';

    case Delayed = 'delayed';
}
