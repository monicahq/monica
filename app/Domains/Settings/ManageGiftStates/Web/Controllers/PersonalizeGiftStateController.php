<?php

namespace App\Domains\Settings\ManageGiftStates\Web\Controllers;

use App\Domains\Settings\ManageGiftStates\Services\CreateGiftState;
use App\Domains\Settings\ManageGiftStates\Services\DestroyGiftState;
use App\Domains\Settings\ManageGiftStates\Services\UpdateGiftState;
use App\Domains\Settings\ManageGiftStates\Web\ViewHelpers\PersonalizeGiftStateViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
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
            'author_id' => Auth::id(),
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
            'author_id' => Auth::id(),
            'gift_state_id' => $giftStateId,
            'label' => $request->input('label'),
        ];

        $newGiftState = (new UpdateGiftState)->execute($data);

        return response()->json([
            'data' => PersonalizeGiftStateViewHelper::dto($newGiftState),
        ], 200);
    }

    public function destroy(Request $request, int $giftStateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'gift_state_id' => $giftStateId,
        ];

        (new DestroyGiftState)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
