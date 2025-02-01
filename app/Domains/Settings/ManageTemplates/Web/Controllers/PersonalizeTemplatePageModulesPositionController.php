<?php

namespace App\Domains\Settings\ManageTemplates\Web\Controllers;

use App\Domains\Settings\ManageTemplates\Services\UpdateModulePosition;
use App\Domains\Settings\ManageTemplates\Web\ViewHelpers\PersonalizeTemplatePageShowViewHelper;
use App\Http\Controllers\Controller;
use App\Models\TemplatePage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeTemplatePageModulesPositionController extends Controller
{
    public function update(Request $request, int $templateId, int $templatePageId, int $moduleId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
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
