<?php

namespace App\Models\Account;

use App\Traits\HasUuid;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityTypeCategory extends Model
{
    use HasUuid;

    protected $table = 'activity_type_categories';

    protected $appends = ['name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'translation_key',
        'account_id',
    ];

    /**
     * Get the account record associated with the activity type group.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the activity type records associated with the category.
     *
     * @return HasMany
     */
    public function activityTypes()
    {
        return $this->hasMany(ActivityType::class);
    }

    /**
     * Get the activity type category's attribute.
     *
     * @return string
     * @psalm-suppress InvalidReturnStatement
     */
    public function getNameAttribute($value)
    {
        if ($this->translation_key && ! $value) {
            return trans('people.activity_type_category_'.$this->translation_key);
        }

        return $value;
    }
}
