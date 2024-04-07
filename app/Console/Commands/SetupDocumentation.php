<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\passthru;
use function Safe\putenv;

/**
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'scribe:setup')]
class SetupDocumentation extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scribe:setup
                            {--clean : Remove database file after generating the documentation.}
                            {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the api documentation.';

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

        if (! File::exists($database = config('database.connections.docs.database'))) {
            File::put($database, '');
        }

        $v = $this->getVerbosity();
        $artisan = base_path('artisan');

        passthru(PHP_BINARY." $artisan migrate:fresh --force -q");
        passthru(PHP_BINARY." $artisan scribe:generate --force$v");

        if ($this->option('clean') && File::exists($database)) {
            File::delete($database);
        }
    }

    private function getVerbosity(): string
    {
        $verbosity = $this->getOutput()->getOutput()->getVerbosity();
        if ($verbosity >= OutputInterface::VERBOSITY_DEBUG) {
            return ' -vvv';
        }

        if ($verbosity >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            return ' -vv';
        }

        if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            return ' -v';
        }

        if ($verbosity <= OutputInterface::VERBOSITY_QUIET) {
            return ' -q';
        }

        return '';
    }
}
