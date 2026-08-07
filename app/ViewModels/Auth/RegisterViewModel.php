<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

class RegisterViewModel extends GuestViewModel
{
    public function termsUrl(): string
    {
        return config('officelife.terms_url');
    }

    public function privacyUrl(): string
    {
        return config('officelife.privacy_url');
    }
}
