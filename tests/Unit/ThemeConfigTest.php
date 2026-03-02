<?php

declare(strict_types=1);

test('theme config defines defaults and themes', function (): void {
    $config = config('themes');

    expect($config)
        ->toHaveKeys(['themes', 'default_dark', 'default_light']);

    $themes = collect($config['themes']);

    expect($themes)->not->toBeEmpty();

    $slugs = $themes->pluck('slug');

    expect($slugs->all())
        ->toContain('arcade-night', 'daylight-pastel');

    expect($config['default_dark'])
        ->toBe('arcade-night');

    expect($config['default_light'])
        ->toBe('daylight-pastel');

    $lightSlugs = $themes->where('light', true)->pluck('slug');

    expect($lightSlugs->all())
        ->toContain($config['default_light']);
});
