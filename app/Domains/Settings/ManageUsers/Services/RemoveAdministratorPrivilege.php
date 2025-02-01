<?php

namespace App\Domains\Settings\ManageUsers\Services;

use App\Interfaces\ServiceInterface;
use App\Models\User;
use App\Services\BaseService;

class RemoveAdministratorPrivilege extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'user_id' => 'required|uuid|exists:users,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Remove the administrator permission from another user.
     */
    public function execute(array $data): User
    {
        $this->validateRules($data);

        /** @var User */
        $user = $this->account()->users()
            ->findOrFail($data['user_id']);

        if ($user->id === $this->author->id) {
            throw new \Exception(trans('You cannot remove your own administrator privilege.'));
        }

        $user->is_account_administrator = false;
        $user->save();

        return $user;
    }
}
