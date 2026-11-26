<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\GroupMember;
use App\Models\LifeEvent;
use App\Models\Relationship;
use App\Models\Reminder;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Bring a contact back from the vault's trash, with the relationships, group memberships, reminders and
 * life events that went there with them. Their reminders run again; the share links stay revoked.
 */
class RestoreContact
{
    use RecountsLifeEvents;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->restore();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->contact->account_id !== $this->user->account_id || $this->contact->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This contact is in another vault.');
        }

        if (! $this->contact->trashed()) {
            throw new ModelNotFoundException('This contact is not deleted.');
        }
    }

    private function restore(): void
    {
        DB::transaction(function (): void {
            $isStillTrashed = Contact::query()
                ->onlyTrashed()
                ->whereKey($this->contact->id)
                ->lockForUpdate()
                ->value('id') !== null;

            if (! $isStillTrashed) {
                throw new ModelNotFoundException('This contact is not deleted.');
            }

            $this->contact->restore();

            Relationship::query()->deletedWithOwner($this->contact)->get()->each(fn (Relationship $relationship) => $relationship->restoreFromTheTrash());
            GroupMember::query()->deletedWithOwner($this->contact)->get()->each(fn (GroupMember $membership) => $membership->restoreFromTheTrash());
            Reminder::query()->deletedWithOwner($this->contact)->get()->each(fn (Reminder $reminder) => $reminder->restoreFromTheTrash());

            LifeEvent::query()->deletedWithOwner($this->contact)->get()->each(function (LifeEvent $lifeEvent): void {
                $lifeEvent->restoreFromTheTrash();

                StructureVersion::query()->whereKey($lifeEvent->structure_version_id)->increment('record_count');
            });

            new ReactivateReminders(
                user: $this->user,
                subject: $this->contact,
                reason: ReminderInactiveReasonEnum::RecordDeleted,
                writesToLog: false,
            )->execute();

            Vault::query()->whereKey($this->contact->vault_id)->increment('contact_count');
            StructureVersion::query()->whereKey($this->contact->structure_version_id)->increment('record_count');

            $this->recountLifeEventsOf([$this->contact->id]);
        });

        $this->contact->resendTheRecordsNamingIt();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactRestoration,
            vault: $this->vault,
            parameters: ['name' => $this->contact->name],
            loggable: $this->contact,
        );
    }
}
