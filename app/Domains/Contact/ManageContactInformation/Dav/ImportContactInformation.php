<?php

namespace App\Domains\Contact\ManageContactInformation\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\ManageContactInformation\Services\CreateContactInformation;
use App\Domains\Contact\ManageContactInformation\Services\DestroyContactInformation;
use App\Domains\Contact\ManageContactInformation\Services\UpdateContactInformation;
use App\Domains\Settings\ManageContactInformationTypes\Services\CreateContactInformationType;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Contact;
use App\Models\ContactInformation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Property;

#[Order(40)]
class ImportContactInformation extends Importer implements ImportVCardResource
{
    /**
     * Test if the Card is handled by this importer.
     */
    public function handle(VCard $vcard): bool
    {
        return $this->kind($vcard) === 'individual';
    }

    private $keys = [
        'EMAIL',
        'IMPP',
        'TEL',
        'URL',
        'X-SOCIAL-PROFILE',
    ];

    /**
     * Import Contact contactInformations.
     */
    public function import(VCard $vcard, ?VCardResource $result): ?VCardResource
    {
        /** @var Contact $contact */
        $contact = $result;

        $contactInformations = self::getContactInformations($contact);

        foreach ($this->keys as $key) {
            $this->importContacts($vcard, $contact, $key, $contactInformations);
        }

        return $contact->refresh();
    }

    private static function getContactInformations(Contact $contact): Collection
    {
        return $contact->contactInformations->mapToGroups(function (ContactInformation $info): array {
            switch ($type = $info->contactInformationType->type) {
                case 'email':
                    return ['EMAIL' => $info];
                case 'phone':
                    return ['TEL' => $info];
                case 'IMPP':
                case 'X-SOCIAL-PROFILE':
                    return [$type => $info];
                default:
                    // For other types, we use a generic key
                    return ['OTHER' => $info];
            }
        });
    }

    private function importContacts(VCard $vcard, Contact $contact, string $key, Collection $contactInformations): void
    {
        $cardInfos = self::getCardInformations($vcard, $key);
        $current = $contactInformations->get($key, collect())
            ->map(fn (ContactInformation $contactInformation) => [
                'id' => $contactInformation->id,
                'value' => $contactInformation->data,
                'parameters' => [
                    'TYPE' => $contactInformation->kind,
                ],
            ]);

        for ($i = 0; $i < $cardInfos->count() || $i < $current->count(); $i++) {
            try {
                if ($i < $cardInfos->count()) {
                    $typeId = $this->getTypeId($cardInfos[$i]);
                    if ($typeId === null) {
                        continue; // Skip unsupported types
                    }

                    if ($i < $current->count()) {
                        $this->updateContactInformation($contact, $current[$i], $cardInfos[$i], $typeId);
                    } else {
                        $this->createContactInformation($contact, $cardInfos[$i], $typeId);
                    }
                } elseif ($i < $current->count()) {
                    $this->removeContactInformation($contact, $current[$i]);
                }
            } catch (NotEnoughPermissionException) {
                continue;
            }
        }
    }

    private function getTypeId(Property $property): ?int
    {
        $name = null;
        if ($property->name === 'EMAIL') {
            $type = $this->account()->contactInformationTypes
                ->where('type', 'email')
                ->first();
        } elseif ($property->name === 'TEL') {
            $type = $this->account()->contactInformationTypes
                ->where('type', 'phone')
                ->first();
        } elseif ($property->name === 'IMPP') {
            $name = self::getParameter($property, 'X-SERVICE-TYPE');
            $type = $this->account()->contactInformationTypes()
                ->whereRaw('UPPER(type) LIKE ?', ['%IMPP%'])
                ->where('name_translation_key', $name)
                ->first();
        } elseif ($property->name === 'X-SOCIAL-PROFILE') {
            $name = self::getParameter($property, 'TYPE');
            $type = $this->account()->contactInformationTypes()
                ->whereRaw('UPPER(type) LIKE ?', ['%X-SOCIAL-PROFILE%'])
                ->where('name_translation_key', $name)
                ->first();
        } else {
            // Type not supported
            return null;
        }

        if (! $type) {
            $type = $this->account()->contactInformationTypes()
                ->whereRaw('UPPER(type) LIKE ?', ['%'.Str::upper($property->name).'%'])
                ->where('name', $name)
                ->first();
        }

        if (! $type) {
            $type = (new CreateContactInformationType)->execute([
                'account_id' => $this->account()->id,
                'author_id' => $this->author()->id,
                'name' => $name,
                'type' => Str::upper($property->name),
            ]);
        }

        return optional($type)->id;
    }

    private function getValue(Property $property): string
    {
        if ($property->name === 'X-SOCIAL-PROFILE') {
            return self::getParameter($property, 'X-USER') ?? $property->getValue();
        }

        return $property->getValue();
    }

    private static function getCardInformations(VCard $vcard, string $key): Collection
    {
        return collect($vcard->select($key));
    }

    private static function getParameter(Property $property, string $parameter = 'TYPE'): ?string
    {
        $kind = Arr::get($property->parameters(), $parameter);

        return optional($kind)->getValue();
    }

    private function createContactInformation(Contact $contact, Property $data, int $typeId): void
    {
        (new CreateContactInformation)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => $contact->id,
            'contact_information_type_id' => $typeId,
            'contact_information_kind' => self::getParameter($data),
            'data' => self::getValue($data),
        ]);
    }

    private function removeContactInformation(Contact $contact, array $info): void
    {
        (new DestroyContactInformation)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => $contact->id,
            'contact_information_id' => $info['id'],
        ]);
    }

    private function updateContactInformation(Contact $contact, array $info, Property $data, int $typeId): void
    {
        $kind = self::getParameter($data);
        $value = self::getValue($data);

        if ($value !== $info['value'] || ! Str::is($kind, $info['parameters']['TYPE'], true)) {
            (new UpdateContactInformation)->execute([
                'account_id' => $this->account()->id,
                'vault_id' => $this->vault()->id,
                'author_id' => $this->author()->id,
                'contact_id' => $contact->id,
                'contact_information_id' => $info['id'],
                'contact_information_type_id' => $typeId,
                'contact_information_kind' => $kind,
                'data' => $value,
            ]);
        }
    }
}
