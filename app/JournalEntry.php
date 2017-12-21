<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Account $account
 * @property User $invitedBy
 */
class JournalEntry extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $table = 'journal_entries';

    protected $dates = [
        'date',
    ];

    /**
     * Get all of the owning "journal-able" models.
     */
    public function journalable()
    {
        return $this->morphTo();
    }

    /**
     * Get the account record associated with the invitation.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function add($resourceToLog)
    {
        $this->account_id = $resourceToLog->account_id;
        $this->date = \Carbon\Carbon::now();
        $this->journalable_id = $resourceToLog->id;
        $this->journalable_type = get_class($resourceToLog);
        $this->save();

        return $this;
    }

    public function getObjectData()
    {
        $type = $this->journalable_type;

        // Instantiating the object
        $correspondingObject = (new $type)::findOrFail($this->journalable_id);

        return $correspondingObject->getInfoForJournalEntry();
    }
}
