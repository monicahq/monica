<?php

namespace App\Jobs;

use App\ImportJob;
use App\ImportJobReport;
use App\Traits\VCardImporter;
use Illuminate\Bus\Queueable;
use Sabre\VObject\Component\VCard;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AddContactFromVCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, VCardImporter;

    const VCARD_SKIPPED = 1;
    const VCARD_IMPORTED = 0;

    const ERROR_CONTACT_EXIST = 'import_vcard_contact_exist';
    const ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME = 'import_vcard_contact_no_firstname';

    protected $importJob;
    private $matchCount;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ImportJob $importJob)
    {
        $this->importJob = $importJob;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->work($this->importJob->account_id, Storage::disk('public')->get($this->importJob->filename));
        } catch (\Exception $e) {
            $this->importJob->contacts_found = $this->matchCount;
            $this->importJob->failed = 1;
            $this->importJob->failed_reason = $e->getMessage();
            $this->importJob->save();

            Storage::disk('public')->delete($this->importJob->filename);
        }
    }

    protected function workInit($matchCount)
    {
        $this->matchCount = $matchCount;
        $this->importJob->started_at = now();

        return true;
    }

    protected function workContactExists($vcard)
    {
        $this->fileImportJobReport($vcard, self::VCARD_SKIPPED, self::ERROR_CONTACT_EXIST);
    }

    protected function workContactNoFirstname($vcard)
    {
        $this->fileImportJobReport($vcard, self::VCARD_SKIPPED, self::ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME);
    }

    protected function workNext($vcard)
    {
        $this->fileImportJobReport($vcard, self::VCARD_IMPORTED);
    }

    protected function workEnd($numberOfContactsInTheFile, $skippedContacts, $importedContacts)
    {
        $this->importJob->contacts_found = $numberOfContactsInTheFile;
        $this->importJob->contacts_skipped = $skippedContacts;
        $this->importJob->contacts_imported = $importedContacts;
        $this->importJob->ended_at = now();
        $this->importJob->save();

        Storage::disk('public')->delete($this->importJob->filename);
    }

    private function fileImportJobReport(VCard $vcard, $status, $reason = null)
    {
        $name = $this->formatValue($vcard->N->getParts()[1]);
        $name .= ' '.$this->formatValue($vcard->N->getParts()[2]);
        $name .= ' '.$this->formatValue($vcard->N->getParts()[0]);
        $name .= ' '.$this->formatValue($vcard->EMAIL);

        $importJobReport = new ImportJobReport;
        $importJobReport->account_id = $this->importJob->account_id;
        $importJobReport->user_id = $this->importJob->user_id;
        $importJobReport->import_job_id = $this->importJob->id;
        $importJobReport->contact_information = trim($name);
        $importJobReport->skipped = $status;
        $importJobReport->skip_reason = $reason;
        $importJobReport->save();
    }
}
