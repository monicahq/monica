<?php

namespace App\Domains\Settings\ManageUserPreferences\Services;

use App\Models\User;
use App\Services\BaseService;

class StoreHelpPreference extends BaseService
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
            'visibility' => 'required|boolean',
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
     * Saves the help preferences.
     * If it's set to true, it will show an help button next to the main features
     * on the screen.
     */
    public function execute(array $data): User
    {
        $this->data = $data;

        $this->validateRules($data);
        $this->updateUser();

        return $this->author;
    }

    private function updateUser(): void
    {
        $this->author->help_shown = $this->data['visibility'];
        $this->author->save();
    }
}
