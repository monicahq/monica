<?php

namespace App\Settings\ManagePostTemplates\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PostTemplate;
use App\Settings\ManagePostTemplates\Services\UpdatePostTemplateSectionPosition;
use App\Settings\ManagePostTemplates\Web\ViewHelpers\PersonalizePostTemplateViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizePostTemplateSectionPositionController extends Controller
{
    public function update(Request $request, int $postTemplateId, int $postTemplateSectionId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'post_template_id' => $postTemplateId,
            'post_template_section_id' => $postTemplateSectionId,
            'new_position' => $request->input('position'),
        ];

        $postTemplateSection = (new UpdatePostTemplateSectionPosition())->execute($data);

        $postTemplate = PostTemplate::findOrFail($postTemplateId);

        return response()->json([
            'data' => PersonalizePostTemplateViewHelper::dtoPostTemplateSection($postTemplate, $postTemplateSection),
        ], 200);
    }
}
