<?php

namespace App\Domains\Contact\ManageContactInformation\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Contact;
use App\Models\ContactInformation;
use Illuminate\Support\Str;
use Sabre\VObject\Component\VCard;

/**
 * @implements ExportVCardResource<Contact>
 */
#[Order(40)]
class ExportContactInformation extends Exporter implements ExportVCardResource
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
        /** @var Contact $contact */
        $contact = $resource;

        $vcard->remove('TEL');
        $vcard->remove('EMAIL');
        $vcard->remove('X-SOCIAL-PROFILE');
        $vcard->remove('IMPP');
        $vcard->remove('URL');

        $contact->contactInformations
            ->each(fn (ContactInformation $contactInformation) => $this->addContactInformationToVCard($vcard, $contactInformation));
    }

    private function addContactInformationToVCard(VCard $vcard, ContactInformation $contactInformation)
    {
        if (Str::is($contactInformation->contactInformationType->type, 'email', true)) {
            // https://datatracker.ietf.org/doc/html/rfc6350#section-6.4.2
            $parameters = [];
            if ($contactInformation->kind !== null) {
                $parameters['TYPE'] = Str::upper($contactInformation->kind);
            }
            if ($contactInformation->pref) {
                $parameters['PREF'] = 1;
            }

            $vcard->add('EMAIL', $contactInformation->data, $parameters);
        } elseif (Str::is($contactInformation->contactInformationType->type, 'phone', true)) {
            // https://datatracker.ietf.org/doc/html/rfc6350#section-6.4.1
            $parameters = [];
            if ($contactInformation->kind !== null) {
                $parameters['TYPE'] = Str::upper($contactInformation->kind);
            }
            if ($contactInformation->pref) {
                $parameters['PREF'] = 1;
            }

            $vcard->add('TEL', $contactInformation->data, $parameters);
        } elseif (Str::is($contactInformation->contactInformationType->type, 'IMPP', true)) {
            // https://datatracker.ietf.org/doc/html/rfc4770
            $vcard->add('IMPP', $contactInformation->data, [
                'X-SERVICE-TYPE' => $contactInformation->contactInformationType->name,
            ]);
        } elseif (Str::is($contactInformation->contactInformationType->type, 'X-SOCIAL-PROFILE', true)) {
            $vcard->add('X-SOCIAL-PROFILE', '', [
                'TYPE' => $contactInformation->contactInformationType->name,
                'X-USER' => $contactInformation->data,
            ]);
        } elseif (! empty($type = $contactInformation->contactInformationType->type)) {
            // If field isn't a supported social profile, but still has a protocol, then export it as a url.
            $vcard->add('URL', $this->escape($type.$contactInformation->data), [
                'TYPE' => $type,
            ]);
        }
    }
}
