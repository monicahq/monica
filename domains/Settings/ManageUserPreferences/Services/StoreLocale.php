<?php

namespace App\Settings\ManageUserPreferences\Services;

use App\Interfaces\ServiceInterface;
use App\Models\User;
use App\Services\BaseService;

class StoreLocale extends BaseService implements ServiceInterface
{
    private array $data;

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
            'locale' => 'required|string|max:10',
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
        ];
    }

    /**
     * Store name order preference for the given user.
     *
     * @param  array  $data
     * @return User
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
        $this->author->locale = $this->data['locale'];
        $this->author->save();
    }
}
