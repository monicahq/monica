<?php

namespace App\Http\Controllers\Vault\ViewHelpers;

use function route;

class VaultCreateViewHelper
{
    /**
     * Get all the data needed for the page.
     *
     * @return array
     */
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
