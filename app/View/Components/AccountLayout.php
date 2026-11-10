<?php

declare(strict_types=1);

namespace App\View\Components;

use App\ViewModels\FullAccessOfferViewModel;
use Illuminate\View\Component;
use Illuminate\View\View;
use Override;

class AccountLayout extends Component
{
    public ?FullAccessOfferViewModel $fullAccessOffer = null;

    public function __construct(
        public ?string $title = null,
    ) {
        $user = auth()->user();

        if ($user?->isOfferedFullAccess()) {
            $this->fullAccessOffer = new FullAccessOfferViewModel(user: $user);
        }
    }

    #[Override]
    public function render(): View
    {
        return view('layouts.account');
    }
}
