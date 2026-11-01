<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\RestoreGroup;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GroupRestorationController extends Controller
{
    public function update(Request $request, Vault $vault, string $groupUuid): RedirectResponse
    {
        $group = Group::onlyTrashed()
            ->where('account_id', $request->user()->account_id)
            ->where('vault_id', $vault->id)
            ->where('uuid', $groupUuid)
            ->firstOrFail();

        new RestoreGroup(
            user: $request->user(),
            vault: $vault,
            group: $group,
        )->execute();

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __('The group :name is back.', ['name' => $group->name]));
    }
}
