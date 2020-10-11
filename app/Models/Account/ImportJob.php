<?php

namespace App\Models\Account;

use App\Models\User\User;
use Sabre\VObject\Reader;
use Illuminate\Support\Arr;
use Sabre\VObject\Component\VCard;
use App\Services\VCard\ImportVCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Sabre\VObject\Splitter\VCard as VCardReader;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

/**
 * @property int $id
 * @property Account $account
 * @property int $account_id
 * @property User $user
 * @property int $user_id
 * @property bool $failed
 * @property string $failed_reason
 * @property string $filename
 * @property int $contacts_found
 * @property int $contacts_skipped
 * @property int $contacts_imported
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 */
class ImportJob extends Model
{
    const VCARD_SKIPPED = true;
    const VCARD_IMPORTED = false;

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
     * @return void
     */
    public function process($behaviour = ImportVCard::BEHAVIOUR_ADD)
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
    private function initJob(): void
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
    private function endJob(): void
    {
        $this->ended_at = now();
        $this->save();
    }

    /**
     * Mark the import job as failed.
     *
     * @param string $reason
     *
     * @return void
     */
    private function fail(string $reason): void
    {
        $this->failed = true;
        $this->failed_reason = $reason;
        $this->endJob();
    }

    /**
     * Get the physical file (the vCard file).
     *
     * @return self
     */
    private function getPhysicalFile()
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
     * @return void
     */
    private function deletePhysicalFile(): void
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
    private function getEntries()
    {
        $this->entries = new VCardReader($this->physicalFile, Reader::OPTION_FORGIVING + Reader::OPTION_IGNORE_INVALID_LINES);
    }

    /**
     * Process all entries contained in the vCard file.
     *
     * @param string $behaviour
     * @return void
     */
    private function processEntries($behaviour = ImportVCard::BEHAVIOUR_ADD)
    {
        while (true) {
            try {
                $entry = $this->entries->getNext();
                if (! $entry) {
                    // file end
                    break;
                }
                $this->contacts_found++;
            } catch (\Throwable $e) {
                $this->skipEntry('?', (string) $e);
                continue;
            }

            $this->processSingleEntry($entry, $behaviour);
        }

        if ($this->contacts_found == 0) {
            $this->fail(trans('settings.import_vcard_file_no_entries'));
        }
    }

    /**
     * Process a single vCard entry.
     *
     * @param  string|VCard $entry
     * @param  string $behaviour
     * @return void
     */
    private function processSingleEntry($entry, $behaviour = ImportVCard::BEHAVIOUR_ADD): void
    {
        try {
            $result = app(ImportVCard::class)->execute([
                'account_id' => $this->account_id,
                'user_id' => $this->user_id,
                'entry' => $entry,
                'behaviour' => $behaviour,
            ]);
        } catch (ValidationException $e) {
            $this->fail(implode(',', $e->validator->errors()->all()));

            return;
        }

        if (Arr::has($result, 'error') && ! empty($result['error'])) {
            $this->skipEntry($result['name'], $result['reason']);

            return;
        }

        $this->contacts_imported++;
        $this->fileImportJobReport($result['name'], self::VCARD_IMPORTED);
    }

    /**
     * Skip the current entry.
     *
     * @param  string $name
     * @param  string $reason
     * @return void
     */
    private function skipEntry($name, $reason = null): void
    {
        $this->fileImportJobReport($name, self::VCARD_SKIPPED, $reason);
        $this->contacts_skipped++;
    }

    /**
     * File an import job report for the current entry.
     *
     * @param  string $name
     * @param  bool $status
     * @param  string $reason
     * @return void
     */
    private function fileImportJobReport($name, $status, $reason = null): void
    {
        $importJobReport = new ImportJobReport;
        $importJobReport->account_id = $this->account_id;
        $importJobReport->user_id = $this->user_id;
        $importJobReport->import_job_id = $this->id;
        $importJobReport->contact_information = trim($name);
        $importJobReport->skipped = $status;
        $importJobReport->skip_reason = $reason;
        $importJobReport->save();
    }
}
