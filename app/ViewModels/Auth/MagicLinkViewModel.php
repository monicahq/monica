<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

class MagicLinkViewModel extends GuestViewModel
{
    public function minutes(): int
    {
        return (int) config('monica.magic_link_duration_minutes');
    }
}
