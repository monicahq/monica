<?php

namespace App\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use Illuminate\Support\Str;
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
        if (($type = $importantDate->contactImportantDateType) !== null && mb_strtolower($type->label) === ContactImportantDate::TYPE_BIRTHDATE) {
            $date = $importantDate->year ? Str::padLeft((string) $importantDate->year, 2, '0') : '--';
            $date .= $importantDate->month ? Str::padLeft((string) $importantDate->month, 2, '0') : '--';
            $date .= $importantDate->day ? Str::padLeft((string) $importantDate->day, 2, '0') : '--';

            // https://datatracker.ietf.org/doc/html/rfc6350#section-6.2.5
            $vcard->add('BDAY', $date);
        }
    }
}
