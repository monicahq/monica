<?php

namespace App\Domains\Vault\ManageReports\Web\Controllers;

use App\Domains\Vault\ManageReports\Web\ViewHelpers\ReportMoodTrackingEventIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReportMoodTrackingEventController extends Controller
{
    public function index(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Reports/MoodTrackingEvents/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ReportMoodTrackingEventIndexViewHelper::data($vault, Auth::user(), Carbon::now()->year),
        ]);
    }
}
