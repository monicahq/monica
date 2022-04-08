<?php

namespace App\Settings\ManageUsers\Services;

use App\Models\User;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class RemoveAdministratorPrivilege extends BaseService implements ServiceInterface
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
            'user_id' => 'required|integer|exists:users,id',
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
     * Remove the administrator permission from another user.
     *
     * @param  array  $data
     * @return User
     */
    public function execute(array $data): User
    {
        $this->validateRules($data);

        $user = User::where('account_id', $data['account_id'])
            ->findOrFail($data['user_id']);

        if ($user->id === $this->author->id) {
            throw new \Exception(trans('account.exception_remove_your_own_permission'));
        }

        $user->is_account_administrator = false;
        $user->save();

        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'administrator_privilege_removed',
            'objects' => json_encode([
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]),
        ])->onQueue('low');

        return $user;
    }
}
