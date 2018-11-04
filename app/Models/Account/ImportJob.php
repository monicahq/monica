<?php

namespace App\Models\Account;

use Exception;
use App\Models\User\User;
use Sabre\VObject\Reader;
use Sabre\VObject\Component\VCard;
use App\Services\VCard\ImportVCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

/**
 * @property Account $account
 * @property User $user
 */
class ImportJob extends Model
{
    const VCARD_SKIPPED = 1;
    const VCARD_IMPORTED = 0;

    protected $table = 'import_jobs';

    /**
     * The physical vCard file on disk.
     */
    public $physicalFile;

    /**
     * All individual entries in the vCard file.
     */
    public $entries;

    /**
     * The current entry that is being processed.
     *
     * @var VCard
     */
    public $currentEntry;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['started_at', 'ended_at'];

    /**
     * @var ImportVCard
     */
    private $service;

    /**
     * Get the account record associated with the import job.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user record associated with the import job.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the import jobs reports records associated with the account.
     *
     * @return HasMany
     */
    public function importJobReports()
    {
        return $this->hasMany(ImportJobReport::class);
    }

    /**
     * Process an import job.
     *
     * @return [type] [description]
     */
    public function process($behaviour = self::BEHAVIOUR_ADD)
    {
        $this->initJob();

        $this->getPhysicalFile();

        $this->getEntries();

        $this->processEntries($behaviour);

        $this->deletePhysicalFile();

        $this->endJob();
    }

    /**
     * Perform preliminary steps to start the import job.
     *
     * @return void
     */
    public function initJob(): void
    {
        $this->started_at = now();
        $this->contacts_imported = 0;
        $this->contacts_skipped = 0;
        $this->save();
    }

    /**
     * Perform the steps to finalize the import job.
     *
     * @return void
     */
    public function endJob(): void
    {
        $this->ended_at = now();
        $this->save();
    }

    /**
     * Mark the import job as failed.
     *
     * @param  string $reason
     * @return Exception
     */
    public function fail(string $reason)
    {
        $this->failed = true;
        $this->failed_reason = $reason;
        $this->endJob();
    }

    /**
     * Get the physical file (the vCard file).
     *
     * @return $this
     */
    public function getPhysicalFile()
    {
        try {
            $this->physicalFile = Storage::disk('public')->get($this->filename);
        } catch (FileNotFoundException $exception) {
            $this->fail(trans('settings.import_vcard_file_not_found'));
        }

        return $this;
    }

    /**
     * Delete the physical file from the disk.
     *
     * @return $this
     */
    public function deletePhysicalFile()
    {
        if (! Storage::disk('public')->delete($this->filename)) {
            $this->fail(trans('settings.import_vcard_file_not_found'));
        }
    }

    /**
     * Get the number of matches in the vCard file.
     *
     * @return void
     */
    public function getEntries()
    {
        $this->contacts_found = preg_match_all('/(BEGIN:VCARD.*?END:VCARD)/s',
                                                $this->physicalFile,
                                                $this->entries);

        $this->contacts_found = count($this->entries[0]);

        if ($this->contacts_found == 0) {
            $this->fail(trans('settings.import_vcard_file_no_entries'));
        }
    }

    /**
     * Process all entries contained in the vCard file.
     *
     * @return
     */
    public function processEntries($behaviour = self::BEHAVIOUR_ADD)
    {
        collect($this->entries[0])->map(function ($vcard) {
            return Reader::read($vcard);
        })->each(function (VCard $vCard) use ($behaviour) {
            $this->processSingleEntry($vCard, $behaviour);
        });
    }

    /**
     * Process a single vCard entry.
     *
     * @param  VCard  $vCard
     * @param  string $behaviour
     * @return Contact
     */
    public function processSingleEntry($vCard, $behaviour = self::BEHAVIOUR_ADD): Contact
    {
        $result = $this->getService()->execute([
            'user_id' => $this->user_id,
            'entry' => $vCard,
            'behaviour' => $behaviour
        ]);

        if ($result == ImportVCard::ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME
            || $result == ImportVCard::ERROR_CONTACT_EXIST) {
            $this->skipEntry($vCard, $result);

            return null;
        }

        $this->contacts_imported++;
        $this->fileImportJobReport($vCard, self::VCARD_IMPORTED);

        return $result;
    }

    /**
     * @return ImportVCard
     */
    private function getService()
    {
        if (! $this->service)
        {
            $this->service = new ImportVCard($this->account_id);
        }
        return $this->service;
    }

    /**
     * Skip the current entry.
     *
     * @param  VCard $entry
     * @param  string $reason
     * @return void
     */
    public function skipEntry($entry, $reason = null): void
    {
        $this->fileImportJobReport($entry, self::VCARD_SKIPPED, $reason);
        $this->contacts_skipped++;
    }

    /**
     * File an import job report for the current entry.
     *
     * @param  VCard $entry
     * @param  bool $status
     * @param  string $reason
     * @return void
     */
    public function fileImportJobReport($entry, $status, $reason = null): void
    {
        $name = $this->name($entry);

        $importJobReport = new ImportJobReport;
        $importJobReport->account_id = $this->account_id;
        $importJobReport->user_id = $this->user_id;
        $importJobReport->import_job_id = $this->id;
        $importJobReport->contact_information = trim($name);
        $importJobReport->skipped = $status;
        $importJobReport->skip_reason = $reason;
        $importJobReport->save();
    }

    /**
     * Return the name and email address of the current entry.
     * John Doe Johnny john@doe.com.
     *
     * @param  VCard $entry
     * @return string
     */
    private function name($entry): string
    {
        if ($this->hasFirstnameInN($entry)) {
            $name = $this->formatValue($entry->N->getParts()[1]);
            $name .= ' '.$this->formatValue($entry->N->getParts()[2]);
            $name .= ' '.$this->formatValue($entry->N->getParts()[0]);
            $name .= ' '.$this->formatValue($entry->EMAIL);
        } elseif ($this->hasNICKNAME($entry)) {
            $name = $this->formatValue($entry->NICKNAME);
            $name .= ' '.$this->formatValue($entry->EMAIL);
        } elseif ($this->hasFN($entry)) {
            $name = $this->formatValue($entry->FN);
            $name .= ' '.$this->formatValue($entry->EMAIL);
        } else {
            $name = trans('settings.import_vcard_unknown_entry');
        }

        return $name;
    }
}
