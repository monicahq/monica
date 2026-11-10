<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\GroupFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class GroupFile
 *
 * A file kept with a group: a team photo, a schedule, a registration form. The row is the
 * attachment, and it holds one reference on the file, so the file is only swept up once every
 * group, field and life event pointing at it has let go and its grace period has passed.
 *
 * Removing a file from a group deletes this row rather than soft deleting it; the file itself is
 * what survives, through its own grace period. Deleting the group keeps the rows, so restoring the
 * group brings its files back.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $group_id
 * @property int $file_id
 * @property int|null $added_by_user_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Group $group
 * @property-read File $file
 * @property-read User|null $addedBy
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'group_id',
    'file_id',
    'added_by_user_id',
])]
class GroupFile extends Model
{
    /** @use HasFactory<GroupFileFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;

    /**
     * Get the account the attachment belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the group the file is kept with.
     *
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the file.
     *
     * @return BelongsTo<File, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Get the user who added it.
     *
     * @return BelongsTo<User, $this>
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }
}
