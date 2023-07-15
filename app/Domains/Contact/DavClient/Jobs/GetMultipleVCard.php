<?php

namespace App\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\Dav\Jobs\UpdateVCard;
use App\Models\AddressBookSubscription;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Sabre\CardDAV\Plugin as CardDav;

class GetMultipleVCard implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private AddressBookSubscription $subscription,
        private array $hrefs
    ) {
        $this->subscription = $subscription->withoutRelations();
    }

    /**
     * Update the Last Consulted At field for the given contact.
     */
    public function handle(): void
    {
        if (! $this->batching()) {
            return; // @codeCoverageIgnore
        }

        $data = $this->subscription->getClient()
            ->addressbookMultiget([
                '{DAV:}getetag',
                $this->getAddressDataProperty(),
            ], $this->hrefs);

        $jobs = collect($data)
            ->filter(fn (array $contact): bool => isset($contact[200]))
            ->map(fn (array $contact, string $href): ?UpdateVCard => $this->updateVCard($contact, $href))
            ->filter()
            ->toArray();

        $this->batch()->add($jobs);
    }

    /**
     * Update the contact.
     */
    private function updateVCard(array $contact, string $href): ?UpdateVCard
    {
        $card = Arr::get($contact, '200.{'.CardDav::NS_CARDDAV.'}address-data');

        return $card === null
            ? null
            : new UpdateVCard([
                'account_id' => $this->subscription->vault->account_id,
                'author_id' => $this->subscription->user_id,
                'vault_id' => $this->subscription->vault_id,
                'uri' => $href,
                'etag' => Arr::get($contact, '200.{DAV:}getetag'),
                'card' => $card,
            ]);
    }

    /**
     * Get data for address-data property.
     */
    private function getAddressDataProperty(): array
    {
        $addressDataAttributes = Arr::get($this->subscription->capabilities, 'addressData', [
            'content-type' => 'text/vcard',
            'version' => '4.0',
        ]);

        return [
            'name' => '{'.CardDav::NS_CARDDAV.'}address-data',
            'value' => null,
            'attributes' => $addressDataAttributes,
        ];
    }
}
