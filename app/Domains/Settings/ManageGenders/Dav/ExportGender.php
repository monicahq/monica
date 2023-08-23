<?php

namespace App\Domains\Settings\ManageGenders\Dav;

use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Contact;
use App\Models\Gender;
use Sabre\VObject\Component\VCard;

/**
 * @implements ExportVCardResource<Contact>
 */
#[Order(10)]
class ExportGender implements ExportVCardResource
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
        $vcard->remove('GENDER');

        if (is_null($resource->gender)) {
            return;
        }

        $gender = $resource->gender->type;
        if (empty($gender)) {
            switch ($resource->gender->name) {
                case trans('Male'):
                    $gender = Gender::MALE;
                    break;
                case trans('Female'):
                    $gender = Gender::FEMALE;
                    break;
                default:
                    $gender = Gender::OTHER;
                    break;
            }
        }
        $vcard->add('GENDER', $gender);
    }
}
