<?php

namespace App\Console\Commands;

use App\User;
use App\Traits\VCardImporter;
use Illuminate\Console\Command;
use Sabre\VObject\Component\VCard;
use Illuminate\Filesystem\Filesystem;

class ImportVCards extends Command
{
    use VCardImporter;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:vcard {user} {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports contacts from vCard files for a specific user';

    private $path;

    /**
     * Execute the console command.
     *
     * @param Filesystem $filesystem
     * @return mixed
     */
    public function handle(Filesystem $filesystem)
    {
        $this->path = './'.$this->argument('path');

        $user = User::where('email', $this->argument('user'))->first();

        if (! $user) {
            $this->error('You need to provide a valid user email!');

            return;
        }

        if (! $filesystem->exists($this->path) || $filesystem->extension($this->path) !== 'vcf') {
            $this->error('The provided vcard file was not found or is not valid!');

            return;
        }

        $this->work($user->account_id, $filesystem->get($this->path));
    }

    protected function workInit($matchCount)
    {
        $this->info("We found {$matchCount} contacts in {$this->path}.");

        if (! $this->confirm('Would you like to import them?', true)) {
            return false;
        }

        $this->info("Importing contacts from {$this->path}");
        $this->output->progressStart($matchCount);

        return true;
    }

    protected function workContactExists($vcard)
    {
        $this->output->progressAdvance();
    }

    protected function workContactNoFirstname($vcard)
    {
        $this->output->progressAdvance();
    }

    protected function workNext($vcard)
    {
        $this->output->progressAdvance();
    }

    protected function workEnd($numberOfContactsInTheFile, $skippedContacts, $importedContacts)
    {
        $this->output->progressFinish();

        $this->info("Successfully imported {$importedContacts} contacts and skipped {$skippedContacts}.");
    }
}
