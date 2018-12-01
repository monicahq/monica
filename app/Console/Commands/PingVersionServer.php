<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace App\Console\Commands;

use GuzzleHttp\Client;
use App\Models\Contact\Contact;
use Illuminate\Console\Command;
use App\Models\Instance\Instance;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Output\OutputInterface;

class PingVersionServer extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:ping {--force}';

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
        if (! $this->confirmToProceed('Checking version deactivated', function () {
            return ! config('monica.check_version') && $this->getLaravel()->environment() == 'production';
        })) {
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
            $this->log('Call url:'.config('monica.weekly_ping_server_url'));
            $client = new Client();
            $response = $client->post(config('monica.weekly_ping_server_url'), [
                'json' => $data,
            ]);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $this->log('ConnectException...');

            return;
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            $this->log('TransferException...');

            return;
        }

        // Receive the JSON
        $json = json_decode($response->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON is invalid
            // The function json_last_error returns the last error occurred during the JSON encoding and decoding
            $this->log('json error...');

            return;
        }

        // make sure the JSON has all the fields we need
        if (! isset($json['latest_version']) || ! isset($json['new_version']) || ! isset($json['number_of_versions_since_user_version'])) {
            return;
        }

        $this->log('instance version:'.$instance->current_version);
        $this->log('current version:'.$json['latest_version']);
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

    public function log($string)
    {
        if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->info($string);
        }
    }
}
