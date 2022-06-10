<?php

namespace App\Settings\ManageGiftStates\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageGiftStates\Services\UpdateGiftStatePosition;
use App\Settings\ManageGiftStates\Web\ViewHelpers\PersonalizeGiftStateViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeGiftStatesPositionController extends Controller
{
    public function update(Request $request, int $giftStateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'gift_state_id' => $giftStateId,
            'new_position' => $request->input('position'),
        ];

        $giftState = (new UpdateGiftStatePosition)->execute($data);

        return response()->json([
            'data' => PersonalizeGiftStateViewHelper::dto($giftState),
        ], 200);
    }
}
