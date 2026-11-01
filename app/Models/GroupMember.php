<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DatePrecisionEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\GroupMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class GroupMember
 *
 * One period during which a contact belonged to a group, with what they were there as. A membership
 * with no leave date is current. Leaving ends the membership rather than deleting it, and coming back
 * starts a new one, so the history of a group is the list of its memberships.
 *
 * A membership is soft deleted only when it was recorded by mistake, or when a merge folds two
 * memberships of the same group into one.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $group_id
 * @property int $contact_id
 * @property string|null $role
 * @property CarbonImmutable|null $joined_on
 * @property DatePrecisionEnum $joined_precision
 * @property CarbonImmutable|null $left_on
 * @property DatePrecisionEnum $left_precision
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read Group $group
 * @property-read Contact $contact
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'group_id',
    'contact_id',
    'role',
    'joined_on',
    'joined_precision',
    'left_on',
    'left_precision',
])]
class GroupMember extends Model
{
    /** @use HasFactory<GroupMemberFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;
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
            'joined_on' => 'date',
            'joined_precision' => DatePrecisionEnum::class,
            'left_on' => 'date',
            'left_precision' => DatePrecisionEnum::class,
        ];
    }

    /**
     * Get the account the membership belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the group.
     *
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the contact who is or was a member.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Whether the contact is in the group today.
     */
    public function isCurrent(): bool
    {
        return $this->left_on === null;
    }
}
