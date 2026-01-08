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

    /**
     * Get or create the address type from vCard ADR field.
     * 
     * Maps vCard TYPE parameter to Monica AddressType. Handles special cases:
     * - "HOME,pref" or "HOME,xxx" maps to "home" type
     * - Creates new types if they don't exist (with permission)
     * 
     * @param Property $adr The vCard ADR property with optional TYPE parameter
     * @return AddressType|null The matched/created AddressType, or null if no type specified
     */
    private function getAddressType(Property $adr): ?AddressType
    {
        $type = Arr::get($adr->parameters(), 'TYPE');

        if ($type) {
            $typeValue = $type->getValue();
            
            // Map HOME,xxx to 'home' type
            if (str_starts_with(strtolower($typeValue), 'home')) {
                $typeValue = 'home';
            }
            
            try {
                return AddressType::where([
                    'account_id' => $this->account()->id,
                    'type' => $typeValue,
                ])->firstOrFail();
            } catch (ModelNotFoundException) {
                try {
                    return (new CreateAddressType)->execute([
                        'account_id' => $this->account()->id,
                        'author_id' => $this->author()->id,
                        'name' => $typeValue,
                        'type' => $typeValue,
                    ]);
                } catch (NotEnoughPermissionException) {
                    // catch
                }
            }
        }

        return null;
    }

    /**
     * Update an existing address with vCard data.
     * 
     * Maps vCard ADR parts to Monica address fields:
     * - parts[0]: PO Box (unused)
     * - parts[1]: Extended address (apartment/suite) → line_2
     * - parts[2]: Street address → line_1 (formatted with comma after number)
     * - parts[3]: City
     * - parts[4]: Province/State
     * - parts[5]: Postal code
     * - parts[6]: Country
     * 
     * @param Property $adr The vCard ADR property
     * @param Address $address The existing Monica address to update
     * @param AddressType|null $addressType The address type (home, work, etc.)
     * @return void
     */
    private function updateAddress(Property $adr, Address $address, ?AddressType $addressType)
    {
        $parts = $adr->getParts();
        
        (new UpdateAddress)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'address_id' => $address->id,
            'address_type_id' => optional($addressType)->id,
            'line_1' => $this->formatStreetAddress(Arr::get($parts, 2)),
            'line_2' => Arr::get($parts, 1),
            'city' => Arr::get($parts, 3),
            'province' => Arr::get($parts, 4),
            'postal_code' => Arr::get($parts, 5),
            'country' => Arr::get($parts, 6),
        ]);
    }

    /**
     * Create a new address from vCard data and associate it with contact.
     * 
     * Maps vCard ADR parts to Monica address fields (see updateAddress for mapping details).
     * Automatically associates the created address with the contact.
     * 
     * @param Contact $contact The contact to associate the address with
     * @param Property $adr The vCard ADR property
     * @param AddressType|null $addressType The address type (home, work, etc.)
     * @return void
     */
    private function createAddress(Contact $contact, Property $adr, ?AddressType $addressType)
    {
        $parts = $adr->getParts();
        $line1 = $this->formatStreetAddress(Arr::get($parts, 2));
        
        $address = Address::where([
            'vault_id' => $this->vault()->id,
            'address_type_id' => optional($addressType)->id,
            'line_1' => $line1,
            'line_2' => Arr::get($parts, 1),
            'city' => Arr::get($parts, 3),
            'province' => Arr::get($parts, 4),
            'postal_code' => Arr::get($parts, 5),
            'country' => Arr::get($parts, 6),
        ])->first();

        if ($address === null) {
            $address = (new CreateAddress)->execute([
                'account_id' => $this->account()->id,
                'vault_id' => $this->vault()->id,
                'author_id' => $this->author()->id,
                'address_type_id' => optional($addressType)->id,
                'line_1' => $line1,
                'line_2' => Arr::get($parts, 1),
                'city' => Arr::get($parts, 3),
                'province' => Arr::get($parts, 4),
                'postal_code' => Arr::get($parts, 5),
                'country' => Arr::get($parts, 6),
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

    /**
     * Remove an address association from a contact.
     * 
     * @param Contact $contact The contact to remove the address from
     * @param Address $address The address to disassociate
     * @return void
     */
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
    
    /**
     * Format street address to add proper spacing after street numbers.
     * 
     * Handles various vCard formatting issues:
     * - Newline characters: "61\nRue Des Lutiers" → "61, Rue Des Lutiers"
     * - Missing space: "61Rue Des Lutiers" → "61, Rue Des Lutiers"
     * - Already spaced: "61 Rue Des Lutiers" → "61, Rue Des Lutiers"
     * 
     * Also normalizes whitespace (tabs, multiple spaces, etc.).
     * 
     * @param string|null $street The raw street address from vCard
     * @return string|null The formatted street address, or null if empty
     */
    private function formatStreetAddress(?string $street): ?string
    {
        if (empty($street)) {
            return null;
        }
        
        // Replace newlines/multiple spaces with single space
        $street = preg_replace('/[\n\r\t]+/', ' ', $street);
        $street = preg_replace('/\s+/', ' ', $street);
        $street = trim($street);
        
        // If address starts with digits followed by space or letter, add ", "
        // Examples: "61 Rue" → "61, Rue" or "61Rue" → "61, Rue"
        if (preg_match('/^(\d+)\s*(.+)$/', $street, $matches)) {
            return $matches[1] . ', ' . trim($matches[2]);
        }
        
        return $street;
    }
}
