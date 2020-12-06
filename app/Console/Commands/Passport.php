<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Laravel\Passport\PersonalAccessClient;
use Symfony\Component\Console\Output\OutputInterface;

class Passport extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:passport {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if encryption keys and Personal Access Client are present, and create them if not.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if ($this->confirmToProceed()) {
            $this->checkEncryptionKeys();
            $this->checkPersonalAccessClient();
        }
    }

    private function checkEncryptionKeys()
    {
        $this->info('Checking encryption keys...', OutputInterface::VERBOSITY_VERBOSE);

        if (! empty(config('passport.private_key')) && ! empty(config('passport.public_key'))) {
            $this->info('✓ PASSPORT_PRIVATE_KEY and PASSPORT_PUBLIC_KEY detected.', OutputInterface::VERBOSITY_VERBOSE);

            return;
        }

        if (file_exists(base_path('storage/oauth-private.key')) && file_exists(base_path('storage/oauth-public.key'))) {
            $this->info('✓ Files storage/oauth-private.key and storage/oauth-public.key detected.', OutputInterface::VERBOSITY_VERBOSE);

            return;
        }

        $this->artisan('✓ Creating encryption keys', 'passport:keys', ['--no-interaction']);
        $this->warn('! Please be careful to backup '.base_path('storage/oauth-public.key').' and '.base_path('storage/oauth-private.key').' files !', OutputInterface::VERBOSITY_VERBOSE);
    }

    private function checkPersonalAccessClient()
    {
        $this->info('Checking Personal Access Client...', OutputInterface::VERBOSITY_VERBOSE);

        if (PersonalAccessClient::count() > 0) {
            $this->info('✓ Personal Access Client already created.', OutputInterface::VERBOSITY_VERBOSE);

            return;
        }

        $this->artisan('✓ Creating personal access client', 'passport:client', ['--personal', '--no-interaction']);
    }
}
