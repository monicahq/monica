<?php

namespace App\Domains\Settings\ManageUsers\Services;

use App\Interfaces\ServiceInterface;
use App\Mail\UserInvited;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InviteUser extends BaseService implements ServiceInterface
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
            'email' => 'required|unique:users|string|max:255',
            'is_administrator' => 'required|boolean',
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
     * Invite another user to the account.
     */
    public function execute(array $data): User
    {
        $this->data = $data;

        $this->validateRules($data);
        $this->createUser();
        $this->sendEmail();

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
}
