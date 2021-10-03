<?php

namespace App\Jobs\Dav;

use App\Models\User\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
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

        Log::info(__CLASS__.' update '.$this->contact->uri);

        $backend = new CardDAVBackend($this->user);
        $newtag = $backend->updateCard($this->addressBookName, $this->contact->uri, $this->contact->card);

        if ($newtag !== $this->contact->etag) {
            Log::warning(__CLASS__.' wrong etag when updating contact. Expected '.$this->contact->etag.', get '.$newtag);
        }
    }
}
