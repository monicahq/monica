<?php

namespace App\Domains\Contact\DavClient\Jobs;

use App\Models\AddressBookSubscription;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteMultipleVCard implements ShouldQueue
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

        $batch = $this->batch();

        collect($this->hrefs)
            ->each(fn ($href) => $this->deleteVCard($href, $batch));
    }

    /**
     * Delete the contact.
     */
    private function deleteVCard(string $href, Batch $batch): void
    {
        $batch->add([
            new DeleteVCard($this->subscription, $href),
        ]);
    }
}
