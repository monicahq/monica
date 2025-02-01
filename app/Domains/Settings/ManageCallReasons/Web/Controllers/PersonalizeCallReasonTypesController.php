<?php

namespace App\Domains\Settings\ManageCallReasons\Web\Controllers;

use App\Domains\Settings\ManageCallReasons\Services\CreateCallReasonType;
use App\Domains\Settings\ManageCallReasons\Services\DestroyCallReasonType;
use App\Domains\Settings\ManageCallReasons\Services\UpdateCallReasonType;
use App\Domains\Settings\ManageCallReasons\Web\ViewHelpers\PersonalizeCallReasonsIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalizeCallReasonTypesController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/CallReasons/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeCallReasonsIndexViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'label' => $request->input('callReasonTypeName'),
        ];

        $callReasonType = (new CreateCallReasonType)->execute($data);

        return response()->json([
            'data' => PersonalizeCallReasonsIndexViewHelper::dtoReasonType($callReasonType),
        ], 201);
    }

    public function update(Request $request, int $callReasonTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'call_reason_type_id' => $callReasonTypeId,
            'label' => $request->input('callReasonTypeName'),
        ];

        $callReasonType = (new UpdateCallReasonType)->execute($data);

        return response()->json([
            'data' => PersonalizeCallReasonsIndexViewHelper::dtoReasonType($callReasonType),
        ], 200);
    }

    public function destroy(Request $request, int $callReasonTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'call_reason_type_id' => $callReasonTypeId,
        ];

        (new DestroyCallReasonType)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
