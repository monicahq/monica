<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface IsJournalableInterface
{
    /**
     * Get all journal entries.
     *
     * @return MorphMany
     */
    public function journalEntries();

    /**
     * Get the journal record associated.
     *
     * @return MorphOne
     */
    public function journalEntry();

    /**
     * Get all the information of the Entry for the journal.
     *
     * @return array
     */
    public function getInfoForJournalEntry();

    /**
     * Delete the Journal Entry associated with the given object.
     *
     * @return bool
     */
    public function deleteJournalEntry();
}
