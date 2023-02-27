<?php

namespace App\Domains\Settings\ManageGenders\Dav;

use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Contact;
use App\Models\Gender;
use Sabre\VObject\Component\VCard;

#[Order(10)]
class ExportGender implements ExportVCardResource
{
    public function export(Contact $contact, VCard $vcard): void
    {
        $vcard->remove('GENDER');

        if (is_null($contact->gender)) {
            return;
        }

        $gender = $contact->gender->type;
        if (empty($gender)) {
            switch ($contact->gender->name) {
                case trans('account.gender_male'):
                    $gender = Gender::MALE;
                    break;
                case trans('account.gender_female'):
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
