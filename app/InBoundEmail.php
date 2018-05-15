<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InBoundEmail extends Model
{
    protected $dates = [
        'sent',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content',
        'subject',
        'to',
        'from',
        'account_id',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the user associated with the email.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    /**
     * Get the Contact records associated with the email.
     *
     * @return HasMany
     */
    public function emails()
    {
        return $this->hasMany('App\Contact', 'email_id');
    }

    /**
     * Link to a to a contact
     *
     * @param Contact $contact
     */
    public function setToContact($contact)
    {
        $contact_email = new ContactInBoundEmail;
        $contact_email->account_id = $this->account_id;
        $contact_email->email_id = $this->id;
        $contact_email->contact_id = $contact->id;
        $contact_email->save();
    }

    public function setSentAttribute($value)
    {
        $this->attributes['sent'] = Carbon::parse($value);
    }

}
