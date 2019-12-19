<?php

namespace App\Models\Account;

use Parsedown;
use App\Helpers\DateHelper;
use App\Traits\Journalable;
use App\Models\Contact\Contact;
use App\Models\Journal\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use App\Models\Instance\Emotion\Emotion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

class ContactFieldValue extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contact_field_values';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the contact record associated with the field value.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the field record associated with the field value.
     *
     * @return BelongsTo
     */
    public function field()
    {
        return $this->belongsTo(Field::class);
    }
}
