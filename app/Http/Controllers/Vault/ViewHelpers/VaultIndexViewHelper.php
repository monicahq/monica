<?php

namespace App\Http\Controllers\Vault\ViewHelpers;

use function route;
use App\Models\User;
use App\Models\Vault;
use function collect;
use Illuminate\Support\Facades\DB;
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
                'settings' => route('settings.index'),
                'logout' => route('logout'),
            ],
        ];
    }

    public static function data(User $user): array
    {
        $vaultIds = DB::table('user_vault')->where('user_id', $user->id)
            ->pluck('vault_id')->toArray();

        $vaults = Vault::where('account_id', $user->account->id)
            ->whereIn('id', $vaultIds)
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
                    'create' => route('vault.create'),
                ],
            ],
        ];
    }
}
