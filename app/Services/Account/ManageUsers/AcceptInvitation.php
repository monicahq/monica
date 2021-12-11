<?php

namespace App\Services\Account\ManageUsers;

use Carbon\Carbon;
use App\Models\User;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class AcceptInvitation extends BaseService implements ServiceInterface
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
            'invitation_code' => 'required|uuid',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'password' => 'required|alpha_dash|string|max:255',
        ];
    }

    /**
     * Accept invitation.
     *
     * @param  array  $data
     * @return User
     */
    public function execute(array $data): User
    {
        $this->data = $data;

        $this->validateRules($data);
        $this->findUserByInvitationCode();
        $this->updateUser();

        return $this->user;
    }

    private function findUserByInvitationCode(): void
    {
        $this->user = User::where('invitation_code', $this->data['invitation_code'])
            ->whereNull('invitation_accepted_at')
            ->firstOrFail();
    }

    private function updateUser(): void
    {
        $this->user->is_account_administrator = false;
        $this->user->first_name = $this->data['first_name'];
        $this->user->last_name = $this->data['last_name'];
        $this->user->invitation_accepted_at = Carbon::now();
        $this->user->email_verified_at = Carbon::now();
        $this->user->save();
    }
}
