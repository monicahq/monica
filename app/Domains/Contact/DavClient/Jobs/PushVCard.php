<?php

namespace App\Domains\Contact\DavClient\Jobs;

use App\Models\AddressBookSubscription;
use App\Models\Contact;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use function Safe\fclose;
use function Safe\stream_get_contents;

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
     * Push VCard data to the distance server.
     */
    public function handle(): void
    {
        Log::shareContext([
            'addressbook_subscription_id' => $this->subscription->id,
        ]);

        try {
            $this->run();
        } finally {
            Log::flushSharedContext();
        }
    }

    /**
     * Run the job.
     */
    private function run(): void
    {
        $contact = Contact::where('vault_id', $this->subscription->vault_id)
            ->findOrFail($this->contactId);

        $etag = $this->pushDistant();

        if ($contact->distant_uri !== null) {
            Contact::withoutTimestamps(function () use ($contact, $etag): void {

                $contact->distant_etag = empty($etag) ? null : $etag;

                $contact->save();
            });
        }
    }

    private function pushDistant(int $depth = 1): string
    {
        try {
            Log::channel('database')->debug("Push card {$this->uri}");

            $response = $this->subscription->getClient()
                ->request('PUT', $this->uri, $this->card, $this->headers());

            return $response->header('Etag');
        } catch (RequestException $e) {
            if ($depth > 0 && $e->response->status() === 412) {
                // If the status is 412 (Precondition Failed), then we retry once with a mode match NONE
                $this->mode = self::MODE_MATCH_NONE;

                return $this->pushDistant(--$depth);
            } else {
                Log::channel('database')->error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [
                    'body' => $e->response->body(),
                    $e,
                ]);
                $this->fail($e);

                throw $e;
            }
        }
    }

    /**
     * Get the headers for the request.
     */
    private function headers(): array
    {
        $headers = [];

        if ($this->mode === self::MODE_MATCH_ETAG) {
            $headers['If-Match'] = $this->etag;
        } elseif ($this->mode === self::MODE_MATCH_ANY) {
            $headers['If-Match'] = '*';
        }

        return $headers;
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
