<?php

namespace App\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\ManageContact\Services\CreateContact;
use App\Domains\Contact\ManageContact\Services\UpdateContact;
use App\Models\Contact;
use App\Models\Gender;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Sabre\VObject\Component\VCard;

#[Order(1)]
class ImportContact extends Importer implements ImportVCardResource
{
    /**
     * The genders that will be associated with imported contacts.
     *
     * @var array<Gender>
     */
    protected array $genders = [];

    /**
     * Import Contact
     */
    public function import(?Contact $contact, VCard $vcard): Contact
    {
        $contactData = $this->getContactData($contact);
        $original = $contactData;

        $contactData = $this->importUid($contactData, $vcard);
        $contactData = $this->importNames($contactData, $vcard);
        $contactData = $this->importGender($contactData, $vcard);

        if ($contact !== null && $contactData !== $original) {
            $contact = app(UpdateContact::class)->execute($contactData);
        } else {
            $contactData['listed'] = true;
            $contact = app(CreateContact::class)->execute($contactData);
        }

        return $contact;
    }

    /**
     * Get contact data.
     */
    private function getContactData(?Contact $contact): array
    {
        $result = [
            'account_id' => $this->account()->id,
            'vault_id' => $this->context->vault->id,
            'author_id' => $this->context->author->id,
            'first_name' => $contact ? $contact->first_name : null,
            'last_name' => $contact ? $contact->last_name : null,
            'middle_name' => $contact ? $contact->middle_name : null,
            'nickname' => $contact ? $contact->nickname : null,
            'gender_id' => $contact ? $contact->gender_id : $this->getGender('O')->id,
        ];

        if ($contact) {
            $result['contact_id'] = $contact->id;
        }

        return $result;
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

        if (Str::of($this->context->author->name_order)->startsWith('%first_name% %last_name%')) {
            $contactData['first_name'] = $this->formatValue($fullnameParts[0]);
            if (count($fullnameParts) > 1) {
                $contactData['last_name'] = $this->formatValue($fullnameParts[1]);
            }
        } elseif (Str::of($this->context->author->name_order)->startsWith('%last_name% %first_name%')) {
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
     * Import uid of the contact.
     */
    private function importUid(array $contactData, VCard $entry): array
    {
        if (! empty($uuid = (string) $entry->UID) && Uuid::isValid($uuid)) {
            $contactData['uuid'] = $uuid;
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
        if (! Arr::has($this->genders, $genderCode)) {
            $gender = $this->getGenderByType($genderCode);
            if (! $gender) {
                switch ($genderCode) {
                    case 'M':
                        $gender = $this->getGenderByName(trans('account.gender_male')) ?? $this->getGenderByName(config('dav.default_gender'));
                        break;
                    case 'F':
                        $gender = $this->getGenderByName(trans('account.gender_female')) ?? $this->getGenderByName(config('dav.default_gender'));
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

            Arr::set($this->genders, $genderCode, $gender);
        }

        return Arr::get($this->genders, $genderCode);
    }

    /**
     * Get the gender by name.
     */
    private function getGenderByName(string $name): ?Gender
    {
        return $this->account()->genders
            ->where('name', $name)
            ->first();
    }

    /**
     * Get the gender by type.
     */
    private function getGenderByType(string $type): ?Gender
    {
        return $this->account()->genders
            ->where('type', $type)
            ->first();
    }
}
