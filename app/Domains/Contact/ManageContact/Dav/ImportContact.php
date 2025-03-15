<?php

namespace App\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Domains\Contact\ManageContact\Services\CreateContact;
use App\Domains\Contact\ManageContact\Services\UpdateContact;
use App\Models\Contact;
use App\Models\Gender;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Sabre\VObject\Component\VCard;

use function Safe\preg_split;

#[Order(1)]
class ImportContact extends Importer implements ImportVCardResource
{
    /**
     * Test if the Card is handled by this importer.
     */
    public function handle(VCard $vcard): bool
    {
        return $this->kind($vcard) === 'individual';
    }

    /**
     * Can import Contact.
     */
    public function can(VCard $vcard): bool
    {
        return $this->hasFN($vcard) || $this->hasNICKNAME($vcard) || $this->hasFirstnameInN($vcard);
    }

    /**
     * Import Contact.
     */
    public function import(VCard $vcard, ?VCardResource $result): ?VCardResource
    {
        $contact = $this->getExistingContact($vcard);

        $data = $this->getContactData($contact);
        $original = $data;

        $data = $this->importUid($data, $vcard);
        $data = $this->importNames($data, $vcard);
        $data = $this->importGender($data, $vcard);

        if ($contact === null) {
            $data['listed'] = true;
            $contact = app(CreateContact::class)->execute($data);
        } elseif ($data !== $original) {
            $contact = app(UpdateContact::class)->execute($data);
        }

        if ($this->context->external && $contact->distant_uuid === null) {
            $contact->distant_uuid = $this->getUid($vcard);
            $contact->save();
        }

        return Contact::withoutTimestamps(function () use ($contact): Contact {
            $uri = Arr::get($this->context->data, 'uri');
            if ($this->context->external) {
                $contact->distant_etag = Arr::get($this->context->data, 'etag');
                $contact->distant_uri = $uri;

                $contact->save();
            }

            return $contact;
        });
    }

    private function getExistingContact(VCard $vcard): ?Contact
    {
        $backend = app(CardDAVBackend::class)->withUser($this->context->author);
        $contact = null;

        if (($uri = Arr::get($this->context->data, 'uri')) !== null) {
            $contact = $backend->getObject((string) $this->vault()->id, $uri);

            if ($contact === null) {
                $contact = Contact::firstWhere([
                    'vault_id' => $this->vault()->id,
                    'distant_uri' => $uri,
                ]);
            }
        }

        if ($contact === null) {
            $contactId = $this->getUid($vcard);
            if ($contactId !== null && Uuid::isValid($contactId)) {
                $contact = Contact::firstWhere([
                    'vault_id' => $this->vault()->id,
                    'id' => $contactId,
                ]);
            }
        }

        if ($contact !== null && $contact->vault_id !== $this->vault()->id) {
            throw new ModelNotFoundException;
        }

        return $contact;
    }

    /**
     * Get contact data.
     */
    private function getContactData(?Contact $contact): array
    {
        return [
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => optional($contact)->id,
            'first_name' => optional($contact)->first_name,
            'last_name' => optional($contact)->last_name,
            'middle_name' => optional($contact)->middle_name,
            'nickname' => optional($contact)->nickname,
            'gender_id' => $contact ? $contact->gender_id : $this->getGender('O')->id,
        ];
    }

    /**
     * Import names of the contact.
     */
    public function importNames(array $contactData, VCard $entry): array
    {
        if ($this->hasFirstnameInN($entry)) {
            $contactData = $this->importNameFromN($contactData, $entry);
        } elseif ($this->hasFN($entry)) {
            $contactData = $this->importNameFromFN($contactData, $entry);
        } elseif ($this->hasNICKNAME($entry)) {
            $contactData = $this->importNameFromNICKNAME($contactData, $entry);
        } else {
            throw new \LogicException('Check if you can import entry!');
        }

        return $contactData;
    }

    private function hasFirstnameInN(VCard $entry): bool
    {
        return $entry->N !== null && ! empty(Arr::get($entry->N->getParts(), '1'));
    }

    private function hasNICKNAME(VCard $entry): bool
    {
        return ! empty((string) $entry->NICKNAME);
    }

    private function hasFN(VCard $entry): bool
    {
        return ! empty((string) $entry->FN);
    }

    private function importNameFromN(array $contactData, VCard $entry): array
    {
        $parts = $entry->N->getParts();

        $contactData['last_name'] = $this->formatValue(Arr::get($parts, '0'));
        $contactData['first_name'] = $this->formatValue(Arr::get($parts, '1'));
        $contactData['middle_name'] = $this->formatValue(Arr::get($parts, '2'));
        // prefix [3]
        // suffix [4]

        if (! empty($entry->NICKNAME)) {
            $contactData['nickname'] = $this->formatValue($entry->NICKNAME);
        }

        return $contactData;
    }

    private function importNameFromNICKNAME(array $contactData, VCard $entry): array
    {
        $contactData['first_name'] = $this->formatValue($entry->NICKNAME);

        return $contactData;
    }

    private function importNameFromFN(array $contactData, VCard $entry): array
    {
        $fullnameParts = preg_split('/\s+/', $entry->FN, 2);

        if (Str::of($this->author()->name_order)->startsWith('%first_name% %last_name%')) {
            $contactData['first_name'] = $this->formatValue($fullnameParts[0]);
            if (count($fullnameParts) > 1) {
                $contactData['last_name'] = $this->formatValue($fullnameParts[1]);
            }
        } elseif (Str::of($this->author()->name_order)->startsWith('%last_name% %first_name%')) {
            $contactData['last_name'] = $this->formatValue($fullnameParts[0]);
            if (count($fullnameParts) > 1) {
                $contactData['first_name'] = $this->formatValue($fullnameParts[1]);
            }
        } else {
            $contactData['first_name'] = $this->formatValue($fullnameParts[0]);
        }

        if (! empty($entry->NICKNAME)) {
            $contactData['nickname'] = $this->formatValue($entry->NICKNAME);
        }

        return $contactData;
    }

    /**
     * Import gender of the contact.
     */
    private function importGender(array $contactData, VCard $entry): array
    {
        if ($entry->GENDER) {
            $contactData['gender_id'] = $this->getGender((string) $entry->GENDER)->id;
        }

        return $contactData;
    }

    /**
     * Get or create the gender called "Vcard" that is associated with all
     * imported contacts.
     */
    private function getGender(string $genderCode): Gender
    {
        $gender = $this->getGenderByType($genderCode);

        if (! $gender) {
            switch ($genderCode) {
                case 'M':
                    $gender = $this->getGenderByName(trans('Male')) ?? $this->getGenderByName(config('dav.default_gender'));
                    break;
                case 'F':
                    $gender = $this->getGenderByName(trans('Female')) ?? $this->getGenderByName(config('dav.default_gender'));
                    break;
                default:
                    $gender = $this->getGenderByName(config('dav.default_gender'));
                    break;
            }
        }

        if (! $gender) {
            $gender = Gender::create([
                'account_id' => $this->account()->id,
                'name' => config('dav.default_gender'),
                'type' => Gender::UNKNOWN,
            ]);
        }

        return $gender;
    }

    /**
     * Get the gender by name.
     */
    private function getGenderByName(string $name): ?Gender
    {
        return $this->account()->genders
            ->firstWhere('name', $name);
    }

    /**
     * Get the gender by type.
     */
    private function getGenderByType(string $type): ?Gender
    {
        return $this->account()->genders
            ->firstWhere('type', $type);
    }
}
