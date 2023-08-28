<?php

namespace App\Domains\Contact\DavClient\Services;

use App\Domains\Contact\DavClient\Services\Utils\AddressBookGetter;
use App\Domains\Contact\DavClient\Services\Utils\Dav\DavClient;
use App\Domains\Contact\DavClient\Services\Utils\Dav\DavClientException;
use App\Models\AddressBookSubscription;
use App\Services\BaseService;

class CreateAddressBookSubscription extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'base_uri' => 'required|string|url',
            'username' => 'required|string',
            'password' => 'required|string',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_in_vault',
            'author_must_be_vault_manager',
            'vault_must_belong_to_account',
        ];
    }

    /**
     * Add a new Adress Book.
     *
     * @throws DavClientException
     */
    public function execute(array $data): AddressBookSubscription
    {
        $this->validateRules($data);

        $addressBookData = $this->getAddressBookData($data);
        if (! $addressBookData) {
            throw new DavClientException(trans('Could not get address book data.'));
        }

        return $this->createAddressBook($data, $addressBookData);
    }

    private function createAddressBook(array $data, array $addressBookData): AddressBookSubscription
    {
        return AddressBookSubscription::create([
            'user_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'username' => $data['username'],
            'password' => $data['password'],
            'uri' => $addressBookData['uri'],
            'capabilities' => $addressBookData['capabilities'],
        ]);
    }

    private function getAddressBookData(array $data): ?array
    {
        $client = $this->getClient($data);

        return app(AddressBookGetter::class)
            ->withClient($client)
            ->execute();
    }

    private function getClient(array $data): DavClient
    {
        return app(DavClient::class)
            ->setBaseUri($data['base_uri'])
            ->setCredentials($data['username'], $data['password']);
    }
}
