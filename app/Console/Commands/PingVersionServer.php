<?php

namespace App\Console\Commands;

use Log;
use App\Contact;
use App\Instance;
use Illuminate\Console\Command;
use GuzzleHttp\Client;

class PingVersionServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:ping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ping version.monicahq.com to know if a new version is available';

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
        if (config('monica.check_version') == false) {
            return;
        }

        $instance = Instance::first();

        // Prepare the json to query version.monicahq.com
        $json = [
            'uuid' => $instance->uuid,
            'version' => $instance->current_version,
            'contacts' => Contact::all()->count()
        ];

        $data["uuid"] = $instance->uuid;
        $data["version"] = $instance->current_version;
        $data["contacts"] = Contact::all()->count();

        // Send the JSON
        $jsonData =json_encode($data);
        $json_url = config('monica.weekly_ping_server_url');
        $ch = curl_init( $json_url);

        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Content-type: application/json') ,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_POST => true
        );

        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result == false) {
            return;
        }

        if (is_string($result) == false) {
            return;
        }

        // Receive the JSON
        $json = json_decode($result, true);

        if ($json['latest_version'] != $instance->current_version) {
            $instance->latest_version = $json['latest_version'];
            $instance->latest_release_notes = $json['notes'];
            $instance->number_of_versions_since_current_version = $json['number_of_versions_since_user_version'];
            $instance->save();
        }
    }
}
