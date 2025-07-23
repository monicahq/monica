<?php

namespace App\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\ManageContactAddresses\Services\AssociateAddressToContact;
use App\Domains\Contact\ManageContactAddresses\Services\RemoveAddressFromContact;
use App\Domains\Settings\ManageAddressTypes\Services\CreateAddressType;
use App\Domains\Vault\ManageAddresses\Services\CreateAddress;
use App\Domains\Vault\ManageAddresses\Services\UpdateAddress;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Address;
use App\Models\AddressType;
use App\Models\Contact;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Property;

#[Order(40)]
class ImportAddress extends Importer implements ImportVCardResource
{
    /**
     * Test if the Card is handled by this importer.
     */
    public function handle(VCard $vcard): bool
    {
        return $this->kind($vcard) === 'individual';
    }

    /**
     * Import Contact addresses.
     */
    public function import(VCard $vcard, ?VCardResource $result): ?VCardResource
    {
        /** @var Contact $contact */
        $contact = $result;

        $addresses = $contact->addresses()
            ->wherePivot('is_past_address', false)
            ->get();
        $adr = $vcard->select('ADR');

        for ($i = 0; $i < count($adr) || $i < $addresses->count(); $i++) {
            if ($i < count($adr)) {
                $addressType = $this->getAddressType($adr[$i]);

                if ($i < $addresses->count()) {
                    $this->updateAddress($adr[$i], $addresses[$i], $addressType);
                } else {
                    $this->createAddress($contact, $adr[$i], $addressType);
                }
            } elseif ($i < $addresses->count()) {
                $this->removeAddress($contact, $addresses[$i]);
            }
        }

        return $contact->refresh();
    }

    private function getAddressType(Property $adr): ?AddressType
    {
        $type = Arr::get($adr->parameters(), 'TYPE');

        if ($type) {
            try {
                return AddressType::where([
                    'account_id' => $this->account()->id,
                    'type' => $type->getValue(),
                ])->firstOrFail();
            } catch (ModelNotFoundException) {
                try {
                    return (new CreateAddressType)->execute([
                        'account_id' => $this->account()->id,
                        'author_id' => $this->author()->id,
                        'name' => $type->getValue(),
                        'type' => $type->getValue(),
                    ]);
                } catch (NotEnoughPermissionException) {
                    // catch
                }
            }
        }

        return null;
    }

    private function updateAddress(Property $adr, Address $address, ?AddressType $addressType)
    {
        (new UpdateAddress)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'address_id' => $address->id,
            'address_type_id' => optional($addressType)->id,
            'line_1' => Arr::get($adr->getParts(), 1),
            'line_2' => Arr::get($adr->getParts(), 2),
            'city' => Arr::get($adr->getParts(), 3),
            'province' => Arr::get($adr->getParts(), 4),
            'postal_code' => Arr::get($adr->getParts(), 5),
            'country' => Arr::get($adr->getParts(), 6),
        ]);
    }

    private function createAddress(Contact $contact, Property $adr, ?AddressType $addressType)
    {
        $address = Address::where([
            'vault_id' => $this->vault()->id,
            'address_type_id' => optional($addressType)->id,
            'line_1' => Arr::get($adr->getParts(), 1),
            'line_2' => Arr::get($adr->getParts(), 2),
            'city' => Arr::get($adr->getParts(), 3),
            'province' => Arr::get($adr->getParts(), 4),
            'postal_code' => Arr::get($adr->getParts(), 5),
            'country' => Arr::get($adr->getParts(), 6),
        ])->first();

        if ($address === null) {
            $address = (new CreateAddress)->execute([
                'account_id' => $this->account()->id,
                'vault_id' => $this->vault()->id,
                'author_id' => $this->author()->id,
                'address_type_id' => optional($addressType)->id,
                'line_1' => Arr::get($adr->getParts(), 1),
                'line_2' => Arr::get($adr->getParts(), 2),
                'city' => Arr::get($adr->getParts(), 3),
                'province' => Arr::get($adr->getParts(), 4),
                'postal_code' => Arr::get($adr->getParts(), 5),
                'country' => Arr::get($adr->getParts(), 6),
            ]);
        }

        (new AssociateAddressToContact)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => $contact->id,
            'address_id' => $address->id,
            'is_past_address' => false,
        ]);
    }

    private function removeAddress(Contact $contact, Address $address)
    {
        (new RemoveAddressFromContact)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => $contact->id,
            'address_id' => $address->id,
        ]);
    }
}
