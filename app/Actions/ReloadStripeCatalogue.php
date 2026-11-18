<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Helpers\StripeCatalogue;
use App\Jobs\LogAdministratorAction;
use App\Models\User;

/**
 * Read the Stripe catalogue again rather than wait for the hour to pass, so a price changed in Stripe
 * reaches every pricing screen at once. Says whether the catalogue could be read.
 */
class ReloadStripeCatalogue
{
    public function __construct(
        private readonly ?User $administrator = null,
    ) {}

    public function execute(): bool
    {
        StripeCatalogue::forget();

        $isAvailable = StripeCatalogue::isAvailable();

        $this->log();

        return $isAvailable;
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::StripeCatalogueReloaded,
        )->onQueue('low');
    }
}
