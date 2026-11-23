<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Make a partial contact a full one, so they join the main lists and count like everybody else.
 */
class PromotePartialContact
{
    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
    ) {}

    public function execute(): Contact
    {
        $this->validate();

        DB::transaction(function (): void {
            $this->promote();
        });

        $this->log();

        return $this->contact;
    }

    private function validate(): void
    {
        if ($this->contact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }

        if (! $this->contact->is_partial) {
            throw new InvalidArgumentException(__(':name is already a full contact.', ['name' => $this->contact->name]));
        }
    }

    private function promote(): void
    {
        Account::query()->whereKey($this->user->account_id)->lockForUpdate()->first();

        if (! $this->contact->refresh()->is_partial) {
            throw new InvalidArgumentException(__(':name is already a full contact.', ['name' => $this->contact->name]));
        }

        $this->user->authorize('create', Contact::class);

        $this->contact->is_partial = false;
        $this->contact->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactPromoted,
            vault: $this->contact->vault,
            parameters: ['name' => $this->contact->name],
            loggable: $this->contact,
        );
    }
}
