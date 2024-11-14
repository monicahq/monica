<?php

namespace App\Domains\Settings\ManageGiftOccasions\Web\Controllers;

use App\Domains\Settings\ManageGiftOccasions\Services\UpdateGiftOccasionPosition;
use App\Domains\Settings\ManageGiftOccasions\Web\ViewHelpers\PersonalizeGiftOccasionViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeGiftOccasionsPositionController extends Controller
{
    public function update(Request $request, int $giftOccasionId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'gift_occasion_id' => $giftOccasionId,
            'new_position' => $request->input('position'),
        ];

        $giftOccasion = (new UpdateGiftOccasionPosition)->execute($data);

        return response()->json([
            'data' => PersonalizeGiftOccasionViewHelper::dto($giftOccasion),
        ], 200);
    }
}
