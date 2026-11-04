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
use App\Models\ContactNameRepresentation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Replace every way a contact's name is written, and which one it is shown under, in one go. A
 * representation left out of the list is deleted, so the list sent is the list kept.
 */
class UpdateContactNameRepresentations
{
    public const string LANGUAGE_PATTERN = '/^[A-Za-z]{2,3}(-[A-Za-z0-9]{1,8})*$/';

    public const string SCRIPT_PATTERN = '/^[A-Z][a-z]{3}$/';

    public const array TEXT_KEYS = ['formatted_name', 'prefix', 'given_name', 'additional_name', 'family_name', 'suffix'];

    public const int MAXIMUM = 20;

    /** @var array<string, array<string, string|null>> */
    private array $changes = [];

    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
        private array $nameRepresentations,
        private readonly ?int $display,
        private ?string $nickname,
        private ?string $maidenName,
        private readonly bool $logsAndLooksForDuplicates = true,
    ) {}

    /**
     * @param  list<string>  $ownIds
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $key, array $ownIds, bool $requiresOne = false): array
    {
        $texts = collect(self::TEXT_KEYS)->map(fn (string $text): string => "{$key}.*.{$text}");

        $rules = [
            $key => [$requiresOne ? 'required' : 'present', 'array', $requiresOne ? 'min:1' : 'min:0', 'max:'.self::MAXIMUM],
            "{$key}.*" => ['array'],
            "{$key}.*.id" => ['nullable', 'string', 'distinct', Rule::in($ownIds)],
            "{$key}.*.kind" => ['required', Rule::enum(ContactNameKindEnum::class)],
            "{$key}.*.language" => ['nullable', 'string', 'max:35', 'regex:'.self::LANGUAGE_PATTERN],
            "{$key}.*.script" => ['nullable', 'string', 'size:4', 'regex:'.self::SCRIPT_PATTERN],
            "{$key}.*.phonetic_system" => ['nullable', 'string', 'max:32', "required_if:{$key}.*.kind,phonetic", "prohibited_unless:{$key}.*.kind,phonetic"],
            "{$key}.*.name_order" => ['nullable', Rule::enum(NameOrderEnum::class)],
            "{$key}.*.prefix" => ['nullable', 'string', 'max:50'],
            "{$key}.*.suffix" => ['nullable', 'string', 'max:50'],
            'display' => ["required_with:{$key}", 'nullable', 'integer', 'min:0'],
        ];

        foreach (['formatted_name', 'given_name', 'additional_name', 'family_name'] as $text) {
            $rules["{$key}.*.{$text}"] = ['nullable', 'string', 'max:255'];
        }

        $rules["{$key}.*.formatted_name"][] = 'required_without_all:'.$texts->reject(fn (string $field): bool => $field === "{$key}.*.formatted_name")->implode(',');
        $rules["{$key}.*.formatted_name"][] = "prohibited_if:{$key}.*.kind,phonetic";

        return $rules;
    }

    /**
     * The words a reader sees in place of the keys of {@see rules()}.
     *
     * @return array<string, string>
     */
    public static function attributes(string $key): array
    {
        return [
            $key => __('names'),
            "{$key}.*.id" => __('name'),
            "{$key}.*.kind" => __('type'),
            "{$key}.*.language" => __('language'),
            "{$key}.*.script" => __('writing system'),
            "{$key}.*.phonetic_system" => __('pronunciation system'),
            "{$key}.*.name_order" => __('reading order'),
            "{$key}.*.formatted_name" => __('full name'),
            "{$key}.*.prefix" => __('prefix'),
            "{$key}.*.given_name" => __('first name'),
            "{$key}.*.additional_name" => __('middle name'),
            "{$key}.*.family_name" => __('last name'),
            "{$key}.*.suffix" => __('suffix'),
            'display' => __('display name'),
        ];
    }

    public function execute(): Contact
    {
        $this->sanitize();
        $this->validate();
        $this->update();

        if ($this->logsAndLooksForDuplicates) {
            $this->log();
            $this->lookForDuplicates();
        }

        return $this->contact;
    }

    private function sanitize(): void
    {
        $this->nickname = TextSanitizer::nullablePlainText($this->nickname);
        $this->maidenName = TextSanitizer::nullablePlainText($this->maidenName);

        $this->nameRepresentations = array_map(function (array $row): array {
            foreach ([...self::TEXT_KEYS, 'language', 'script', 'phonetic_system'] as $key) {
                $value = $row[$key] ?? null;
                $row[$key] = is_string($value) ? TextSanitizer::nullablePlainText($value) : null;
            }

            foreach (['vcard_alt_id', 'vcard_property_id'] as $key) {
                if (array_key_exists($key, $row)) {
                    $row[$key] = is_string($row[$key]) ? TextSanitizer::nullablePlainText($row[$key]) : null;
                }
            }

            return $row;
        }, array_values($this->nameRepresentations));
    }

    private function validate(): void
    {
        if ($this->contact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }

        if (count($this->nameRepresentations) > self::MAXIMUM) {
            throw new InvalidArgumentException(__('A contact holds at most twenty ways of writing its name.'));
        }

        if ($this->nameRepresentations === []) {
            return;
        }

        if ($this->display === null || ! array_key_exists($this->display, $this->nameRepresentations)) {
            throw new InvalidArgumentException(__('The name to show has to be one of the names sent.'));
        }

        $ownIds = $this->contact->nameRepresentations()->pluck('uuid')->all();

        foreach ($this->nameRepresentations as $row) {
            $this->validateRow($row, $ownIds);
        }

        if ($this->kindOf($this->nameRepresentations[$this->display]) === ContactNameKindEnum::Phonetic) {
            throw new InvalidArgumentException(__('A pronunciation cannot be the name the contact is shown under.'));
        }

        if (count(array_unique(array_filter(array_column($this->nameRepresentations, 'id')))) !== count(array_filter(array_column($this->nameRepresentations, 'id')))) {
            throw new InvalidArgumentException(__('Each name can be sent only once.'));
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $ownIds
     */
    private function validateRow(array $row, array $ownIds): void
    {
        if (isset($row['id']) && ! in_array($row['id'], $ownIds, true)) {
            throw new InvalidArgumentException(__('This name is not one of this contact’s.'));
        }

        $kind = $this->kindOf($row);

        if ($kind === null) {
            throw new InvalidArgumentException(__('A name is written, transliterated or phonetic.'));
        }

        $holdsText = array_filter(array_map(fn (string $key): ?string => $row[$key], self::TEXT_KEYS)) !== [];

        if (! $holdsText) {
            throw new InvalidArgumentException(__('A name needs a full name or at least one part.'));
        }

        if ($kind === ContactNameKindEnum::Phonetic && $row['formatted_name'] !== null) {
            throw new InvalidArgumentException(__('A pronunciation is written in its parts, without a full name.'));
        }

        if ($kind === ContactNameKindEnum::Phonetic && $row['phonetic_system'] === null) {
            throw new InvalidArgumentException(__('A pronunciation needs its pronunciation system.'));
        }

        if ($kind !== ContactNameKindEnum::Phonetic && $row['phonetic_system'] !== null) {
            throw new InvalidArgumentException(__('Only a pronunciation has a pronunciation system.'));
        }

        if ($row['language'] !== null && preg_match(self::LANGUAGE_PATTERN, $row['language']) !== 1) {
            throw new InvalidArgumentException(__('This language tag is not valid.'));
        }

        if ($row['script'] !== null && preg_match(self::SCRIPT_PATTERN, $row['script']) !== 1) {
            throw new InvalidArgumentException(__('This script code is not valid.'));
        }
    }

    /** @param array<string, mixed> $row */
    private function kindOf(array $row): ?ContactNameKindEnum
    {
        return $row['kind'] instanceof ContactNameKindEnum ? $row['kind'] : ContactNameKindEnum::tryFrom((string) $row['kind']);
    }

    private function update(): void
    {
        $before = $this->contact->nameRepresentations()->get()->map(fn (ContactNameRepresentation $representation): string => $representation->displayText())->implode(', ');
        $beforeNickname = $this->contact->nickname;
        $beforeMaidenName = $this->contact->maiden_name;

        DB::transaction(function (): void {
            $existing = $this->contact->nameRepresentations()->get()->keyBy('uuid');
            $kept = [];
            $displayed = null;

            foreach ($this->nameRepresentations as $index => $row) {
                $representation = isset($row['id']) ? $existing[$row['id']] : new ContactNameRepresentation(['contact_id' => $this->contact->id]);
                $representation->fill($this->attributesOf($row, $index + 1, $representation));
                $representation->setRelation('contact', $this->contact);
                $representation->save();

                $kept[] = $representation->id;

                if ($index === $this->display) {
                    $displayed = $representation;
                }
            }

            $this->contact->display_name_representation_id = $displayed?->id;
            $this->contact->nickname = $this->nickname;
            $this->contact->maiden_name = $this->maidenName;
            $this->contact->save();

            $existing->reject(fn (ContactNameRepresentation $representation): bool => in_array($representation->id, $kept, true))
                ->each(fn (ContactNameRepresentation $representation): ?bool => $representation->setRelation('contact', $this->contact)->delete());
        });

        $this->contact->unsetRelation('nameRepresentations');
        $this->contact->unsetRelation('displayNameRepresentation');

        $after = $this->contact->nameRepresentations()->get()->map(fn (ContactNameRepresentation $representation): string => $representation->displayText())->implode(', ');

        foreach (['names' => [$before, $after], 'nickname' => [$beforeNickname, $this->contact->nickname], 'maiden_name' => [$beforeMaidenName, $this->contact->maiden_name]] as $field => [$was, $now]) {
            if ($was !== $now) {
                $this->changes[$field] = ['before' => $was, 'after' => $now];
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function attributesOf(array $row, int $position, ContactNameRepresentation $representation): array
    {
        $kind = $row['kind'] instanceof ContactNameKindEnum ? $row['kind'] : ContactNameKindEnum::from((string) $row['kind']);
        $nameOrder = $row['name_order'] ?? null;

        return [
            'kind' => $kind,
            'language_tag' => $row['language'],
            'script_code' => $row['script'],
            'phonetic_system' => $row['phonetic_system'],
            'formatted_name' => $row['formatted_name'],
            'prefix' => $row['prefix'],
            'given_name' => $row['given_name'],
            'additional_name' => $row['additional_name'],
            'family_name' => $row['family_name'],
            'suffix' => $row['suffix'],
            'name_order' => $nameOrder instanceof NameOrderEnum ? $nameOrder : NameOrderEnum::tryFrom((string) $nameOrder),
            'vcard_alt_id' => array_key_exists('vcard_alt_id', $row) ? $row['vcard_alt_id'] : $representation->vcard_alt_id,
            'vcard_property_id' => array_key_exists('vcard_property_id', $row) ? $row['vcard_property_id'] : $representation->vcard_property_id,
            'position' => $position,
        ];
    }

    private function lookForDuplicates(): void
    {
        if ($this->contact->vault->isBeingImportedInto()) {
            return;
        }

        DetectDuplicateContacts::dispatch($this->contact)->onQueue('low')->afterCommit();
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
        )->afterCommit();
    }
}
