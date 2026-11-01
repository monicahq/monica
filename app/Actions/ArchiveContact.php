<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Take a contact out of the main lists without deleting them.
 */
class ArchiveContact
{
    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
    ) {}

    public function execute(): Contact
    {
        $this->validate();
        $this->archive();
        $this->log();

        return $this->contact;
    }

    private function validate(): void
    {
        if ($this->contact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }
    }

    private function archive(): void
    {
        $this->contact->is_archived = true;
        $this->contact->save();

        new DeactivateReminders(
            user: $this->user,
            subject: $this->contact,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactArchived,
            vault: $this->contact->vault,
            parameters: ['name' => $this->contact->name],
            loggable: $this->contact,
        );
    }
}
