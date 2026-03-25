<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('news:enrich')->daily()->at('02:00')->withoutOverlapping();
Schedule::command('game-requests:process')->daily()->at('03:00')->withoutOverlapping();
