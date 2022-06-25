<?php

namespace App\Settings\ManageCallReasons\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageCallReasons\Services\CreateCallReason;
use App\Settings\ManageCallReasons\Services\DestroyCallReason;
use App\Settings\ManageCallReasons\Services\UpdateCallReason;
use App\Settings\ManageCallReasons\Web\ViewHelpers\PersonalizeCallReasonsIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeCallReasonsController extends Controller
{
    public function store(Request $request, int $callReasonTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'call_reason_type_id' => $callReasonTypeId,
            'label' => $request->input('label'),
        ];

        $reason = (new CreateCallReason())->execute($data);

        return response()->json([
            'data' => PersonalizeCallReasonsIndexViewHelper::dtoReason($reason->callReasonType, $reason),
        ], 201);
    }

    public function update(Request $request, int $callReasonTypeId, int $reasonId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'call_reason_type_id' => $callReasonTypeId,
            'call_reason_id' => $reasonId,
            'label' => $request->input('label'),
        ];

        $reason = (new UpdateCallReason())->execute($data);

        return response()->json([
            'data' => PersonalizeCallReasonsIndexViewHelper::dtoReason($reason->callReasonType, $reason),
        ], 200);
    }

    public function destroy(Request $request, int $callReasonTypeId, int $reasonId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'call_reason_type_id' => $callReasonTypeId,
            'call_reason_id' => $reasonId,
        ];

        (new DestroyCallReason())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
