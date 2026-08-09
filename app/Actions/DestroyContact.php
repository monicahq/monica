<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteTheMeContact;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a contact. The deletion is soft, and {@see RestoreContact} brings them
 * back with everything they were part of.
 *
 * Nothing the contact took part in is destroyed. Relationships, activities and
 * files outlive them, because they describe things that happened rather than
 * the row itself.
 *
 * The contact representing the user never goes. Relationships and activities
 * are written from its point of view, and a vault that lost it would be talking
 * about nobody.
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

        if ($this->contact->is_me) {
            throw new CannotDeleteTheMeContact('The contact representing you cannot be deleted.');
        }
    }

    private function destroy(): void
    {
        $this->name = $this->contact->name;

        DB::transaction(function (): void {
            $this->contact->delete();

            Vault::query()
                ->whereKey($this->contact->vault_id)
                ->where('contact_count', '>', 0)
                ->decrement('contact_count');

            TemplateVersion::query()
                ->whereKey($this->contact->template_version_id)
                ->where('record_count', '>', 0)
                ->decrement('record_count');
        });
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
        )->onQueue('low');
    }
}
