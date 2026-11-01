<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountImportOutcomeEnum;
use Carbon\CarbonImmutable;
use Database\Factories\AccountImportItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

/**
 * One thing read from an imported file: a contact, a relationship, a note, a
 * file, a journal entry.
 *
 * It exists so a file is read once and written in short batches, and so every
 * item carries what became of it. The same rows are the report of the import and
 * the ledger its rollback reads, walking them in reverse `position` and hard
 * deleting each record they name.
 *
 * An item with an `outcome` is never written again, which is what makes a
 * retried batch safe. An item that cannot be written is left out with its
 * `reason`, a translation key, and the rest of the import goes on. `payload` is
 * cleared once the item is written, except on an item that made a copy of a
 * structure: it keeps which section of the copy each section of the file became,
 * and the value lists and kinds of record the copy made, for the records written
 * after it and for the rollback.
 *
 * The record it created is a morph, so `record_type` and `record_id` carry no
 * foreign key. The item is never addressed by a URL: it is reached through its
 * import.
 *
 * @property int $id
 * @property int $account_id
 * @property int $account_import_id
 * @property string|null $vault_source_uuid
 * @property string $kind
 * @property string|null $source_uuid
 * @property string|null $name
 * @property int $position
 * @property array<string, mixed>|null $payload
 * @property AccountImportOutcomeEnum|null $outcome
 * @property string|null $reason
 * @property string|null $record_type
 * @property int|null $record_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read AccountImport $accountImport
 * @property-read Model|null $record
 */
#[Fillable([
    'account_id',
    'account_import_id',
    'vault_source_uuid',
    'kind',
    'source_uuid',
    'name',
    'position',
    'payload',
    'outcome',
    'reason',
    'record_type',
    'record_id',
])]
class AccountImportItem extends Model
{
    /** @use HasFactory<AccountImportItemFactory> */
    use HasFactory;

    /**
     * The kinds that never show in the report: the archiving and the merge into Me are steps of
     * other items, and the values pointing at another record belong to records already counted.
     *
     * @var list<string>
     */
    public const array KINDS_LEFT_OUT_OF_THE_REPORT = ['archive', 'me', 'related_values'];

    /**
     * The kinds of the file's summary that are written as items of their own. Every other kind
     * is written inside an item, which counts what it wrote, and the report takes it from there.
     *
     * @var list<string>
     */
    public const array SUMMARY_KINDS_WRITTEN_AS_ITEMS = [
        'vaults', 'templates', 'life_event_types', 'relationship_types', 'files', 'photos', 'documents',
        'contacts', 'entities', 'life_events', 'calls', 'conversations', 'pets', 'activities',
        'reminders', 'relationships', 'groups', 'journal_entries', 'day_ratings',
    ];

    /**
     * The order items are written in, by kind: what is pointed at comes before what points at it,
     * and archiving comes last so nothing is refused for being written onto an archived contact.
     * The values of a record pointing at another record wait until every record is written, and
     * reminders wait for them, since a reminder can read a date among those values.
     *
     * @var array<string, int>
     */
    public const array RANKS = [
        'vault' => 0,
        'structure' => 0,
        'life_event_type' => 0,
        'relationship_type' => 0,
        'file' => 1,
        'contact' => 2,
        'entity' => 3,
        'life_event' => 3,
        'call' => 3,
        'conversation' => 3,
        'pet' => 3,
        'activity' => 4,
        'related_values' => 4,
        'reminder' => 5,
        'relationship' => 5,
        'group' => 5,
        'journal_entry' => 6,
        'me' => 7,
        'archive' => 8,
    ];

    /** The room each rank leaves for its items, so a position names the rank and the order inside it. */
    public const int RANK_SPAN = 1_000_000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'payload' => 'array',
            'outcome' => AccountImportOutcomeEnum::class,
        ];
    }

    /**
     * Get the account of the import.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the import the item was read for.
     *
     * @return BelongsTo<AccountImport, $this>
     */
    public function accountImport(): BelongsTo
    {
        return $this->belongsTo(AccountImport::class);
    }

    /**
     * Get the record the item created, null until it is written and when it was
     * left out.
     *
     * @return MorphTo<Model, $this>
     */
    public function record(): MorphTo
    {
        return $this->morphTo();
    }
}
