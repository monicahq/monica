<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property Account $account
 * @property Contact|null $contact
 * @property string $title
 * @property string $description
 * @property string $uuid
 * @property bool $completed
 * @property \Carbon\Carbon|null $completed_at
 *
 * @method static Builder completed()
 * @method static Builder inProgress()
 */
class Task extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<string>
     */
    protected $dates = [
        'completed_at',
        'archived_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'completed' => 'boolean',
        'archived' => 'boolean',
    ];

    /**
     * Eager load with every task.
     */
    protected $with = [
        'account',
        'contact',
    ];

    /**
     * Get the account record associated with the task.
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
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Limit tasks to completed ones.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeCompleted(Builder $query)
    {
        return $query->where('completed', true);
    }

    /**
     * Limit tasks to in-progress ones.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeInProgress(Builder $query)
    {
        return $query->where('completed', false);
    }
}
