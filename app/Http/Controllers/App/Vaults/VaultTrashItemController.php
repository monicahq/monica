<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\DestroyTrashItem;
use App\Enums\TrashItemKindEnum;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VaultTrashItemController extends Controller
{
    public function destroy(Request $request, Vault $vault, string $kind, string $item): RedirectResponse
    {
        $kind = TrashItemKindEnum::from($kind);

        abort_unless($kind->isAvailableFor($vault->account), 404);

        try {
            new DestroyTrashItem(
                user: $request->user(),
                vault: $vault,
                kind: $kind,
                uuid: $item,
            )->execute();
        } catch (ModelNotFoundException) {
            return redirect()
                ->route('vaults.trash.index', ['vault' => $vault])
                ->with('error', __('This item is no longer in the trash. It was restored or deleted for good in the meantime.'));
        }

        return redirect()
            ->route('vaults.trash.index', ['vault' => $vault])
            ->with('status', __('Deleted for good.'));
    }
}
