<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\EmptyVaultTrash;
use App\Enums\TrashItemKindEnum;
use App\Helpers\PageSize;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultTrashViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VaultTrashController extends Controller
{
    public function index(Request $request, Vault $vault, ?string $kind = null): View
    {
        $kind = $kind === null ? null : TrashItemKindEnum::from($kind);

        abort_if($kind !== null && ! $kind->isAvailableFor($vault->account), 404);

        return view('app.vaults.settings.trash', [
            'viewModel' => new VaultTrashViewModel(
                user: $request->user(),
                vault: $vault,
                kind: $kind,
                size: PageSize::from($request, VaultTrashViewModel::PER_PAGE),
                page: max(1, $request->integer('page', 1)),
            ),
        ]);
    }

    public function destroy(Request $request, Vault $vault): RedirectResponse
    {
        new EmptyVaultTrash(
            user: $request->user(),
            vault: $vault,
        )->execute();

        return redirect()
            ->route('vaults.trash.index', ['vault' => $vault])
            ->with('status', __('The trash is empty.'));
    }
}
