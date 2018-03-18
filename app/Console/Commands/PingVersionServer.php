<?php

namespace App\Console\Commands;

use App\Contact;
use App\Instance;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! config('monica.check_version')) {
            return false;
        }

        if (! \App::environment('production')) {
            return false;
        }

        $instance = Instance::first();

        // Prepare the json to query version.monicahq.com
        $json = [
            'uuid' => $instance->uuid,
            'version' => $instance->current_version,
            'contacts' => Contact::count(),
        ];

        $data = [
            'uuid' => $instance->uuid,
            'version' => $instance->current_version,
            'contacts' => Contact::all()->count(),
        ];

        // Send the JSON
        try {
            $client = new Client();
            $response = $client->post(config('monica.weekly_ping_server_url'), [
                'json' => $data,
            ]);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return;
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            return;
        }

        // Receive the JSON
        $json = json_decode($response->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON is invalid
            // The function json_last_error returns the last error occurred during the JSON encoding and decoding
            return;
        }

        // make sure the JSON has all the fields we need
        if (! isset($json['latest_version']) || ! isset($json['new_version']) || ! isset($json['number_of_versions_since_user_version'])) {
            return;
        }

        if ($json['latest_version'] != $instance->current_version) {
            $instance->latest_version = $json['latest_version'];
            $instance->latest_release_notes = $json['notes'];
            $instance->number_of_versions_since_current_version = $json['number_of_versions_since_user_version'];
            $instance->save();
        } else {
            $instance->latest_release_notes = null;
            $instance->number_of_versions_since_current_version = null;
            $instance->save();
        }
    }
}
