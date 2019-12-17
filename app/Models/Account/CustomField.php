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

class CustomField extends Model
{
    use Journalable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'custom_fields';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the account record associated with the custom field.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
