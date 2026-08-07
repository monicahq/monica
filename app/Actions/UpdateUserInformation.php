<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Update the details a user signs in with. Anybody in the account may do this
 * to anybody else in it.
 */
class UpdateUserInformation
{
    public function __construct(
        private readonly User $author,
        private readonly User $user,
        private string $email,
        private ?string $locale = null,
    ) {}

    public function execute(): User
    {
        $this->validate();
        $this->sanitize();
        $this->update();
        $this->log();

        return $this->user;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->author->account,
            user: $this->author,
            action: UserActionEnum::UserInformationUpdate,
            parameters: ['email' => $this->email],
        )->onQueue('low');
    }

    private function validate(): void
    {
        if ($this->author->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('User not found');
        }
    }

    private function sanitize(): void
    {
        $this->email = mb_strtolower(TextSanitizer::plainText($this->email));
        $this->locale = TextSanitizer::nullablePlainText($this->locale);
    }

    private function update(): void
    {
        $emailHasChanged = $this->user->email !== $this->email;

        $this->user->email = $this->email;
        $this->user->locale = $this->locale;

        if ($emailHasChanged) {
            $this->user->email_verified_at = null;
        }

        $this->user->save();
    }
}
