<?php

namespace App\Models\Journal;

use App\Models\Contact\Entry;
use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use App\Interfaces\IsJournalableInterface;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
     * Eager load with every entry.
     */
    protected $with = [
        'journalable',
    ];

    /**
     * Get all of the owning "journal-able" models.
     *
     * @return MorphTo
     */
    public function journalable()
    {
        return $this->morphTo();
    }

    /**
     * Get the account record associated with the journal entry.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Adds a new entry in the journal.
     *
     * @param \App\Interfaces\IsJournalableInterface $resourceToLog
     * @return self
     */
    public static function add(IsJournalableInterface $resourceToLog) : self
    {
        $journal = new self;
        $journal->account_id = $resourceToLog->account_id;
        $journal->date = now();
        if ($resourceToLog instanceof \App\Models\Account\Activity) {
            $journal->date = $resourceToLog->date_it_happened;
        } elseif ($resourceToLog instanceof \App\Models\Journal\Entry) {
            $journal->date = $resourceToLog->attributes['date'];
        }
        $journal->save();
        $resourceToLog->journalEntries()->save($journal);

        return $journal;
    }

    /**
     * Update an entry in the journal.
     *
     * @param \App\Interfaces\IsJournalableInterface $resourceToLog
     * @return self
     */
    public function edit(IsJournalableInterface $resourceToLog) : self
    {
        if ($resourceToLog instanceof \App\Models\Journal\Entry) {
            $this->date = $resourceToLog->attributes['date'];
        }
        $this->save();

        return $this;
    }

    /**
     * Get the information about the object represented by the Journal Entry.
     *
     * @return array
     */
    public function getObjectData()
    {
        // Instantiating the object
        /** @var IsJournalableInterface */
        $correspondingObject = $this->journalable;

        return $correspondingObject->getInfoForJournalEntry();
    }
}
