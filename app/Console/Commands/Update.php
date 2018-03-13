<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class Update extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:update {--force} {--update-composer}';

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
                $this->artisan('✓ Maintenance mode on', 'down', [
                    '--message' => 'Upgrading Monica v'.config('monica.app_version'),
                    '--retry' => '10',
                    ]);

                if ($this->option('update-composer') === true) {
                    $this->exec('✓ Updating composer dependencies', 'composer install --no-interaction --no-suggest --no-dev');
                }

                $this->artisan('✓ Performing migrations', 'migrate', ['--force' => true]);

                $this->artisan('✓ Filling the Activity Types table', 'db:seed', ['--class' => 'ActivityTypesTableSeeder', '--force' => true]);
                $this->artisan('✓ Filling the Countries table', 'db:seed', ['--class' => 'CountriesSeederTable', '--force' => true]);
                $this->artisan('✓ Symlink the storage folder', 'storage:link');
            } finally {
                $this->artisan('✓ Maintenance mode off', 'up');
            }

            $this->line('Monica v'.config('monica.app_version').' is set up, enjoy.');
        }
    }

    public function exec($message, $command)
    {
        $this->info($message);
        $this->line($command);
        exec($command, $output);
        foreach ($output as $line) {
            $this->line($line);
        }
        $this->line('');
    }

    public function artisan($message, $command, array $arguments = [])
    {
        $this->info($message);
        $this->line('php artisan '.$command);
        $this->callSilent($command, $arguments);
        $this->line('');
    }
}
