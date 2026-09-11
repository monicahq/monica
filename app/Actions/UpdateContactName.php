<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NameOrderEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\DetectDuplicateContacts;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change how somebody is called.
 */
class UpdateContactName
{
    /** @var array<string, array<string, string|null>> */
    private array $changes = [];

    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
        private ?string $prefix = null,
        private ?string $firstName = null,
        private ?string $middleName = null,
        private ?string $lastName = null,
        private ?string $suffix = null,
        private ?string $maidenName = null,
        private ?string $nickname = null,
        private readonly ?NameOrderEnum $nameOrder = null,
    ) {}

    public function execute(): Contact
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();
        $this->lookForDuplicates();

        return $this->contact;
    }

    private function sanitize(): void
    {
        $this->prefix = TextSanitizer::nullablePlainText($this->prefix);
        $this->firstName = TextSanitizer::nullablePlainText($this->firstName);
        $this->middleName = TextSanitizer::nullablePlainText($this->middleName);
        $this->lastName = TextSanitizer::nullablePlainText($this->lastName);
        $this->suffix = TextSanitizer::nullablePlainText($this->suffix);
        $this->maidenName = TextSanitizer::nullablePlainText($this->maidenName);
        $this->nickname = TextSanitizer::nullablePlainText($this->nickname);
    }

    private function validate(): void
    {
        if ($this->contact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }
    }

    private function update(): void
    {
        $before = [
            'prefix' => $this->contact->prefix,
            'first_name' => $this->contact->first_name,
            'middle_name' => $this->contact->middle_name,
            'last_name' => $this->contact->last_name,
            'suffix' => $this->contact->suffix,
            'maiden_name' => $this->contact->maiden_name,
            'nickname' => $this->contact->nickname,
        ];

        $this->contact->prefix = $this->prefix;
        $this->contact->first_name = $this->firstName;
        $this->contact->middle_name = $this->middleName;
        $this->contact->last_name = $this->lastName;
        $this->contact->suffix = $this->suffix;
        $this->contact->maiden_name = $this->maidenName;
        $this->contact->nickname = $this->nickname;
        $this->contact->name_order = $this->nameOrder;
        $this->contact->save();

        foreach ($before as $field => $value) {
            if ($value !== $this->contact->$field) {
                $this->changes[$field] = ['before' => $value, 'after' => $this->contact->$field];
            }
        }
    }

    private function lookForDuplicates(): void
    {
        DetectDuplicateContacts::dispatch($this->contact)->onQueue('low');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactNameUpdate,
            vault: $this->contact->vault,
            parameters: ['name' => $this->contact->name],
            loggable: $this->contact,
            changes: $this->changes,
        )->onQueue('low');
    }
}
