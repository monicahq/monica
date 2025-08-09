<?php

namespace App\Domains\Contact\ManageContactImportantDates\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use Sabre\VObject\Component\VCard;

/**
 * @implements ExportVCardResource<Contact>
 */
#[Order(40)]
class ExportImportantDates extends Exporter implements ExportVCardResource
{
    public function getType(): string
    {
        return Contact::class;
    }

    /**
     * @param  Contact  $resource
     */
    public function export(mixed $resource, VCard $vcard): void
    {
        $vcard->remove('BDAY');

        $resource->importantDates
            ->each(fn ($importantDate) => $this->addImportantDateToVCard($vcard, $importantDate));
    }

    public function addImportantDateToVCard(VCard $vcard, ContactImportantDate $importantDate)
    {
        if (optional($importantDate->contactImportantDateType)->internal_type === ContactImportantDate::TYPE_BIRTHDATE) {
            // https://datatracker.ietf.org/doc/html/rfc6350#section-6.2.5
            $vcard->BDAY = $importantDate->getVCardDate();
        }
    }
}
