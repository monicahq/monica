<?php

namespace App\Domains\Settings\ManageContactInformationTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactInformationType;
use App\Services\BaseService;

class DestroyContactInformationType extends BaseService implements ServiceInterface
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
            'contact_information_type_id' => 'required|integer|exists:contact_information_types,id',
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
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Destroy a contact information type.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $type = ContactInformationType::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_information_type_id']);

        $type->delete();
    }
}
