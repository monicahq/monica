<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Override;

class Shell extends Component
{
    public function __construct(
        public ?string $title = null,
        public bool $wide = false,
    ) {}

    #[Override]
    public function render(): View
    {
        return view('layouts.shell');
    }
}
