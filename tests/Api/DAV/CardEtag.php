<?php

namespace Tests\Api\DAV;

use App\Models\Contact\Task;
use App\Models\Contact\Contact;
use App\Models\Instance\SpecialDate;
use App\Models\Contact\ContactFieldType;

trait CardEtag
{
    protected function getEtag($obj)
    {
        $data = '';
        if ($obj instanceof Contact) {
            $data = $this->getCard($obj, true);
        } elseif ($obj instanceof SpecialDate) {
            $data = $this->getCal($obj, true);
        } elseif ($obj instanceof Task) {
            $data = $this->getVTodo($obj, true);
        }

        return md5($data);
    }

    protected function getCard(Contact $contact, bool $realFormat = false): string
    {
        $contact = $contact->refresh();
        $url = route('people.show', $contact);
        $sabreversion = \Sabre\VObject\Version::VERSION;
        $timestamp = $contact->updated_at->format('Ymd\THis\Z');

        $data = "BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject {$sabreversion}//EN
UID:{$contact->uuid}
SOURCE:{$url}
FN:{$contact->name}
N:{$contact->last_name};{$contact->first_name};{$contact->middle_name};;
";

        if ($contact->gender) {
            $data .= "GENDER:{$contact->gender->type}";
            $data .= "\n";
        }

        $picture = $contact->getAvatarURL();
        if (! empty($picture)) {
            $data .= "PHOTO;VALUE=URI:{$picture}\n";
        }

        foreach ($contact->addresses as $address) {
            $data .= 'ADR:;;';
            $data .= $address->place->street.';';
            $data .= $address->place->city.';';
            $data .= $address->place->province.';';
            $data .= $address->place->postal_code.';';
            $data .= $address->place->country;
            $data .= "\n";
        }
        foreach ($contact->contactFields as $contactField) {
            $type = '';
            if ($contactField->labels->count() > 0) {
                $type = ';TYPE='.$contactField->labels->map(function ($label) {
                    return $label->label_i18n ?: $label->label;
                })->join(',');
            }
            switch ($contactField->contactFieldType->type) {
                case ContactFieldType::PHONE:
                    $data .= "TEL$type:{$contactField->data}\n";
                    break;
                case ContactFieldType::EMAIL:
                    $data .= "EMAIL$type:{$contactField->data}\n";
                    break;
                default:
                    break;
            }
        }
        $data .= "REV:{$timestamp}\n";
        $tags = $contact->getTagsAsString();
        if (! empty($tags)) {
            $data .= "CATEGORIES:{$tags}\n";
        }
        $data .= "END:VCARD\n";

        if ($realFormat) {
            $data = mb_ereg_replace("\n", "\r\n", $data);
        }

        return $data;
    }

    protected function getCal(SpecialDate $specialDate, bool $realFormat = false): string
    {
        $contact = $specialDate->contact;
        $url = route('people.show', $contact);
        $description = "See {$contact->name}’s profile: {$url}";
        $description1 = mb_substr($description, 0, 61);
        $description2 = mb_substr($description, 61);

        $sabreversion = \Sabre\VObject\Version::VERSION;
        $timestamp = $specialDate->created_at->format('Ymd\THis\Z');

        $start = $specialDate->date->format('Ymd');
        $end = $specialDate->date->addDays(1)->format('Ymd');

        $data = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject {$sabreversion}//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:UTC
END:VTIMEZONE
BEGIN:VEVENT
UID:{$specialDate->uuid}
DTSTART;VALUE=DATE:{$start}
DTEND;VALUE=DATE:{$end}
RRULE:FREQ=YEARLY
DTSTAMP:{$timestamp}
CREATED:{$timestamp}
SUMMARY:Birthday of {$contact->name}
ATTACH:{$url}
DESCRIPTION:{$description1}
 {$description2}
END:VEVENT
END:VCALENDAR
";

        if ($realFormat) {
            $data = mb_ereg_replace("\n", "\r\n", $data);
        }

        return $data;
    }

    protected function getVTodo(Task $task, bool $realFormat = false): string
    {
        $sabreversion = \Sabre\VObject\Version::VERSION;
        $timestamp = $task->created_at->format('Ymd\THis\Z');
        $contact = $task->contact;

        $data = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject {$sabreversion}//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:UTC
END:VTIMEZONE
BEGIN:VTODO
UID:{$task->uuid}
SUMMARY:{$task->title}
DTSTAMP:{$timestamp}
CREATED:{$timestamp}
DESCRIPTION:{$task->description}
";
        if ($contact) {
            $url = route('people.show', $contact);
            $data .= "ATTACH:{$url}
";
        }
        $data .= 'END:VTODO
END:VCALENDAR
';

        if ($realFormat) {
            $data = mb_ereg_replace("\n", "\r\n", $data);
        }

        return $data;
    }
}
