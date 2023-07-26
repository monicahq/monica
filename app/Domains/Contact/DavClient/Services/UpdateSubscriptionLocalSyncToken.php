<?php

namespace App\Domains\Contact\DavClient\Services;

use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Interfaces\ServiceInterface;
use App\Models\AddressBookSubscription;
use App\Services\QueuableService;
use Illuminate\Support\Facades\Log;

class UpdateSubscriptionLocalSyncToken extends QueuableService implements ServiceInterface
{
    /**
     * Create a new job instance.
     *
     * @param  array|null  $data  The data to run service.
     */
    public function __construct(
        public ?array $data = null
    ) {
        if ($data) {
            dump($data);
            $subscription = AddressBookSubscription::find($data['addressbook_subscription_id']);
            if ($subscription != null) {
                dump("subscription: {$subscription->id}");
            } else {
                dump('subscription not found');
            }
            // dump($subscription);
            $this->validateRules($data);
            dump('validation passed');
        }
    }

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'addressbook_subscription_id' => 'required|uuid|exists:addressbook_subscriptions,id',
        ];
    }

    public function execute(array $data): void
    {
        $this->validateRules($data);

        $subscription = AddressBookSubscription::findOrFail($data['addressbook_subscription_id']);

        $this->updateSyncToken($subscription);
    }

    /**
     * Update the synctoken.
     */
    private function updateSyncToken(AddressBookSubscription $subscription): void
    {
        $token = app(CardDAVBackend::class)
            ->withUser($subscription->user)
            ->getCurrentSyncToken($subscription->vault_id);

        if ($token !== null) {
            $subscription->sync_token_id = $token->id;
            $subscription->save();
        }

        Log::debug(__CLASS__.' '.__FUNCTION__.': '.$subscription->id.' '.$subscription->sync_token_id);
    }
}
