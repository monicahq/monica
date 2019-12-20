<?php

namespace App\Services\Account\Settings;

use App\Helpers\DBHelper;
use App\Models\User\User;
use Illuminate\Support\Str;
use App\Services\BaseService;
use App\Models\Account\Account;
use App\Models\Contact\Document;
use Illuminate\Support\Facades\DB;
use App\Exceptions\NoAccountException;
use App\Services\Account\Settings\DestroyAllDocuments;
use Illuminate\Support\Facades\Storage;

class ResetAccount extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
        ];
    }

    /**
     * Reset the account.
     *
     * @param array $data
     * @return void
     */
    public function execute(array $data): void
    {
        $this->validate($data);

        $account = Account::find($data['account_id']);

        $this->destroyCompanies($account);

        $this->destroyDays($account);

        $this->destroyPlaces($account);

        $this->destroyDocuments($account);

        $this->destroyPhotos($account);

        $this->destroyJournalEntries($account);

        $this->destroyImportJobs($account);

        $this->destroyContacts($account);
    }

    /**
     * Destroy the companies.
     *
     * @param Account $account
     * @return void
     */
    private function destroyCompanies(Account $account)
    {
        $companies = $account->companies;
        foreach ($companies as $company) {
            $company->delete;
        }
    }

    /**
     * Destroy the days.
     *
     * @param Account $account
     * @return void
     */
    private function destroyDays(Account $account)
    {
        $days = $account->days;
        foreach ($days as $day) {
            $day->delete;
        }
    }

    /**
     * Destroy the places.
     *
     * @param Account $account
     * @return void
     */
    private function destroyPlaces(Account $account)
    {
        $places = $account->places;
        foreach ($places as $place) {
            $place->delete;
        }
    }

    /**
     * Destroy the documents.
     *
     * @param Account $account
     * @return void
     */
    private function destroyDocuments(Account $account)
    {
        app(DestroyAllDocuments::class)->execute([
            'account_id' => $account->id,
        ]);
    }

    /**
     * Destroy the photos.
     *
     * @param Account $account
     * @return void
     */
    private function destroyPhotos(Account $account)
    {
        app(DestroyAllPhotos::class)->execute([
            'account_id' => $account->id,
        ]);
    }

    /**
     * Destroy the journal entries associated with all the contacts of this
     * account.
     *
     * @param Account $account
     * @return void
     */
    private function destroyJournalEntries(Account $account)
    {
        $entries = $account->entries;
        foreach ($entries as $entry) {
            $entry->delete;
        }

        $activities = $account->activities;
        foreach ($activities as $activity) {
            $entries = $activity->journalEntries;
            foreach ($entries as $entry) {
                $entry->delete();
            }

            $activity->delete();
        }
    }

    /**
     * Destroy the import jobs.
     *
     * @param Account $account
     * @return void
     */
    private function destroyImportJobs(Account $account)
    {
        $importjobs = $account->importjobs;
        foreach ($importjobs as $importjob) {
            $importjob->delete();
        }
    }

    /**
     * Destroy all the contacts associated with this account.
     *
     * @param Account $account
     * @return void
     */
    private function destroyContacts(Account $account)
    {
        $contacts = $account->contacts;
        foreach ($contacts as $contact) {
            $contact->delete();
        }
    }
}
