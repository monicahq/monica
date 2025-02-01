<?php

namespace App\Domains\Settings\ManageTemplates\Web\Controllers;

use App\Domains\Settings\ManageTemplates\Services\CreateTemplatePage;
use App\Domains\Settings\ManageTemplates\Services\DestroyTemplatePage;
use App\Domains\Settings\ManageTemplates\Services\UpdateTemplatePage;
use App\Domains\Settings\ManageTemplates\Web\ViewHelpers\PersonalizeTemplatePageShowViewHelper;
use App\Domains\Settings\ManageTemplates\Web\ViewHelpers\PersonalizeTemplateShowViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplatePage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeTemplatePagesController extends Controller
{
    public function store(Request $request, int $templateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'template_id' => $templateId,
            'name' => $request->input('name'),
            'can_be_deleted' => true,
        ];

        $templatePage = (new CreateTemplatePage)->execute($data);

        return response()->json([
            'data' => PersonalizeTemplateShowViewHelper::dtoTemplatePage($templatePage->template, $templatePage),
        ], 201);
    }

    public function update(Request $request, int $templateId, int $templatePageId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'template_id' => $templateId,
            'template_page_id' => $templatePageId,
            'name' => $request->input('name'),
        ];

        $templatePage = (new UpdateTemplatePage)->execute($data);

        return response()->json([
            'data' => PersonalizeTemplateShowViewHelper::dtoTemplatePage($templatePage->template, $templatePage),
        ], 200);
    }

    public function destroy(Request $request, int $templateId, int $templatePageId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'template_id' => $templateId,
            'template_page_id' => $templatePageId,
        ];

        (new DestroyTemplatePage)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }

    public function show(Request $request, int $templateId, int $templatePageId)
    {
        $template = Template::where('account_id', Auth::user()->account_id)
            ->findOrFail($templateId);

        $templatePage = TemplatePage::where('template_id', $template->id)
            ->findOrFail($templatePageId);

        return response()->json([
            'data' => PersonalizeTemplatePageShowViewHelper::data($templatePage),
        ], 200);
    }
}
