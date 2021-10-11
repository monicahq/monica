<?php

namespace App\Jobs\Dav;

use App\Models\User\User;
use Illuminate\Support\Arr;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use App\Services\VCard\GetEtag;
use App\Services\VCard\ImportVCard;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\DavClient\Utils\Model\ContactUpdateDto;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

class UpdateVCard implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User
     */
    private $user;

    /**
     * @var string
     */
    private $addressBookName;

    /**
     * @var ContactUpdateDto
     */
    private $contact;

    /**
     * Create a new job instance.
     *
     * @param  User  $user
     * @param  string  $addressBookName
     * @param  ContactUpdateDto  $contact
     * @return void
     */
    public function __construct(User $user, string $addressBookName, ContactUpdateDto $contact)
    {
        $this->user = $user->withoutRelations();
        $this->addressBookName = $addressBookName;
        $this->contact = $contact;
    }

    /**
     * Update the Last Consulted At field for the given contact.
     *
     * @return void
     */
    public function handle(): void
    {
        if (! $this->batching()) {
            return;
        }

        $newtag = $this->updateCard($this->addressBookName, $this->contact->uri, $this->contact->card);

        if (! is_null($this->contact->etag) && $newtag !== $this->contact->etag) {
            Log::warning(__CLASS__.' wrong etag when updating contact. Expected '.$this->contact->etag.', get '.$newtag, [
                'contacturl' => $this->contact->uri,
                'carddata' => $this->contact->card,
            ]);
        }
    }

    /**
     * Update the contact with the carddata.
     *
     * @param  mixed  $addressBookId
     * @param  string  $cardUri
     * @param  string  $cardData
     * @return string|null
     */
    private function updateCard($addressBookId, $cardUri, $cardData): ?string
    {
        $backend = app(CardDAVBackend::class)->init($this->user);

        $contact_id = null;
        if ($cardUri) {
            $contact = $backend->getObject($addressBookId, $cardUri);

            if ($contact) {
                $contact_id = $contact->id;
            }
        }

        try {
            $result = app(ImportVCard::class)
                ->execute([
                    'account_id' => $this->user->account_id,
                    'user_id' => $this->user->id,
                    'contact_id' => $contact_id,
                    'entry' => $cardData,
                    'etag' => $this->contact->etag,
                    'behaviour' => ImportVCard::BEHAVIOUR_REPLACE,
                    'addressBookName' => $addressBookId === $backend->backendUri() ? null : $addressBookId,
                ]);

            if (! Arr::has($result, 'error')) {
                return app(GetEtag::class)->execute([
                    'account_id' => $this->user->account_id,
                    'contact_id' => $result['contact_id'],
                ]);
            }
        } catch (\Exception $e) {
            Log::debug(__CLASS__.' updateCard: '.$e->getMessage(), [
                $e,
                'contacturl' => $cardUri,
                'carddata' => $cardData,
            ]);
            throw $e;
        }

        return null;
    }
}
