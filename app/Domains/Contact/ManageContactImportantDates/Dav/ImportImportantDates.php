<?php

namespace App\Domains\Contact\ManageContactImportantDates\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\ManageContactImportantDates\Services\CreateContactImportantDate;
use App\Domains\Contact\ManageContactImportantDates\Services\DestroyContactImportantDate;
use App\Domains\Contact\ManageContactImportantDates\Services\UpdateContactImportantDate;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactImportantDateType;
use Illuminate\Support\Collection;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Property;

#[Order(40)]
class ImportImportantDates extends Importer implements ImportVCardResource
{
    private ?ContactImportantDateType $birthdateType = null;

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
        $this->birthdateType = $this->getBirthdateType();

        /** @var Contact $contact */
        $contact = $result;

        $contactImportantDates = $this->getImportantDates($contact);
        $bdays = $this->getBday($vcard);

        $toAdd = $bdays->diffKeys($contactImportantDates);
        $toRemove = $contactImportantDates->diffKeys($bdays);
        $intersect = $contactImportantDates->intersectByKeys($bdays);

        $refresh = false;
        foreach ($toRemove as $importantDate) {
            $this->removeContactImportantDate($contact, $importantDate);
            $refresh = true;
        }
        foreach ($toAdd as $bday) {
            $this->createContactImportantDate($contact, $bday);
            $refresh = true;
        }
        foreach ($intersect as $current) {
            $refresh = $this->updateContactImportantDate($contact, $current) || $refresh;
        }

        return $refresh ? $contact->refresh() : $contact;
    }

    private function getImportantDates(Contact $contact): Collection
    {
        return $contact->importantDates
            ->filter(fn (ContactImportantDate $importantDate) => $importantDate->contactImportantDateType === null || optional($importantDate->contactImportantDateType)->internal_type === ContactImportantDate::TYPE_BIRTHDATE)
            ->mapWithKeys(fn (ContactImportantDate $importantDate): array => [
                $importantDate->getVCardDate() => $importantDate,
            ]);
    }

    private function getBday(VCard $vcard): Collection
    {
        return collect($vcard->BDAY)
            ->mapWithKeys(fn (Property $bday): array => [
                $bday->getValue() => DateTimeParser::parseVCardDateTime($bday->getValue()),
            ]);
    }

    private function getBirthdateType(): ?ContactImportantDateType
    {
        return $this->vault()->contactImportantDateTypes
            ->where('internal_type', ContactImportantDate::TYPE_BIRTHDATE)
            ->first();
    }

    private function createContactImportantDate(Contact $contact, array $date): void
    {
        (new CreateContactImportantDate)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => $contact->id,
            'contact_important_date_type_id' => optional($this->birthdateType)->id,
            'label' => trans('Birthday', [], $this->author()->locale),
            'day' => $date['date'] === null ? null : intval($date['date']),
            'month' => $date['month'] === null ? null : intval($date['month']),
            'year' => $date['year'] === null ? null : intval($date['year']),
        ]);
    }

    private function updateContactImportantDate(Contact $contact, ContactImportantDate $importantDate): bool
    {
        if ($importantDate->contactImportantDateType === null && $this->birthdateType !== null) {
            (new UpdateContactImportantDate)->execute([
                'account_id' => $this->account()->id,
                'vault_id' => $this->vault()->id,
                'author_id' => $this->author()->id,
                'contact_id' => $contact->id,
                'contact_important_date_id' => $importantDate->id,
                'contact_important_date_type_id' => $this->birthdateType->id,
                'label' => $importantDate->label,
                'day' => $importantDate->day,
                'month' => $importantDate->month,
                'year' => $importantDate->year,
            ]);

            return true;
        }

        return false;
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
