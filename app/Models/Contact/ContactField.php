<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use App\Models\ModelBindingWithContact as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactField extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = ['contact'];

    /**
     * Get the account record associated with the contact field.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the contact field.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the contact record associated with the contact field.
     *
     * @return BelongsTo
     */
    public function contactFieldType()
    {
        return $this->belongsTo(ContactFieldType::class);
    }

    /**
     * Scope a query to only include contact field of email type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmail($query)
    {
        return $query->whereHas('contactFieldType', function ($query) {
            $query->where('type', '=', ContactFieldType::EMAIL);
        });
    }

    /**
     * Scope a query to only include contact field of phone type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePhone($query)
    {
        return $query->whereHas('contactFieldType', function ($query) {
            $query->where('type', '=', ContactFieldType::PHONE);
        });
    }
}
