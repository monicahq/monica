<?php

namespace App\Models\Family;

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\ModelBindingHasher as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Family extends Model
{
    protected $table = 'families';

    /**
     * The attributes that should be cast as dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'name',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the account associated with the family.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contacts record associated with the family.
     *
     * @return belongsToMany
     */
    public function contacts()
    {
        return $this->belongsToMany(Contact::class)->withTimestamps();
    }
}
