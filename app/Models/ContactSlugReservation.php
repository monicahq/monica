<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ContactSlugReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ContactSlugReservation
 *
 * A slug a contact has carried, kept so that no other contact of the vault is ever given it.
 *
 * A contact's slug follows the name they are shown under, so correcting a name releases the slug it
 * built. Handing that slug to somebody else is how an address saved last month would open the wrong
 * person, which is the one thing the readable address must never do. A row is written here the
 * first time a contact answers to a slug and is never deleted, so the slug stays spent through a
 * rename, a deletion and a merge alike.
 *
 * Nothing resolves through this table. An address naming a released slug is not found, which is
 * what it should be: the reservation exists to withhold the name, not to forward it. A contact an
 * undone merge brings back still holds its own slug on its row, so it is reachable again without
 * this table being read.
 *
 * @property int $id
 * @property int $vault_id
 * @property int $contact_id
 * @property string $slug
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Vault $vault
 * @property-read Contact $contact
 */
#[Fillable([
    'vault_id',
    'contact_id',
    'slug',
])]
class ContactSlugReservation extends Model
{
    /** @use HasFactory<ContactSlugReservationFactory> */
    use HasFactory;

    /**
     * Get the vault the reservation is scoped to.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the contact that carried the slug.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
