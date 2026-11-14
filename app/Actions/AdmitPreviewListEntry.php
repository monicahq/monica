<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Exceptions\AddressAlreadyHoldsAnAccount;
use App\Jobs\LogAdministratorAction;
use App\Models\PreviewListEntry;
use App\Models\User;
use App\Notifications\PreviewListEntryAdmitted;
use Illuminate\Support\Str;

/**
 * Let one address of the preview list create an account while registration is closed, by emailing
 * it a link that replaces any link sent before.
 */
class AdmitPreviewListEntry
{
    private string $token;

    public function __construct(
        private readonly User $administrator,
        private readonly PreviewListEntry $entry,
    ) {}

    public function execute(): PreviewListEntry
    {
        $this->validate();
        $this->admit();
        $this->send();
        $this->log();

        return $this->entry;
    }

    private function validate(): void
    {
        if ($this->entry->registered_at !== null || User::query()->where('email', $this->entry->email)->exists()) {
            throw new AddressAlreadyHoldsAnAccount(__(':email already holds an account on this instance.', ['email' => $this->entry->email]));
        }
    }

    private function admit(): void
    {
        $this->token = Str::random(64);

        $this->entry->update([
            'token' => hash('sha256', $this->token),
            'token_expires_at' => now()->addDays(PreviewListEntry::ADMISSION_DAYS),
            'admitted_at' => now(),
        ]);
    }

    private function send(): void
    {
        $this->entry->notify(new PreviewListEntryAdmitted(token: $this->token));
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::PreviewListEntryAdmitted,
            parameters: ['email' => $this->entry->email],
            subject: $this->entry,
        )->onQueue('low');
    }
}
