<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\RestoreTrashItem;
use App\Enums\TrashItemKindEnum;
use App\Exceptions\CannotRestoreFromTheTrash;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VaultTrashRestorationController extends Controller
{
    public function update(Request $request, Vault $vault, string $kind, string $item): RedirectResponse
    {
        $kind = TrashItemKindEnum::from($kind);

        abort_unless($kind->isAvailableFor($vault->account), 404);

        $restoration = new RestoreTrashItem(
            user: $request->user(),
            vault: $vault,
            kind: $kind,
            uuid: $item,
        );

        try {
            $restored = $restoration->execute();
        } catch (CannotRestoreFromTheTrash $e) {
            return back()->with('error', $e->getMessage());
        } catch (ModelNotFoundException) {
            return redirect()
                ->route('vaults.trash.index', ['vault' => $vault])
                ->with('error', __('This item is no longer in the trash. It was restored or deleted for good in the meantime.'));
        }

        $status = $restoration->broughtBack() === []
            ? __(':name is back.', ['name' => $restored->title()])
            : __(':name is back, with what was deleted at the same time: :things.', ['name' => $restored->title(), 'things' => TrashItemKindEnum::countsPhrase($restoration->broughtBack())]);

        return redirect()
            ->route('vaults.trash.index', ['vault' => $vault])
            ->with('status', $status);
    }
}
