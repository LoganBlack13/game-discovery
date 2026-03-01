<?php

declare(strict_types=1);

namespace App\Contracts;

interface GameDataProviderResolver
{
    public function resolve(string $source): GameDataProvider;
}
