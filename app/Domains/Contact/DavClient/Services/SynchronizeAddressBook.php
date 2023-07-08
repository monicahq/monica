<?php

namespace App\Domains\Contact\DavClient\Services;

use App\Domains\Contact\DavClient\Services\Utils\AddressBookSynchronizer;
use App\Models\AddressBookSubscription;
use App\Services\BaseService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SynchronizeAddressBook extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'addressbook_subscription_id' => 'required|uuid|exists:addressbook_subscriptions,id',
            'force' => 'nullable|boolean',
        ];
    }

    public function execute(array $data): void
    {
        $this->validateRules($data);

        // TODO: check if account is limited

        $subscription = AddressBookSubscription::findOrFail($data['addressbook_subscription_id']);

        if ($subscription->user->account_id !== $data['account_id']) {
            throw new ModelNotFoundException();
        }

        if (! $subscription->active) {
            return;
        }

        try {
            $this->sync($data, $subscription);
        } catch (ClientException $e) {
            Log::error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [
                'body' => $e->hasResponse() ? $e->getResponse()->getBody() : null,
                $e,
            ]);
        }
    }

    private function sync(array $data, AddressBookSubscription $subscription)
    {
        $force = Arr::get($data, 'force', false);

        app(AddressBookSynchronizer::class)
            ->withSubscription($subscription)
            ->execute($force);
    }
}
