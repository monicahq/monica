<?php

namespace App\Services\VCard;

use Illuminate\Support\Str;
use App\Services\BaseService;
use App\Models\Contact\Gender;
use App\Models\Contact\Contact;
use App\Interfaces\LabelInterface;
use Sabre\VObject\Component\VCard;
use App\Models\Contact\ContactFieldType;
use App\Models\Contact\ContactFieldLabel;

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
            'contact_id' => 'required|integer|exists:contacts,id',
        ];
    }

    /**
     * Export one VCard.
     *
     * @param array $data
     * @return VCard
     */
    public function execute(array $data): VCard
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        return $this->export($contact);
    }

    private function escape($value): string
    {
        return ! empty((string) $value) ? trim((string) $value) : (string) null;
    }

    /**
     * @param Contact $contact
     * @return VCard
     */
    private function export(Contact $contact): VCard
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
            'SOURCE' => $contact->getLink(),
            'VERSION' => '4.0',
        ]);

        $this->exportNames($contact, $vcard);
        $this->exportGender($contact, $vcard);
        $this->exportPhoto($contact, $vcard);
        $this->exportWorkInformation($contact, $vcard);
        $this->exportBirthday($contact, $vcard);
        $this->exportAddress($contact, $vcard);
        $this->exportContactFields($contact, $vcard);
        $this->exportTimestamp($contact, $vcard);
        $this->exportTags($contact, $vcard);

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
        if (is_null($contact->gender)) {
            return;
        }

        $gender = $contact->gender->type;
        if (empty($gender)) {
            switch ($contact->gender->name) {
                case trans('app.gender_male'):
                    $gender = Gender::MALE;
                    break;
                case trans('app.gender_female'):
                    $gender = Gender::FEMALE;
                    break;
                default:
                    $gender = Gender::OTHER;
                    break;
            }
        }
        $vcard->add('GENDER', $gender);
    }

    /**
     * @param Contact $contact
     * @param VCard $vcard
     */
    private function exportPhoto(Contact $contact, VCard $vcard)
    {
        if ($contact->avatar_source == 'photo') {
            $photo = $contact->avatarPhoto;

            $vcard->add('PHOTO', $photo->dataUrl());
        } else {
            $picture = $contact->getAvatarURL();

            if (! empty($picture)) {
                $vcard->add('PHOTO', $picture);
            }
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
            if ($contact->birthdate->is_year_unknown) {
                $date = $contact->birthdate->date->format('--m-d');
            } else {
                $date = $contact->birthdate->date->format('Ymd');
            }
            $vcard->add('BDAY', $date);
        }
    }

    /**
     * @param Contact $contact
     * @param VCard $vcard
     * @see https://tools.ietf.org/html/rfc6350#section-6.3.1
     */
    private function exportAddress(Contact $contact, VCard $vcard)
    {
        foreach ($contact->addresses as $address) {
            $type = $this->getContactFieldLabel($address);
            $arguments = [];
            if ($type != '') {
                $arguments['TYPE'] = $type;
            }
            $vcard->add('ADR', [
                '',
                '',
                $address->place->street,
                $address->place->city,
                $address->place->province,
                $address->place->postal_code,
                $address->place->country,
            ],
                $arguments
            );
        }
    }

    /**
     * @param Contact $contact
     * @param VCard $vcard
     */
    private function exportContactFields(Contact $contact, VCard $vcard)
    {
        foreach ($contact->contactFields as $contactField) {
            $type = $this->getContactFieldLabel($contactField);
            switch ($contactField->contactFieldType->type) {
                case ContactFieldType::PHONE:
                    $vcard->add('TEL', $this->escape($contactField->data), $type);
                    break;
                case ContactFieldType::EMAIL:
                    $vcard->add('EMAIL', $this->escape($contactField->data), $type);
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
                case 'LinkedIn':
                    $vcard->add('socialProfile', $this->escape('http://www.linkedin.com/in/'.$contactField->data), ['type' => 'linkedin']);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * @param LabelInterface $labelProvider
     * @return array|null
     */
    private function getContactFieldLabel(LabelInterface $labelProvider): ?array
    {
        $type = null;
        $labels = $labelProvider->labels()->get();
        if ($labels->count() > 0) {
            $type = [];
            $type['type'] = $labels->map(function ($label) {
                /** @var ContactFieldLabel */
                $cflabel = $label;

                return mb_strtoupper($cflabel->label_i18n) ?: $cflabel->label;
            })->join(',');
        }

        return $type;
    }

    /**
     * @param Contact $contact
     * @param VCard $vcard
     */
    private function exportTimestamp(Contact $contact, VCard $vcard)
    {
        $vcard->REV = $contact->updated_at->format('Ymd\\THis\\Z');
    }

    /**
     * @param Contact $contact
     * @param VCard $vcard
     */
    private function exportTags(Contact $contact, VCard $vcard)
    {
        if ($contact->tags->count() > 0) {
            $vcard->CATEGORIES = $contact->tags->map(function ($tag) {
                return $tag->name;
            })->toArray();
        }
    }
}
