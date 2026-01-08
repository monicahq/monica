<?php

namespace App\Domains\Contact\ManageNotes\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\ManageNotes\Services\CreateNote;
use App\Models\Contact;
use Sabre\VObject\Component\VCard;

#[Order(40)]
class ImportNotes extends Importer implements ImportVCardResource
{
    /**
     * Test if the Card is handled by this importer.
     */
    public function handle(VCard $vcard): bool
    {
        return $this->kind($vcard) === 'individual';
    }

    /**
     * Import Contact notes from NOTE field.
     */
    public function import(VCard $vcard, ?VCardResource $result): ?VCardResource
    {
        /** @var Contact $contact */
        $contact = $result;

        if (! isset($vcard->NOTE)) {
            return $contact;
        }

        try {
            $noteContent = (string) $vcard->NOTE;
            
            // Clean up the note content
            // vCard notes can have \n for newlines
            $noteContent = str_replace('\n', "\n", $noteContent);
            $noteContent = trim($noteContent);

            if (! empty($noteContent)) {
                // Create a note for this contact
                (new CreateNote)->execute([
                    'account_id' => $this->account()->id,
                    'author_id' => $this->author()->id,
                    'vault_id' => $this->vault()->id,
                    'contact_id' => $contact->id,
                    'title' => 'Imported note',
                    'body' => $noteContent,
                ]);
            }
        } catch (\Exception $e) {
            // Fail silently - don't block contact import if note creation fails
            \Log::warning('Failed to import note for contact '.$contact->id.': '.$e->getMessage());
        }

        return $contact->refresh();
    }
}
