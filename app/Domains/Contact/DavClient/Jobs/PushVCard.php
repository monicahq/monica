<?php

namespace App\Domains\Contact\DavClient\Jobs;

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

    public const MODE_MATCH_NONE = 0;

    public const MODE_MATCH_ETAG = 1;

    public const MODE_MATCH_ANY = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AddressBookSubscription $subscription,
        public string $uri,
        public ?string $etag,
        public mixed $card,
        public string $contactId,
        public int $mode = self::MODE_MATCH_NONE

    ) {
        $this->subscription = $subscription->withoutRelations();
        $this->card = self::transformCard($card);
    }

    /**
     * Update the Last Consulted At field for the given contact.
     */
    public function handle(): void
    {
        Log::info(__CLASS__.' '.$this->uri);

        $contact = Contact::where('vault_id', $this->subscription->vault_id)
            ->findOrFail($this->contactId);

        $etag = $this->pushDistant();

        $contact->distant_etag = empty($etag) ? null : $etag;
        $contact->save();
    }

    private function pushDistant(): string
    {
        $headers = [];

        if ($this->mode === self::MODE_MATCH_ETAG) {
            $headers['If-Match'] = $this->etag;
        } elseif ($this->mode === self::MODE_MATCH_ANY) {
            $headers['If-Match'] = '*';
        }

        $response = $this->subscription->getClient()
            ->request('PUT', $this->uri, $this->card, $headers);

        return $response->header('Etag');
    }

    /**
     * Transform card.
     *
     * @param  string|resource  $card
     */
    protected static function transformCard(mixed $card): string
    {
        if (is_resource($card)) {
            return tap(stream_get_contents($card), fn () => fclose($card));
        }

        return $card;
    }
}
