<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\PreviewListEntry;
use App\Models\User;

/**
 * Take an address off the preview list, which also ends the link it was sent. No account is touched.
 */
class RemovePreviewListEntry
{
    public function __construct(
        private readonly User $administrator,
        private readonly PreviewListEntry $entry,
    ) {}

    public function execute(): void
    {
        $this->entry->delete();

        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::PreviewListEntryRemoved,
            parameters: ['email' => $this->entry->email],
        )->onQueue('low');
    }
}
