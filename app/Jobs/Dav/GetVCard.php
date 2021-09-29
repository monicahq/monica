<?php

namespace App\Jobs\Dav;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Model\ContactDto;
use App\Services\DavClient\Utils\Model\ContactUpdateDto;

class GetVCard implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var AddressBookSubscription
     */
    private $subscription;

    /**
     * @var ContactDto
     */
    private $contact;

    /**
     * Create a new job instance.
     *
     * @param  string  $addressBookName
     * @param  ContactDto  $contact
     * @return void
     */
    public function __construct(AddressBookSubscription $subscription, ContactDto $contact)
    {
        $this->subscription = $subscription->withoutRelations();
        $this->contact = $contact;
    }

    /**
     * Update the Last Consulted At field for the given contact.
     *
     * @return void
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        Log::info(__CLASS__.' getVCard '.$this->contact->uri);

        $response = $this->subscription->getRequest()
            ->get($this->contact->uri);

        $response->throw();

        if (($card = $response->body()) !== null) {
            $dto = new ContactUpdateDto($this->contact->uri, $this->contact->etag, $card);
            $this->batch()->add([
                new UpdateVCard($this->subscription->user, $this->subscription->addressbook->name, $dto),
            ]);
        }
    }
}
