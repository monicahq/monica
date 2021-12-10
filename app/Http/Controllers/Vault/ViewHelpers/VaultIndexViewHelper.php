<?php

namespace App\Http\Controllers\Vault\ViewHelpers;

use function route;
use App\Models\Vault;
use function collect;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;

class VaultIndexViewHelper
{
    /**
     * Get all the data needed for the general layout page.
     *
     * @param  Vault|null  $vault
     * @return array
     */
    public static function layoutData(Vault $vault = null): array
    {
        return [
            'user' => [
                'name' => Auth::user()->name,
            ],
            'vault' => $vault ? [
                'id' => $vault->id,
                'name' => $vault->name,
            ] : null,
            'url' => [
                'vaults' => route('vault.index'),
                'logout' => route('logout'),
            ],
        ];
    }

    /**
     * Get all the data needed for the general layout page.
     *
     * @param  Account  $account
     * @return array
     */
    public static function data(Account $account): array
    {
        $vaults = Vault::where('account_id', $account->id)
            ->orderBy('name', 'asc')
            ->get();

        $vaultCollection = collect();
        foreach ($vaults as $vault) {
            $vaultCollection->push([
                'id' => $vault->id,
                'name' => $vault->name,
                'description' => $vault->description,
                'url' => [
                    'show' => route('vault.show', [
                        'vault' => $vault,
                    ]),
                ],
            ]);
        }

        return [
            'vaults' => $vaultCollection,
            'url' => [
                'vault' => [
                    'new' => route('vault.new'),
                ],
            ],
        ];
    }
}
