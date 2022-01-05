<?php

namespace App\Jobs\Dav;

use Illuminate\Support\Arr;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Model\ContactUpdateDto;

class GetMultipleVCard implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var AddressBookSubscription
     */
    private $subscription;

    /**
     * @var array
     */
    private $hrefs;

    /**
     * Create a new job instance.
     *
     * @param  AddressBookSubscription  $subscription
     * @param  array  $hrefs
     * @return void
     */
    public function __construct(AddressBookSubscription $subscription, array $hrefs)
    {
        $this->subscription = $subscription->withoutRelations();
        $this->hrefs = $hrefs;
    }

    /**
     * Update the Last Consulted At field for the given contact.
     *
     * @return void
     */
    public function handle(): void
    {
        if (! $this->batching()) {
            return; // @codeCoverageIgnore
        }

        $datas = $this->subscription->getClient()
            ->addressbookMultiget([
                '{DAV:}getetag',
                $this->getAddressDataProperty(),
            ], $this->hrefs);

        collect($datas)
            ->filter(function (array $contact): bool {
                return isset($contact[200]);
            })
            ->each(function (array $contact, $href) {
                $this->updateVCard($contact, $href);
            });
    }

    /**
     * Update the contact.
     *
     * @param  array  $contact
     * @param  string  $href
     * @return void
     */
    private function updateVCard(array $contact, $href): void
    {
        $card = Arr::get($contact, '200.{'.CardDAVPlugin::NS_CARDDAV.'}address-data');

        if ($card !== null) {
            $dto = new ContactUpdateDto($href, Arr::get($contact, '200.{DAV:}getetag'), $card);

            if (($batch = $this->batch()) !== null) {
                $batch->add([
                    new UpdateVCard($this->subscription->user, $this->subscription->addressbook->name, $dto),
                ]);
            }
        }
    }

    /**
     * Get data for address-data property.
     *
     * @return array
     */
    private function getAddressDataProperty(): array
    {
        $addressDataAttributes = Arr::get($this->subscription->capabilities, 'addressData', [
            'content-type' => 'text/vcard',
            'version' => '4.0',
        ]);

        return [
            'name' => '{'.CardDAVPlugin::NS_CARDDAV.'}address-data',
            'value' => null,
            'attributes' => $addressDataAttributes,
        ];
    }
}
