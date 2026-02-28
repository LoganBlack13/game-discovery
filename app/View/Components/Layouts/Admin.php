<?php

declare(strict_types=1);

namespace App\View\Components\Layouts;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Admin extends Component
{
    public function __construct(
        public ?string $title = null
    ) {}

    public function render(): View
    {
        return view('layouts.admin');
    }
}
