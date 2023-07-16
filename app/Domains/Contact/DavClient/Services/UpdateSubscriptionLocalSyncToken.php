<?php

namespace App\Domains\Contact\DavClient\Services;

use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Models\AddressBookSubscription;
use App\Services\BaseService;

class UpdateSubscriptionLocalSyncToken extends BaseService
{
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
            $subscription->localSyncToken = $token->id;
            $subscription->save();
        }
    }
}
