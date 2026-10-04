<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Set a new password for somebody who followed a reset link.
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
        $this->reset();

        return $this->user;
    }

    private function sanitize(): void
    {
        $this->email = mb_strtolower($this->email);
    }

    private function reset(): void
    {
        $status = Password::reset([
            'email' => $this->email,
            'token' => $this->token,
            'password' => $this->password,
        ], function (User $user): void {
            $this->user = $user;

            $this->update();
        });

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => __(Password::InvalidToken),
            ]);
        }
    }

    private function update(): void
    {
        new UpdateUserPassword(
            user: $this->user,
            password: $this->password,
        )->execute();
    }
}
