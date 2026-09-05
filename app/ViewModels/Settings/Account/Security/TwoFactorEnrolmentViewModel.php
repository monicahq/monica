<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Models\User;
use Illuminate\Support\HtmlString;

class TwoFactorEnrolmentViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly string $secret,
        private readonly string $qrCode,
    ) {}

    public function qrCode(): HtmlString
    {
        return new HtmlString($this->qrCode);
    }

    public function secret(): string
    {
        return mb_trim(chunk_split($this->secret, 4, ' '));
    }

    public function email(): string
    {
        return $this->user->email;
    }

    public function user(): User
    {
        return $this->user;
    }
}
