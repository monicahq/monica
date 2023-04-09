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

    /**
     * Append content to the vcf, and split if the line is greater than 76 characters.
     */
    protected function append(string $content, string $data = ''): string
    {
        $tab = '';
        while (mb_strlen($content) > 75) {
            $chunk = mb_substr($content, 0, 75);
            $content = mb_substr($content, 75);
            $data .= $tab.$chunk."\n";
            $tab = ' ';
        }
        $data .= $tab.$content."\n";

        return $data;
    }
}
