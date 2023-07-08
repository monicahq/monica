<?php

namespace App\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\DavClient\Services\Utils\Model\ContactPushDto;
use App\Models\AddressBookSubscription;
use App\Models\Contact;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushVCard implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private AddressBookSubscription $subscription,
        private ContactPushDto $contact
    ) {
        $this->subscription = $subscription->withoutRelations();
    }

    /**
     * Update the Last Consulted At field for the given contact.
     */
    public function handle(): void
    {
        if (! $this->batching()) {
            return;
        }

        Log::info(__CLASS__.' '.$this->contact->uri);

        $contact = Contact::where('vault_id', $this->subscription->vault_id)
            ->findOrFail($this->contact->contactId);

        $etag = $this->pushDistant();

        $contact->distant_etag = empty($etag) ? null : $etag;
        $contact->save();
    }

    private function pushDistant(): string
    {
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

        return $response->header('Etag');
    }
}
