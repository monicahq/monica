<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\TextSanitizer;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Create a user. A user always belongs to a company. The password is null when
 * the user signs in through an SSO provider.
 */
class CreateUser
{
    private User $user;

    public function __construct(
        private readonly Company $company,
        private string $email,
        private readonly ?string $password = null,
        private ?string $ssoProvider = null,
        private readonly ?string $locale = null,
    ) {}

    public function execute(): User
    {
        $this->sanitize();
        $this->create();

        return $this->user;
    }

    private function sanitize(): void
    {
        $this->email = mb_strtolower(TextSanitizer::plainText($this->email));
        $this->ssoProvider = TextSanitizer::nullablePlainText($this->ssoProvider);
    }

    private function create(): void
    {
        $this->user = User::query()->create([
            'company_id' => $this->company->id,
            'email' => $this->email,
            'password_hash' => $this->password === null ? null : Hash::make($this->password),
            'password_changed_at' => $this->password === null ? null : now(),
            'sso_provider' => $this->ssoProvider,
            'is_active' => true,
            'locale' => $this->locale,
        ]);
    }
}
