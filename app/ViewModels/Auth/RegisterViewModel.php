<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

class RegisterViewModel extends GuestViewModel
{
    public function termsUrl(): string
    {
        return config('monica.terms_url');
    }

    public function privacyUrl(): string
    {
        return config('monica.privacy_url');
    }
}
