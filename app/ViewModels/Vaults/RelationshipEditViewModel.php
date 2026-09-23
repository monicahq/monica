<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\RelationshipStatusEnum;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Str;

class RelationshipEditViewModel
{
    public const string OTHER_HAS_A_RECORD = 'record';

    public const string OTHER_IS_NAMED = 'named';

    public const string OTHER_IS_NAMELESS = 'nameless';

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly Relationship $relationship,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function title(): string
    {
        return __('Edit this relationship');
    }

    public function description(): string
    {
        return __('Between :one and :two.', [
            'one' => $this->contact->name,
            'two' => $this->otherName($this->otherRole()),
        ]);
    }

    public function action(): string
    {
        return route('contacts.relationships.update', [
            'vault' => $this->vault,
            'contact' => $this->contact,
            'relationship' => $this->relationship,
        ]);
    }

    public function method(): string
    {
        return 'put';
    }

    public function backUrl(): string
    {
        return route('contacts.show', [
            'vault' => $this->vault,
            'contact' => $this->contact,
            'page' => ContactViewModel::PAGE_RELATIONSHIPS,
        ]);
    }

    /**
     * Every nature on offer, each written as the sentences it can produce between the two people.
     * The value names the nature and the role the page's contact holds, `a` carrying `role_a`.
     *
     * @return array<int, array{label: string, options: array<int, array{value: string, label: string, otherRole: string}>}>
     */
    public function sentenceGroups(): array
    {
        return collect($this->choices()->groups())
            ->map(fn (array $group): array => [
                'label' => $group['label'],
                'options' => collect($group['types'])
                    ->flatMap(function (array $type): array {
                        $options = [[
                            'value' => "{$type['value']}-a",
                            'label' => $this->sentence(
                                one: $this->contact->name,
                                two: $this->otherName($type['roleB']),
                                role: $type['roleA'],
                            ),
                            'otherRole' => $type['roleB'],
                        ]];

                        if ($type['isSymmetric']) {
                            return $options;
                        }

                        $options[] = [
                            'value' => "{$type['value']}-b",
                            'label' => $this->sentence(
                                one: Str::ucfirst($this->otherName($type['roleA'])),
                                two: $this->contact->name,
                                role: $type['roleA'],
                            ),
                            'otherRole' => $type['roleA'],
                        ];

                        return $options;
                    })
                    ->all(),
            ])
            ->all();
    }

    public function otherSide(): Contact
    {
        return $this->relationship->contact_a_id === $this->contact->id
            ? $this->relationship->contactB
            : $this->relationship->contactA;
    }

    public function otherState(): string
    {
        $other = $this->otherSide();

        if (! $other->is_partial) {
            return self::OTHER_HAS_A_RECORD;
        }

        return $other->isNameless() ? self::OTHER_IS_NAMELESS : self::OTHER_IS_NAMED;
    }

    /**
     * The role the other person holds in the sentence chosen when the screen opens.
     */
    public function otherRole(): string
    {
        $type = $this->relationship->relationshipType;

        return $this->relationship->contact_a_id === $this->contact->id ? $type->role_b : $type->role_a;
    }

    public function renameLine(): string
    {
        return __(':name has no contact record, so their name is changed here.', [
            'name' => $this->otherSide()->first_name,
        ]);
    }

    /** @return array{relationship: string, hasEnded: bool, startedOn: string, endedOn: string, mode: string, firstName: string, lastName: string, createsRecord: bool} */
    public function values(): array
    {
        $other = $this->otherSide();

        return [
            'relationship' => (string) old('relationship', $this->storedSentence()),
            'hasEnded' => session()->hasOldInput()
                ? (bool) old('has_ended')
                : $this->relationship->status === RelationshipStatusEnum::Ended,
            'startedOn' => (string) old('started_on', $this->relationship->started_on?->format('Y-m-d') ?? ''),
            'endedOn' => (string) old('ended_on', $this->relationship->ended_on?->format('Y-m-d') ?? ''),
            'mode' => (string) old('mode', $this->otherState() === self::OTHER_IS_NAMED ? 'known' : 'unknown'),
            'firstName' => (string) old('first_name', $other->first_name ?? ''),
            'lastName' => (string) old('last_name', $other->last_name ?? ''),
            'createsRecord' => (bool) old('creates_record'),
        ];
    }

    private function storedSentence(): string
    {
        $type = $this->relationship->relationshipType;
        $side = $type->is_symmetric || $this->relationship->contact_a_id === $this->contact->id ? 'a' : 'b';

        return "{$type->id}-{$side}";
    }

    private function sentence(string $one, string $two, string $role): string
    {
        return __(':one is :two’s :role.', [
            'one' => $one,
            'two' => $two,
            'role' => mb_strtolower($role),
        ]);
    }

    private function otherName(string $role): string
    {
        $other = $this->otherSide();

        if ($other->isNameless()) {
            return __('an unnamed :role', ['role' => mb_strtolower($role)]);
        }

        return $other->name;
    }

    /**
     * The nature the link carries stays on the list even when the account has withdrawn it, so
     * saving without changing it never fails.
     */
    private function choices(): RelationshipTypeChoices
    {
        return once(fn (): RelationshipTypeChoices => new RelationshipTypeChoices(
            accountId: $this->user->account_id,
            keeping: $this->relationship->relationshipType,
        ));
    }
}
