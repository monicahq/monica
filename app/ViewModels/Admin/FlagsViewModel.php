<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use Laravel\Pennant\Feature;

class FlagsViewModel
{
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
