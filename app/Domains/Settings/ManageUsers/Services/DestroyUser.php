<?php

namespace App\Domains\Settings\ManageUsers\Services;

use App\Interfaces\ServiceInterface;
use App\Models\User;
use App\Models\Vault;
use App\Services\BaseService;
use Dotenv\Exception\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyUser extends BaseService implements ServiceInterface
{
    private User $user;

    private array $data;

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
     * Destroy the given user.
     * The user who calls the service can't delete himself.
     */
    public function execute(array $data): void
    {
        $this->data = $data;

        $this->validate();
        $this->destroyAllVaults();
        $this->destroy();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        /** @var User */
        $user = $this->account()->users()
            ->findOrFail($this->data['user_id']);
        $this->user = $user;

        if ($this->data['user_id'] === $this->data['author_id']) {
            throw new ValidationException(
                'You can\'t delete yourself.',
            );
        }
    }

    /**
     * We will destroy all the vaults the user is the manager of, IF there are
     * no other managers of the vault.
     */
    private function destroyAllVaults(): void
    {
        $vaultsUserIsManagerOf = $this->user->vaults()
            ->wherePivot('permission', Vault::PERMISSION_MANAGE)
            ->get();

        foreach ($vaultsUserIsManagerOf as $vault) {
            try {
                $vault->users()
                    ->wherePivot('user_id', '!=', $this->user->id)
                    ->wherePivot('permission', Vault::PERMISSION_MANAGE)
                    ->firstOrFail();
            } catch (ModelNotFoundException) {
                $vault->delete();
            }
        }
    }

    private function destroy(): void
    {
        $this->user->delete();
    }
}
