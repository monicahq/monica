<?php

namespace App\Jobs\Dav;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Account\AddressBookSubscription;

class DeleteMultipleVCard implements ShouldQueue
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
            return;
        }

        collect($this->hrefs)
            ->each(function ($href) {
                $this->deleteVCard($href);
            });
    }

    /**
     * Delete the contact.
     *
     * @param  string  $href
     * @return void
     */
    private function deleteVCard($href): void
    {
        if (($batch = $this->batch()) !== null) {
            $batch->add([
                new DeleteVCard($this->subscription,$href),
            ]);
        }
    }
}
