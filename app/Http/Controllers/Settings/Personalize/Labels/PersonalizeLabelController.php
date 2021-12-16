<?php

namespace App\Http\Controllers\Settings\Personalize\Labels;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Account\ManageLabels\CreateLabel;
use App\Services\Account\ManageLabels\UpdateLabel;
use App\Services\Account\ManageLabels\DestroyLabel;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Settings\Personalize\Labels\ViewHelpers\PersonalizeLabelIndexViewHelper;

class PersonalizeLabelController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/Labels/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeLabelIndexViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ];

        $label = (new CreateLabel)->execute($data);

        return response()->json([
            'data' => PersonalizeLabelIndexViewHelper::dtoLabel($label),
        ], 201);
    }

    public function update(Request $request, int $labelId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'label_id' => $labelId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ];

        $label = (new UpdateLabel)->execute($data);

        return response()->json([
            'data' => PersonalizeLabelIndexViewHelper::dtoLabel($label),
        ], 200);
    }

    public function destroy(Request $request, int $labelId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'label_id' => $labelId,
        ];

        (new DestroyLabel)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
