<?php

declare(strict_types=1);

namespace App\ViewModels\Welcome;

use App\Models\User;

class WelcomeViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function caption(): string
    {
        return __('Welcome to :app', ['app' => config('app.name')]);
    }

    public function greeting(): string
    {
        return __('Hi :name, we’re glad you’re here.', [
            'name' => explode(' ', $this->user->first_name)[0],
        ]);
    }

    public function introduction(): string
    {
        return __('A place to remember the little things about the people who matter to you.');
    }

    /** @return array<int, string> */
    public function letter(): array
    {
        return [
            __('We’ve been working on Monica since 2017, and we still care deeply about making it better. Helping people look after their relationships is something we’re passionate about, and we’re happy you’ve chosen to let Monica be part of yours.'),
            __('What you write here is personal: a conversation you want to remember, something a friend is going through, a moment you shared. Thank you for trusting us with it. That trust means a lot to both of us.'),
            __('Start with one person and something you’d like to remember about them. Take your time, make this space your own, and tell us how it feels. Your feedback helps us decide what to improve next.'),
        ];
    }

    public function signatories(): string
    {
        return __(':first and :second', ['first' => 'Regis', 'second' => 'Alexis']);
    }

    public function signatoriesRole(): string
    {
        return __('The people behind :app', ['app' => config('app.name')]);
    }

    /** @return array<int, string> */
    public function portraitUrls(): array
    {
        return [
            asset('images/regis.webp'),
            asset('images/alexis.webp'),
        ];
    }

    public function dismissUrl(): string
    {
        return route('welcome.update');
    }
}
