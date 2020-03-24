<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DavClient\SynchronizeAddressBook;

class DavClientsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:dav';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        app(SynchronizeAddressBook::class)->execute([]);
    }
}
