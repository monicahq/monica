<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\GroupNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class GroupNote
 *
 * Something written down about a group as a whole: where they meet, who has the spare key, what to
 * bring. It belongs to the group alone and never reaches the page of anybody in it, which is what
 * sets it apart from an activity, whose participants carry it onto their own timelines.
 *
 * Its shape is fixed by the product (a text, the day it was written, who wrote it), so it is not
 * user defined data and needs no template. The date the group's history shows is `created_at`.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $group_id
 * @property string $body
 * @property int|null $created_by_user_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read Group $group
 * @property-read User|null $createdBy
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'group_id',
    'body',
    'created_by_user_id',
])]
class GroupNote extends Model
{
    /** @use HasFactory<GroupNoteFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;
    use SoftDeletes;

    /**
     * Get the account the note belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault the group lives in.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the group the note is about.
     *
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who wrote it.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
