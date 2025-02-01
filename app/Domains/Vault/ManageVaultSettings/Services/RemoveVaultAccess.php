<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Exceptions\SameUserException;
use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Services\BaseService;

class RemoveVaultAccess extends BaseService implements ServiceInterface
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
            'vault_id' => 'required|uuid|exists:vaults,id',
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
            'vault_must_belong_to_account',
            'author_must_be_vault_manager',
        ];
    }

    /**
     * Remove the access to the given vault to the given user.
     */
    public function execute(array $data): void
    {
        $this->data = $data;
        $this->validate();
        $this->remove();
        $this->removeAllRemindersForThisUserInThisVault();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        /** @var User */
        $user = $this->account()->users()
            ->findOrFail($this->data['user_id']);
        $this->user = $user;

        if ($this->user->id === $this->author->id) {
            throw new SameUserException;
        }
    }

    /**
     * Thanks to relational databases, if we delete the contact linked to the
     * user we want to remove from the vault, it will delete the access in the
     * `user_vault` table, effectively removing the user's access.
     */
    private function remove(): void
    {
        $vault = $this->user->vaults()
            ->wherePivot('vault_id', $this->vault->id)
            ->first();

        if ($vault !== null) {
            Contact::find($vault->pivot->contact_id)->delete();
        }
    }

    /**
     * We need to remove all the contact reminders that were scheduled for this
     * user in this vault.
     */
    private function removeAllRemindersForThisUserInThisVault(): void
    {
        $this->user->notificationChannels->each(function (UserNotificationChannel $notificationChannel) {
            $notificationChannel->contactReminders->each(function ($reminder) {
                $reminder->pivot->delete();
            });
        });
    }
}
