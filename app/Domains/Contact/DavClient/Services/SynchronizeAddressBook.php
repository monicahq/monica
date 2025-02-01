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
    private AddressBookSubscription $subscription;

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

    public function execute(array $data): ?string
    {
        $this->validateRules($data);

        $this->validate($data);

        $force = Arr::get($data, 'force', false);

        return $this->synchronize($force);
    }

    private function synchronize(bool $force): ?string
    {
        if (! $this->subscription->active) {
            return null;
        }

        try {
            return app(AddressBookSynchronizer::class)
                ->withSubscription($this->subscription)
                ->execute($force);
        } catch (ClientException $e) {
            Log::channel('database')->error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [
                'body' => $e->hasResponse() ? $e->getResponse()->getBody() : null,
                $e,
            ]);
        }

        return null;
    }

    private function validate(array $data): void
    {
        $this->subscription = AddressBookSubscription::findOrFail($data['addressbook_subscription_id']);

        if ($this->subscription->user->account_id !== $data['account_id']) {
            throw new ModelNotFoundException;
        }

        // TODO: check if account is limited
    }
}
