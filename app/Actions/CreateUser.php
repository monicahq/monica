<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\TextSanitizer;
use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Create a user.
 */
class CreateUser
{
    private User $user;

    public function __construct(
        private readonly Account $account,
        private string $firstName,
        private string $lastName,
        private string $email,
        private readonly ?string $password = null,
        private ?string $ssoProvider = null,
        private readonly ?string $locale = null,
        private readonly ?string $timezone = null,
    ) {}

    public function execute(): User
    {
        $this->sanitize();
        $this->create();

        return $this->user;
    }

    private function sanitize(): void
    {
        $this->firstName = TextSanitizer::plainText($this->firstName);
        $this->lastName = TextSanitizer::plainText($this->lastName);
        $this->email = mb_strtolower(TextSanitizer::plainText($this->email));
        $this->ssoProvider = TextSanitizer::nullablePlainText($this->ssoProvider);
    }

    private function create(): void
    {
        $this->user = User::query()->create([
            'account_id' => $this->account->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'password' => $this->password === null ? null : Hash::make($this->password),
            'password_changed_at' => $this->password === null ? null : now(),
            'sso_provider' => $this->ssoProvider,
            'is_active' => true,
            'locale' => $this->locale,
            'timezone' => $this->timezone ?? config('app.timezone'),
        ]);
    }
}
