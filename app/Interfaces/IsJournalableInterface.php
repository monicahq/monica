<?php

namespace App\Interfaces;

interface IsJournalableInterface
{
    public function getInfoForJournalEntry();

    public function deleteJournalEntry();
}
