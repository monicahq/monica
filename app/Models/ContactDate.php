<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactDate extends Model
{
    use HasFactory;

    protected $table = 'contact_dates';

    /**
     * Possible type.
     */
    const TYPE_BIRTHDATE = 'birthdate';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_id',
        'label',
        'date',
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
