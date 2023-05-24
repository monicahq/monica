<?php

namespace App\Domains\Settings\ManageUserPreferences\Services;

use App\Interfaces\ServiceInterface;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Validation\Rule;

class StoreNumberFormatPreference extends BaseService implements ServiceInterface
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
            'number_format' => [
                'required',
                'string',
                Rule::in(User::NUMBER_FORMAT_TYPES),
            ],
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
     * Store date format preferences for the given user.
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
        $this->author->number_format = $this->data['number_format'];
        $this->author->save();
    }
}
