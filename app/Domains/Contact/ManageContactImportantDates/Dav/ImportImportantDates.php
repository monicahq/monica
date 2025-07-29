<?php

namespace App\Domains\Contact\ManageContactImportantDates\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\ManageContactImportantDates\Services\CreateContactImportantDate;
use App\Domains\Contact\ManageContactImportantDates\Services\DestroyContactImportantDate;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Property;

#[Order(40)]
class ImportImportantDates extends Importer implements ImportVCardResource
{
    /**
     * Test if the Card is handled by this importer.
     */
    public function handle(VCard $vcard): bool
    {
        return $this->kind($vcard) === 'individual';
    }

    /**
     * Import Contact contactInformations.
     */
    public function import(VCard $vcard, ?VCardResource $result): ?VCardResource
    {
        /** @var Contact $contact */
        $contact = $result;

        $contactImportantDates = $this->getImportantDates($contact);
        $bdays = $this->getBday($vcard);

        $toAdd = $bdays->diffKeys($contactImportantDates);
        $toRemove = $contactImportantDates->diffKeys($bdays);

        $refresh = false;
        foreach ($toRemove as $importantDate) {
            $this->removeContactImportantDate($contact, $importantDate);
            $refresh = true;
        }
        foreach ($toAdd as $bday) {
            $this->createContactImportantDate($contact, $bday);
            $refresh = true;
        }

        return $refresh ? $contact->refresh() : $contact;
    }

    private function getImportantDates(Contact $contact): Collection
    {
        return $contact->importantDates
            ->filter(fn (ContactImportantDate $importantDate) => optional($importantDate->contactImportantDateType)->internal_type === ContactImportantDate::TYPE_BIRTHDATE)
            ->mapWithKeys(function (ContactImportantDate $importantDate): array {
                $date = $importantDate->year ? Str::padLeft((string) $importantDate->year, 2, '0') : '--';
                $date .= $importantDate->month ? Str::padLeft((string) $importantDate->month, 2, '0') : '--';
                $date .= $importantDate->day ? Str::padLeft((string) $importantDate->day, 2, '0') : '--';

                return [
                    $date => $importantDate,
                ];
            });
    }

    private function getBday(VCard $vcard): Collection
    {
        return collect($vcard->BDAY)
            ->map(fn (Property $bday) => $bday->getValue());
    }

    private function createContactImportantDate(Contact $contact, string $date): void
    {
        (new CreateContactImportantDate)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => $contact->id,
            'contact_information_type_id' => $this->vault()->contactImportantDateTypes
                ->where('internal_type', ContactImportantDate::TYPE_BIRTHDATE)
                ->firstOrFail()
                ->id,
            'label' => trans('Birthday', [], $this->author()->locale),
            'day' => intval(Str::replace('-', '', Str::substr($date, 6, 2))),
            'month' => intval(Str::replace('-', '', Str::substr($date, 4, 2))),
            'year' => intval(Str::replace('-', '', Str::substr($date, 0, 4))),
        ]);
    }

    private function removeContactImportantDate(Contact $contact, ContactImportantDate $importantDate): void
    {
        (new DestroyContactImportantDate)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => $contact->id,
            'contact_important_date_id' => $importantDate->id,
        ]);
    }
}
