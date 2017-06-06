<?php

namespace App\Console\Commands;

use App\User;
use App\Contact;
use Illuminate\Console\Command;

class ImportCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv {user} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports CSV in Google format to user account';

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
        $file = $this->argument('file');
        $user = User::find($this->argument('user'));
        $this->info("Importing CSV file $file to user {$user->id}");
        $row = 0;
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle)) !== FALSE) {
                $row++;
                
                // don't import the columns
                if ( $row == 1 ) { continue; }

                $contact = new Contact();
                $contact->account_id = $user->id;
                $contact->first_name = $data[1];    // Given Name
                $contact->middle_name = $data[2];   // Additional Name
                $contact->last_name = $data[3];     // Family Name
                $contact->birthdate = date('Y-m-d', strtotime($data[14]));
                $contact->email = $data[28];        // Email 1 Value
                $contact->phone_number = $data[42]; // Phone 1 Value
                $contact->street = $data[49];       // address 1 street
                $contact->city = $data[50];         // address 1 city
                $contact->province = $data[52];     // address 1 region (state)
                $contact->postal_code = $data[53];  // address 1 postal code (zip) 53
                $contact->job = $data[66];          // organization 1 name 66
             
                // can't have empty email
                if ( empty( $contact->email ) ) {
                    $contact->email = NULL;
                }

                $contact->save();
            }
            fclose($handle);
        }        
    }
}
