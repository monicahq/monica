<?php

namespace App\Services\DavClient;

use App\Services\BaseService;
use App\Models\Account\AddressBookSubscription;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

class UpdateSubscriptionLocalSyncToken extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'addressbook_subscription_id' => 'required|integer|exists:addressbook_subscriptions,id',
        ];
    }

    /**
     * @param  array  $data
     * @return void
     */
    public function execute(array $data): void
    {
        $this->validate($data);

        $subscription = AddressBookSubscription::where('account_id', $data['account_id'])
            ->findOrFail($data['addressbook_subscription_id']);

        $this->updateSyncToken($subscription);
    }

    /**
     * Update the synctoken.
     *
     * @return void
     */
    private function updateSyncToken(AddressBookSubscription $subscription): void
    {
        $backend = app(CardDAVBackend::class)
            ->init($subscription->user);

        $token = $backend->getCurrentSyncToken($subscription->addressbook->name);

        if ($token !== null) {
            $subscription->localSyncToken = $token->id;
            $subscription->save();
        }
    }
}
