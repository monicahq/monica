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
        $imported = 0;
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle)) !== FALSE) {
                $row++;
                $has_data = false;

                // don't import the columns
                if ( $row == 1 ) { continue; }

                $contact = new Contact();
                $contact->account_id = $user->id;

                if ( ! empty( $data[1] ) ) {
                    $contact->first_name = $data[1];    // Given Name
                    $has_data = true;
                }

                if ( ! empty( $data[2] ) ) {
                    $contact->middle_name = $data[2];   // Additional Name
                    $has_data = true;
                }

                if ( ! empty( $data[3] ) ) {
                    $contact->last_name = $data[3];     // Family Name
                    $has_data = true;
                }

                if ( ! empty( $data[14] ) ) {
                    $contact->birthdate = date('Y-m-d', strtotime($data[14]));
                }

                // gender required - default to female
                $contact->gender = ( substr($data[15], 0, 1 ) == 'm' ) ? 'male' : 'female';

                if ( ! empty( $data[28] ) ) {
                    $contact->email = $data[28];        // Email 1 Value
                }

                if ( ! empty( $data[42 ] ) ) {
                    $contact->phone_number = $data[42]; // Phone 1 Value
                }

                if ( ! empty( $data[49] ) ) {
                    $contact->street = $data[49];       // address 1 street
                }

                if ( ! empty( $data[50] ) ) {
                    $contact->city = $data[50];         // address 1 city
                }
                if ( ! empty( $data[52] ) ) {
                    $contact->province = $data[52];     // address 1 region (state)
                }

                if ( ! empty( $data[53] ) ) {
                    $contact->postal_code = $data[53];  // address 1 postal code (zip) 53
                }
                if ( ! empty( $data[66] ) ) {
                    $contact->job = $data[66];          // organization 1 name 66
                }
             
                // can't have empty email
                if ( empty( $contact->email ) ) {
                    $contact->email = NULL;
                }

                if ( $has_data ) {
                    $contact->save();
                    $contact->setAvatarColor();
                    $imported++;
                }
            }
            fclose($handle);
        }
        $this->info("Imported {$imported} Contacts");
    }
}
