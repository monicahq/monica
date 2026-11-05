<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Override;

class GuestLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public bool $error = false,
    ) {}

    #[Override]
    public function render(): View
    {
        return view('layouts.guest');
    }
}
