<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RelationshipStatusEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\RelationshipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class Relationship
 *
 * A link the user entered between two people. One row holds both sides and is read from either
 * contact's point of view, so the application never writes a mirror row.
 *
 * For a directed type A carries `role_a` and B carries `role_b`: on Parent / Child, A is the parent.
 * For a symmetric type the two sides say the same word, so the lower contact id is stored as A to
 * give the pair one canonical order.
 *
 * Both endpoints are always real contacts, whether or not their names are known. A relationship
 * never holds a free-text person and never has an absent side.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $relationship_type_id
 * @property int $contact_a_id
 * @property int $contact_b_id
 * @property RelationshipStatusEnum $status
 * @property CarbonImmutable|null $started_on
 * @property CarbonImmutable|null $ended_on
 * @property string|null $notes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read RelationshipType $relationshipType
 * @property-read Contact $contactA
 * @property-read Contact $contactB
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'relationship_type_id',
    'contact_a_id',
    'contact_b_id',
    'status',
    'started_on',
    'ended_on',
    'notes',
])]
class Relationship extends Model
{
    /** @use HasFactory<RelationshipFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;
    use SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => RelationshipStatusEnum::class,
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    /**
     * Get the account the relationship belongs to.
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
     * Get the type giving the link its structure.
     *
     * @return BelongsTo<RelationshipType, $this>
     */
    public function relationshipType(): BelongsTo
    {
        return $this->belongsTo(RelationshipType::class);
    }

    /**
     * Get the side carrying role A.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contactA(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_a_id');
    }

    /**
     * Get the side carrying role B.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contactB(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_b_id');
    }
}
