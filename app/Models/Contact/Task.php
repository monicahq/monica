<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ModelBindingWithContact as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Account $account
 * @property Contact $contact
 * @method static Builder completed()
 * @method static Builder inProgress()
 */
class Task extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['completed_at', 'archived_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'completed' => 'boolean',
        'archived' => 'boolean',
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
     * @param Builder $query
     * @return Builder
     */
    public function scopeCompleted(Builder $query)
    {
        return $query->where('completed', true);
    }

    /**
     * Limit tasks to in-progress ones.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInProgress(Builder $query)
    {
        return $query->where('completed', false);
    }
}
