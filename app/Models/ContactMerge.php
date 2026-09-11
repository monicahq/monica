<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\ContactMergeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class ContactMerge
 *
 * The record of two contacts being made one, kept so the merge can be explained and undone. The
 * absorbed contact is soft deleted rather than destroyed, so every identifier that pointed at it
 * keeps resolving and its address lands on the survivor.
 *
 * `resolutions` is what the user decided, conflict by conflict. `ledger` is what actually moved,
 * and is what the undo replays backwards. `snapshot_id` is not how the undo works, since a snapshot
 * describes one record and a merge touches two, but it is the state an undo can be checked against.
 *
 * Neither contact column carries a foreign key: the record of a merge is history and has to outlive
 * what it names.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $kept_contact_id
 * @property int $merged_contact_id
 * @property array<array-key, mixed> $resolutions
 * @property array<array-key, mixed> $ledger
 * @property int|null $snapshot_id
 * @property int|null $merged_by_user_id
 * @property CarbonImmutable|null $undone_at
 * @property int|null $undone_by_user_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read Contact $keptContact
 * @property-read Contact $mergedContact
 * @property-read RecordSnapshot|null $snapshot
 * @property-read User|null $mergedBy
 * @property-read User|null $undoneBy
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'kept_contact_id',
    'merged_contact_id',
    'resolutions',
    'ledger',
    'snapshot_id',
    'merged_by_user_id',
    'undone_at',
    'undone_by_user_id',
])]
class ContactMerge extends Model
{
    /** @use HasFactory<ContactMergeFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'resolutions' => 'array',
            'ledger' => 'array',
            'undone_at' => 'datetime',
        ];
    }

    /**
     * Get the account the merge happened in.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault both contacts live in.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the contact that survived the merge.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function keptContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'kept_contact_id');
    }

    /**
     * Get the contact that was absorbed. It is soft deleted, so it is only
     * reached with the trashed rows included.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function mergedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'merged_contact_id')->withTrashed();
    }

    /**
     * Get the state of the survivor captured before the merge ran.
     *
     * @return BelongsTo<RecordSnapshot, $this>
     */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(RecordSnapshot::class, 'snapshot_id');
    }

    /**
     * Get the user who ran the merge.
     *
     * @return BelongsTo<User, $this>
     */
    public function mergedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by_user_id');
    }

    /**
     * Get the user who reversed the merge.
     *
     * @return BelongsTo<User, $this>
     */
    public function undoneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'undone_by_user_id');
    }

    /**
     * Whether the merge still stands. A merge that has been reversed keeps its
     * row, so that the history of the two contacts stays readable.
     */
    public function stands(): bool
    {
        return $this->undone_at === null;
    }
}
