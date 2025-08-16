<?php

namespace Tests\Unit\Domains\Contact\DAV;

use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactTask;

trait CardEtag
{
    protected function getEtag($obj, bool $quotes = false)
    {
        $data = '';
        if ($obj instanceof Contact) {
            $data = $this->getCard($obj, true);
        } elseif ($obj instanceof ContactTask) {
            $data = $this->getTask($obj, true);
        } elseif ($obj instanceof ContactImportantDate) {
            $data = $this->getDate($obj, true);
        }

        $etag = hash('sha256', $data);
        if ($quotes) {
            $etag = '"'.$etag.'"';
        }

        return $etag;
    }

    protected function getCard(Contact $contact, bool $realFormat = false): string
    {
        $contact = $contact->refresh();
        $url = $contact->vault_id ? route('contact.show', [
            'vault' => $contact->vault_id,
            'contact' => $contact->id,
        ]) : null;
        $sabreversion = \Sabre\VObject\Version::VERSION;
        $timestamp = $contact->updated_at->format('Ymd\THis\Z');

        $data = $this->append('BEGIN:VCARD');
        $data = $this->append('VERSION:4.0', $data);
        $data = $this->append("PRODID:-//Sabre//Sabre VObject {$sabreversion}//EN", $data);
        $data = $this->append("UID:{$contact->id}", $data);
        $data = $this->append("SOURCE:{$url}", $data);
        $data = $this->append("FN:{$contact->name}", $data);
        $data = $this->append("N:{$contact->last_name};{$contact->first_name};{$contact->middle_name};;", $data);

        if ($contact->nickname) {
            $data = $this->append("NICKNAME:{$contact->nickname}", $data);
        }

        if ($contact->gender) {
            $data = $this->append("GENDER:{$contact->gender->type}", $data);
        }

        $data = $this->append("REV:{$timestamp}", $data);
        $data = $this->append('END:VCARD', $data);

        if ($realFormat) {
            $data = mb_ereg_replace("\n", "\r\n", $data);
        }

        return $data;
    }

    protected function getTask(ContactTask $task): string
    {
        $task = $task->refresh();
        $url = route('contact.show', [
            'vault' => $task->contact->vault_id,
            'contact' => $task->contact,
        ]);
        $sabreversion = \Sabre\VObject\Version::VERSION;

        $data = $this->append('BEGIN:VCALENDAR');
        $data = $this->append('VERSION:2.0', $data);
        $data = $this->append("PRODID:-//Sabre//Sabre VObject {$sabreversion}//EN", $data);
        $data = $this->append('CALSCALE:GREGORIAN', $data);
        $data = $this->append("SOURCE:{$url}", $data);

        $data = $this->append('BEGIN:VTIMEZONE', $data);
        $data = $this->append('TZID:UTC', $data);
        $data = $this->append('END:VTIMEZONE', $data);

        $data = $this->append('BEGIN:VTODO', $data);
        $data = $this->append("UID:{$task->uuid}", $data);
        $data = $this->append("SUMMARY:{$task->label}", $data);
        $data = $this->append("DTSTAMP:{$task->created_at->format('Ymd\THis\Z')}", $data);
        $data = $this->append("CREATED:{$task->created_at->format('Ymd\THis\Z')}", $data);
        $data = $this->append("LAST-MODIFIED:{$task->updated_at->format('Ymd\THis\Z')}", $data);
        if ($task->due_at) {
            $data = $this->append("DUE:{$task->due_at->format('Ymd\THis\Z')}", $data);
        }

        $data = $this->append("ATTACH:{$url}", $data);
        $data = $this->append('END:VTODO', $data);
        $data = $this->append('END:VCALENDAR', $data);

        $data = mb_ereg_replace("\n", "\r\n", $data);

        return $data;
    }

    protected function getDate(ContactImportantDate $date): string
    {
        $date = $date->refresh();
        $url = route('contact.show', [
            'vault' => $date->contact->vault_id,
            'contact' => $date->contact,
        ]);
        $sabreversion = \Sabre\VObject\Version::VERSION;

        $data = $this->append('BEGIN:VCALENDAR');
        $data = $this->append('VERSION:2.0', $data);
        $data = $this->append("PRODID:-//Sabre//Sabre VObject {$sabreversion}//EN", $data);
        $data = $this->append('CALSCALE:GREGORIAN', $data);
        $data = $this->append("SOURCE:{$url}", $data);

        $data = $this->append('BEGIN:VTIMEZONE', $data);
        $data = $this->append('TZID:UTC', $data);
        $data = $this->append('END:VTIMEZONE', $data);

        $data = $this->append('BEGIN:VEVENT', $data);
        $data = $this->append("UID:{$date->uuid}", $data);
        $data = $this->append("SUMMARY:{$date->label}", $data);
        $data = $this->append("DTSTART;VALUE=DATE:{$date->year}{$date->month}{$date->day}", $data);
        $day = $date->day + 1;
        $data = $this->append("DTEND;VALUE=DATE:{$date->year}{$date->month}{$day}", $data);
        $data = $this->append("RRULE:FREQ=YEARLY;BYMONTH={$date->month};BYMONTHDAY={$date->day}", $data);

        $data = $this->append("DTSTAMP:{$date->created_at->format('Ymd\THis\Z')}", $data);
        $data = $this->append("CREATED:{$date->created_at->format('Ymd\THis\Z')}", $data);
        $data = $this->append("LAST-MODIFIED:{$date->updated_at->format('Ymd\THis\Z')}", $data);

        $data = $this->append("ATTACH:{$url}", $data);
        $data = $this->append("DESCRIPTION:See {$date->contact->name} profile {$url}", $data);
        $data = $this->append('END:VEVENT', $data);
        $data = $this->append('END:VCALENDAR', $data);

        $data = mb_ereg_replace("\n", "\r\n", $data);

        return $data;
    }

    /**
     * Append content to the vcf, and split if the line is greater than 76 characters.
     */
    protected function append(string $content, string $data = ''): string
    {
        $tab = '';
        while (mb_strlen($tab.$content) > 75) {
            $chunk = mb_substr($tab.$content, 0, 75);
            $content = mb_substr($tab.$content, 75);
            $data .= $chunk."\n";
            $tab = ' ';
        }
        $data .= $tab.$content."\n";

        return $data;
    }
}
