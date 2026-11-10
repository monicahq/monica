<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateGroupNote;
use App\Actions\DestroyGroupNote;
use App\Actions\UpdateGroupNote;
use App\Exceptions\NoteIsEmpty;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupNote;
use App\Models\Vault;
use App\ViewModels\Vaults\GroupOverviewViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupNoteController extends Controller
{
    public function create(Request $request, Vault $vault, Group $group): RedirectResponse
    {
        $validated = $request->validate(['body' => ['required', 'string', 'max:65535']], [
            'body.required' => __('Write something about the group.'),
        ]);

        try {
            new CreateGroupNote(user: $request->user(), vault: $vault, group: $group, body: $validated['body'])->execute();
        } catch (NoteIsEmpty $e) {
            return back()->withInput()->withErrors(['body' => $e->getMessage()]);
        }

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __('The note is saved on the group.'));
    }

    public function edit(Request $request, Vault $vault, Group $group, GroupNote $groupNote): View
    {
        abort_unless($groupNote->group_id === $group->id, 404);

        return view('app.vaults.groups.show', [
            'viewModel' => new GroupOverviewViewModel(
                user: $request->user(),
                vault: $vault,
                group: $group,
                editingNoteId: $groupNote->id,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, Group $group, GroupNote $groupNote): RedirectResponse
    {
        abort_unless($groupNote->group_id === $group->id, 404);

        $validated = $request->validateWithBag('editNote', ['body' => ['required', 'string', 'max:65535']], [
            'body.required' => __('Write something about the group.'),
        ]);

        try {
            new UpdateGroupNote(user: $request->user(), vault: $vault, group: $group, note: $groupNote, body: $validated['body'])->execute();
        } catch (NoteIsEmpty $e) {
            return back()->withInput()->withErrors(['body' => $e->getMessage()], 'editNote');
        }

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __('Changes saved'));
    }

    public function destroy(Request $request, Vault $vault, Group $group, GroupNote $groupNote): RedirectResponse
    {
        abort_unless($groupNote->group_id === $group->id, 404);

        new DestroyGroupNote(user: $request->user(), vault: $vault, group: $group, note: $groupNote)->execute();

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __('The note is deleted.'));
    }
}
