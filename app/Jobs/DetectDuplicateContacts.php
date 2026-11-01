<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\LocaleCollation;
use App\Models\Contact;
use App\Models\ContactDuplicateCandidate;
use App\Models\ContactNameRepresentation;
use App\Models\FieldValue;
use App\Models\Vault;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Work out which contacts of a vault look like the same person. It proposes, and a person decides.
 *
 * It is on a queue because it must never be on the path of a save. Given a vault rather than a
 * contact it looks at the whole vault, which is the only thing that notices a template that has just
 * gained an identifying field. Given nothing, which is what runs nightly, it queues one pass per
 * vault, so no single job grows with the instance and one vault failing leaves the others done.
 */
class DetectDuplicateContacts implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** How long the lock on a subject may outlive a worker that died holding it. */
    public int $uniqueFor = 3600;

    /** How alike two folded given names have to be, as a percentage. The block already matches the family name. */
    private const int NAME_THRESHOLD = 60;

    private const int NAME_SCORE_FLOOR = 40;

    private const int VALUE_SCORE = 95;

    /** A value held by more contacts than this, such as a switchboard number, says nothing about who is a duplicate. */
    private const int MOST_CONTACTS_SHARING_A_VALUE = 20;

    public function __construct(private readonly Contact|Vault|null $subject = null) {}

    public function uniqueId(): string
    {
        if ($this->subject === null) {
            return 'everywhere';
        }

        return $this->subject->getMorphClass().':'.$this->subject->id;
    }

    public function handle(): void
    {
        if ($this->subject === null) {
            $this->everywhere();

            return;
        }

        $found = $this->subject instanceof Vault
            ? $this->acrossTheVault($this->subject)
            : $this->around($this->subject);

        $this->write($found);
    }

    private function everywhere(): void
    {
        $vaults = 0;

        Vault::query()->select('id')->chunkById(500, function ($chunk) use (&$vaults): void {
            foreach ($chunk as $vault) {
                $vaults++;
                self::dispatch($vault)->onQueue('low');
            }
        });

        Log::info('Queued a search for duplicate contacts in every vault.', ['vaults' => $vaults]);
    }

    /**
     * @return array<string, array{vault_id: int, account_id: int, contact_id: int, other_contact_id: int, score: int, reasons: list<array<string, mixed>>}>
     */
    private function acrossTheVault(Vault $vault): array
    {
        $found = [];

        foreach ($this->blocks($vault) as $block) {
            $this->compareWithin($block, $found);
        }

        foreach ($this->sharedValues($vault) as $sharing) {
            $this->recordSharedValue($sharing, $found);
        }

        return $found;
    }

    /**
     * @return array<string, array{vault_id: int, account_id: int, contact_id: int, other_contact_id: int, score: int, reasons: list<array<string, mixed>>}>
     */
    private function around(Contact $contact): array
    {
        $found = [];

        $keys = $contact->nameRepresentations()->pluck('match_key')
            ->push($contact->match_key)
            ->filter(fn (string $key): bool => $key !== '')
            ->unique()
            ->values()
            ->all();

        foreach ($this->entriesSharing($contact->vault_id, $keys) as $block) {
            $this->compareWithin($block, $found, $contact->id);
        }

        foreach ($this->sharedValues($contact->vault, $contact) as $sharing) {
            $this->recordSharedValue($sharing, $found);
        }

        return $found;
    }

    /** @return list<list<array{contact: Contact, given: string}>> */
    private function blocks(Vault $vault): array
    {
        $fromContacts = Contact::query()
            ->where('vault_id', $vault->id)
            ->where('match_key', '!=', '')
            ->where('is_me', false)
            ->select('id', 'match_key');

        $fromNames = ContactNameRepresentation::query()
            ->join('contacts', 'contacts.id', '=', 'contact_name_representations.contact_id')
            ->where('contacts.vault_id', $vault->id)
            ->whereNull('contacts.deleted_at')
            ->where('contacts.is_me', false)
            ->where('contact_name_representations.match_key', '!=', '')
            ->select('contacts.id', 'contact_name_representations.match_key');

        $keys = DB::query()
            ->fromSub($fromContacts->toBase()->union($fromNames->toBase()), 'keyed')
            ->groupBy('match_key')
            ->havingRaw('count(distinct id) > 1')
            ->pluck('match_key')
            ->all();

        return $this->entriesSharing($vault->id, $keys);
    }

    /**
     * @param  list<string>  $keys
     * @return list<list<array{contact: Contact, given: string}>>
     */
    private function entriesSharing(int $vaultId, array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $blocks = [];

        $contacts = Contact::query()
            ->where('vault_id', $vaultId)
            ->whereIn('match_key', $keys)
            ->where('is_me', false)
            ->get();

        foreach ($contacts as $contact) {
            $given = $this->givenNames($contact);
            $blocks[$contact->match_key]["{$contact->id}:{$given}"] = ['contact' => $contact, 'given' => $given];
        }

        $representations = ContactNameRepresentation::query()
            ->whereIn('match_key', $keys)
            ->whereHas('contact', fn ($query) => $query->where('vault_id', $vaultId)->where('is_me', false))
            ->with('contact')
            ->get();

        foreach ($representations as $representation) {
            $given = $this->givenNamesOf($representation);
            $blocks[$representation->match_key]["{$representation->contact_id}:{$given}"] ??= ['contact' => $representation->contact, 'given' => $given];
        }

        $blocks = array_map(array_values(...), $blocks);

        return array_values(array_filter($blocks, fn (array $block): bool => count(array_unique(array_map(fn (array $entry): int => $entry['contact']->id, $block))) > 1));
    }

    /**
     * @param  list<array{contact: Contact, given: string}>  $block
     * @param  array<string, array<string, mixed>>  $found
     */
    private function compareWithin(array $block, array &$found, ?int $only = null): void
    {
        if ($only !== null) {
            $block = [
                ...array_filter($block, fn (array $entry): bool => $entry['contact']->id === $only),
                ...array_filter($block, fn (array $entry): bool => $entry['contact']->id !== $only),
            ];
        }

        foreach ($block as $index => $one) {
            if ($only !== null && $one['contact']->id !== $only) {
                break;
            }

            foreach (array_slice($block, $index + 1) as $two) {
                if ($one['contact']->id === $two['contact']->id) {
                    continue;
                }

                if ($only !== null && $one['contact']->id !== $only && $two['contact']->id !== $only) {
                    continue;
                }

                $likeness = $this->likeness($one['given'], $two['given']);

                if ($likeness < self::NAME_THRESHOLD) {
                    continue;
                }

                $this->add($found, $one['contact'], $two['contact'], max(self::NAME_SCORE_FLOOR, $likeness), ['kind' => 'name']);
            }
        }
    }

    private function givenNames(Contact $contact): string
    {
        $parts = collect([$contact->first_name, $contact->middle_name])->filter()->implode(' ');

        if ($parts === '') {
            $parts = (string) ($contact->nickname ?? $contact->maiden_name ?? '');
        }

        return $parts === '' ? '' : LocaleCollation::foldForSearch($parts, $this->locale());
    }

    private function givenNamesOf(ContactNameRepresentation $representation): string
    {
        $parts = collect([$representation->given_name, $representation->additional_name])->filter()->implode(' ');

        if ($parts === '' && $representation->family_name === null) {
            $parts = (string) $representation->formatted_name;
        }

        return $parts === '' ? '' : LocaleCollation::foldForSearch($parts, $this->locale());
    }

    private function locale(): string
    {
        return once(fn (): string => (string) $this->subject?->account->locale());
    }

    /** @return list<array{vault_id: int, field_key: string, value: string, ids: list<int>}> */
    private function sharedValues(Vault $vault, ?Contact $around = null): array
    {
        $values = FieldValue::query()
            ->select('field_values.id', 'field_values.valuable_id', 'field_values.field_key', 'field_values.value_string')
            ->join('fields', 'fields.id', '=', 'field_values.field_id')
            ->where('field_values.vault_id', $vault->id)
            ->where('field_values.valuable_type', Contact::class)
            ->where('field_values.is_detached', false)
            ->where('fields.is_identifying', true)
            ->whereNotNull('field_values.value_string')
            ->where('field_values.value_string', '!=', '')
            ->get();

        $groups = [];

        foreach ($values as $value) {
            $key = $value->field_key.'|'.mb_strtolower((string) $value->value_string);
            $groups[$key]['field_key'] = $value->field_key;
            $groups[$key]['value'] = (string) $value->value_string;
            $groups[$key]['ids'][$value->valuable_id] = $value->valuable_id;
        }

        $sharing = [];

        foreach ($groups as $group) {
            $ids = array_values($group['ids']);

            if (count($ids) < 2) {
                continue;
            }

            if (count($ids) > self::MOST_CONTACTS_SHARING_A_VALUE) {
                continue;
            }

            if ($around instanceof Contact && ! in_array($around->id, $ids, true)) {
                continue;
            }

            $sharing[] = [
                'vault_id' => $vault->id,
                'field_key' => $group['field_key'],
                'value' => $group['value'],
                'ids' => $ids,
            ];
        }

        return $sharing;
    }

    /**
     * @param  array{vault_id: int, field_key: string, value: string, ids: list<int>}  $sharing
     * @param  array<string, array<string, mixed>>  $found
     */
    private function recordSharedValue(array $sharing, array &$found): void
    {
        $contacts = Contact::query()->whereIn('id', $sharing['ids'])->where('is_me', false)->get()->values();

        foreach ($contacts as $index => $one) {
            foreach ($contacts->slice($index + 1) as $two) {
                $this->add($found, $one, $two, self::VALUE_SCORE, [
                    'kind' => 'field',
                    'field_key' => $sharing['field_key'],
                    'value' => $sharing['value'],
                ]);
            }
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $found
     * @param  array<string, mixed>  $reason
     */
    private function add(array &$found, Contact $one, Contact $two, int $score, array $reason): void
    {
        if ($one->id === $two->id) {
            return;
        }

        [$low, $high] = $one->id < $two->id ? [$one, $two] : [$two, $one];
        $key = "{$low->id}:{$high->id}";

        if (! isset($found[$key])) {
            $found[$key] = [
                'account_id' => $low->account_id,
                'vault_id' => $low->vault_id,
                'contact_id' => $low->id,
                'other_contact_id' => $high->id,
                'score' => 0,
                'reasons' => [],
            ];
        }

        $found[$key]['score'] = max($found[$key]['score'], $score);

        if (! in_array($reason, $found[$key]['reasons'], true)) {
            $found[$key]['reasons'][] = $reason;
        }
    }

    /** `similar_text` rather than a Levenshtein distance, which is capped at 255 bytes. */
    private function likeness(string $one, string $two): int
    {
        if ($one === $two) {
            return 100;
        }

        if ($one === '' || $two === '') {
            return 0;
        }

        if (str_starts_with($one, $two) || str_starts_with($two, $one)) {
            return 100;
        }

        similar_text($one, $two, $percent);

        return (int) round($percent);
    }

    /** @param array<string, array<string, mixed>> $found */
    private function write(array $found): void
    {
        DB::transaction(function () use ($found): void {
            foreach ($found as $pair) {
                ContactDuplicateCandidate::query()->updateOrCreate(
                    [
                        'contact_id' => $pair['contact_id'],
                        'other_contact_id' => $pair['other_contact_id'],
                    ],
                    [
                        'account_id' => $pair['account_id'],
                        'vault_id' => $pair['vault_id'],
                        'score' => $pair['score'],
                        'reasons' => $pair['reasons'],
                        'computed_at' => now(),
                    ],
                );
            }

            $this->forgetWhatNoLongerMatches($found);
        });
    }

    /** @param array<string, array<string, mixed>> $found */
    private function forgetWhatNoLongerMatches(array $found): void
    {
        $stale = ContactDuplicateCandidate::query()
            ->standing()
            ->where('vault_id', $this->vaultId())
            ->when(
                $this->subject instanceof Contact,
                fn ($query) => $query->where(function ($query): void {
                    $query->where('contact_id', $this->subject->id)
                        ->orWhere('other_contact_id', $this->subject->id);
                }),
            )
            ->get()
            ->reject(fn (ContactDuplicateCandidate $candidate): bool => isset($found["{$candidate->contact_id}:{$candidate->other_contact_id}"]));

        ContactDuplicateCandidate::query()->whereIn('id', $stale->modelKeys())->delete();
    }

    private function vaultId(): int
    {
        return $this->subject instanceof Vault ? $this->subject->id : (int) $this->subject?->vault_id;
    }
}
