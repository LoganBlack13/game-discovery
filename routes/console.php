<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('news:enrich')->daily()->at('02:00');
