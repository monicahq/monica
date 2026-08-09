<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VaultColourEnum;
use Carbon\Carbon;
use Database\Factories\VaultFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Vault
 *
 * A watertight compartment of data: "Famille", "Pro", "Amis". Contacts,
 * entities and activities live in one, and nothing reaches across. Separating
 * contexts is the whole point, so that a work search never returns a family
 * contact and a private link scoped to one part of a life exposes no other.
 *
 * The vault a user is shown is found by its uuid rather than by its id, since
 * the identifier travels in URLs and through the API and guessing the next one
 * must lead nowhere.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $name
 * @property string|null $description
 * @property string $colour
 * @property int $position
 * @property bool $is_default
 * @property int $contact_count
 * @property Carbon|null $last_visited_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Account $account
 */
class Vault extends Model
{
    /** @use HasFactory<VaultFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    /**
     * The colour a vault is given when whoever created it did not pick one.
     * Every colour on offer is a case of {@see VaultColourEnum}.
     */
    public const string DEFAULT_COLOUR = VaultColourEnum::Blue->value;

    protected $table = 'vaults';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'description',
        'colour',
        'position',
        'is_default',
        'contact_count',
        'last_visited_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_default' => 'boolean',
            'contact_count' => 'integer',
            'last_visited_at' => 'datetime',
        ];
    }

    /**
     * Get the columns that are given a unique identifier of their own. The
     * primary key stays an incrementing integer, and the uuid is what the
     * outside world is shown.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Get the column a vault is looked up by in a URL.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the account the vault belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contacts the vault holds. Nothing outside the vault reaches
     * them, which is the whole point of the compartment.
     *
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the structures a new record starts on here, one per kind of record.
     * They differ from vault to vault, since a work contact and a family
     * contact do not start from the same page.
     *
     * @return HasMany<VaultDefaultTemplate, $this>
     */
    public function defaultTemplates(): HasMany
    {
        return $this->hasMany(VaultDefaultTemplate::class);
    }
}
