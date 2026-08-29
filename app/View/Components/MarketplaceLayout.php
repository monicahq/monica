<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Override;

class MarketplaceLayout extends Component
{
    public function __construct(
        public ?string $title = null,
    ) {}

    #[Override]
    public function render(): View
    {
        return view('layouts.marketplace');
    }
}
