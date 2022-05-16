<?php

namespace App\Models\Contact;

use App\Traits\HasUuid;
use App\Models\Account\Account;
use App\Models\ModelBindingHasher as Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    use HasUuid;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<string>
     */
    protected $dates = ['happened_at'];

    /**
     * Get the account record associated with the conversation.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the conversation.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the contact field type record associated with the conversation.
     *
     * @return BelongsTo
     */
    public function contactFieldType()
    {
        return $this->belongsTo(ContactFieldType::class);
    }

    /**
     * Get the Message records associated with the conversation.
     *
     * @return HasMany
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
