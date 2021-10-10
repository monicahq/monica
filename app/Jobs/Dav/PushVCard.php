<?php

namespace App\Jobs\Dav;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
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

        if ($this->contact->mode === 1) {
            $headers['If-Match'] = $this->contact->etag;
        } elseif ($this->contact->mode === 2) {
            $headers['If-Match'] = '*';
        }

        $response = $this->subscription->getClient()
            ->request('PUT', $this->contact->uri, $this->contact->card, $headers);

        if (! empty($etag = $response->header('Etag')) && $etag !== $this->contact->etag) {
            Log::warning(__CLASS__.' wrong etag when updating contact. Expected '.$this->contact->etag.', get '.$etag);
        }
    }
}
