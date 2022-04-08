<?php

namespace App\Settings\ManageTemplates\Web\Controllers;

use App\Models\TemplatePage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Settings\ManageTemplates\Services\UpdateModulePosition;
use App\Settings\ManageTemplates\Web\ViewHelpers\PersonalizeTemplatePageShowViewHelper;

class PersonalizeTemplatePageModulesPositionController extends Controller
{
    public function update(Request $request, int $templateId, int $templatePageId, int $moduleId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'template_id' => $templateId,
            'template_page_id' => $templatePageId,
            'module_id' => $moduleId,
            'new_position' => $request->input('position'),
        ];

        $templatePage = TemplatePage::findOrFail($templatePageId);
        $module = (new UpdateModulePosition)->execute($data);

        return response()->json([
            'data' => PersonalizeTemplatePageShowViewHelper::dtoModule($templatePage, $module),
        ], 200);
    }
}
