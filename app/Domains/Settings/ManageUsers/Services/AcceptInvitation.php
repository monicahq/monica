<?php

namespace App\Domains\Settings\ManageUsers\Services;

use App\Domains\Settings\ManageNotificationChannels\Services\CreateUserNotificationChannel;
use App\Interfaces\ServiceInterface;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AcceptInvitation extends BaseService implements ServiceInterface
{
    private User $user;

    private array $data;

    /**
     * Get the validation rules that apply to the service.
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
     */
    public function execute(array $data): User
    {
        $this->data = $data;

        $this->validateRules($data);
        $this->findUserByInvitationCode();
        $this->updateUser();
        $this->createNotificationChannel();

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
        $this->user->password = Hash::make($this->data['password']);
        $this->user->timezone = 'UTC';
        $this->user->save();
    }

    private function createNotificationChannel(): void
    {
        $channel = (new CreateUserNotificationChannel)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => 'Email address',
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => $this->user->email,
            'verify_email' => false,
            'preferred_time' => '09:00',
        ]);

        $channel->verified_at = Carbon::now();
        $channel->active = true;
        $channel->save();
    }
}
