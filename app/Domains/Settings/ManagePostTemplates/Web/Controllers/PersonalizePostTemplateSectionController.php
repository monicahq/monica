<?php

namespace App\Domains\Settings\ManagePostTemplates\Web\Controllers;

use App\Domains\Settings\ManagePostTemplates\Services\CreatePostTemplateSection;
use App\Domains\Settings\ManagePostTemplates\Services\DestroyPostTemplateSection;
use App\Domains\Settings\ManagePostTemplates\Services\UpdatePostTemplateSection;
use App\Domains\Settings\ManagePostTemplates\Web\ViewHelpers\PersonalizePostTemplateViewHelper;
use App\Http\Controllers\Controller;
use App\Models\PostTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizePostTemplateSectionController extends Controller
{
    public function store(Request $request, int $postTemplateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'post_template_id' => $postTemplateId,
            'label' => $request->input('label'),
            'can_be_deleted' => true,
        ];

        $postTemplateSection = (new CreatePostTemplateSection)->execute($data);
        $postTemplate = PostTemplate::findOrFail($postTemplateId);

        return response()->json([
            'data' => PersonalizePostTemplateViewHelper::dtoPostTemplateSection($postTemplate, $postTemplateSection),
        ], 201);
    }

    public function update(Request $request, int $postTemplateId, int $postTemplateSectionId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'post_template_id' => $postTemplateId,
            'post_template_section_id' => $postTemplateSectionId,
            'label' => $request->input('label'),
        ];

        $postTemplateSection = (new UpdatePostTemplateSection)->execute($data);
        $postTemplate = PostTemplate::findOrFail($postTemplateId);

        return response()->json([
            'data' => PersonalizePostTemplateViewHelper::dtoPostTemplateSection($postTemplate, $postTemplateSection),
        ], 200);
    }

    public function destroy(Request $request, int $postTemplateId, int $postTemplateSectionId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'post_template_id' => $postTemplateId,
            'post_template_section_id' => $postTemplateSectionId,
        ];

        (new DestroyPostTemplateSection)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
