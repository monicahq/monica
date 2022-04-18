<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactImportantDate extends Model
{
    use HasFactory;

    protected $table = 'contact_important_dates';

    /**
     * Possible type.
     */
    const TYPE_BIRTHDATE = 'birthdate';
    const TYPE_DECEASED_DATE = 'deceased_date';

    /**
     * Possible type of dates.
     */
    const TYPE_FULL_DATE = 'full_date';
    const TYPE_MONTH_DAY = 'month_day';
    const TYPE_YEAR = 'year';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_id',
        'label',
        'day',
        'month',
        'year',
        'type',
    ];

    /**
     * Get the contact associated with the contact date.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
