<?php

namespace App\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
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
    public Contact $contact;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Get the permissions that users need to execute the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [];
    }

    /**
     * Validate an array against a set of rules.
     *
     * @param  array  $data
     * @return bool
     */
    public function validateRules(array $data): bool
    {
        $validator = Validator::make($data, $this->rules())->validate();

        if (in_array('author_must_belong_to_account', $this->permissions())) {
            $this->validateAuthorBelongsToAccount($data);
        }

        if (in_array('author_must_be_account_administrator', $this->permissions())) {
            $this->validateAuthorIsAccountAdministrator();
        }

        if (in_array('vault_must_belong_to_account', $this->permissions())) {
            $this->validateVaultExists($data);
        }

        if (in_array('author_must_be_vault_manager', $this->permissions())) {
            $this->validateUserPermissionInVault(Vault::PERMISSION_MANAGE);
        }

        if (in_array('author_must_be_vault_editor', $this->permissions())) {
            $this->validateUserPermissionInVault(Vault::PERMISSION_EDIT);
        }

        if (in_array('author_must_be_in_vault', $this->permissions())) {
            $this->validateUserPermissionInVault(Vault::PERMISSION_VIEW);
        }

        if (in_array('contact_must_belong_to_vault', $this->permissions())) {
            $this->validateContactBelongsToVault($data);
        }

        return true;
    }

    /**
     * Validate that the author of the action belongs to the account.
     *
     * @param  array  $data
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
            throw new NotEnoughPermissionException();
        }
    }

    /**
     * Validate that the vault belongs to the account.
     *
     * @param  array  $data
     */
    private function validateVaultExists(array $data): void
    {
        $this->vault = Vault::where('account_id', $data['account_id'])
            ->findOrFail($data['vault_id']);
    }

    /**
     * Validate that the user has the right to do what he's supposed to do in
     * the given vault.
     *
     * @param  int  $permission
     */
    public function validateUserPermissionInVault(int $permission): void
    {
        $exists = $this->author->vaults()
            ->where('vaults.id', $this->vault->id)
            ->wherePivot('permission', '<=', $permission)
            ->exists();

        if (! $exists) {
            throw new NotEnoughPermissionException();
        }
    }

    /**
     * Validate that the contact belongs to the account.
     *
     * @param  array  $data
     */
    public function validateContactBelongsToVault(array $data): void
    {
        $this->contact = Contact::where('vault_id', $data['vault_id'])
            ->findOrFail($data['contact_id']);
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

        return $data[$index] == '' ? null : $data[$index];
    }
}
