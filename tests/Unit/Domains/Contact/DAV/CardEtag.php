<?php

namespace Tests\Unit\Domains\Contact\DAV;

use App\Models\Contact;

trait CardEtag
{
    protected function getEtag($obj, bool $quotes = false)
    {
        $data = '';
        if ($obj instanceof Contact) {
            $data = $this->getCard($obj, true);
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
        $url = route('contact.show', [
            'vault' => $contact->vault_id,
            'contact' => $contact->id,
        ]);
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

        if ($contact->nickname) {
            $data .= "NICKNAME:{$contact->nickname}\n";
        }

        if ($contact->gender) {
            $data .= "GENDER:{$contact->gender->type}\n";
        }

        $data .= "REV:{$timestamp}\n";
        $data .= "END:VCARD\n";

        if ($realFormat) {
            $data = mb_ereg_replace("\n", "\r\n", $data);
        }

        return $data;
    }
}
