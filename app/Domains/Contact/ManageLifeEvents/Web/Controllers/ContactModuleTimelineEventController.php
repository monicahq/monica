<?php

namespace App\Domains\Contact\ManageLifeEvents\Web\Controllers;

use App\Domains\Contact\ManageLifeEvents\Services\CreateLifeEvent;
use App\Domains\Contact\ManageLifeEvents\Services\CreateTimelineEvent;
use App\Domains\Contact\ManageLifeEvents\Services\DestroyTimelineEvent;
use App\Domains\Contact\ManageLifeEvents\Web\ViewHelpers\ModuleLifeEventViewHelper;
use App\Helpers\PaginatorHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleTimelineEventController extends Controller
{
    public function index(Request $request, string $vaultId, string $contactId)
    {
        $contact = Contact::where('vault_id', $vaultId)->findOrFail($contactId);

        $timelineEvents = $contact
            ->timelineEvents()
            ->orderBy('started_at', 'desc')
            ->paginate(15);

        return response()->json([
            'data' => ModuleLifeEventViewHelper::timelineEvents($timelineEvents, Auth::user(), $contact),
            'paginator' => PaginatorHelper::getData($timelineEvents),
        ], 200);
    }

    public function store(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'label' => $request->input('label'),
            'started_at' => $request->input('started_at'),
        ];

        $timelineEvent = (new CreateTimelineEvent)->execute($data);

        // we also need to add the current contact to the list of participants
        // finally, just so we are sure that we don't have the same participant
        // twice in the list, we need to remove duplicates
        $participants = collect($request->input('participants'))
            ->push(['id' => $contactId])
            ->unique('id')
            ->pluck('id')
            ->toArray();

        $carbonDate = Carbon::parse($request->input('started_at'));

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'label' => $request->input('label'),
            'timeline_event_id' => $timelineEvent->id,
            'life_event_type_id' => $request->input('lifeEventTypeId'),
            'summary' => $request->input('summary'),
            'description' => $request->input('description'),
            'happened_at' => $carbonDate->format('Y-m-d'),
            'costs' => $request->input('costs'),
            'currency_id' => $request->input('currency_id'),
            'paid_by_contact_id' => $request->input('paid_by_contact_id'),
            'duration_in_minutes' => $request->input('duration_in_minutes'),
            'distance' => $request->input('distance'),
            'distance_unit' => $request->input('distance_unit'),
            'from_place' => $request->input('from_place'),
            'to_place' => $request->input('to_place'),
            'place' => $request->input('place'),
            'participant_ids' => $participants,
        ];

        $lifeEvent = (new CreateLifeEvent)->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleLifeEventViewHelper::dtoTimelineEvent($timelineEvent, Auth::user(), $contact),
        ], 201);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $timelineEventId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'timeline_event_id' => $timelineEventId,
        ];

        (new DestroyTimelineEvent)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
