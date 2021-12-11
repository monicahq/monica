<?php

namespace App\Http\Controllers\Vault\ViewHelpers;

use function route;

class VaultCreateViewHelper
{
    public static function data(): array
    {
        return [
            'url' => [
                'store' => route('vault.store'),
                'back' => route('vault.index'),
            ],
        ];
    }
}
