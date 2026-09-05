<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Bring a deleted contact back, on the version they were rendering from before they went.
 */
class RestoreContact
{
    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
    ) {}

    public function execute(): Contact
    {
        $this->validate();
        $this->restore();
        $this->log();

        return $this->contact;
    }

    private function validate(): void
    {
        if ($this->contact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }
    }

    private function restore(): void
    {
        if (! $this->contact->trashed()) {
            return;
        }

        DB::transaction(function (): void {
            $this->contact->restore();

            $this->contact->vault->increment('contact_count');
            $this->contact->templateVersion->increment('record_count');

            new ReactivateReminders(
                user: $this->user,
                subject: $this->contact,
                reason: ReminderInactiveReasonEnum::RecordDeleted,
            )->execute();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactRestoration,
            vault: $this->contact->vault,
            parameters: ['name' => $this->contact->name],
            loggable: $this->contact,
        )->onQueue('low');
    }
}
