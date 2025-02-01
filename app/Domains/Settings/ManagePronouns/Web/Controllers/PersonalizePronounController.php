<?php

namespace App\Domains\Settings\ManagePronouns\Web\Controllers;

use App\Domains\Settings\ManagePronouns\Services\CreatePronoun;
use App\Domains\Settings\ManagePronouns\Services\DestroyPronoun;
use App\Domains\Settings\ManagePronouns\Services\UpdatePronoun;
use App\Domains\Settings\ManagePronouns\Web\ViewHelpers\PersonalizePronounIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalizePronounController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/Pronouns/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizePronounIndexViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'name' => $request->input('name'),
        ];

        $pronoun = (new CreatePronoun)->execute($data);

        return response()->json([
            'data' => PersonalizePronounIndexViewHelper::dtoPronoun($pronoun),
        ], 201);
    }

    public function update(Request $request, int $pronounId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'pronoun_id' => $pronounId,
            'name' => $request->input('name'),
        ];

        $pronoun = (new UpdatePronoun)->execute($data);

        return response()->json([
            'data' => PersonalizePronounIndexViewHelper::dtoPronoun($pronoun),
        ], 200);
    }

    public function destroy(Request $request, int $pronounId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'pronoun_id' => $pronounId,
        ];

        (new DestroyPronoun)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
