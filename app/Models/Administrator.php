<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AdministratorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Administrator
 *
 * Somebody who runs the instance. The capacity is a row rather than a column on
 * the user, for two reasons. It carries where it came from, which a boolean
 * cannot: who granted it and when. And it stays out of `users`, which several
 * actions mass assign into, so no self-service route can ever set it by
 * accident.
 *
 * Withdrawing the capacity deletes the row. That the capacity was once held is
 * remembered by {@see AdministratorLog}, which is append only, rather than by a
 * tombstone here.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $granted_by_user_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read User|null $grantedBy
 */
#[Fillable([
    'user_id',
    'granted_by_user_id',
])]
class Administrator extends Model
{
    /** @use HasFactory<AdministratorFactory> */
    use HasFactory;

    /**
     * Get the person holding the capacity.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the administrator who granted it, as long as they still exist. Null
     * also means the console designated them, which is how the first
     * administrator of an instance arrives.
     *
     * @return BelongsTo<User, $this>
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }
}
