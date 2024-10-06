<?php

namespace App\Domains\Contact\ManageLifeEvents\Web\Controllers;

use App\Domains\Contact\ManageLifeEvents\Services\ToggleTimelineEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToggleTimelineEventController
{
    public function store(Request $request, string $vaultId, string $contactId, int $timelineEventId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'timeline_event_id' => $timelineEventId,
        ];

        (new ToggleTimelineEvent)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
