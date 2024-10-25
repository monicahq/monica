<?php

namespace App\Domains\Contact\ManageLifeEvents\Web\Controllers;

use App\Domains\Contact\ManageLifeEvents\Services\ToggleLifeEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToggleLifeEventController
{
    public function store(Request $request, string $vaultId, string $contactId, int $timelineEventId, int $lifeEventId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'timeline_event_id' => $timelineEventId,
            'life_event_id' => $lifeEventId,
        ];

        (new ToggleLifeEvent)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
