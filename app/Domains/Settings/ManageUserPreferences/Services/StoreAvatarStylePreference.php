<?php

namespace App\Domains\Settings\ManageUserPreferences\Services;

use App\Models\User;
use App\Services\BaseService;

class StoreAvatarStylePreference extends BaseService
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
            'avatar_style' => 'nullable|string',
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
     * Saves the avatar style preferences.
     * Determines which dicebear style is used to generate avatar's across the app for this user.
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
        $this->author->avatar_style = $this->data['avatar_style'];
        $this->author->save();
    }
}
