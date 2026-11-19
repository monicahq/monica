<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Helpers\FlagState;
use App\Models\Account;
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

    /** @return list<array{name: string, isOnForEveryAccount: bool, reach: string, activateUrl: string, deactivateUrl: string}> */
    public function flags(): array
    {
        $accounts = Account::query()->count();

        return array_map(fn (string $flag): array => [
            'name' => $flag,
            'isOnForEveryAccount' => Feature::for(null)->active($flag),
            'reach' => trans_choice('On for :on of :count account|On for :on of :count accounts', $accounts, ['on' => FlagState::accountsOn($flag)]),
            'activateUrl' => route('admin.flags.activation.create', ['flag' => $flag]),
            'deactivateUrl' => route('admin.flags.activation.destroy', ['flag' => $flag]),
        ], array_values(Feature::defined()));
    }
}
