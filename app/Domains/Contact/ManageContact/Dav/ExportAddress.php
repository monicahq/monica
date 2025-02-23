<?php

namespace App\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Contact;
use Sabre\VObject\Component\VCard;

/**
 * @implements ExportVCardResource<Contact>
 */
#[Order(20)]
class ExportAddress implements ExportVCardResource
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
        $vcard->remove('ADR');

        $addresses = $resource->addresses()
            ->wherePivot('is_past_address', false)
            ->get();

        if ($addresses !== null) {
            foreach ($addresses as $address) {
                // https://datatracker.ietf.org/doc/html/rfc6350#section-6.3.1
                $vcard->add('ADR', [
                    '',
                    $address->line_1,
                    $address->line_2,
                    $address->city,
                    $address->province,
                    $address->postal_code,
                    $address->country,
                ], $address->addressType ? [
                    'TYPE' => $address->addressType->type,
                ] : []);
            }
        }
    }
}
