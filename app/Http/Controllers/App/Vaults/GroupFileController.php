<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\AddGroupFile;
use App\Actions\RemoveGroupFile;
use App\Exceptions\NotEnoughStorageLeft;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\Vault;
use App\ViewModels\Vaults\GroupFilesViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupFileController extends Controller
{
    public function index(Request $request, Vault $vault, Group $group): View
    {
        return view('app.vaults.groups.files', [
            'viewModel' => new GroupFilesViewModel(user: $request->user(), vault: $vault, group: $group),
        ]);
    }

    public function create(Request $request, Vault $vault, Group $group): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.(int) config('monica.max_file_size_in_kilobytes')],
        ]);

        try {
            new AddGroupFile(
                user: $request->user(),
                vault: $vault,
                group: $group,
                upload: $validated['file'],
            )->execute();
        } catch (NotEnoughStorageLeft $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        return back()->with('status', __('The file is kept with the group.'));
    }

    public function destroy(Request $request, Vault $vault, Group $group, GroupFile $groupFile): RedirectResponse
    {
        abort_unless($groupFile->group_id === $group->id, 404);

        new RemoveGroupFile(
            user: $request->user(),
            vault: $vault,
            group: $group,
            attachment: $groupFile,
        )->execute();

        return redirect()->route('groups.files.index', ['vault' => $vault, 'group' => $group])
            ->with('status', __('The file is no longer kept with the group.'));
    }
}
