<?php

namespace App\Domains\Contact\ManageMoodTrackingEvents\Web\Controllers;

use App\Domains\Contact\ManageMoodTrackingEvents\Services\CreateMoodTrackingEvent;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultShowViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactMoodTrackingEventsController extends Controller
{
    public function store(Request $request, string $vaultId, string $contactId): JsonResponse
    {
        $carbonDate = Carbon::parse($request->input('date'))->format('Y-m-d');

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'mood_tracking_parameter_id' => $request->input('parameter_id'),
            'rated_at' => $carbonDate,
            'note' => $request->input('note') ?? null,
            'number_of_hours_slept' => $request->input('hours') ?? null,
        ];

        $moodTrackingEvent = (new CreateMoodTrackingEvent)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => VaultShowViewHelper::dtoMoodTrackingEvent($moodTrackingEvent, Auth::user()),
        ], 201);
    }
}
