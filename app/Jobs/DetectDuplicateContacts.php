<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\LocaleCollation;
use App\Models\Contact;
use App\Models\ContactDuplicateCandidate;
use App\Models\FieldValue;
use App\Models\Vault;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Work out which contacts of a vault look like the same person.
 *
 * It is on a queue because it must never be on the path of a save. Given one
 * contact it looks at that contact alone, which is what runs after somebody
 * creates or renames one. Given a vault it looks at the whole vault, which is
 * what runs nightly and is the only thing that notices a template that has just
 * gained an identifying field, since no contact is written at that moment.
 *
 * Two signals. Names that resemble each other, narrowed in the database by
 * `match_key` and then compared in PHP, because no comparison of two strings by
 * similarity is available in SQLite, MySQL and PostgreSQL alike and the
 * constitution forbids SQL only one of them speaks. And an exact shared value in
 * a field the structure marks as identifying, which is plain portable SQL.
 *
 * Nothing here ever joins two contacts. It proposes, and a person decides.
 */
class DetectDuplicateContacts implements ShouldQueue
{
    use Queueable;

    /**
     * How alike two folded given names have to be before the pair is worth
     * showing, as a percentage. Measured on the given names alone, because the
     * block already guarantees the family name matches and comparing the whole
     * name would let that shared half carry every pair of siblings onto the
     * screen.
     */
    private const int NAME_THRESHOLD = 60;

    private const int NAME_SCORE_FLOOR = 40;

    private const int VALUE_SCORE = 95;

    public function __construct(private readonly Contact|Vault|null $subject = null) {}

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

    /**
     * The nightly pass. It runs vault by vault so that one enormous vault does
     * not hold the whole instance in memory, and it is what notices a template
     * that has just gained an identifying field, since no contact is written at
     * that moment.
     */
    private function everywhere(): void
    {
        $vaults = 0;

        Vault::query()->chunkById(100, function ($chunk) use (&$vaults): void {
            foreach ($chunk as $vault) {
                $vaults++;
                new self($vault)->handle();
            }
        });

        Log::info('Looked for duplicate contacts.', ['vaults' => $vaults]);
    }

    /**
     * Every pair worth proposing in one vault, keyed by the two ids in order.
     *
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
     * Every pair worth proposing that involves one contact.
     *
     * @return array<string, array{vault_id: int, account_id: int, contact_id: int, other_contact_id: int, score: int, reasons: list<array<string, mixed>>}>
     */
    private function around(Contact $contact): array
    {
        $found = [];

        if ($contact->match_key !== '') {
            $block = Contact::query()
                ->where('vault_id', $contact->vault_id)
                ->where('match_key', $contact->match_key)
                ->where('is_me', false)
                ->get();

            $this->compareWithin($block, $found, $contact->id);
        }

        foreach ($this->sharedValues($contact->vault, $contact) as $sharing) {
            $this->recordSharedValue($sharing, $found);
        }

        return $found;
    }

    /**
     * The vault's contacts gathered into groups that share a match key. A group
     * of one cannot hold a pair, so it never leaves the database.
     *
     * @return list<Collection<int, Contact>>
     */
    private function blocks(Vault $vault): array
    {
        $keys = Contact::query()
            ->where('vault_id', $vault->id)
            ->where('match_key', '!=', '')
            ->groupBy('match_key')
            ->havingRaw('count(*) > 1')
            ->pluck('match_key')
            ->all();

        if ($keys === []) {
            return [];
        }

        return Contact::query()
            ->where('vault_id', $vault->id)
            ->whereIn('match_key', $keys)
            ->where('is_me', false)
            ->get()
            ->groupBy('match_key')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Contact>  $block
     * @param  array<string, array<string, mixed>>  $found
     */
    private function compareWithin(Collection $block, array &$found, ?int $only = null): void
    {
        $contacts = $block->values();
        $given = $contacts->mapWithKeys(fn (Contact $one): array => [$one->id => $this->givenNames($one)]);

        foreach ($contacts as $index => $one) {
            foreach ($contacts->slice($index + 1) as $two) {
                if ($only !== null && $one->id !== $only && $two->id !== $only) {
                    continue;
                }

                $likeness = $this->likeness($given[$one->id], $given[$two->id]);

                if ($likeness < self::NAME_THRESHOLD) {
                    continue;
                }

                $this->add($found, $one, $two, max(self::NAME_SCORE_FLOOR, $likeness), ['kind' => 'name']);
            }
        }
    }

    /**
     * The part of the name that is not the family name, folded. Two records of
     * one person agree on the family name and differ here, which is why this is
     * what gets compared.
     */
    private function givenNames(Contact $contact): string
    {
        $parts = collect([$contact->first_name, $contact->middle_name])->filter()->implode(' ');

        if ($parts === '') {
            $parts = (string) ($contact->nickname ?? $contact->maiden_name ?? '');
        }

        return $parts === '' ? '' : LocaleCollation::foldForSearch($parts, $this->locale());
    }

    private function locale(): string
    {
        return once(fn (): string => (string) $this->subject?->account->locale());
    }

    /**
     * Values held by more than one contact in a field the structure marks as
     * identifying. Grouped in SQL, which every driver does the same way.
     *
     * @return list<array{vault_id: int, field_key: string, value: string, ids: list<int>}>
     */
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
        $found[$key]['reasons'][] = $reason;
    }

    /**
     * How alike two folded names are, as a percentage. `similar_text` is used
     * rather than a Levenshtein distance because it is not capped at 255 bytes,
     * which a name in a script wider than Latin passes easily.
     */
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

    /**
     * @param  array<string, array<string, mixed>>  $found
     */
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

    /**
     * A pair that stopped matching is dropped, unless somebody dismissed it. A
     * dismissal is permanent, so its row stays whatever detection now thinks.
     *
     * @param  array<string, array<string, mixed>>  $found
     */
    private function forgetWhatNoLongerMatches(array $found): void
    {
        $keeping = array_map(
            fn (array $pair): array => [$pair['contact_id'], $pair['other_contact_id']],
            array_values($found),
        );

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
            ->reject(fn (ContactDuplicateCandidate $candidate): bool => in_array(
                [$candidate->contact_id, $candidate->other_contact_id],
                $keeping,
                true,
            ));

        ContactDuplicateCandidate::query()->whereIn('id', $stale->modelKeys())->delete();
    }

    private function vaultId(): int
    {
        return $this->subject instanceof Vault ? $this->subject->id : (int) $this->subject?->vault_id;
    }
}
