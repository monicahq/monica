<?php

namespace App\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Contact;
use App\Models\ContactInformation;
use Sabre\VObject\Component\VCard;

/**
 * @implements ExportVCardResource<Contact>
 */
#[Order(30)]
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
        $vcard->remove('TEL');
        $vcard->remove('EMAIL');
        $vcard->remove('socialProfile');
        $vcard->remove('URL');

        $resource->contactInformations
            ->each(fn ($contactInformation) => $this->addContactInformationToVCard($vcard, $contactInformation));
    }

    private function addContactInformationToVCard(VCard $vcard, ContactInformation $contactInformation)
    {
        switch ($contactInformation->contactInformationType->name) {
            case trans('Email address'):
                // https://datatracker.ietf.org/doc/html/rfc6350#section-6.4.2
                $vcard->add('EMAIL', $contactInformation->data, [
                    // 'TYPE' => $contactInformation->contactInformationType->type,
                ]);
                break;
            case trans('Phone'):
                // https://datatracker.ietf.org/doc/html/rfc6350#section-6.4.1
                $vcard->add('TEL', $contactInformation->data, [
                    //'TYPE' => $contactInformation->contactInformationType->type,
                ]);
                break;
            case trans('Facebook'):
                $vcard->add('socialProfile', $this->escape('https://www.facebook.com/'.$contactInformation->data), [
                    'TYPE' => 'facebook',
                ]);
                break;
            case trans('Mastodon'):
                $vcard->add('socialProfile', $this->escape($contactInformation->data), [
                    'TYPE' => 'Mastodon',
                ]);
                break;
            case trans('Whatsapp'):
                $vcard->add('socialProfile', $this->escape('https://wa.me/'.$contactInformation->data), [
                    'TYPE' => 'whatsapp',
                ]);
                break;
            case trans('Telegram'):
                $vcard->add('socialProfile', $this->escape('https://t.me/'.$contactInformation->data), [
                    'TYPE' => 'telegram',
                ]);
                break;
            case trans('LinkedIn'):
                $vcard->add('socialProfile', $this->escape('https://www.linkedin.com/in/'.$contactInformation->data), [
                    'TYPE' => 'linkedin',
                ]);
                break;
            default:
                // If field isn't a supported social profile, but still has a protocol, then export it as a url.
                if (! empty($type = $contactInformation->contactInformationType->type)) {
                    $vcard->add('URL', $this->escape($type.$contactInformation->data));
                }
                break;
        }
    }
}
