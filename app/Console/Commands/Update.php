<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:update {--force} {--composer-install} {--dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update monica dependencies and migrations after a new release';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->confirmToProceed()) {
            $this->artisan('✓ Resetting config cache', 'config:cache');
            try {
                $this->artisan('✓ Maintenance mode: on', 'down', [
                    '--message' => 'Upgrading Monica v'.config('monica.app_version'),
                    '--retry' => '10',
                    ]);

                if ($this->option('composer-install') === true) {
                    $this->exec('✓ Updating composer dependencies', 'composer install --no-interaction --no-suggest --ignore-platform-reqs'.($this->option('composer-install') === false ? '--no-dev' : ''));
                }

                $this->artisan('✓ Performing migrations', 'migrate', ['--force' => 'true']);

                if (DB::table('activity_types')->count() == 0) {
                    $this->artisan('✓ Filling the Activity Types table', 'db:seed', ['--class' => 'ActivityTypesTableSeeder', '--force' => 'true']);
                }
                if (DB::table('countries')->count() == 0) {
                    $this->artisan('✓ Filling the Countries table', 'db:seed', ['--class' => 'CountriesSeederTable', '--force' => 'true']);
                }
                if (! file_exists(public_path('storage'))) {
                    $this->artisan('✓ Symlink the storage folder', 'storage:link');
                }
            } finally {
                $this->artisan('✓ Maintenance mode: off', 'up');
            }

            $this->line('Monica v'.config('monica.app_version').' is set up, enjoy.');
        }
    }

    public function exec($message, $command)
    {
        $this->info($message);
        $this->line($command);
        exec($command.' 2>&1', $output);
        if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            foreach ($output as $line) {
                $this->line($line);
            }
        }
        $this->line('');
    }

    public function artisan($message, $command, array $arguments = [])
    {
        $this->info($message);
        $info = '';
        foreach ($arguments as $key => $value)
        {
            $info = $info.' '.$key.'='.$value;
        }
        $this->line('php artisan '.$command.$info);
        if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->call($command, $arguments);
        } else {
            $this->callSilent($command, $arguments);
        }
        $this->line('');
    }
}
