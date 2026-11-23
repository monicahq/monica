<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Make a full contact partial, taking them off the main lists and out of the plan's count while keeping everything about them.
 */
class DemoteFullContact
{
    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
    ) {}

    public function execute(): Contact
    {
        $this->validate();
        $this->demote();
        $this->log();

        return $this->contact;
    }

    private function validate(): void
    {
        if ($this->contact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }

        $this->user->authorize('demote', $this->contact);

        if ($this->contact->is_partial) {
            throw new InvalidArgumentException(__(':name is already a partial contact.', ['name' => $this->contact->name]));
        }
    }

    private function demote(): void
    {
        $this->contact->is_partial = true;
        $this->contact->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactDemoted,
            vault: $this->contact->vault,
            parameters: ['name' => $this->contact->name],
            loggable: $this->contact,
        );
    }
}
