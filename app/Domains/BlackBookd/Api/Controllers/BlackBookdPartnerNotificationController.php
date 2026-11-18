<?php

namespace App\Domains\BlackBookd\Api\Controllers;

use App\Domains\BlackBookd\ManagePartnerNotification\Services\GetPartnerNotificationContacts;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class BlackBookdPartnerNotificationController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read')->only('index');
        parent::__construct();
    }

    public function index(Request $request, string $vaultId)
    {
        $result = (new GetPartnerNotificationContacts)->execute([
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'vault_id' => $vaultId,
            'positive_test_date' => $request->input('positive_test_date'),
            'lookback_days' => $request->input('lookback_days'),
        ]);

        return response()->json([
            'data' => $result->map(fn (array $row) => [
                'contact_id' => $row['contact']->id,
                'contact_name' => $row['contact']->name,
                'encounter_count' => $row['encounter_count'],
                'last_encounter_at' => $row['last_encounter_at'],
            ])->values(),
        ]);
    }
}