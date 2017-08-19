<?php

namespace App;

use Parsedown;
use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Account $account
 * @property Contact $contact
 * @property string $parsed_body
 */
class Note extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the account record associated with the note.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the note.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Return the markdown parsed body.
     *
     * @return string
     */
    public function getParsedBodyAttribute()
    {
        return (new Parsedown())->text($this->body);
    }

    /**
     * Get the description of a note.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Gets the activity date for this note.
     *
     * @param  string $locale
     * @return string
     */
    public function getCreatedAt($locale)
    {
        return DateHelper::getShortDate($this->created_at, $locale);
    }

    /**
     * Gets the content of the activity and formats it for the email.
     *
     * @return string
     */
    public function getContent()
    {
        return wordwrap($this->getBody(), 75);
    }
}
