<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactNameKindEnum;
use App\Enums\NameOrderEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\DetectDuplicateContacts;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change how somebody is called: the parts of the name they are shown under, their nickname and
 * their maiden name. Every other way their name is written is left alone.
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

        [$nameRepresentations, $display] = $this->nameRepresentationsWithTheNewParts();

        new UpdateContactNameRepresentations(
            user: $this->user,
            contact: $this->contact,
            nameRepresentations: $nameRepresentations,
            display: $display,
            nickname: $this->nickname,
            maidenName: $this->maidenName,
            logsAndLooksForDuplicates: false,
        )->execute();

        foreach ($before as $field => $value) {
            if ($value !== $this->contact->$field) {
                $this->changes[$field] = ['before' => $value, 'after' => $this->contact->$field];
            }
        }
    }

    /**
     * The contact's representations as they stand, the displayed one carrying the new parts. Parts
     * equal to the ones it holds leave it untouched, its full name included, so a caller changing
     * only the nickname renames nobody. New parts replace its full name, which no longer matches
     * them. Parts that are all empty take it away, and the next one is shown instead; a contact
     * with none gets a written one.
     *
     * @return array{0: list<array<string, mixed>>, 1: int|null}
     */
    private function nameRepresentationsWithTheNewParts(): array
    {
        $parts = [
            'formatted_name' => null,
            'prefix' => $this->prefix,
            'given_name' => $this->firstName,
            'additional_name' => $this->middleName,
            'family_name' => $this->lastName,
            'suffix' => $this->suffix,
            'name_order' => $this->nameOrder,
        ];
        $hasParts = array_filter([$this->prefix, $this->firstName, $this->middleName, $this->lastName, $this->suffix]) !== [];

        $rows = [];
        $display = null;

        foreach ($this->contact->nameRepresentations()->get() as $representation) {
            $row = ['id' => $representation->uuid, ...$representation->toComparable(), 'vcard_alt_id' => $representation->vcard_alt_id, 'vcard_property_id' => $representation->vcard_property_id];

            if ($representation->id !== $this->contact->display_name_representation_id) {
                $rows[] = $row;

                continue;
            }

            if ($representation->prefix === $this->prefix
                && $representation->given_name === $this->firstName
                && $representation->additional_name === $this->middleName
                && $representation->family_name === $this->lastName
                && $representation->suffix === $this->suffix
                && $representation->name_order === $this->nameOrder) {
                $display = count($rows);
                $rows[] = $row;

                continue;
            }

            if ($hasParts) {
                $display = count($rows);
                $rows[] = [...$row, ...$parts];
            }
        }

        if ($display === null && $hasParts) {
            $display = count($rows);
            $rows[] = ['kind' => ContactNameKindEnum::Written, ...$parts];
        }

        if ($display === null && $rows !== []) {
            $display = 0;
        }

        return [$rows, $display];
    }

    private function lookForDuplicates(): void
    {
        if ($this->contact->vault->isBeingImportedInto()) {
            return;
        }

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
        );
    }
}
