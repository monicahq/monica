<?php

namespace App\Services\Account\ManageUsers;

use App\Models\User;
use App\Mail\UserInvited;
use Illuminate\Support\Str;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;
use Illuminate\Support\Facades\Mail;

class InviteUser extends BaseService implements ServiceInterface
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
            'email' => 'required|unique:users|string|max:255',
            'is_administrator' => 'required|boolean',
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
     * Invite another user to the account.
     *
     * @param  array  $data
     * @return User
     */
    public function execute(array $data): User
    {
        $this->data = $data;

        $this->validateRules($data);
        $this->createUser();
        $this->sendEmail();
        $this->log();

        return $this->user;
    }

    private function createUser(): void
    {
        $this->user = User::create([
            'account_id' => $this->data['account_id'],
            'email' => $this->data['email'],
            'invitation_code' => (string) Str::uuid(),
            'is_account_administrator' => $this->data['is_administrator'],
        ]);
    }

    private function sendEmail(): void
    {
        Mail::to($this->user->email)
            ->queue(new UserInvited($this->user, $this->author));
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'user_invited',
            'objects' => json_encode([
                'user_email' => $this->user->email,
            ]),
        ]);
    }
}
