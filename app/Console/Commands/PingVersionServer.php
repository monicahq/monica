<?php

namespace App\Console\Commands;

use PharIo\Version\Version;
use App\Models\Contact\Contact;
use Illuminate\Console\Command;
use App\Models\Instance\Instance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Http\Client\RequestException;
use Symfony\Component\Console\Output\OutputInterface;

class PingVersionServer extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:ping
                            {--force : Force the operation to run when in production.}';

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
            return;
        }

        if (! $this->confirmToProceed('Checking version deactivated', function () {
            return $this->getLaravel()->environment() === 'production';
        })) {
            return false;
        }

        $instance = Instance::first();
        $instance->current_version = config('monica.app_version');

        if ($instance->current_version == '') {
            Log::warning('Current instance version is not set, skipping version check.');

            return;
        }

        // Query version.monicahq.com
        try {
            $this->log('Call url: '.config('monica.weekly_ping_server_url'));
            $response = Http::acceptJson()
                ->post(config('monica.weekly_ping_server_url'), [
                    'uuid' => $instance->uuid,
                    'version' => $instance->current_version,
                    'contacts' => Contact::count(),
                ])
                ->throw();
        } catch (RequestException $e) {
            $this->error('Error calling "'.config('monica.weekly_ping_server_url').'": '.$e->getMessage());
            Log::error(__CLASS__.' Error calling "'.config('monica.weekly_ping_server_url').'": '.$e->getMessage(), [$e]);

            return;
        }

        // Receive the JSON
        $json = $response->json();

        $this->log('instance version: '.$instance->current_version);
        $currentVersion = $this->getVersion($instance->current_version);

        $this->log('current version: '.$json['latest_version']);
        $latestVersion = $this->getVersion($json['latest_version']);

        if ($latestVersion > $currentVersion) {
            $instance->latest_version = $json['latest_version'];
            $instance->latest_release_notes = $json['notes'];
            $instance->number_of_versions_since_current_version = $json['number_of_versions_since_user_version'];
        } else {
            $instance->latest_release_notes = null;
            $instance->number_of_versions_since_current_version = null;
        }

        $instance->save();
    }

    public function log($string)
    {
        $this->info($string, OutputInterface::VERBOSITY_VERBOSE);
    }

    private function getVersion(string $version): ?Version
    {
        try {
            return new Version($version);
        } catch (\Exception $e) {
            $this->error("Error parsing version '$version': ".$e->getMessage());
        }

        return null;
    }
}
