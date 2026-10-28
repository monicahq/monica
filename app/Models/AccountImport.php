<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportSourceEnum;
use App\Enums\AccountImportStatusEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\AccountImportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * One run of a file, a Monica 4 export or an export of this application, into
 * the account.
 *
 * The run is long, happens on a queue, can be cancelled and must be undone
 * completely when it fails, so where it stands, what was chosen and its report
 * need a home of their own. The file is read once into items, and nothing is
 * written to a vault before the person confirms. An account runs one import at a
 * time.
 *
 * `source_format` is null while the file is read, since reading it is what tells
 * the formats apart, and stays null when the file was refused as neither.
 * `vault_id` is the vault chosen for a file of one vault. A file of several
 * writes each into a new vault, listed in `created_vault_ids`, which a rollback
 * destroys. An import only adds, so rolling back removes exactly what its items
 * say it created.
 *
 * The uuid is the source detail of every log the import writes.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $user_id
 * @property AccountImportSourceEnum|null $source_format
 * @property string $source_checksum
 * @property string $disk
 * @property string $path
 * @property AccountImportStatusEnum $status
 * @property string|null $refusal
 * @property int|null $vault_id
 * @property list<int> $created_vault_ids
 * @property string|null $me_source_uuid
 * @property array<string, mixed>|null $summary
 * @property array<string, mixed>|null $source_references
 * @property string|null $writing_run
 * @property int $items_total
 * @property int $items_done
 * @property CarbonImmutable|null $ended_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read User $user
 * @property-read Vault|null $vault
 * @property-read Collection<int, AccountImportItem> $items
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'user_id',
    'source_format',
    'source_checksum',
    'disk',
    'path',
    'status',
    'refusal',
    'vault_id',
    'created_vault_ids',
    'me_source_uuid',
    'summary',
    'source_references',
    'writing_run',
    'items_total',
    'items_done',
    'ended_at',
])]
class AccountImport extends Model
{
    /** @use HasFactory<AccountImportFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'source_format' => AccountImportSourceEnum::class,
            'status' => AccountImportStatusEnum::class,
            'created_vault_ids' => 'array',
            'summary' => 'array',
            'source_references' => 'array',
            'items_total' => 'integer',
            'items_done' => 'integer',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Get the account the data goes into.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who started the import.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vault chosen for a file of one vault.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get what was read from the file, in the order it is written.
     *
     * @return HasMany<AccountImportItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(AccountImportItem::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Get the folder holding this import's working files: the upload and what was decoded from it.
     * It sits under the account's prefix and is named after the import, so two imports never share
     * a file and removing one folder removes exactly one import's files.
     */
    public function directory(): string
    {
        return "{$this->account->transfersDirectory()}/{$this->uuid}";
    }

    /**
     * Count, by kind of the summary, the values written inside an item rather than as items of
     * their own: the ways to reach a Monica 4 contact, its addresses, notes, gifts, debts, tasks
     * and tags. Each item keeps the count of what it wrote, so a value the writer could not place,
     * or a contact it refused, is not counted as imported.
     *
     * @return array<string, int>
     */
    public function writtenInsideItems(): array
    {
        $written = [];

        $this->items()
            ->reorder()
            ->whereIn('outcome', [AccountImportOutcomeEnum::Imported->value, AccountImportOutcomeEnum::MergedIntoMe->value])
            ->whereNotNull('payload')
            ->pluck('payload')
            ->each(function (mixed $payload) use (&$written): void {
                foreach ((array) (is_array($payload) ? ($payload['written'] ?? []) : []) as $kind => $count) {
                    $written[(string) $kind] = ($written[(string) $kind] ?? 0) + (int) $count;
                }
            });

        return $written;
    }

    /**
     * Whether the file holds more than one vault. Each of them then arrives as a new vault of its
     * own, with the contact it marks as its owner joining the person, so there is no vault to
     * choose and no contact to pick as the person.
     */
    public function holdsSeveralVaults(): bool
    {
        return $this->items()->where('kind', 'vault')->count() > 1;
    }
}
