<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class VaultLayout extends Component
{
    public function __construct(
        public ?string $title = null,
    ) {}

    public function render(): View
    {
        return view('layouts.vault');
    }
}
