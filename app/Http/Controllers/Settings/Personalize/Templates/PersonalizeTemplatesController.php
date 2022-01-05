<?php

namespace App\Http\Controllers\Settings\Personalize\Templates;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Account\ManageTemplate\CreateTemplate;
use App\Services\Account\ManageTemplate\UpdateTemplate;
use App\Services\Account\ManageTemplate\DestroyTemplate;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Settings\Personalize\Templates\ViewHelpers\PersonalizeTemplateIndexViewHelper;

class PersonalizeTemplatesController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/Templates/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeTemplateIndexViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'name' => $request->input('name'),
        ];

        $template = (new CreateTemplate)->execute($data);

        return response()->json([
            'data' => PersonalizeTemplateIndexViewHelper::dtoTemplate($template),
        ], 201);
    }

    public function update(Request $request, int $templateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'template_id' => $templateId,
            'name' => $request->input('name'),
        ];

        $template = (new UpdateTemplate)->execute($data);

        return response()->json([
            'data' => PersonalizeTemplateIndexViewHelper::dtoTemplate($template),
        ], 200);
    }

    public function destroy(Request $request, int $templateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'template_id' => $templateId,
        ];

        (new DestroyTemplate)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
