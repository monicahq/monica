<?php

namespace App\Vault\ManageVaultSettings\Services;

use App\Contact\ManageReminders\Services\ScheduleContactReminderForUser;
use App\Exceptions\SameUserException;
use App\Helpers\AvatarHelper;
use App\Helpers\VaultHelper;
use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\User;
use App\Models\Vault;
use App\Services\BaseService;

class GrantVaultAccessToUser extends BaseService implements ServiceInterface
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
            'permission' => 'required|integer',
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
     * Grant the access to the given vault to the given user.
     *
     * @param  array  $data
     * @return User
     */
    public function execute(array $data): User
    {
        $this->data = $data;
        $this->validate();
        $this->grant();
        $this->scheduleContactReminders();
        $this->log();

        return $this->user;
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

    private function grant(): void
    {
        $contact = Contact::create([
            'vault_id' => $this->vault->id,
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'can_be_deleted' => false,
            'template_id' => $this->vault->default_template_id,
        ]);

        $this->vault->users()->save($this->user, [
            'permission' => $this->data['permission'],
            'contact_id' => $contact->id,
        ]);

        $avatar = AvatarHelper::generateRandomAvatar($contact);

        $contact->avatar_id = $avatar->id;
        $contact->save();
    }

    /**
     * When a user is granted access to a vault, we need to schedule all the
     * contact reminders for all the contacts in the vault, for the user.
     */
    private function scheduleContactReminders(): void
    {
        $contactIds = $this->vault->contacts->pluck('id')->toArray();
        $contactReminders = ContactReminder::whereIn('contact_id', $contactIds)->get();

        foreach ($contactReminders as $contactReminder) {

            // if the contact reminder is a one time reminder, we need to make
            // sure the `number_times_triggered` is equal to 0, as otherwise,
            // the reminder will be scheduled again, which we don't want.
            if ($contactReminder->type === ContactReminder::TYPE_ONE_TIME &&
                $contactReminder->number_times_triggered != 0) {
                continue;
            }

            (new ScheduleContactReminderForUser())->execute([
                'contact_reminder_id' => $contactReminder->id,
                'user_id' => $this->user->id,
            ]);
        }
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'vault_access_grant',
            'objects' => json_encode([
                'user_name' => $this->user->name,
                'vault_name' => $this->vault->name,
                'permission_type' => VaultHelper::getPermissionFriendlyName($this->data['permission']),
            ]),
        ])->onQueue('low');
    }
}
