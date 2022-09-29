<?php

namespace App\Settings\ManagePostTemplates\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManagePostTemplates\Services\UpdatePostTemplatePosition;
use App\Settings\ManagePostTemplates\Web\ViewHelpers\PersonalizePostTemplateViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizePostTemplatePositionController extends Controller
{
    public function update(Request $request, int $postTemplateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'post_template_id' => $postTemplateId,
            'new_position' => $request->input('position'),
        ];

        $postTemplate = (new UpdatePostTemplatePosition())->execute($data);

        return response()->json([
            'data' => PersonalizePostTemplateViewHelper::dto($postTemplate),
        ], 200);
    }
}
