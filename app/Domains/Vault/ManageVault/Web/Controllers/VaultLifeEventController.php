<?php

namespace App\Domains\Vault\ManageVault\Web\Controllers;

use App\Domains\Contact\ManageLifeEvents\Web\ViewHelpers\ModuleLifeEventViewHelper;
use App\Helpers\PaginatorHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultLifeEventController extends Controller
{
    public function show(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Auth::user()->getContactInVault($vault);

        $timelineEvents = $contact
            ->timelineEvents()
            ->orderBy('started_at', 'desc')
            ->paginate(15);

        return response()->json([
            'data' => ModuleLifeEventViewHelper::timelineEvents($timelineEvents, Auth::user(), $contact),
            'paginator' => PaginatorHelper::getData($timelineEvents),
        ], 200);
    }
}
