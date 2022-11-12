<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
class SetupDocumentation extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scribe:setup
                            {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install or update the application, and run migrations after a new release';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ($this->confirmToProceed()) {
            $this->documentation();
        }
    }

    /**
     * Regenerate api documentation.
     *
     * @return void
     */
    protected function documentation(): void
    {
        putenv('DB_CONNECTION=docs');
        putenv('APP_ENV=testing');
        putenv('CACHE_DRIVER=array');
        putenv('QUEUE_CONNECTION=sync');
        putenv('SESSION_DRIVER=array');
        putenv('MAIL_MAILER=log');
        putenv('SCOUT_DRIVER=null');

        exec('php artisan scribe:generate --verbose --force', $output);

        if ($this->getOutput()->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            foreach ($output as $line) {
                $this->line($line);
            }
            $this->line('');
        }
    }
}
