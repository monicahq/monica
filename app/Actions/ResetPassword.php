<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Set a new password for somebody who followed a reset link.
 *
 * The token is checked against the one that was emailed, and thrown away once
 * it works, so a reset link cannot be used twice.
 */
class ResetPassword
{
    private User $user;

    public function __construct(
        private string $email,
        private readonly string $token,
        private readonly string $password,
    ) {}

    public function execute(): User
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->burnToken();

        return $this->user;
    }

    private function sanitize(): void
    {
        $this->email = mb_strtolower($this->email);
    }

    private function validate(): void
    {
        $user = User::query()->where('email', $this->email)->first();

        if ($user === null || ! Password::getRepository()->exists($user, $this->token)) {
            throw ValidationException::withMessages([
                'email' => __('That link no longer works. Ask for another one.'),
            ]);
        }

        $this->user = $user;
    }

    private function update(): void
    {
        new UpdateUserPassword(
            user: $this->user,
            password: $this->password,
        )->execute();
    }

    private function burnToken(): void
    {
        Password::getRepository()->delete($this->user);
    }
}
