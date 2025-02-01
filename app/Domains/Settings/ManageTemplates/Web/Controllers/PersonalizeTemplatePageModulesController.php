<?php

namespace App\Domains\Settings\ManageTemplates\Web\Controllers;

use App\Domains\Settings\ManageTemplates\Services\AssociateModuleToTemplatePage;
use App\Domains\Settings\ManageTemplates\Services\RemoveModuleFromTemplatePage;
use App\Domains\Settings\ManageTemplates\Web\ViewHelpers\PersonalizeTemplatePageShowViewHelper;
use App\Http\Controllers\Controller;
use App\Models\TemplatePage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeTemplatePageModulesController extends Controller
{
    public function store(Request $request, int $templateId, int $templatePageId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'template_id' => $templateId,
            'template_page_id' => $templatePageId,
            'module_id' => $request->input('module_id'),
        ];

        $templatePage = TemplatePage::findOrFail($templatePageId);

        $module = (new AssociateModuleToTemplatePage)->execute($data);

        return response()->json([
            'data' => PersonalizeTemplatePageShowViewHelper::dtoModule($templatePage, $module),
        ], 201);
    }

    public function destroy(Request $request, int $templateId, int $templatePageId, int $moduleId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'template_id' => $templateId,
            'template_page_id' => $templatePageId,
            'module_id' => $moduleId,
        ];

        $templatePage = TemplatePage::findOrFail($templatePageId);

        $module = (new RemoveModuleFromTemplatePage)->execute($data);

        return response()->json([
            'data' => PersonalizeTemplatePageShowViewHelper::dtoModule($templatePage, $module),
        ], 200);
    }
}
