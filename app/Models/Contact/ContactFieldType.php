<?php

namespace App\Models\Contact;

use App\Traits\HasUuid;
use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactFieldType extends Model
{
    use HasUuid;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    protected $table = 'contact_field_types';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'delible' => 'boolean',
    ];

    /**
     * Email type contact field.
     *
     * @var string
     */
    public const EMAIL = 'email';

    /**
     * Phone type contact field.
     *
     * @var string
     */
    public const PHONE = 'phone';

    /**
     * Get the account record associated with the contact field type.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the conversations associated with the contact field type.
     *
     * @return HasMany
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
