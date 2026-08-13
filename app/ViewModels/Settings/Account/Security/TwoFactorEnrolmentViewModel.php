<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Models\User;
use Illuminate\Support\HtmlString;

/**
 * What the screen that sets up two factor authentication shows: the square to
 * point a camera at, the same secret written out for anybody who cannot, and
 * the shell around it.
 */
class TwoFactorEnrolmentViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly string $secret,
        private readonly string $qrCode,
    ) {}

    /**
     * The square, as svg meant to be written into the page as it is. It is ours
     * rather than the visitor's, so it is marked safe to print.
     */
    public function qrCode(): HtmlString
    {
        return new HtmlString($this->qrCode);
    }

    /**
     * The same secret the square carries, in groups of four, for somebody whose
     * authenticator app is on the machine they are reading this on and has no
     * camera to point.
     */
    public function secret(): string
    {
        return mb_trim(chunk_split($this->secret, 4, ' '));
    }

    /**
     * The address the authenticator app files the account under, so the screen
     * can say which account the square belongs to.
     */
    public function email(): string
    {
        return $this->user->email;
    }

    public function user(): User
    {
        return $this->user;
    }
}
