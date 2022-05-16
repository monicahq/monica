<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $label
 * @property string $label_i18n
 */
class ContactFieldLabel extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    protected $table = 'contact_field_labels';

    /** @var array<string> */
    public static $standardLabels = [
        'home',
        'work',
        'cell',
        'fax',
        'pager',
        'main',
        'other',
    ];

    /**
     * Get the account record associated with the contact field type.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
