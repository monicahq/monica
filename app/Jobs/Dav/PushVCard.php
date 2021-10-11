<?php

namespace App\Jobs\Dav;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Model\ContactPushDto;

class PushVCard implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var AddressBookSubscription
     */
    private $subscription;

    /**
     * @var ContactPushDto
     */
    private $contact;

    /**
     * Create a new job instance.
     *
     * @param  AddressBookSubscription  $subscription
     * @param  ContactPushDto  $contact
     * @return void
     */
    public function __construct(AddressBookSubscription $subscription, ContactPushDto $contact)
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
        if (! $this->batching()) {
            return;
        }

        Log::info(__CLASS__.' '.$this->contact->uri);

        $headers = [];

        switch ($this->contact->mode) {
            case ContactPushDto::MODE_MATCH_ETAG:
                $headers['If-Match'] = $this->contact->etag;
                break;
            case ContactPushDto::MODE_MATCH_ANY:
                $headers['If-Match'] = '*';
                break;
        }

        $response = $this->subscription->getClient()
            ->request('PUT', $this->contact->uri, $this->contact->card, $headers);

        $etag = $response->header('Etag');

        $contact = Contact::where('account_id', $this->subscription->account_id)
            ->findOrFail($this->contact->contactId);
        $contact->distant_etag = empty($etag) ? null : $etag;
        $contact->save();
    }
}
