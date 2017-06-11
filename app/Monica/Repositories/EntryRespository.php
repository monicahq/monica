<?php
namespace Monica\Repositories;
use App\Entry;

class EntryRepository{

  public function getJournalEntriesByAccount( $accountID )
  {
      $entries = Entry::where('account_id', $accountID )
                    ->orderBy('created_at', 'desc')
                    ->get();
      return $entries;
  }


}
?>
