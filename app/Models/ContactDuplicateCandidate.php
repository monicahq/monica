<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\ContactDuplicateCandidateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class ContactDuplicateCandidate
 *
 * Two contacts of one vault that look like the same person, with the reason for
 * thinking so.
 *
 * The pair is always stored with the lower id first, which is what makes it one
 * row however it was found. Detection looks from both sides and would otherwise
 * write the pair twice.
 *
 * A dismissal lives on this row rather than in a table of its own, so it
 * survives every recomputation: detection updates a pair it still believes in,
 * and deletes only a pair it no longer believes in that nobody has dismissed.
 *
 * `score` is a number between 0 and 100 and orders the screen. It says how
 * strongly the two resemble each other, never how certain the product is: the
 * product never joins two contacts on its own, whatever the score.
 *
 * @property int $id
 * @property int $account_id
 * @property int $vault_id
 * @property int $contact_id
 * @property int $other_contact_id
 * @property int $score
 * @property array<array-key, mixed> $reasons
 * @property CarbonImmutable|null $dismissed_at
 * @property int|null $dismissed_by_user_id
 * @property CarbonImmutable $computed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read Contact $contact
 * @property-read Contact $otherContact
 * @property-read User|null $dismissedBy
 */
#[Fillable([
    'account_id',
    'vault_id',
    'contact_id',
    'other_contact_id',
    'score',
    'reasons',
    'dismissed_at',
    'dismissed_by_user_id',
    'computed_at',
])]
class ContactDuplicateCandidate extends Model
{
    /** @use HasFactory<ContactDuplicateCandidateFactory> */
    use HasFactory;

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
            'score' => 'integer',
            'reasons' => 'array',
            'dismissed_at' => 'datetime',
            'computed_at' => 'datetime',
        ];
    }

    /**
     * Get the account the pair belongs to.
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
     * Get the first of the two contacts, the one with the lower id.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the second of the two contacts.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function otherContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'other_contact_id');
    }

    /**
     * Get the user who said these are two different people.
     *
     * @return BelongsTo<User, $this>
     */
    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by_user_id');
    }

    /**
     * Only the pairs nobody has ruled out.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeStanding(Builder $query): void
    {
        $query->whereNull('dismissed_at');
    }

    public function isDismissed(): bool
    {
        return $this->dismissed_at !== null;
    }
}
