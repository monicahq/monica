<?php

namespace App\Console\Commands;

use DB;
use App\Contact;
use Illuminate\Console\Command;

class MigratePeopleInformation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:migratecontacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One time use only. Remove encrypt for first/last names';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $contacts = Contact::all();

        foreach ($contacts as $contact) {
            $contact->first_name = decrypt($contact->first_name);

            if (! is_null($contact->middle_name)) {
                $contact->middle_name = decrypt($contact->middle_name);
            }

            if (! is_null($contact->last_name)) {
                $contact->last_name = decrypt($contact->last_name);
            }

            $contact->save();
        }
    }
}
