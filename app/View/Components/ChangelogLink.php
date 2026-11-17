<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Helpers\ChangelogAvailability;
use App\ViewModels\ChangelogLinkViewModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;
use Override;

/**
 * The link to What's new is drawn on every signed in screen from inside `x-bar-links`, as the
 * notification bar is, so it is a class for the same reason: it can hold a view model no screen
 * has to hand over.
 */
class ChangelogLink extends Component
{
    public function __construct(
        public ?ChangelogLinkViewModel $viewModel = null,
    ) {
        $user = Auth::user();

        $this->viewModel ??= $user === null || ! ChangelogAvailability::isEnabled() ? null : new ChangelogLinkViewModel(user: $user);
    }

    #[Override]
    public function render(): View
    {
        return view('components.changelog-link');
    }
}
