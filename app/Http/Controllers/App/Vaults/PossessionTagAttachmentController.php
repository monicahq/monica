<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\AttachPossessionTag;
use App\Actions\CreatePossessionTag;
use App\Actions\DetachPossessionTag;
use App\Helpers\PossessionTagNames;
use App\Http\Controllers\Controller;
use App\Models\Possession;
use App\Models\PossessionTag;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PossessionTagAttachmentController extends Controller
{
    public function create(Request $request, Vault $vault, Possession $possession): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:'.PossessionTagNames::MAX_LENGTH],
        ]);

        $user = $request->user();

        $possessionTag = new CreatePossessionTag(
            user: $user,
            name: $request->string('name')->toString(),
            colour: $user->account->nextPossessionTagColour(),
        )->execute();

        new AttachPossessionTag(
            user: $user,
            possession: $possession,
            possessionTag: $possessionTag,
        )->execute();

        return back()->with('status', __('Created “:name” and added it.', ['name' => $possessionTag->name]));
    }

    public function update(Request $request, Vault $vault, Possession $possession, PossessionTag $possessionTag): RedirectResponse
    {
        new AttachPossessionTag(
            user: $request->user(),
            possession: $possession,
            possessionTag: $possessionTag,
        )->execute();

        return back()->with('status', __('Added “:name”.', ['name' => $possessionTag->name]));
    }

    public function destroy(Request $request, Vault $vault, Possession $possession, PossessionTag $possessionTag): RedirectResponse
    {
        new DetachPossessionTag(
            user: $request->user(),
            possession: $possession,
            possessionTag: $possessionTag,
        )->execute();

        return back()->with('status', __('Removed “:name” from this possession.', ['name' => $possessionTag->name]));
    }
}
