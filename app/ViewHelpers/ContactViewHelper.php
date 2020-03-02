<?php

namespace App\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Contact\Contact;
use Illuminate\Support\Collection;

/**
 * These are methods used on the contact page.
 */
class ContactViewHelper
{
    /**
     * Prepare a collection of audit logs.
     *
     * @param mixed $logs
     * @return Collection
     */
    public static function getListOfAuditLogs($logs): Collection
    {
        $logsCollection = collect();

        foreach ($logs as $log) {
            $description = trans('logs.contact_log_'.$log->action);

            $logsCollection->push([
                'author_name' => ($log->author) ? $log->author->name : $log->author_name,
                'description' => $description,
                'audited_at' => DateHelper::getShortDateWithTime($log->audited_at),
            ]);
        }

        return $logsCollection;
    }

    /**
     * Get the work information for the given contact.
     *
     * @param Contact $contact
     * @return string
     */
    public static function getWorkInformation(Contact $contact): string
    {
        $information = trans('people.work_information_no_work_defined');

        if ($contact->job && $contact->company) {
            $information = trans('people.work_information_job_company', ['title' => $contact->job, 'company' => $contact->company]);
        }

        if ($contact->job && ! $contact->company) {
            $information = $contact->job;
        }

        if (! $contact->job && $contact->company) {
            $information = trans('people.work_information_company', ['company' => $contact->company]);
        }

        return $information;
    }

    /**
     * Get all the addresses of the given contact.
     *
     * @param Contact $contact
     * @return Collection
     */
    public static function getAddresses(Contact $contact): Collection
    {
        $addresses = $contact->addresses()->with('place')->get();

        $addressesCollection = collect();

        foreach ($addresses as $address) {
            $addressesCollection->push([
                'street' => $address->place->street,
                'city' => $address->place->city,
                'province' => $address->place->province,
                'postal_code' => $address->place->postal_code,
                'country' => $address->place->getCountryName(),
                'latitude' => $address->place->latitude,
                'longitude' => $address->place->longitude,
                'google_map_link' => $address->place->getGoogleMapAddress(),
                'full' => $address->place->getAddressAsString(),
            ]);
        }

        return $addressesCollection;
    }
}
