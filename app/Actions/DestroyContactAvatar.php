<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Take the picture off a contact, falling them back to their initials.
 */
class DestroyContactAvatar
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
    ) {}

    public function execute(): Contact
    {
        $this->validate();

        $file = $this->contact->avatar;

        if ($file === null) {
            return $this->contact;
        }

        $this->contact->avatar_file_id = null;
        $this->contact->save();

        new DetachFile(user: $this->user, file: $file)->execute();

        $this->log();

        return $this->contact;
    }

    private function validate(): void
    {
        if (! $this->contact->isReachableThrough($this->user, $this->vault)) {
            throw new ModelNotFoundException('This contact is not in that vault.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactAvatarDeletion,
            vault: $this->vault,
            parameters: ['name' => $this->contact->name],
            loggable: $this->contact,
        );
    }
}
