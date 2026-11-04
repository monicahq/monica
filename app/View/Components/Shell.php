<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Vault;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;
use Illuminate\View\View;
use Override;

class Shell extends Component
{
    /** The vault the screen is in, whose search the top bar offers; null outside a vault and on the search page itself. */
    public ?Vault $searchVault = null;

    /** Billing and the screens reached from it already make the offer, so the banner stays off there. */
    public bool $offersFullAccess = false;

    public function __construct(
        public ?string $title = null,
        public bool $wide = false,
    ) {
        $vault = Route::current()?->parameter('vault');

        if ($vault instanceof Vault && ! Route::is('search.index')) {
            $this->searchVault = $vault;
        }

        if (! Route::is('settings.billing*', 'settings.checkout*', 'settings.subscription*', 'settings.invoices.*')) {
            $this->offersFullAccess = auth()->user()?->isOfferedFullAccess() ?? false;
        }
    }

    #[Override]
    public function render(): View
    {
        return view('layouts.shell');
    }
}
