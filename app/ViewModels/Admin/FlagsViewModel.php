<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Models\User;
use App\ViewModels\Auth\ConfirmPasswordViewModel;
use Laravel\Pennant\Feature;

class FlagsViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function credentials(): ConfirmPasswordViewModel
    {
        return new ConfirmPasswordViewModel(user: $this->user);
    }

    /** @return list<array{name: string, isOnForEveryAccount: bool, activateUrl: string, deactivateUrl: string}> */
    public function flags(): array
    {
        return array_map(fn (string $flag): array => [
            'name' => $flag,
            'isOnForEveryAccount' => Feature::for(null)->active($flag),
            'activateUrl' => route('admin.flags.activation.create', ['flag' => $flag]),
            'deactivateUrl' => route('admin.flags.activation.destroy', ['flag' => $flag]),
        ], array_values(Feature::defined()));
    }
}
