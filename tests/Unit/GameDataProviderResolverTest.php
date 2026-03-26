<?php

declare(strict_types=1);

use App\Contracts\GameDataProvider;
use App\Services\GameDataProviderResolver;
use App\Services\IgdbGameDataProvider;
use App\Services\RawgGameDataProvider;

uses()->group('unit');

test('resolve returns RawgGameDataProvider for rawg source', function (): void {
    $resolver = resolve(GameDataProviderResolver::class);

    $provider = $resolver->resolve('rawg');

    expect($provider)->toBeInstanceOf(RawgGameDataProvider::class)
        ->toBeInstanceOf(GameDataProvider::class);
});

test('resolve returns IgdbGameDataProvider for igdb source', function (): void {
    $resolver = resolve(GameDataProviderResolver::class);

    $provider = $resolver->resolve('igdb');

    expect($provider)->toBeInstanceOf(IgdbGameDataProvider::class)
        ->toBeInstanceOf(GameDataProvider::class);
});

test('resolve is case-insensitive', function (): void {
    $resolver = resolve(GameDataProviderResolver::class);

    $provider = $resolver->resolve('RAWG');

    expect($provider)->toBeInstanceOf(RawgGameDataProvider::class);
});

test('resolve throws InvalidArgumentException for unknown source', function (): void {
    $resolver = resolve(GameDataProviderResolver::class);

    expect(fn () => $resolver->resolve('steam'))
        ->toThrow(InvalidArgumentException::class);
});
