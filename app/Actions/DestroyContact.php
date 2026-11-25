<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a contact.
 */
class DestroyContact
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->contact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }

        $this->user->authorize('delete', $this->contact);
    }

    private function destroy(): void
    {
        $this->name = $this->contact->name;

        DB::transaction(function (): void {
            $isStillThere = Contact::query()
                ->whereKey($this->contact->id)
                ->lockForUpdate()
                ->value('id') !== null;

            if (! $isStillThere) {
                throw new ModelNotFoundException('This contact is already deleted.');
            }

            $this->contact->delete();

            $this->revokeTheLinksOntoThem();

            new DeactivateReminders(
                user: $this->user,
                subject: $this->contact,
                reason: ReminderInactiveReasonEnum::RecordDeleted,
            )->execute();

            Vault::query()
                ->whereKey($this->contact->vault_id)
                ->where('contact_count', '>', 0)
                ->decrement('contact_count');

            StructureVersion::query()
                ->whereKey($this->contact->structure_version_id)
                ->where('record_count', '>', 0)
                ->decrement('record_count');
        });

        $this->contact->resendTheRecordsNamingIt();
    }

    private function revokeTheLinksOntoThem(): void
    {
        ShareLink::query()
            ->where('shareable_type', $this->contact->getMorphClass())
            ->where('shareable_id', $this->contact->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactDeletion,
            vault: $this->contact->vault,
            parameters: ['name' => $this->name],
            loggable: $this->contact,
        );
    }
}
