<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\Account\AddressBook;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\DavClient\SynchronizeAddressBook;

class SynchronizeAddressBooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $addressBook;

    /**
     * Create a new job instance.
     *
     * @param AddressBook $addressBook
     * @return void
     */
    public function __construct(AddressBook $addressBook)
    {
        $this->addressBook = $addressBook;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app(SynchronizeAddressBook::class)->execute([
            'account_id' => $this->addressBook->account_id,
            'user_id' => $this->addressBook->user_id,
            'addressbook_id' => $this->addressBook->id,
            /*
            'addressbook' => $this->addressBook->uri,
            'addressBookId' => $this->addressBook->addressBookId,
            'username' => $this->addressBook->username,
            'password' => $this->addressBook->password,
            'localSyncToken' => null, // TODO
            'syncToken' => $this->addressBook->syncToken,
            'capabilities' => $this->addressBook->capabilities,
            */
        ]);
    }
}
