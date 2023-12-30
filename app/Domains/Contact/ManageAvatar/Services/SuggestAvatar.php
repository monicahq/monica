<?php

namespace App\Domains\Contact\ManageAvatar\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;
use App\Services\GooglePhotoService;

class SuggestAvatar extends BaseService implements ServiceInterface
{
    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
            'search_term' => 'nullable|string',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Remove the current file used as avatar and put the default avatar back.
     */
    public function execute(array $data): array
    {
        $this->data = $data;
        $this->validate();

        $search_term = $data['search_term'] ?? $this->contact->name;

        if (empty($search_term)) {
            return [];
        }

        try {
            return (new GooglePhotoService())->search($search_term);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @throws \Exception
     */
    private function validate(): void
    {
        $this->validateRules($this->data);
    }
}
