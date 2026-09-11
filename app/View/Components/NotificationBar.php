<?php

declare(strict_types=1);

namespace App\View\Components;

use App\ViewModels\NotificationBarViewModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;
use Override;

/**
 * The bar is drawn on every signed in screen, from inside `x-bar-links`, which
 * both layouts already include. No screen passes it anything, so unlike the
 * anonymous components around it this one is a class: it is the only way it can
 * be given a view model without a screen having to remember to hand one over.
 */
class NotificationBar extends Component
{
    public function __construct(
        public ?NotificationBarViewModel $viewModel = null,
    ) {
        $user = Auth::user();

        $this->viewModel ??= $user === null ? null : new NotificationBarViewModel(user: $user);
    }

    #[Override]
    public function render(): View
    {
        return view('components.notification-bar');
    }
}
