<?php

namespace App\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\Dav\Jobs\UpdateVCard;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Models\AddressBookSubscription;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GetVCard implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private AddressBookSubscription $subscription,
        private ContactDto $contact
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

        $response = $this->subscription->getClient()
            ->request('GET', $this->contact->uri);

        $this->chainUpdateVCard($response->body());
    }

    private function chainUpdateVCard(string $card): void
    {
        if (($batch = $this->batch()) !== null) {
            $batch->add([
                new UpdateVCard([
                    'account_id' => $this->subscription->vault->account_id,
                    'author_id' => $this->subscription->user_id,
                    'vault_id' => $this->subscription->vault_id,
                    'uri' => $this->contact->uri,
                    'etag' => $this->contact->etag,
                    'card' => $card,
                ]),
            ]);
        }
    }
}
