<?php

namespace App\Models\Contact;

use App\Traits\HasUuid;
use App\Helpers\DateHelper;
use App\Models\Account\Account;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ModelBindingWithContact as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Account $account
 * @property Contact $contact
 * @property string $parsed_body
 * @property string $body
 * @property bool $is_favorited
 * @property \Illuminate\Support\Carbon|null $favorited_at
 */
class Note extends Model
{
    use HasUuid;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
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
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'contact_id',
        'body',
        'is_favorited',
    ];

    /**
     * Eager load with every note.
     */
    protected $with = [
        'account',
        'contact',
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
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeFavorited(Builder $query)
    {
        return $query->where('is_favorited', true);
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
