<?php

namespace App\Domains\Contact\DavClient\Jobs;

use App\Models\AddressBookSubscription;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        Log::debug(__CLASS__.' '.implode(',', $this->hrefs));

        $jobs = collect($this->hrefs)
            ->map(fn (string $href): DeleteVCard => $this->deleteVCard($href));

        $this->batch()->add($jobs);
    }

    /**
     * Delete the contact.
     */
    private function deleteVCard(string $href): DeleteVCard
    {
        Log::debug(__CLASS__.' deleteVCard:'.$href);

        return new DeleteVCard($this->subscription, $href);
    }
}
