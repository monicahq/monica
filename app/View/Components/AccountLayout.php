<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Override;

class AccountLayout extends Component
{
    public bool $offersFullAccess;

    public function __construct(
        public ?string $title = null,
    ) {
        $this->offersFullAccess = auth()->user()?->isOfferedFullAccess() ?? false;
    }

    #[Override]
    public function render(): View
    {
        return view('layouts.account');
    }
}
