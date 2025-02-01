<?php

namespace App\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

abstract class BaseService
{
    /**
     * The user who calls the service.
     */
    public User $author;

    /**
     * The vault object.
     */
    public Vault $vault;

    /**
     * The contact object.
     */
    public ?Contact $contact = null;

    /**
     * The group object.
     */
    public ?Group $group = null;

    /**
     * Dependencies between permissions.
     *
     * @var array<string,array<string>>
     */
    private static array $permissionDependencies = [
        'author_must_belong_to_account' => [],
        'author_must_be_account_administrator' => [
            'author_must_belong_to_account',
        ],
        'vault_must_belong_to_account' => [],
        'author_must_be_vault_manager' => [
            'vault_must_belong_to_account',
            'author_must_belong_to_account',
        ],
        'author_must_be_vault_editor' => [
            'vault_must_belong_to_account',
            'author_must_belong_to_account',
        ],
        'author_must_be_in_vault' => [
            'vault_must_belong_to_account',
            'author_must_belong_to_account',
        ],
        'contact_must_belong_to_vault' => [
            'vault_must_belong_to_account',
            'author_must_belong_to_account',
        ],
        'group_must_belong_to_vault' => [
            'vault_must_belong_to_account',
            'author_must_belong_to_account',
        ],
    ];

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Get the permissions that users need to execute the service.
     */
    public function permissions(): array
    {
        return [];
    }

    /**
     * Get author's account.
     */
    public function account(): Account
    {
        return $this->author->account;
    }

    /**
     * Validate an array against a set of rules.
     */
    public function validateRules(array $data): bool
    {
        Validator::make($data, $this->rules())->validate();

        $permissions = collect($this->permissions());

        foreach (self::$permissionDependencies as $key => $values) {
            if ($permissions->contains($key)) {
                collect($values)->each(function ($value) use ($permissions, $key) {
                    if (! $permissions->contains($value)) {
                        throw new \Exception("$key requires $value");
                    }
                });

                $this->validatePermission($key, $data);
            }
        }

        if (($e = $permissions->diff(collect(self::$permissionDependencies)->keys()))->isNotEmpty()) {
            throw new \Exception('Unknown permission: '.$e->first());
        }

        return true;
    }

    /**
     * Validate a permission.
     */
    private function validatePermission(string $permission, array $data): void
    {
        switch ($permission) {
            case 'author_must_belong_to_account':
                $this->validateAuthorBelongsToAccount($data);
                break;
            case 'author_must_be_account_administrator':
                $this->validateAuthorIsAccountAdministrator();
                break;
            case 'vault_must_belong_to_account':
                $this->validateVaultExists($data);
                break;
            case 'author_must_be_vault_manager':
                $this->validateUserPermissionInVault(Vault::PERMISSION_MANAGE);
                break;
            case 'author_must_be_vault_editor':
                $this->validateUserPermissionInVault(Vault::PERMISSION_EDIT);
                break;
            case 'author_must_be_in_vault':
                $this->validateUserPermissionInVault(Vault::PERMISSION_VIEW);
                break;
            case 'contact_must_belong_to_vault':
                $this->validateContactBelongsToVault($data);
                break;
            case 'group_must_belong_to_vault':
                $this->validateGroupBelongsToVault($data);
                break;
            default:
                throw new \Exception("Unknown permission: $permission");
        }
    }

    /**
     * Validate that the author of the action belongs to the account.
     */
    private function validateAuthorBelongsToAccount(array $data): void
    {
        $this->author = User::where('account_id', $data['account_id'])
            ->findOrFail($data['author_id']);
    }

    /**
     * Validate that the author of the action is the account administrator.
     *
     * @throws NotEnoughPermissionException
     */
    private function validateAuthorIsAccountAdministrator(): void
    {
        if (! $this->author->is_account_administrator) {
            throw new NotEnoughPermissionException;
        }
    }

    /**
     * Validate that the vault belongs to the account.
     */
    private function validateVaultExists(array $data): void
    {
        $this->vault = Vault::where('account_id', $data['account_id'])
            ->findOrFail($data['vault_id']);
    }

    /**
     * Validate that the user has the right to do what he's supposed to do in
     * the given vault.
     */
    public function validateUserPermissionInVault(int $permission): void
    {
        $exists = $this->author->vaults()
            ->where('vaults.id', $this->vault->id)
            ->wherePivot('permission', '<=', $permission)
            ->exists();

        if (! $exists) {
            throw new NotEnoughPermissionException;
        }
    }

    /**
     * Validate that the contact belongs to the account.
     */
    public function validateContactBelongsToVault(array $data): void
    {
        if (isset($data['contact_id'])) {
            $this->contact = $this->vault->contacts()
                ->findOrFail($data['contact_id']);

            if ($this->contact->vault_id !== $this->vault->id) {
                throw new ModelNotFoundException;
            }
        }
    }

    /**
     * Validate that the group belongs to the account.
     */
    public function validateGroupBelongsToVault(array $data): void
    {
        if (isset($data['group_id'])) {
            $this->group = $this->vault->groups()
                ->findOrFail($data['group_id']);

            if ($this->group->vault_id !== $this->vault->id) {
                throw new ModelNotFoundException;
            }
        }
    }

    /**
     * Returns the value if it's defined, or false otherwise.
     *
     * @param  mixed  $data
     * @param  mixed  $index
     * @return mixed
     */
    public function valueOrFalse($data, $index)
    {
        if (empty($data[$index])) {
            return false;
        }

        return $data[$index];
    }

    /**
     * Returns the value if it's defined, or true otherwise.
     *
     * @param  mixed  $data
     * @param  mixed  $index
     * @return mixed
     */
    public function valueOrTrue($data, $index)
    {
        if (empty($data[$index]) && $data[$index] !== false) {
            return true;
        }

        return $data[$index];
    }

    /**
     * Checks if the value is empty or null.
     *
     * @param  mixed  $data
     * @param  mixed  $index
     * @return mixed
     */
    public function valueOrNull($data, $index)
    {
        if (empty($data[$index])) {
            return;
        }

        return $data[$index];
    }
}
