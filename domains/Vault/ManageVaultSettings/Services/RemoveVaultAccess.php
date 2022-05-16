<?php

namespace App\Vault\ManageVaultSettings\Services;

use App\Exceptions\SameUserException;
use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Models\Contact;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class RemoveVaultAccess extends BaseService implements ServiceInterface
{
    private User $user;
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
            'vault_id' => 'required|integer|exists:vaults,id',
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
            'vault_must_belong_to_account',
            'author_must_be_vault_manager',
        ];
    }

    /**
     * Remove the access to the given vault to the given user.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->data = $data;
        $this->validate();
        $this->remove();
        $this->removeAllRemindersForThisUserInThisVault();

        $this->log();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->user = User::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['user_id']);

        if ($this->user->id === $this->author->id) {
            throw new SameUserException();
        }
    }

    /**
     * Thanks to relational databases, if we delete the contact linked to the
     * user we want to remove from the vault, it will delete the access in the
     * `user_vault` table, effectively removing the user's access.
     */
    private function remove(): void
    {
        $contact = DB::table('user_vault')
            ->where('vault_id', $this->vault->id)
            ->where('user_id', $this->user->id)
            ->select('contact_id')->first();

        Contact::find($contact->contact_id)->delete();
    }

    /**
     * We need to remove all the contact reminders that were scheduled for this
     * user in this vault.
     *
     * @return void
     */
    private function removeAllRemindersForThisUserInThisVault(): void
    {
        $userNotificationChannelIds = $this->user->notificationChannels()->pluck('id')->toArray();

        DB::table('contact_reminder_scheduled')
            ->whereIn('user_notification_channel_id', $userNotificationChannelIds)
            ->delete();
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'vault_access_removed',
            'objects' => json_encode([
                'user_name' => $this->user->name,
                'vault_name' => $this->vault->name,
            ]),
        ])->onQueue('low');
    }
}
