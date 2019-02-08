<?php

namespace App\Models\Contact;

use Parsedown;
use App\Helpers\DateHelper;
use App\Models\Account\Account;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ModelBindingWithContact as Model;
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
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_favorited' => 'boolean',
    ];

    protected $dates = [
        'favorited_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'contact_id',
        'body',
        'is_favorited',
    ];

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
     * Limit notes to favorited ones.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeFavorited(Builder $query)
    {
        return $query->where('is_favorited', true);
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
     * @return string
     */
    public function getCreatedAt()
    {
        return DateHelper::getShortDate($this->created_at);
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
