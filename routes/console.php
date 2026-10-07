<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('news:enrich')->daily()->at('02:00')->withoutOverlapping();
Schedule::command('game-requests:process')->daily()->at('03:00')->withoutOverlapping();
Schedule::command('games:sync-credits')->daily()->at('04:00')->withoutOverlapping();
Schedule::command('companion:purge-pairings')->daily()->at('04:30');
Schedule::command('companion:close-stale-sessions')->everyTenMinutes()->withoutOverlapping();
