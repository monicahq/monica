<?php

namespace App\Services\VCard;

use Illuminate\Support\Str;
use App\Services\BaseService;
use App\Models\Contact\Gender;
use App\Models\Contact\Contact;
use Sabre\VObject\Component\VCard;

class ExportVCard extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'contact_id' => 'nullable|integer',
        ];
    }

    /**
     * Import one VCard.
     *
     * @param array $data
     * @return VCard
     */
    public function execute(array $data) : VCard
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        return $this->export($contact);
    }

    private function escape($value) : string
    {
        return ! empty((string) $value) ? trim((string) $value) : (string) null;
    }

    /**
     * @param Contact $contact
     * @return VCard
     */
    private function export(Contact $contact) : VCard
    {
        // The standard for most of these fields can be found on https://tools.ietf.org/html/rfc6350
        if (! $contact->uuid) {
            $contact->forceFill([
                'uuid' => Str::uuid(),
            ])->save();
        }

        // Basic information
        $vcard = new VCard([
            'UID' => $contact->uuid,
            'SOURCE' => route('people.show', $contact),
            'VERSION' => '4.0',
        ]);

        $this->exportNames($contact, $vcard);
        $this->exportGender($contact, $vcard);
        $this->exportPhoto($contact, $vcard);
        $this->exportWorkInformation($contact, $vcard);
        $this->exportBirthday($contact, $vcard);
        $this->exportAddress($contact, $vcard);
        $this->exportContactFields($contact, $vcard);

        return $vcard;
    }

    /**
     * @param Contact $contact
     * @param VCard $vcard
     */
    private function exportNames(Contact $contact, VCard $vcard)
    {
        $vcard->add('FN', $this->escape($contact->name));

        $vcard->add('N', [
            $this->escape($contact->last_name),
            $this->escape($contact->first_name),
            $this->escape($contact->middle_name),
        ]);

        if (! empty($contact->nickname)) {
            $vcard->add('NICKNAME', $this->escape($contact->nickname));
        }
    }

    /**
     * @param Contact $contact
     * @param VCard $vcard
     */
    private function exportGender(Contact $contact, VCard $vcard)
    {
        switch ($contact->gender->name) {
            case 'Man':
                $gender = 'M';
                break;
            case 'Woman':
                $gender = 'F';
                break;
            default:
                $gender = 'O';
                break;
        }
        $vcard->add('GENDER', [$gender, $contact->gender->name]);
    }

    /**
     * @param Contact $contact
     * @param VCard $vcard
     */
    private function exportPhoto(Contact $contact, VCard $vcard)
    {
        $picture = $contact->getAvatarURL();
        if (! is_null($picture)) {
            $vcard->add('PHOTO', $picture);
        }
    }

    /**
     * @param Contact $contact
     * @param VCard $vcard
     */
    private function exportWorkInformation(Contact $contact, VCard $vcard)
    {
        if (! empty($contact->company)) {
            $vcard->add('ORG', $this->escape($contact->company));
        }

        if (! empty($contact->job)) {
            $vcard->add('TITLE', $this->escape($contact->job));
        }
    }

    /**
     * @param Contact $contact
     * @param VCard $vcard
     */
    private function exportBirthday(Contact $contact, VCard $vcard)
    {
        if (! is_null($contact->birthdate)) {
            $date = $contact->birthdate->date->format('Ymd');
            $vcard->add('BDAY', $date);
        }
    }

    /**
     * @param Contact $contact
     * @param VCard $vcard
     */
    private function exportAddress(Contact $contact, VCard $vcard)
    {
        foreach ($contact->addresses as $address) {
            $vcard->add('ADR', [
                '',
                '',
                $address->street,
                $address->city,
                $address->province,
                $address->postal_code,
                $address->country,
            ]);
        }
    }

    /**
     * @param Contact $contact
     * @param VCard $vcard
     */
    private function exportContactFields(Contact $contact, VCard $vcard)
    {
        foreach ($contact->contactFields as $contactField) {
            switch ($contactField->contactFieldType->type) {
                case 'phone':
                    $vcard->add('TEL', $this->escape($contactField->data));
                    break;
                case 'email':
                    $vcard->add('EMAIL', $this->escape($contactField->data));
                    break;
                default:
                    break;
            }
            switch ($contactField->contactFieldType->name) {
                // See https://tools.ietf.org/id/draft-george-vcarddav-vcard-extension-02.html
                case 'Facebook':
                    $vcard->add('socialProfile', $this->escape('https://www.facebook.com/'.$contactField->data), ['type' => 'facebook']);
                    break;
                case 'Twitter':
                    $vcard->add('socialProfile', $this->escape('https://twitter.com/'.$contactField->data), ['type' => 'twitter']);
                    break;
                case 'Whatsapp':
                    $vcard->add('socialProfile', $this->escape('https://wa.me/'.$contactField->data), ['type' => 'whatsapp']);
                    break;
                case 'Telegram':
                    $vcard->add('socialProfile', $this->escape('http://t.me/'.$contactField->data), ['type' => 'telegram']);
                    break;
                default:
                    break;
            }
        }

        if (! is_null($contact->linkedin_profile_url)) {
            $vcard->add('socialProfile', $this->escape('http://www.linkedin.com/in/'.$contact->linkedin_profile_url), ['type' => 'linkedin']);
        }
    }
}
