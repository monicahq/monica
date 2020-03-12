<?php

namespace App\Models\Account;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Account $account
 * @property User $invitedBy
 * @property string $invitation_key
 */
class Invitation extends Model
{
    use Notifiable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the account record associated with the invitation.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the task.
     *
     * @return BelongsTo
     */
    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }
}
