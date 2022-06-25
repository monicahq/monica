<?php

namespace App\Settings\ManageGiftOccasions\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageGiftOccasions\Services\CreateGiftOccasion;
use App\Settings\ManageGiftOccasions\Services\DestroyGiftOccasion;
use App\Settings\ManageGiftOccasions\Services\UpdateGiftOccasion;
use App\Settings\ManageGiftOccasions\Web\ViewHelpers\PersonalizeGiftOccasionViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalizeGiftOccasionController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/GiftOccasions/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeGiftOccasionViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'label' => $request->input('label'),
        ];

        $giftOccasion = (new CreateGiftOccasion())->execute($data);

        return response()->json([
            'data' => PersonalizeGiftOccasionViewHelper::dto($giftOccasion),
        ], 201);
    }

    public function update(Request $request, int $giftOccasionId, int $giftOccasion)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'gift_occasion_id' => $giftOccasionId,
            'label' => $request->input('label'),
        ];

        $giftOccasion = (new UpdateGiftOccasion())->execute($data);

        return response()->json([
            'data' => PersonalizeGiftOccasionViewHelper::dto($giftOccasion),
        ], 200);
    }

    public function destroy(Request $request, int $giftOccasionId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'gift_occasion_id' => $giftOccasionId,
        ];

        (new DestroyGiftOccasion())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
