<?php

namespace App\Domains\Contact\ManageAvatar\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;
use App\Services\GooglePhotoService;
use Exception;

class SuggestAvatar extends BaseService implements ServiceInterface
{
    /**
     * The contact instance.
     */
    private string $search_term;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array<string,string>
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
     *
     * @return array<string>
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
     *
     * @throws Exception
     */
    public function execute(array $data): array
    {
        $this->validate($data);
        $this->setSearchTerm($data);

        if (empty($this->getSearchTerm())) {
            return [];
        }

        try {
            return (new GooglePhotoService())->search($this->getSearchTerm());
        } catch (Exception $e) {
            return [];
        }
    }

    public function getSearchTerm(): string
    {
        return $this->search_term;
    }

    public function setSearchTerm(array $data): self
    {
        $this->search_term = $data['search_term'] ?? $this->contact->name;

        return $this;
    }

    /**
     * @throws Exception
     */
    private function validate(array $data): void
    {
        $this->validateRules($data);
    }
}
