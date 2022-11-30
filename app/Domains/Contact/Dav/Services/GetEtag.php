<?php

namespace App\Domains\Contact\Dav\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class GetEtag extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'contact_id' => 'required|integer|exists:contacts,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_in_vault',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Export etag of the VCard.
     *
     * @param  array  $data
     * @return string
     */
    public function execute(array $data): string
    {
        $this->validateRules($data);

        return $this->contact->distant_etag ?? '"'.hash('sha256', $this->contact->vcard).'"';
    }
}
