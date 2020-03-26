<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account\AddressBook;
use App\Jobs\SynchronizeAddressBooks;
use App\Services\DavClient\AddAddressBook;

class DavClientsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:davclients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all dav subscriptions';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //app(AddAddressBook::class)->execute([]);
        //return;

        $addressBooks = AddressBook::all();

        foreach ($addressBooks as $addressBook)
        {
            SynchronizeAddressBooks::dispatch($addressBook);
        }
    }
}
