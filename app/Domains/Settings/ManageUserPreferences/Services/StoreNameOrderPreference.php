<?php

namespace App\Domains\Settings\ManageUserPreferences\Services;

use App\Interfaces\ServiceInterface;
use App\Models\User;
use App\Services\BaseService;

class StoreNameOrderPreference extends BaseService implements ServiceInterface
{
    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'name_order' => 'required|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
        ];
    }

    /**
     * Store name order preference for the given user.
     */
    public function execute(array $data): User
    {
        $this->data = $data;

        $this->validateRules($data);
        $this->checkNameOrderValidity();
        $this->updateUser();

        return $this->author;
    }

    private function checkNameOrderValidity(): void
    {
        // there should be at least one variable in the name order
        if (substr_count($this->data['name_order'], '%') < 1) {
            throw new \Exception('The name order must contain at least one variable.');
        }

        if (substr_count($this->data['name_order'], '%') % 2 == 1) {
            throw new \Exception('At least one % is missing to have a valid name order.');
        }
    }

    private function updateUser(): void
    {
        $this->author->name_order = $this->data['name_order'];
        $this->author->save();
    }
}
