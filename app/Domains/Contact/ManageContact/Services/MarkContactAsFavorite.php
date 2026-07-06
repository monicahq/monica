<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Services\BaseService;
use Carbon\Carbon;

class MarkContactAsFavorite extends BaseService implements ServiceInterface
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Mark a contact as favorite.
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate();

        $this->contact->is_favorite = true;
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        return $this->contact;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }
}
