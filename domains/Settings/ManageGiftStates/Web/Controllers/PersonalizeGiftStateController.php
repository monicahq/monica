<?php

namespace App\Settings\ManageGiftStates\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageGiftStates\Services\CreateGiftState;
use App\Settings\ManageGiftStates\Services\DestroyGiftState;
use App\Settings\ManageGiftStates\Services\UpdateGiftState;
use App\Settings\ManageGiftStates\Web\ViewHelpers\PersonalizeGiftStateViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalizeGiftStateController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/GiftStates/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeGiftStateViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'label' => $request->input('label'),
        ];

        $giftState = (new CreateGiftState)->execute($data);

        return response()->json([
            'data' => PersonalizeGiftStateViewHelper::dto($giftState),
        ], 201);
    }

    public function update(Request $request, int $giftStateId, int $giftState)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'gift_state_id' => $giftStateId,
            'label' => $request->input('label'),
        ];

        $giftState = (new UpdateGiftState)->execute($data);

        return response()->json([
            'data' => PersonalizeGiftStateViewHelper::dto($giftState),
        ], 200);
    }

    public function destroy(Request $request, int $giftStateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'gift_state_id' => $giftStateId,
        ];

        (new DestroyGiftState)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
