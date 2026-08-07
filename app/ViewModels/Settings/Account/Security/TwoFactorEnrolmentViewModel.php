<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Models\Employee;
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
        private readonly ?Employee $employee,
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

    /**
     * The name to show and to draw initials from. Somebody whose account is not
     * attached to an employee record has only an email address to go by.
     */
    public function name(): string
    {
        return $this->employee->name ?? $this->user->email;
    }

    /**
     * The record the avatar draws from, so the sidebar can show the photo when
     * there is one. An account that belongs to nobody who works here has none.
     */
    public function employee(): ?Employee
    {
        return $this->employee;
    }

    public function companyName(): string
    {
        return $this->user->company->name;
    }
}
