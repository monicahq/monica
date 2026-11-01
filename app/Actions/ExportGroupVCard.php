<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Http\Dav\GroupVCard;
use App\Http\Dav\VCardMerger;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Hand a group over as one file: the group's own card, then the card of every member a device would
 * be given, so an address book importing it rebuilds the group with its people in it.
 */
class ExportGroupVCard
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
    ) {}

    public function execute(): string
    {
        $this->validate();

        $groupCard = new GroupVCard($this->group);
        $merger = new VCardMerger(reachesSensitiveFields: true);

        $cards = $groupCard->members()
            ->map(fn (Contact $contact): string => $merger->merge($contact))
            ->prepend($groupCard->serialize());

        $this->log();

        return $cards->implode('');
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->group->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This group is in another vault.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupExported,
            vault: $this->vault,
            parameters: ['name' => $this->group->name],
            loggable: $this->group,
        )->onQueue('low');
    }
}
