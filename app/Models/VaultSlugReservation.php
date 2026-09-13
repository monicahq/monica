<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\VaultSlugReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class VaultSlugReservation
 *
 * A slug a vault has carried, kept so that no other vault of the account is ever given it.
 *
 * A vault's slug follows its name, so renaming one releases the slug it had. Releasing it back into
 * circulation is what would make an address saved last month open a different vault, which is the
 * one thing the readable address must never do. A row is written here the first time a vault
 * answers to a slug, and it is never deleted, so the slug stays spent whatever happens to the vault
 * afterwards.
 *
 * Nothing resolves through this table. An address naming a released slug is not found, which is
 * what it should be: the reservation exists to withhold the name, not to forward it.
 *
 * @property int $id
 * @property int $account_id
 * @property int $vault_id
 * @property string $slug
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Vault $vault
 */
#[Fillable([
    'account_id',
    'vault_id',
    'slug',
])]
class VaultSlugReservation extends Model
{
    /** @use HasFactory<VaultSlugReservationFactory> */
    use HasFactory;

    /**
     * Get the account the reservation is scoped to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault that carried the slug.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }
}
