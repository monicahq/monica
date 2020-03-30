<?php

namespace App\Console\Commands;

use App\Models\User\User;
use Illuminate\Http\File;
use Illuminate\Console\Command;
use App\Jobs\AddContactFromVCard;
use App\Models\Account\ImportJob;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class ImportVCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:vcard
                            {--user= : user to import the contacts}
                            {--path= : path of the file to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports contacts from vCard files for a specific user';

    /**
     * Execute the console command.
     *
     * @param Filesystem $filesystem
     * @return mixed
     */
    public function handle(Filesystem $filesystem)
    {
        $email = $this->option('user');

        // if no email was passed to the option, prompt the user to enter the email
        if (! $email) {
            $email = $this->ask('what is the user\'s email?');
        }

        // retrieve the user with the specified email
        $user = User::where('email', $email)->first();

        if (! $user) {
            // show an error and exist if the user does not exist
            $this->error('No user with that email.');

            return;
        }

        $path = $this->option('path');

        // if no email was passed to the option, prompt the user to enter the email
        if (! $path) {
            $path = $this->ask('what file you want to import?');
        }

        if (! $filesystem->exists($path) || ! $this->acceptedExtensions($filesystem, $path)) {
            $this->error('The provided vcard file was not found or is not valid!');

            return;
        }

        $importJob = $this->import($path, $user);

        return $this->report($importJob) ? 0 : 1;
    }

    private function acceptedExtensions(Filesystem $filesystem, string $path): bool
    {
        switch ($filesystem->extension($path)) {
            case 'vcf':
            case 'vcard':
                return true;
            default:
                return false;
        }
    }

    private function import(string $path, User $user): ImportJob
    {
        $pathName = Storage::putFile('public', new File($path));

        $importJob = $user->account->importjobs()->create([
            'user_id' => $user->id,
            'type' => 'vcard',
            'filename' => $pathName,
        ]);

        dispatch_now(new AddContactFromVCard($importJob));

        return $importJob;
    }

    private function report(ImportJob $importJob)
    {
        $importJob->refresh();

        if ($importJob->failed) {
            $this->warn('Error: '.$importJob->failed_reason);

            return false;
        }

        $this->info('Contacts found: '.$importJob->contacts_found);
        $this->info('Contacts skipped: '.$importJob->contacts_skipped);
        $this->info('Contacts imported: '.$importJob->contacts_imported);

        return true;
    }
}
