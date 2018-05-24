<?php

namespace App\Models\Settings\CustomFields;

use App\Account;
use App\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Settings\CustomFields\CustomFieldPattern;

class ContactCustomFieldPattern extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $table = 'contact_custom_field_patterns';

    /**
     * Get the account record associated with the contact custom field.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the custom field record associated with the contact custom field.
     *
     * @return BelongsTo
     */
    public function customFieldPattern()
    {
        return $this->belongsTo(CustomFieldPattern::class);
    }

    /**
     * Get the contact record associated with the contact custom field.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
