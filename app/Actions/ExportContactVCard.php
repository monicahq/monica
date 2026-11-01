<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Http\Dav\VCardMerger;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Hand a contact over as the card a synchronising device would be given, for somebody to keep as a
 * file. The card is built by the same writer, so the file and the device never disagree.
 */
class ExportContactVCard
{
    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
        private readonly bool $reachesSensitiveFields = false,
    ) {}

    public function execute(): string
    {
        $this->validate();

        $card = new VCardMerger($this->reachesSensitiveFields)->merge($this->contact);

        $this->log();

        return $card;
    }

    private function validate(): void
    {
        if ($this->contact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactExported,
            vault: $this->contact->vault,
            parameters: ['name' => $this->contact->name],
            loggable: $this->contact,
        );
    }
}
